<?php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;

class SuggestionBoxAdmin extends BaseAdmin
{
    public function configureRoutes(RouteCollection $collection)
    {
        $collection
            ->remove('create')
            ->remove('edit')
            ->add('handle', $this->getRouterIdParameter().'/handle')
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('displayName', null, array(
               'label' => 'Nom complet',
            ))
            ->add('date', null, array(
                'label' => 'Date',
            ))
            ->add('type', null, array(
                'label' => 'Type',
            ))
            ->add('object', null, array(
                'label' => 'Objet'
            ))
            ->add('email', null, array(
                'label' => 'E-mail',
            ))
            ->add('handler', null, array(
                'label' => 'Admin en charge',
            ))
            ->add('done', null, array(
                'DONE',
                'editable' => true,
            ))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show' => array(),
                    'handle' => array(
                        'template' => 'AppBundle:Admin/SuggestionBox:icon_handle.html.twig'
                    ),
                )
            ))
        ;
    }

    protected function configureShowFields(ShowMapper $show)
    {
        $show
            ->add('firstname', null, array(
                'label' => 'Prénom',
            ))
            ->add('name', null, array(
                'label' => 'Nom de famille',
            ))
            ->add('email', null, array(
                'label' => 'Adresse e-mail',
            ))
            ->add('phone', null, array(
                'label' => 'Téléphone',
            ))
            ->add('date', null, array(
                'label' => "Date d'envoi",
            ))
            ->add('type', null, array(
                'label' => 'Type',
            ))
            ->add('user', null, array(
                'label' => 'Membre',
                'route' => array('name' => 'show'),
            ))
            ->add('mailCopy', null, array(
                'label' => 'A souhaité une copie par mail',
            ))
            ->add('object', null, array(
                'label' => 'Objet',
            ))
            ->add('message', null, array(
                'label' => 'Message',
            ))
            ->add('handler', null, array(
                'label' => 'Admin en charge',
            ))
            ->add('done', null, array(
                'DONE',
            ))
        ;
    }
}