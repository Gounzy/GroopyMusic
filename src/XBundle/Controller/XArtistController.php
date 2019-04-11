<?php

namespace XBundle\Controller;

use AppBundle\Controller\BaseController;
use AppBundle\Services\MailDispatcher;
use AppBundle\Services\PaymentManager;
use AppBundle\Services\TicketingManager;
use Doctrine\ORM\EntityManagerInterface;
//use Ob\HighchartsBundle\Highcharts\Highchart;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use XBundle\Entity\Product;
use XBundle\Entity\Project;
use XBundle\Entity\OptionProduct;
use XBundle\Entity\ChoiceOption;
use XBundle\Entity\XTransactionalMessage;
use XBundle\Form\ProductType;
use XBundle\Form\ProjectType;
use XBundle\Form\XTransactionalMessageType;


class XArtistController extends BaseController
{

    /**
     * @Route("/dashboard", name="x_artist_dashboard")
     */
    public function dashboardAction(EntityManagerInterface $em, UserInterface $user = null, Request $request)
    {
        $this->checkIfArtistAuthorized($user);

        $otherCurrentProjects = null;
        $otherPassedProjects = null;
        if ($user->isSuperAdmin()) {
            $otherCurrentProjects = $em->getRepository('XBundle:Project')->getOtherCurrentProjects($user);
            $otherPassedProjects = $em->getRepository('XBundle:Project')->getOtherPassedProjects($user);
        }

        $currentProjects = $em->getRepository('XBundle:Project')->getCurrentProjects($user);
        $passedProjects = $em->getRepository('XBundle:Project')->getPassedProjects($user);

        return $this->render('@X/XArtist/dashboard_artist.html.twig', [
            'current_projects' => $currentProjects,
            'passed_projects' => $passedProjects,
            'other_current_projects' => $otherCurrentProjects,
            'other_passed_projects' => $otherPassedProjects,
        ]);
    }


    /**
     * @Route("/project/new", name="x_artist_project_new")
     */
    public function newProjectAction(EntityManagerInterface $em, UserInterface $user = null, Request $request, MailDispatcher $mailDispatcher)
    {
        $this->checkIfArtistAuthorized($user);

        $project = new Project();
        $project->setCreator($user);

        $form = $this->createForm(ProjectType::class, $project, ['creation' => true]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $artist = $form->get('artist')->getData();
            $project->setArtist($artist);

            // add all artist owners to project
            $artistOwners = $em->getRepository('AppBundle:Artist_User')->getArtistOwners($artist->getId());
            foreach($artistOwners as $ao) {
                $project->addHandler($ao->getUser());
            }

            $em->persist($project);
            $em->flush();

            $message = 'Le projet "' . $project->getTitle() . '" a bien été créé. Il doit maintenant être validé par l\'équipe d\'Un-Mute pour être visible par le public sur Chapots';
            $this->addFlash('x_notice', $message);
            $mailDispatcher->sendAdminNewProject($project); 
            return $this->redirectToRoute('x_artist_dashboard');
        }

        return $this->render('@X/XArtist/project_new.html.twig', array(
            'form' => $form->createView(),
            'project' => $project
        ));
    }


    /**
     * @Route("/passed-projects", name="x_artist_passed_projects")
     */
    public function passedProjectsAction(EntityManagerInterface $em, UserInterface $user = null)
    {
        $this->checkIfArtistAuthorized($user);

        $passedProjects = $em->getRepository('XBundle:Project')->getPassedProjects($user);
        $otherPassedProjects = $em->getRepository('XBundle:Project')->getOtherPassedProjects($user);

        return $this->render('@X/XArtist/passed_projects.html.twig', [
            'projects' => $passedProjects,
            'other_projects' => $otherPassedProjects
        ]);
    }


    /**
     * @Route("/project/{id}/update", name="x_artist_project_update")
     */
    public function updateProjectAction(EntityManagerInterface $em, UserInterface $user = null, Request $request, Project $project)
    {
        $this->checkIfArtistAuthorized($user, $project);

        if($project->isPassed()) {
            // addFlash('x_error')
            return $this->redirectToRoute('x_artist_passed_projets');
        }
        
        $form = $this->createForm(ProjectType::class, $project, ['is_edit' => true]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $em->persist($project);
            $em->flush();

            $this->addFlash('x_notice', 'Le projet a bien été mis à jour.');
            return $this->redirectToRoute($request->get('_route'), $request->get('_route_params'));
        }

        return $this->render('@X/XArtist/project_new.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
        ]);
    }


    /**
     * @Route("/project/{id}/confirm", name="x_artist_project_confirm")
     */
    public function confirmProjectAction(EntityManagerInterface $em, Project $project, UserInterface $user = null, MailDispatcher $mailDispatcher, TicketingManager $ticketingManager)
    {
        $this->checkIfArtistAuthorized($user);

        //$mailDispatcher->sendConfirmedProject($project);

        // Generate and send tickets
        /*foreach($project->getSalesPaid() as $sale) {
            if(!empty($sale->getTicketsPurchases())) {
                $ticketingManager->generateAndSendXTickets($sale);
            }
        }*/

        $ticketingManager->ticketingTest();

        //$project->setSuccessful(true);
        //$em->flush();

        $this->addFlash('x_notice', "Le projet a bien été confirmé. Les contributeurs ont été avertis et les éventuels tickets vendus ont été envoyés");
        return $this->redirectToRoute('x_artist_dashboard');
    }


    /**
     * @Route("/project/{id}/refund", name="x_artist_project_refund")
     */
    public function refundProjectAction(EntityManagerInterface $em, Project $project, UserInterface $user = null, PaymentManager $paymentManager)
    {
        $this->checkIfArtistAuthorized($user);

        $project->setFailed(true);
        $paymentManager->refundStripeAndProject($project);
        $em->flush();

        $this->addFlash('x_notice', 'Le projet a bien été annulé. Les éventuels contributeurs ont été avertis et remboursés.');
        return $this->redirectToRoute('x_artist_dashboard');
    }


    /**
     * @Route("/project/{id}/delete", name="x_artist_project_delete")
     */
    public function deleteProjectAction(EntityManagerInterface $em, Project $project, UserInterface $user = null)
    {
        $this->checkIfArtistAuthorized($user);

        if($project->getCollectedAmount() == 0) {
            $project->setDeleted(true);
            $project->setFailed(true);

            foreach ($project->getProducts() as $product) {
                $product->setDeleted(true);
            }

            $em->flush();
            $this->addFlash('x_notice', 'Le projet a bien été supprimé');
        } else {
            $this->addFlash('yb_error', 'Ce projet ne peut être supprimé car le montant collecté est supérieur à 0 €.');
        }
        return $this->redirectToRoute('x_artist_dashboard');

    }


    /**
     * @Route("/project/{id}/contributions", name="x_artist_project_contributions")
     */
    public function contributionsProjectAction(EntityManagerInterface $em, UserInterface $user = null, Project $project)
    {
        $this->checkIfArtistAuthorized($user, $project);

        $donations = $project->getDonationsPaid();
        $sales = $project->getSalesPaid();

        return $this->render('@X/XArtist/project_contributions.html.twig', array(
            'project' => $project,
            'donations' => $donations,
            'sales' => $sales
        ));
    }


    /**
     * @Route("/project/{id}/products", name="x_artist_project_products")
     */
    public function productsProjectAction(EntityManagerInterface $em, UserInterface $user = null, Project $project)
    {
        $this->checkIfArtistAuthorized($user, $project);

        $products = $em->getRepository('XBundle:Product')->getProductsForProject($project);
        
        return $this->render('@X/XArtist/Product/products.html.twig', array(
            'project' => $project,
            'products' => $products
        ));
    }


    /**
     * @Route("/project/{id}/product/add", name="x_artist_product_add")
     */
    public function addProductAction(EntityManagerInterface $em, UserInterface $user = null, Request $request, Project $project, MailDispatcher $mailDispatcher)
    {
        $this->checkIfArtistAuthorized($user, $project);
        
        $product = new Product();
        
        $form = $this->createForm(ProductType::class, $product, ['creation' => true]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $product->setProject($project);
            $em->persist($product);
            $em->flush();

            $this->addFlash('x_notice', 'La mise en vente de l\'article "' . $product->getName() . '" a bien été enregistrée! Elle doit maintenant être validé par l\'équipe d\'Un-Mute');
            $mailDispatcher->sendAdminNewProduct($product); 
            return $this->redirectToRoute('x_artist_project_products', ['id' => $project->getId()]);
        }

        return $this->render('@X/XArtist/Product/product_add.html.twig', array(
            'form' => $form->createView(),
            'project' => $project,
            'product' => $product
        ));
    }


    /**
     * @Route("/project/{id}/product/{idProd}/update", name="x_artist_product_update")
     */
    public function updateProductAction(EntityManagerInterface $em, UserInterface $user = null, Request $request, Project $project, $idProd)
    {
        $this->checkIfArtistAuthorized($user, $project);

        $product = $em->getRepository('XBundle:Product')->find($idProd);

        $form = $this->createForm(ProductType::class, $product, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($product);
            $em->flush();

            $this->addFlash('x_notice', 'L\'article a bien été modifié.');
            return $this->redirectToRoute('x_artist_project_products', ['id' => $project->getId()]);
        }

        return $this->render('@X/XArtist/Product/product_add.html.twig', array(
            'form' => $form->createView(),
            'project' => $project,
            'product' => $product
        ));
    }

    /**
     * @Route("/project/{id}/product/{idProd}/delete", name="x_artist_product_delete")
     */
    public function deleteProductAction(EntityManagerInterface $em, UserInterface $user = null, Project $project, Product $product)
    {
        $this->checkIfArtistAuthorized($user, $project);

        if($product->getProductsSold() == 0) {
            $product->setDeleted(true);
            $em->flush();
            $this->addFlash('x_notice', 'L\'article a bien été supprimé');
        } else {
            $this->addFlash('yb_error', 'L\'article ne peut être supprimé car il a déjà été vendu un certain nombre');
        }
        return $this->redirectToRoute('x_artist_project_products', ['id' => $project->getId()]);

    }


    /**
     * @Route("/project/{id}/transactional-message", name="x_artist_project_transactional_message")
     */
    public function transactionalMessageProjectAction(Request $request, UserInterface $user = null, Project $project)
    {
        $this->checkIfArtistAuthorized($user, $project);

        $message = new XTransactionalMessage($project);
        $oldMessages = $project->getTransactionalMessages();

        $form = $this->createForm(XTransactionalMessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if(!empty($project->getDonators()) && !empty($project->getBuyers())) {
                if ($form->get('toDonators')->getData() == true && $form->get('toBuyers')->getData() == false) {
                    $message->setToDonators(true);
                    $contributors = $project->getDonators();
                } elseif ($form->get('toDonators')->getData() == false && $form->get('toBuyers')->getData() == true) {
                    $message->setToBuyers(true);
                    $contributors = $project->getBuyers();
                } else {
                    $message->setToDonators(true);
                    $message->setToBuyers(true);
                    $contributors = $project->getContributors();
                }
            } else {
                if(!empty($project->getDonators())){
                    $message->setToDonators(true);
                    $contributors = $project->getDonators();
                } elseif(!empty($project->getBuyers())) {
                    $message->setToBuyers(true);
                    $contributors = $project->getBuyers();
                }
            }

            $this->em->persist($message);
            $this->em->flush();

            $this->mailDispatcher->sendXTransactionalMessageWithCopy($message, $contributors);

            $this->addFlash('x_notice', 'Votre message a bien été envoyé.');
            return $this->redirectToRoute($request->get('_route'), $request->get('_route_params'));
        }

        return $this->render('@X/XArtist/project_transactional_message.html.twig', [
            'form' => $form->createView(),
            'project' => $project,
            'old_messages' => $oldMessages,
        ]);

    }


    /**
     * @Route("/project/{id}/{code}remove-photo", name="x_artist_project_remove_photo")
     */
    public function removePhotoAction(EntityManagerInterface $em, UserInterface $user = null, Request $request, Project $project, $code)
    {
        $filename = $request->get('filename');
        $photo = $em->getRepository('XBundle:Image')->findOneBy(['filename' => $filename]);
        
        $em->remove($photo);
        $project->removeProjectPhoto($photo);
        
        $filesystem = new Filesystem();
        $filesystem->remove($this->get('kernel')->getRootDir().'/../web/' . Project::getWebPath($photo));
        
        $em->persist($project);
        $em->flush();
        return new Response();
    }





}

?>