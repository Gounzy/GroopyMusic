<?php

namespace AppBundle\Controller;

use AppBundle\Entity\ContractFan;
use AppBundle\Entity\Ticket;
use AppBundle\Entity\User;
use AppBundle\Entity\YB\YBContractArtist;
use AppBundle\Exception\YBAuthenticationException;
use AppBundle\Form\UserBankAccountType;
use AppBundle\Form\YB\YBContractArtistCrowdType;
use AppBundle\Form\YB\YBContractArtistType;
use AppBundle\Services\PaymentManager;
use AppBundle\Services\StringHelper;
use AppBundle\Services\TicketingManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class YBMembersController extends Controller
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function checkIfAuthorized($user, YBContractArtist $campaign = null) {
        if(!$user || !$user instanceof User) {
            throw new YBAuthenticationException();
        }
        if($campaign != null && !$user->ownsYBCampaign($campaign)) {
            throw new YBAuthenticationException();
        }
    }

    private function checkCampaignCode(YBContractArtist $campaign, $code) {
        if($campaign->getCode() != $code) {
            throw $this->createAccessDeniedException();
        }
    }

    /**
     * @Route("/dashboard", name="yb_members_dashboard")
     */
    public function dashboardAction(EntityManagerInterface $em, UserInterface $user = null)
    {
        $this->checkIfAuthorized($user);

        $current_campaigns = $em->getRepository('AppBundle:YB\YBContractArtist')->getCurrentYBCampaigns($user);
        $passed_campaigns = $em->getRepository('AppBundle:YB\YBContractArtist')->getPassedYBCampaigns($user);

        return $this->render('@App/YB/Members/dashboard.html.twig', [
            'current_campaigns' => $current_campaigns,
            'passed_campaigns' => $passed_campaigns,
        ]);
    }

    /**
     * @Route("/campaign/new", name="yb_members_campaign_new")
     */
    public function newCampaignAction(UserInterface $user = null, Request $request, EntityManagerInterface $em) {
        $this->checkIfAuthorized($user);
        $campaign = new YBContractArtist();
        $campaign->addHandler($user);
        $form = $this->createForm(YBContractArtistType::class, $campaign, ['creation' => true]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $em->persist($campaign);
            $em->flush();

            $this->addFlash('yb_notice', 'La campagne a bien été créée.');
            return $this->redirectToRoute('yb_members_dashboard');
        }

        return $this->render('@App/YB/Members/campaign_new.html.twig', [
            'form' => $form->createView(),
            'campaign' => $campaign,
        ]);
    }

    /**
     * @Route("/campaign/{id}/update", name="yb_members_campaign_edit")
     */
    public function editCampaignAction(YBContractArtist $campaign, UserInterface $user = null, Request $request, EntityManagerInterface $em) {
        $this->checkIfAuthorized($user, $campaign);

        if($campaign->isPassed()) {
            $this->addFlash('yb_error', 'Cette campagne est passée. Il est donc impossible de la modifier.');
            return $this->redirectToRoute('yb_members_passed_campaigns');
        }

        $form = $this->createForm(YBContractArtistType::class, $campaign);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $em->persist($campaign);
            $em->flush();

            $this->addFlash('yb_notice', 'La campagne a bien été modifiée.');
            return $this->redirectToRoute($request->get('_route'), $request->get('_route_params'));
        }
        return $this->render('@App/YB/Members/campaign_new.html.twig', [
            'form' => $form->createView(),
            'campaign' => $campaign,
        ]);
    }

    /**
     * @Route("/campaign/{id}/crowdfunding", name="yb_members_campaign_crowdfunding")
     */
    public function crowdfundingCampaignAction(YBContractArtist $campaign, UserInterface $user = null, Request $request, EntityManagerInterface $em, TicketingManager $ticketingManager, PaymentManager $paymentManager) {
        $this->checkIfAuthorized($user, $campaign);

        $form = $this->createForm(YBContractArtistCrowdType::class, $campaign);

        $form->handleRequest($request);

        if($form->isSubmitted()) {
            if($form->get('refund')->isClicked() && !$campaign->getRefunded()) {
                $campaign->setFailed(true);
                $paymentManager->refundStripeAndYBContractArtist($campaign);

                $em->flush();

                $this->addFlash('yb_notice', 'La campagne a bien été annulée. Les éventuels contributeurs ont été avertis et remboursés.');
                return $this->redirectToRoute($request->get('_route'), $request->get('_route_params'));
            }

            if($form->get('validate')->isClicked() && !$campaign->getTicketsSent()) {
                foreach($campaign->getContractsFanPaid() as $cf) {
                    $ticketingManager->generateAndSendYBTickets($cf, true);
                }

                $campaign->setTicketsSent(true)->setSuccessful(true);
                $em->flush();

                $this->addFlash('yb_notice', "L'événement a bien été confirmé et les tickets envoyés aux différents acheteurs.");
                return $this->redirectToRoute($request->get('_route'), $request->get('_route_params'));
            }
        }
        return $this->render('@App/YB/Members/campaign_crowdfunding.html.twig', [
            'form' => $form->createView(),
            'campaign' => $campaign,
        ]);
    }

    /**
     * @Route("/campaign/{id}/orders", name="yb_members_campaign_orders")
     */
    public function ordersCampaignAction(YBContractArtist $campaign, UserInterface $user = null) {
        $this->checkIfAuthorized($user, $campaign);

        $cfs = array_reverse($campaign->getContractsFanPaid());

        return $this->render('@App/YB/Members/campaign_orders.html.twig', [
            'cfs' => $cfs,
            'campaign' => $campaign,
        ]);
    }

    /**
     * @Route("/facturation", name="yb_members_payment_options")
     */
    public function paymentOptionsAction(UserInterface $user = null, Request $request) {
        $this->checkIfAuthorized($user, null);

        $form = $this->createForm(UserBankAccountType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('yb_notice', "Vos données de facturation ont bien été mises à jour.");
            return $this->redirectToRoute($request->get('_route'), $request->get('_route_params'));
        }

        return $this->render('@App/YB/Members/payment_options.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/passed-campaigns", name="yb_members_passed_campaigns")
     */
    public function passedCampaignsAction(EntityManagerInterface $em, UserInterface $user = null)
    {
        $this->checkIfAuthorized($user);

        $passed_campaigns = $em->getRepository('AppBundle:YB\YBContractArtist')->getPassedYBCampaigns($user);

        return $this->render('@App/YB/Members/passed_campaigns.html.twig', [
            'campaigns' => $passed_campaigns,
        ]);
    }

    /**
     * @Route("/campaign/{id}/excel", name="yb_members_campaign_excel")
     */
    public function excelAction(YBContractArtist $campaign, UserInterface $user = null, StringHelper $strHelper) {
        $this->checkIfAuthorized($user, $campaign);

        // ask the service for a Excel5
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();

        $phpExcelObject->getProperties()->setCreator("Ticked-it.be")
            ->setLastModifiedBy("Ticked-it robot")
            ->setTitle("Commandes et tickets")
            ->setSubject("Commandes")
            ->setDescription("Commandes et tickets")
            ->setKeywords("commandes, tickets");

        $cfs = array_reverse($campaign->getContractsFanPaid());

        if(count($cfs) > 0) {

            $colonnes = array(
                'Numéro de commande',
                'Date de commande',
                'Acheteur',
                'Adresse e-mail',
                'Prix',
                'Détail'
            );

            $lettre = "A";

            foreach($colonnes as $colonne) {
                $phpExcelObject->setActiveSheetIndex(0)->setCellValue($lettre . '1', $colonne);
                $lettre++;
            }

            $chiffre = 2;

            foreach($cfs as $cf) {
                /** @var ContractFan $cf */
                $lettre = "A";
                $colonnes = array(
                    $cf->getId(),
                    \PHPExcel_Shared_Date::PHPToExcel($cf->getDate()->getTimeStamp()),
                    $cf->getDisplayName(),
                    $cf->getEmail(),
                    $cf->getAmount(),
                    $cf->getPurchasesExport(),
                );


                foreach($colonnes as $key => $colonne) {
                    $phpExcelObject->setActiveSheetIndex(0)->setCellValue($lettre. "" . $chiffre, $colonne);

                    // Date
                    if($key == 1)
                        $phpExcelObject->getActiveSheet()
                            ->getStyle($lettre. "" . $chiffre)
                            ->getNumberFormat()
                            ->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DATETIME);
                    $lettre++;
                }
                $chiffre++;
            }
        }

        $phpExcelObject->getActiveSheet()->setTitle('Commandes');

        if($campaign->getTicketsSent()) {
            $phpExcelObject->createSheet();
            $colonnes = array(
                'Identifiant du ticket',
                'Numéro de la commande associée',
                'Acheteur',
                'Prix',
                'Type de ticket',
            );

            $lettre = "A";

            foreach($colonnes as $colonne) {
                $phpExcelObject->setActiveSheetIndex(1)->setCellValue($lettre . '1', $colonne);
                $lettre++;
            }

            $chiffre = 2;

            foreach($cfs as $cf) {
                /** @var ContractFan $cf */

                foreach ($cf->getTickets() as $ticket) {
                    $lettre = "A";

                    /** @var Ticket $ticket */
                    $colonnes = array(
                        $ticket->getBarcodeText(),
                        $ticket->getContractFan()->getId(),
                        $ticket->getName(),
                        $ticket->getPrice(),
                        $ticket->getCounterPart()->__toString(),
                    );

                    foreach($colonnes as $key => $colonne) {
                        $phpExcelObject->setActiveSheetIndex(1)->setCellValue($lettre. "" . $chiffre, $colonne);
                        $lettre++;
                    }
                    $chiffre++;
                }

                $phpExcelObject->setActiveSheetIndex(1);
                $phpExcelObject->getActiveSheet()->setTitle('Tickets');
            }
        }

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $phpExcelObject->setActiveSheetIndex(0);

        // create the writer
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $strHelper->slugify($campaign->getTitle()) . '.xls'
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    /**
     * @Route("/campaign/{id}/{code}remove-photo", name="yb_members_campaign_remove_photo")
     */
    public function removePhotoAction(Request $request, YBContractArtist $campaign, $code) {

        $this->checkCampaignCode($campaign, $code);

        $em = $this->getDoctrine()->getManager();

        $filename = $request->get('filename');

        $photo = $em->getRepository('AppBundle:Photo')->findOneBy(['filename' => $filename]);

        $em->remove($photo);

        $campaign->removeCampaignPhoto($photo);

        $filesystem = new Filesystem();
        $filesystem->remove($this->get('kernel')->getRootDir().'/../web/' . YBContractArtist::getWebPath($photo));

        $em->persist($campaign);
        $em->flush();

        return new Response();
    }

}
