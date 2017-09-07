<?php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\TranslationBundle\Filter\TranslationFieldFilter;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class HallAdmin extends PartnerAdmin  {

    public function configureListFields(ListMapper $listMapper)
    {
        parent::configureListFields($listMapper);
        $listMapper
            ->remove('type')
            ->add('step', null, array(
                'label' => 'Palier',
            ))
        ;
    }

    public function configureShowFields(ShowMapper $showMapper)
    {
        parent::configureShowFields($showMapper);

        $showMapper
            ->with('Données de la salle')
                ->add('step', 'sonata_type_model', array(
                    'label' => 'Palier correspondant',
                ))
                ->add('province', 'sonata_type_model', array(
                    'label' => 'Province',
                ))
                ->add('capacity', null, array(
                    'label' => 'Capacité (en nombre de personnes)',
                ))
                ->add('delay', null, array(
                    'label' => 'Délai demandé (en jours)',
                ))
                ->add('price', null, array(
                    'label' => 'Prix demandé',
                ))
                ->add('cron_automatic_days_formatted', null, array(
                    'label' => 'Jours automatiques',
                ))
                ->add('available_dates_string', 'text', array(
                    'label' => 'Dates disponibles (calculé automatiquement mais modifiable)',
                ))
                ->add('technical_specs', 'sonata_type_model', array(
                    'label' => 'Spécifications techniques (PDF)'
                ))
                ->add('gallery', 'sonata_type_model', array(
                    'label' => 'Galerie photos'
                ))
            ->end()
        ;
    }

    public function configureFormFields(FormMapper $form)
    {
        parent::configureFormFields($form);
        $form
            ->with('Données de la salle')
                ->add('step', 'sonata_type_model', array(
                    'label' => 'Palier correspondant',
                    'required' => true,
                    'btn_add' => false,
                ))
                ->add('province', 'sonata_type_model', array(
                    'label' => 'Province',
                    'required' => true,
                    'btn_add' => false,
                ))
                ->add('capacity', null, array(
                    'label' => 'Capacité (en nombre de personnes)',
                    'required' => true,
                ))
                ->add('delay', null, array(
                    'label' => 'Délai demandé (en jours)',
                    'required' => true,
                ))
                ->add('price', null, array(
                    'label' => 'Prix demandé',
                    'required' => true,
                ))
                ->add('cron_automatic_days', null, array(
                    'required' => false,
                    'label' => 'Jours automatiques',
                    'entry_type' => 'checkbox',
                    'entry_options' => [
                        'required' => false,
                    ]
                ))
                ->add('available_dates_string', 'text', array(
                    'label' => 'Dates disponibles (calculé automatiquement mais modifiable)',
                    'empty_data' => '',
                    'required' => false,
                    'attr' => ['class' => 'multiDatesPicker'],
                ))
                ->add('technical_specs', 'sonata_type_model', array(
                    'required' => false,
                    'label' => 'Spécifications techniques (PDF)'
                ))
                ->add('gallery', 'sonata_type_model', array(
                    'required' => false,
                    'label' => 'Galerie photos'
                ))
            ->end()
        ;
    }

    public function prePersist($object)
    {
        parent::prePersist($object);
        $object->refreshDates();
    }

    public function preUpdate($object)
    {
        parent::preUpdate($object);
        $object->refreshDates();
    }

}