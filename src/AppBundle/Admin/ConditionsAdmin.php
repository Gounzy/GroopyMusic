<?php

namespace AppBundle\Admin;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\FormatterBundle\Form\Type\FormatterType;
use Sonata\FormatterBundle\Form\Type\SimpleFormatterType;

class ConditionsAdmin extends BaseAdmin
{
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id', null, array(
                'label' => 'Version',
            ))
            ->add('date', null, array(
                'label' => 'Date de création',
            ))
            ->add('_action', 'actions', [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('date')
                ->add('translations', TranslationsType::class, array(
                    'fields' => [
                        'content' => [
                            'label' => 'Contenu',
                            'field_type' => SimpleFormatterType::class,
                            'format' => 'richhtml',
                            'ckeditor_context' => 'my_config',
                        ]
                    ]
                ))
            ->end()
        ;
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('date')
        ;
    }
}
