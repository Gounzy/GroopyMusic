<?php

namespace XBundle\Controller;

use AppBundle\Controller\BaseController;
use AppBundle\Services\CaptchaManager;
use AppBundle\Services\MailDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use XBundle\Entity\Project;
use XBundle\Entity\XCart;
use XBundle\Entity\XContractFan;
use XBundle\Entity\XPurchase;
use XBundle\Entity\XCategory;
use XBundle\Entity\XContact;
use XBundle\Entity\XOrder;
use XBundle\Entity\XPayment;
use XBundle\Entity\Tag;
use XBundle\Form\XContractFanType;
use XBundle\Form\XContactType;
use XBundle\Form\DonationType;
//use XBundle\Form\PurchaseType;


class XPublicController extends BaseController
{
    /**
     * @Route("/", name="x_homepage")
     */
    public function indexAction(EntityManagerInterface $em, Request $request, MailDispatcher $mailDispatcher, CaptchaManager $captchaManager)
    {
        // à changer pour afficher que les populaires et courant
        $projects = $em->getRepository('XBundle:Project')->findValidatedProjects();
        
        $contact = new XContact();
        $form = $this->createForm(XContactType::class, $contact, ['action' => $this->generateUrl('x_homepage') . '#contact']);

        if($form->isSubmitted() && $form->isValid()) {
            if(!$captchaManager->verify()) {
                $this->addFlash('error', 'Le test anti-robots a échoué... seriez-vous un androïde ??? Veuillez réessayer !');
                 return $this->render('@X/XPublic/home.html.twig', [
                     'form' => $form->createView(),
                 ]);
            }
 
             // DB save
             $em->persist($contact);
             $em->flush();
 
             // Mail
             //$mailDispatcher->sendXContactCopy($contact);
             $mailDispatcher->sendAdminXContact($contact);
 
             $this->addFlash('x_notice', 'Merci pour votre message. Nous vous recontacterons aussi vite que possible.');
             return $this->redirectToRoute('x_homepage');
         }
        
        return $this->render('@X/XPublic/home.html.twig', array(
            'form' => $form->createView(),
            'projects' => $projects,
        ));
    }


    /**
     * @Route("/projects", name="x_projects")
     */
    public function projectsAction(EntityManagerInterface $em)
    {
        $projects = $em->getRepository('XBundle:Project')->findValidatedProjects();
        $categories = $em->getRepository('XBundle:XCategory')->findAll();

        return $this->render('@X/XPublic/catalog_projects.html.twig', array(
            'projects' => $projects,
            'categories' => $categories
        ));
    }


    /**
     * @Route("/project/{id}-{slug}", name="x_project")
     */
    public function projectAction(EntityManagerInterface $em, Request $request, Project $project, $slug = null)
    {
        if($slug != null && $project->getSlug() != $slug) {
            return $this->redirectToRoute('x_project', ['id' => $project->getId(), 'slug' => $project->getSlug()]);
        }

        $contribution = new XContractFan($project);

        //$products = $em->getRepository('XBundle:Product')->getVisibleProductsForProject($project);

        $form = $this->createForm(DonationType::class);
        $form->handleRequest($request);

        $formPurchase = $this->createForm(XContractFanType::class, $contribution);
        $formPurchase->handleRequest($request);

        // DONATION SUBMIT
        /*if($form->isSubmitted() && $form->isValid()) {

            $cart = new XCart();
            $cart->setConfirmed(true);
            $cart->setDonationAmount($form['donationAmount']->getData());
            $cart->setProject($project);
            $cart->generateBarCode();

            $em->persist($cart);
            $em->flush();

            return $this->redirectToRoute('x_payment_checkout', ['code' => $cart->getBarcodeText()]);
        }*/

        // PURCHASE SUMIT
        if($formPurchase->isSubmitted() && $form->isValid()) {

            $cart = new XCart();

            foreach($contribution->getPurchases() as $purchase) {
                if($purchase->getQuantity() == 0) {
                    $contribution->removePurchase($purchase);
                }
                $purchase->setContractFan($contribution);
                $em->persist($purchase);
            }

            $contribution->initAmount();

            $cart->addContract($contribution);
            $cart->setConfirmed(true);
            $cart->generateBarCode();

            $contribution->setCart($cart);

            $em->persist($cart);
            $em->persist($contribution);
            $em->flush();

            return $this->redirectToRoute('x_payment_checkout', ['code' => $cart->getBarcodeText()]);
        }

        return $this->render('@X/XPublic/project.html.twig', array(
            'form' => $form->createView(),
            'form_purchase' => $formPurchase->createView(),
            'project' => $project,
            //'products' => $products,
        ));

    }


    /**
     * @Route("/signin", name="x_login")
     */
    public function loginAction(Request $request, CsrfTokenManagerInterface $tokenManager = null, UserInterface $user = null)
    {
        // à changer pcq compte artiste et compte contributeur!
        if($user != null) {
            return $this->redirectToRoute('x_artist_dashboard');
        }

        /** @var $session Session */
        $session = $request->getSession();

        $authErrorKey = Security::AUTHENTICATION_ERROR;
        $lastUsernameKey = Security::LAST_USERNAME;

        if ($request->attributes->has($authErrorKey)) {
            $error = $request->attributes->get($authErrorKey);
        } elseif (null !== $session && $session->has($authErrorKey)) {
            $error = $session->get($authErrorKey);
            $session->remove($authErrorKey);
        } else {
            $error = null;
        }

        if (!$error instanceof AuthenticationException) {
            $error = null; // The value does not come from the security component.
        }

        // last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get($lastUsernameKey);

        $csrfToken = $tokenManager
            ? $tokenManager->getToken('authenticate')->getValue()
            : null;

        return $this->render('@X/XPublic/login.html.twig', array(
            'last_username' => $lastUsername,
            'error' => $error,
            'csrf_token' => $csrfToken,
        ));
    }


    /**
     * @Route("/signout", name="x_logout")
     */
    public function logoutAction(Request $request, TokenStorageInterface $tokenStorage)
    {
        $tokenStorage->setToken(null);
        $session = $request->getSession();
        $session->invalidate();
        $response = new RedirectResponse($this->generateUrl('x_homepage'));
        $cookieNames = [
            $this->getParameter('session_name'),
            $this->getParameter('remember_me_name'),
        ];
        foreach ($cookieNames as $cookieName) {
            $response->headers->clearCookie($cookieName);
        }
        //$this->addFlash('x_notice', "Vous êtes bien déconnecté.");
        return $response;
    }


    /**
     * @Route("/conditions", name="x_terms")
     */
    public function termsAction()
    {
        return $this->render('@X/XPublic/terms.html.twig', []);
    }




    ///////////////////////// PAYMENT /////////////////////////


    /**
	 * @Route("/payment/{code}", name="x_payment_checkout")
	 */
	public function paymentCheckoutAction(EntityManagerInterface $em, Request $request, $code){
		
		$cart = $em->getRepository('XBundle:XCart')->findOneBy(['barcode_text' => $code]);

        /** @var XCart $cart */
        if ($cart == null || count($cart->getContracts()) == 0 || $cart->getPaid() || $cart->isRefunded()) {
            throw $this->createNotFoundException("Pas de panier, pas de paiement !");
        }
        
		if($request->getMethod() == 'POST' /*&& isset($_POST['accept_conditions']) && $_POST['accept_conditions']*/) {

            //$amount = $cart->getDonationAmount();

            $amount = intval($_POST['amount']);

			//$xorder = $em->getRepository('XBundle:XOrder')->findOneBy(array('cart' => $cart));
			//$xorder->setCart($cart);
    
            if($cart->getOrder() == null) {
                $firstName = $_POST['first_name'];
                $lastName = $_POST['last_name'];
                $email = $_POST['email'];

                $order = new XOrder();
                $order->setEmail($email)
                       ->setFirstName($firstName)
                       ->setLastName($lastName)
                       ->setCart($cart);
            }

            else {
                $order = $cart->getOrder();
            }

            // check error
            /*/foreach($cart->getContracts() as $contribution) {
                $project = $contribution->getProject();
                // check if project validated
                foreach($contribution->getPurchases() as $purchase) {
                }
            }*/

			$em->persist($order);
			$em->flush();
		
            \Stripe\Stripe::setApiKey($this->getParameter('stripe_api_secret'));
            
            $source = $_POST['stripeSource'];
            
            try{
                $payment = new XPayment();
                $payment->setCart($cart)
                        ->setDate(new \DateTime())
                        ->setRefunded(false)
                        ->setAmount($cart->getAmount());
                
                $charge = \Stripe\Charge::create(array(
                    "amount" => $amount,
                    "currency" => "eur",
                    "source" => $source,
                    "description" => "Chapots - payment " . $cart->getId(),
                ));
                    
                $payment->setChargeId($charge->id);
                $em->persist($payment);

                $em->persist($cart);
                return $this->redirectToRoute('x_payment_success', array('code' => $cart->getBarcodeText())); //, 'sponsorship' => $sponsorship));
                
            } catch (\Stripe\Error\Card $e) {
                $this->addFlash('error', 'errors.stripe.card');
                // $this->get(MailDispatcher::class)->sendAdminStripeError($e, $user, $cart);
            } catch (\Stripe\Error\RateLimit $e) {
                $this->addFlash('error', 'errors.stripe.rate_limit');
                // $this->get(MailDispatcher::class)->sendAdminStripeError($e, $user, $cart);
            } catch (\Stripe\Error\InvalidRequest $e) {
                $this->addFlash('error', 'errors.stripe.invalid_request');
                // $this->get(MailDispatcher::class)->sendAdminStripeError($e, $user, $cart);
            } catch (\Stripe\Error\Authentication $e) {
                $this->addFlash('error', 'errors.stripe.authentication');
                // $this->get(MailDispatcher::class)->sendAdminStripeError($e, $user, $cart);
            } catch (\Stripe\Error\ApiConnection $e) {
                $this->addFlash('error', 'errors.stripe.api_connection');
                // $this->get(MailDispatcher::class)->sendAdminStripeError($e, $user, $cart);
            } catch (\Stripe\Error\Base $e) {
                $this->addFlash('error', 'errors.stripe.generic');
                // $this->get(MailDispatcher::class)->sendAdminStripeError($e, $user, $cart);
            } catch (\Exception $e) {
                $this->addFlash('error', 'errors.stripe.other');
                // $this->get(MailDispatcher::class)->sendAdminStripeError($e, $user, $cart);
            }
        }
        
		return $this->render('@X/XPublic/Payment/payment_checkout.html.twig', array(
            'cart' => $cart,
            'error_conditions' => isset($_POST['accept_conditions']) && !$_POST['accept_conditions']
        ));
    }
    

    /**
	 * @Route("/payment/pending/{code}", name="x_payment_pending")
	 */
	public function paymentPendingAction(EntityManagerInterface $em, Request $request, $code)
    {
        /** @var XCart $cart */
		$cart = $em->getRepository('XBundle:XCart')->findOneBy(['barcode_text' => $code]);
        
        if ($cart == null || count($cart->getContracts()) == 0 || $cart->getPaid() || $cart->isRefunded()) {
            throw $this->createNotFoundException("Pas de panier, pas de paiement !");
        }

        $source = $request->get('source');
        $client_secret = $request->get('client_secret');

        return $this->render('@X/XPublic/Payment/payment_pending.html.twig', array(
        	'cart' => $cart,
            'source' => $source,
            'client_secret' => $client_secret,
        ));
    }


	/**
	 * @Route("/payment/success/{code}", name="x_payment_success")
	 */
	public function paymentSuccessAction(EntityManagerInterface $em, Request $request, $code) {

        /** @var XCart $cart */
        $cart = $em->getRepository('XBundle:XCart')->findOneBy(['barcode_text' => $code]);
        if ($cart == null || count($cart->getContracts()) == 0 || $cart->getPaid() || $cart->isRefunded()) {
            throw $this->createNotFoundException("Pas de panier, pas de paiement !");
        }
        
        /*$project = $em->getRepository('XBundle:Project')->find($cart->getProject());
        $project->addAmount($cart->getDonationAmount());  
        if ($cart->getProduct() == null) {
            $project->addNbDonations();
        } else {
            $project->addNbSales();
            $product = $em->getRepository('XBundle:Product')->find($cart->getProduct());
            $qty = $cart->getProdQuantity();
            $product->addProductsSold($qty);
        }*/

        foreach($cart->getContracts() as $contribution) {
            /** @var Project $project */
            $project = $contribution->getProject();
            $project->addAmount($contribution->getAmount());
            $project->addNbSales();

            foreach($contribution->getPurchases() as $purchase) {
                $product = $purchase->getProduct();
                $product->addProductsSold($purchase->getQuantity());
                $em->persist($product);
            }

            $em->persist($project);
        }

        $cart->setPaid(true);
        //$em->persist($project);
        $em->flush();

        $this->addFlash('x_notice', 'Paiement bien reçu !');

        return $this->redirectToRoute('x_project', ['id' => $project->getId()]);

    }

	
	/**
	 * @Route("/api/submit-order-coordinates", name="x_ajax_post_order")
	 */
    public function orderAjaxAction(EntityManagerInterface $em, Request $request)
    {
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $email = $_POST['email'];
        $code = $_POST['cart_code'];

        /** @var XCart $cart */
        $cart = $em->getRepository('XBundle:XCart')->findOneBy(['barcode_text' => $code]);
        
        if ($cart == null || count($cart->getContracts()) == 0 || $cart->getPaid() || $cart->isRefunded()) {
            throw $this->createNotFoundException("Pas de panier, pas de paiement !");
        }

        $order = new XOrder();
        $order->setCart($cart)
               ->setEmail($email)
               ->setFirstName($firstName)
               ->setLastName($lastName);
        $cart->setOrder($order);

        $em->persist($order);
        $em->persist($cart);
        $em->flush();

        return new Response(' ', 200);
    }



}
