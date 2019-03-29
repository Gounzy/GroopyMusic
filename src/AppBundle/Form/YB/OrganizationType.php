<?php

namespace AppBundle\Form\YB;

use Symfony\Component\Form\AbstractType;
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
            ))
            ->add('vatNumber', TextType::class, array(
                'label' => 'Numéro de TVA (si applicable)',
                'required' => false,
            ))
            ->add('bankAccount', TextType::class, array(
                'label' => 'Numéro de compte IBAN',
                'required' => false,
            ))
            ->add('save', SubmitType::class, array(
                'label' => 'Enregistrer',
                'attr' => array('class' => 'btn'),
            ));
    }

    public function configureOptions(OptionsResolver $resolver){
        $resolver->setDefaults(array(
            'data_class' => Organization::class,
        ));
    }

}