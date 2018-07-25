<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Cart;
use AppBundle\Entity\ContractFan;
use AppBundle\Entity\Payment;
use AppBundle\Entity\YB\YBContact;
use AppBundle\Entity\YB\YBContractArtist;
use AppBundle\Entity\YB\YBOrder;
use AppBundle\Form\ContractFanType;
use AppBundle\Form\YB\YBContactType;
use AppBundle\Form\YBOrderType;
use AppBundle\Services\MailDispatcher;
use AppBundle\Services\PDFWriter;
use AppBundle\Services\TicketingManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class YBController extends Controller
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @Route("/", name="yb_index")
     */
    public function indexAction(Request $request, EntityManagerInterface $em, MailDispatcher $mailDispatcher)
    {
        $contact = new YBContact();
        $form = $this->createForm(YBContactType::class, $contact, ['action' => $this->generateUrl('yb_index') . '#contact']);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $em->persist($contact);
            $em->flush();

            // Mail
            $mailDispatcher->sendYBContactCopy($contact);
            $mailDispatcher->sendAdminYBContact($contact);

            $this->addFlash('yb_notice', 'Thank you for your message. We will come back to you soon.');
            return $this->redirectToRoute('yb_index');
        }

        return $this->render('@App/YB/home.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/campaign/{id}/{slug}", name="yb_campaign")
     */
    public function campaignAction(YBContractArtist $c, EntityManagerInterface $em, Request $request, $slug = null) {

        if($slug != null && $c->getSlug() != $slug) {
            return $this->redirectToRoute('yb_campaign', ['id' => $c->getId(), 'slug' => $c->getSlug()]);
        }

        $cf = new ContractFan($c);
        $form = $this->createForm(ContractFanType::class, $cf, ['user_rewards' => null]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $cart = new Cart();

            foreach($cf->getPurchases() as $purchase) {
                if($purchase->getQuantity() == 0) {
                    $cf->removePurchase($purchase);
                }
            }

            $cf->initAmount();

            $cart->addContract($cf);
            $cart->setConfirmed(true);

            $em->persist($cart);
            $em->flush();

            $request->getSession()->set('yb_cart_id', $cart->getId());

            return $this->redirectToRoute('yb_checkout');
        }

        return $this->render('@App/YB/campaign.html.twig', [
            'campaign' => $c,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/terms", name="yb_terms")
     */
    public function termsAction() {

        return $this->render('@App/YB/terms.html.twig', [

        ]);
    }

    /**
     * @Route("/checkout", name="yb_checkout")
     */
    public function checkoutAction(Request $request, EntityManagerInterface $em, ValidatorInterface $validator) {

        /** @var Cart $cart */
        $cart = $em->getRepository('AppBundle:Cart')->find(intval($request->getSession()->get('yb_cart_id')));
        if ($cart == null || count($cart->getContracts()) == 0) {
            throw $this->createAccessDeniedException("Pas de panier, pas de paiement !");
        }

        if ($request->getMethod() == 'POST' && $_POST['accept_conditions']) {

            $amount = intval($_POST['amount']);
            // We set an explicit test for amount changes as it has legal impacts
            if (floatval($amount) !=  floatval($cart->getAmount() * 100)) {
                $this->addFlash('error', 'errors.order_changed');
                return $this->render('@App/YB/checkout.html.twig', array(
                    'cart' => $cart,
                    'error_conditions' => false,
                ));
            }

            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $email = $_POST['email'];

            $order = new YBOrder();
            $order->setEmail($email)->setFirstName($first_name)->setLastName($last_name)->setCart($cart);

            $errors = $validator->validate($order);
            if(count($errors) > 0) {
                $this->addFlash('error', 'errors.order_coords');
                return $this->render('@App/YB/checkout.html.twig', array(
                    'cart' => $cart,
                    'error_conditions' => false,
                ));
            }

            foreach($cart->getContracts() as $cf) {
                /** @var ContractFan $cf */
                /** @var YBContractArtist $contract_artist */
                $contract_artist = $cf->getContractArtist();
                if ($contract_artist->isUncrowdable()) {
                    $this->addFlash('error', 'errors.event_uncrowdable');
                    return $this->redirectToRoute('artist_contract', ['id' => $contract_artist->getId(), $contract_artist->getSlug()]);
                }

                foreach ($cf->getPurchases() as $purchase) {
                    if ($contract_artist->getNbAvailable($purchase->getCounterpart()) < $purchase->getQuantityOrganic()) {
                        $this->addFlash('error', 'errors.order_max');
                        return $this->redirectToRoute('artist_contract', ['id' => $contract_artist->getId(), $contract_artist->getSlug()]);
                    }
                }
            }

            $em->persist($order);
            $em->flush();
            // Set your secret key: remember to change this to your live secret key in production
            // See your keys here: https://dashboard.stripe.com/account/apikeys
            \Stripe\Stripe::setApiKey($this->getParameter('stripe_api_secret'));

            // Token is created using Stripe.js or Checkout!
            // Get the payment token submitted by the form:
            $source = $_POST['stripeSource'];

            // Charge the user's card:
            try {
                foreach($cart->getContracts() as $contract) {
                    /** @var ContractFan $contract
                     * @var YBContractArtist $contract_artist */
                    $contract->calculatePromotions();
                    $contract_artist = $contract->getContractArtist();

                    $contract_artist->addAmount($contract->getAmount());
                    $contract_artist->addSoldCounterparts($contract->getTresholdIncrease());
                }

                $payment = new Payment();
                $payment->setDate(new \DateTime())->setUser(null)
                    ->setCart($cart)->setRefunded(false)->setAmount($cart->getAmount());

                $charge = \Stripe\Charge::create(array(
                    "amount" => $amount,
                    "currency" => "eur",
                    "description" => "Un-Mute - payment " . $cart->getId(),
                    "source" => $source,
                ));

                $payment->setChargeId($charge->id);
                $em->persist($payment);

                $cart->setConfirmed(true)->setPaid(true);

                $em->persist($cart);
                return $this->redirectToRoute('yb_payment_success', array('id' => $cart->getId())); //, 'sponsorship' => $sponsorship));

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

        return $this->render('@App/YB/checkout.html.twig', [
            'cart' => $cart,
            'error_conditions' => isset($_POST['accept_conditions']) && !$_POST['accept_conditions'],
        ]);
    }

    /**
     * @Route("/payment/success/{id}", name="yb_payment_success")
     */
    public function paymentSuccessAction(Cart $cart, MailDispatcher $mailDispatcher, TicketingManager $ticketingManager) {

        // Send order recap
        $mailDispatcher->sendYBOrderRecap($cart);

        $i = 0;
        $only_c = null;
        foreach($cart->getContracts() as $contract) {
            /** @var YBContractArtist $campaign */
            $campaign = $contract->getContractArtist();

            // Need to also send tickets
            if($campaign->isEvent() && ($campaign->getSuccessful() || $campaign->getTicketsSent() || $campaign->hasNoThreshold())) {
                $ticketingManager->generateAndSendYBTickets($contract);
            }

            $i++;
            $only_c = $campaign;
        }

        $this->addFlash('yb_notice', 'Paiement bien reçu ! Checkez vos mails.');

        if($i == 1) {
            return $this->redirectToRoute('yb_campaign', ['id' => $only_c->getId()]);
        }
        else {
            return $this->redirectToRoute('yb_index');
        }
    }

    /**
     * @Route("payment/pending/{id}", name="yb_cart_payment_pending")
     */
    public function cartPendingAction(Request $request, Cart $cart)
    {
        /** @var Cart $cart */
        if ($cart == null || count($cart->getContracts()) == 0) {
            throw $this->createAccessDeniedException("Pas de panier, pas de paiement !");
        }

        $source = $request->get('source');
        $client_secret = $request->get('client_secret');

        return $this->render('@App/YB/payment_pending.html.twig', array(
            'cart' => $cart,
            'source' => $source,
            'client_secret' => $client_secret,
        ));
    }
}
