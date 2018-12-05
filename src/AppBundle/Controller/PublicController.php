<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Artist;
use AppBundle\Entity\Artist_User;
use AppBundle\Entity\Cart;
use AppBundle\Entity\ContractArtist;
use AppBundle\Entity\ContractArtistSales;
use AppBundle\Entity\ContractFan;
use AppBundle\Entity\Hall;
use AppBundle\Entity\PropositionContractArtist;
use AppBundle\Entity\User;
use AppBundle\Entity\SuggestionBox;
use AppBundle\Form\CartType;
use AppBundle\Form\ContractFanType;
use AppBundle\Form\PropositionContractArtistType;
use AppBundle\Services\MailDispatcher;
use AppBundle\Services\NotificationDispatcher;
use AppBundle\Services\RewardSpendingService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Form\Factory\FactoryInterface;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserManagerInterface;
use Mailgun\Mailgun;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use AppBundle\Form\SuggestionBoxType;
use AppBundle\Form\UserSuggestionBoxType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Translation\TranslatorInterface;
use AppBundle\Services\ArrayHelper;

class PublicController extends Controller
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

	///////////////////////////////////////////////
    ///Private, checkout-specific methods//////////
    ///////////////////////////////////////////////

    /**
     * Returns $cart cleaned, i.e. emptied of all contracts; except if $cart is paid, then creates a new one and returns it
     */
    private function cleanCart(Cart $cart, $em)
    {
       if ($cart->getPaid() && $cart->getConfirmed()) {
            return $this->createCartForUser($cart->getUser());
        } else {
            foreach ($cart->getContracts() as $contract) {
                $cart->removeContract($contract);
                $this->getDoctrine()->getManager()->remove($contract);
            }
            return $cart;
        }
    }

	# Duplicated from UserController
    private function createCartForUser($user)
    {
        $cart = new Cart();
        $cart->setUser($user);
        $cart->generateBarCode();
        $this->getDoctrine()->getManager()->persist($cart);
        return $cart;
    }

    # Pupulates cart with empty orders, one for each given $artistContracts
    private function populateCart(Cart $cart, $artistContracts) {
        foreach($artistContracts as $artistContract) {
            $fanContract = new ContractFan($artistContract);
            $cart->addContract($fanContract);
        }
        return $cart;
    }

    /**
     * Creates a new Cart filled with $cfs
     */
    private function handleCheckout($cfs, $user, EntityManagerInterface $em, Request $request) {
        /** @var Cart $cart */
        $cart = null;
        if ($user != null) {
            $cart = $em->getRepository('AppBundle:Cart')->findCurrentForUser($user);
        }

        $cart = $this->createCartForUser($user);

        foreach($cfs as $cf) {
            /** @var ContractFan $cf */
            $qty = 0;
            foreach ($cf->getPurchases() as $purchase) {
                $pqty = $purchase->getQuantity();
                if ($pqty == 0 || $pqty == null) {
                    $cf->removePurchase($purchase);
                }
                $qty += $pqty;
            }
            if($qty == 0) {
                if ($cart->hasContract($cf)) {
                    $cart->removeContract($cf);
                }
            }
            else {
                if(!$cart->hasContract($cf)) {
                    $cart->addContract($cf);
                }
            }
        }

        $em->persist($cart);
        $em->flush();
        return $cart;
    }


	///////////////////////////////////////////////
    ///Quasi-static pages ////////////////////////
    ///////////////////////////////////////////////

    /**
	 * Homepage: fetches current festivals and their artists 
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request, UserInterface $user = null)
    {
        $em = $this->getDoctrine()->getManager();

        $crowdfundings = $em->getRepository('AppBundle:ContractArtist')->findVisible();

        $news = [];

        foreach($crowdfundings as $crowd) {
            $news = array_merge($news, $crowd->getAllArtists());
        }

        $news = array_unique($news);
        shuffle($news);

        return $this->render('AppBundle:Public:home.html.twig', array(
            'news' => $news,
            'crowdfundings' => $crowdfundings,
        ));
    }

    /**
     * About page: purely static
     * @Route("/about", name="about")
     */
    public function aboutAction()
    {
        return $this->render('AppBundle:Public:about.html.twig');
    }

    /**
     * Contact page: fetches information sessions info to display them
     * The contact form itself is handled by contactFormAction() controller and is submitted using AJAX
     * @Route("/suggestions", name="suggestionBox")
     */
    public function contactAction(EntityManagerInterface $em)
    {
        $sessions = $em->getRepository('AppBundle:InformationSession')->findVisible();
        return $this->render('AppBundle:Public:suggestionBox.html.twig', array(
            'sessions' => $sessions,
        ));
    }

    /**
     * Contact form: creates form and, if valid, sends email notifications
     * @Route("/suggestions/post", name="suggestionBox_form")
     */
    public function contactFormAction(Request $request, UserInterface $user = null)
    {
        $suggestionBox = new SuggestionBox();

       	# Some fields will be pre-filled if user is logged in
        if ($user != null) {
            $suggestionBox->setUser($user);
            $form = $this->createForm(UserSuggestionBoxType::class, $suggestionBox, ['attr' => ['class' => 'suggestionBoxForm'], 'action' => $this->generateUrl('suggestionBox_form')]);
        } else {
            $form = $this->createForm(SuggestionBoxType::class, $suggestionBox, ['attr' => ['class' => 'suggestionBoxForm'], 'action' => $this->generateUrl('suggestionBox_form')]);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($suggestionBox);
            $em->flush();

            $mailDispatcher = $this->get(MailDispatcher::class);
            if ($suggestionBox->getMailCopy() && !empty($suggestionBox->getEmail())) {
                $mailDispatcher->sendSuggestionBoxCopy($suggestionBox);
            }

            $mailDispatcher->sendAdminContact($suggestionBox);
            $notifDispatcher = $this->get(NotificationDispatcher::class);
            $notifDispatcher->notifyAdminContact($suggestionBox);

            return new Response($this->renderView('AppBundle:Public/Form:suggestionBox_ok.html.twig'));
        }
        return new Response($this->renderView('AppBundle:Public/Form:suggestionBox.html.twig', array(
            'form' => $form->createView(),
        )));
    }

    /**
     * Passed festivals page: fetches passed (successful AND failed) festivals
     * @Route("/passed-festivals", name="passed_festivals")
     */
    public function passedFestivalsAction(EntityManagerInterface $em) {
        $contracts = $em->getRepository('AppBundle:ContractArtist')->findPassed();

        return $this->render('@App/Public/passed_festivals.html.twig', [
            'contracts' => $contracts,
        ]);
    }

    /**
     * Festival page with info & tickets: fetches festival info + handles order form
     * @Route("/events/{id}-{slug}", name="artist_contract")
     */
    public function artistContractAction(Request $request, UserInterface $user = null, ContractArtist $contract, $slug = null)
    {
        if ($contract->getSlug() != $slug) {
            return $this->redirectToRoute('artist_contract', ['id' => $contract->getId(), 'slug' => $contract->getSlug()]);
        }

        $em = $this->getDoctrine()->getManager();

        $cf = new ContractFan($contract);
        $form = $this->createForm(ContractFanType::class, $cf, ['user_rewards' => []]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $cart = $this->handleCheckout([$cf], $user, $em, $request);

            return $this->redirectToRoute('checkout', ['cart_code' => $cart->getBarcodeText()]);
        }

        return $this->render('@App/Public/artist_contract.html.twig', array(
            'contract' => $contract,
            'form' => $form->createView(),
        ));
    }

    /**
     * Tickets marketplace: lists all available tickets with order form
     * @Route("/tickets", name="tickets_marketplace")
     */
    public function ticketsAction(Request $request, EntityManagerInterface $em, UserInterface $user = null) {
        $current_contracts = $em->getRepository('AppBundle:ContractArtist')->findVisible();

        $cart = new Cart();

        $cart = $this->populateCart($cart, $current_contracts);

        $form = $this->createForm(CartType::class, $cart);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cart = $this->handleCheckout($cart->getContracts()->toArray(), $user, $em, $request);

            return $this->redirectToRoute('checkout', ['cart_code' => $cart->getBarcodeText()]);
        }

        return $this->render('@App/Public/tickets.html.twig', [
            'form' => $form->createView(),
        ]);
    }
	
    /**
     * Artists catalog: lists all artists with filtering possibilities (by genre/province/...)
     * @Route("/artists", name="catalog_artists")
     */
    public function artistsAction(Request $request, UserInterface $user = null)
    {
        $em = $this->getDoctrine()->getManager();

        $artists = $em->getRepository('AppBundle:Artist')->findVisible();
        $genres = $em->getRepository('AppBundle:Genre')->findAll();
        $provinces = $em->getRepository('AppBundle:Province')->findAll();

        if ($user != null && count($user->getGenres()) > 0) {
            usort($artists, function (Artist $a, Artist $b) use ($user) {
                if ($a->getScore($user) == $b->getScore($user))
                    return 0;
                if ($a->getScore($user) > $b->getScore($user))
                    return 1;
                return -1;
            });
        }

        return $this->render('@App/Public/catalog_artists.html.twig', array(
            'artists' => $artists,
            'genres' => $genres,
            'provinces' => $provinces,
            'affiche_checked' => $request->get('affiche', false),
        ));
    }

    /**
     * Artist profile: fetches artist info & his potential current festivals
     * @Route("/artists/{id}-{slug}", name="artist_profile")
     */
    public function artistProfileAction(Request $request, UserInterface $user = null, Artist $artist, $slug = null, EntityManagerInterface $em)
    {
        $current_sales = $em->getRepository('AppBundle:ContractArtistSales')->findCurrentsForArtist($artist);

        if ($slug !== null && $slug != $artist->getSlug()) {
            return $this->redirectToRoute('artist_profile', ['id' => $artist->getId(), 'slug' => $artist->getSlug()]);
        }

        return $this->render('@App/Public/artist_profile.html.twig', array(
            'artist' => $artist,
            'current_sales' => $current_sales,
        ));
    }


	///////////////////////////////////////////////
    ///Checkout related pages//////////////////////
    ///////////////////////////////////////////////

    /**
     * Checkout: displays checkout page, and checks if access to this page is allowed in current conditions, + allows user to sign up or log in
     * Actual checkout & payment are triggered through Stripe's JavaScript and detailed in PaymentController
     *
     * @Route("/checkout/{cart_code}", name="checkout")
     */
    public function checkoutAction(Request $request, UserInterface $user = null, RewardSpendingService $rewardSpendingService, $cart_code)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var $cart Cart */
        $cart = $em->getRepository('AppBundle:Cart')->findOneBy(['barcode_text' => $cart_code]);

        # No cart corresponding to request
        if ($cart == null) {
            $this->addFlash('error', 'errors.order_changed');
            return $this->redirectToRoute('tickets_marketplace');
        }

        # Cart not to be processed anymore
        if($cart->getFinalized() || $cart->getConfirmed() || $cart->getPaid()) {
            throw $this->createNotFoundException();
        }

        # First, only keep relevant items in cart
        foreach($cart->getContracts() as $contract) {
            $em->persist($contract);
            $rewardSpendingService->setBaseAmount($contract);
            foreach($contract->getPurchases() as $purchase) {
                if($purchase->getAmount() == 0) {
                    $contract->removePurchase($purchase);
                }
            }

            if($contract->getAmount() == 0) {
                $cart->removeContract($contract);
            }
        }

        # When user logs in at this point, we could find another cart already related to him
        # -> that potential cart must be removed from DB as we should only use the $cart instance
        if ($user != null) {
            $other_potential_cart = $em->getRepository('AppBundle:Cart')->findCurrentForUser($user);

            if ($other_potential_cart != null && $other_potential_cart->getId() != $cart->getId()) {
                $em->remove($other_potential_cart);
            }
        }

        if($user != null) {
            # Reward consumption
            foreach($cart->getContracts() as $cf) {
                $cf->setUserRewards(new arrayCollection($rewardSpendingService->getApplicableReward($cf)));
                $rewardSpendingService->applyReward($cf);
            }

            $cart->setUser($user);
            $em->persist($cart);
            
        }

        $em->flush();
        
        $form_view = null;
        
        /**
        	Registration form
         	@see FOSUserBundle 
         */ 
        if (!$user) {
            /** @var $formFactory FactoryInterface */
            $formFactory = $this->get('fos_user.registration.form.factory');
            /** @var $userManager UserManagerInterface */
            $userManager = $this->get('fos_user.user_manager');
            /** @var $dispatcher EventDispatcherInterface */
            $dispatcher = $this->get('event_dispatcher');

            $user = $userManager->createUser();
            $user->setEnabled(true);

            $event = new GetResponseUserEvent($user, $request);
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, $event);

            if (null !== $event->getResponse()) {
                return $event->getResponse();
            }

            $form = $formFactory->createForm();
            $form->setData($user);

            $form->handleRequest($request);

            $form_view = $form->createView();

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $event = new FormEvent($form, $request);
                    $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);

                    $userManager->updateUser($user);

                    if (null === $response = $event->getResponse()) {
                        $url = $this->generateUrl('fos_user_registration_confirmed');
                        $response = new RedirectResponse($url);
                    }

                    $dispatcher->dispatch(FOSUserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

                    $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
                    $this->get('security.token_storage')->setToken($token);
                    $this->get('session')->set('_security_main', serialize($token));
                    $event = new InteractiveLoginEvent($request, $token);
                    $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

                    $form_view = null;

                    $this->addFlash('notice', 'notices.registration');
                    return $this->redirectToRoute($request->get('_route'), $request->get('_route_params'));
                } else {
                    $event = new FormEvent($form, $request);
                    $dispatcher->dispatch(FOSUserEvents::REGISTRATION_FAILURE, $event);
                }

            }
        }

        return $this->render('@App/User/pay_cart.html.twig', array(
            'cart' => $cart,
            'error_conditions' => false,
            'form' => $form_view,
        ));
    }
}