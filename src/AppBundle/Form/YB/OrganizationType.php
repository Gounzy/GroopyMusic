<?php

namespace AppBundle\Form\YB;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Entity\YB\Organization;

class OrganizationType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options){
        $builder
            ->add('name', TextType::class, array(
                'label' => 'Nom de l\'organisation',
                'required' => true,
            ))
            ->add('vatNumber', TextType::class, array(
                'label' => 'Numéro de TVA (si applicable)',
                'required' => false,
            ))
            ->add('bankAccount', TextType::class, array(
                'label' => 'Numéro de compte IBAN',
                'required' => false,
            ))
            ->add('published', CheckboxType::class, array(
                'label' => 'Publier (si coché, l\'organisation aura sa propre page sur Ticked-it! reprenant la liste des événements qu\'elle organise',
                'required' => false,
            ))
            ->add('translations', TranslationsType::class, [
                'label' => false,
                'locales' => ['fr'],
                'required' => false,
                'fields' => [
                    'description' => [
                        'field_type' => TextareaType::class,
                        'label' => 'Description',
                        'required' => false,
                    ],
                ],
            ])
            ->add('save', SubmitType::class, array(
                'label' => 'Enregistrer',
                'attr' => array('class' => 'btn'),
            ));
    }

    public function configureOptions(OptionsResolver $resolver){
        $resolver->setDefaults(array(
            'data_class' => Organization::class,
            'constraints' => [
                new UniqueEntity(['fields' => ['name']]),
            ],
        ));
    }

}