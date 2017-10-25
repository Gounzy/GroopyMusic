<?php
// src/AppBundle/Form/RegistrationType.php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->remove('username')
            // Duplicated from ProfileType
            ->add('lastname', TextType::class, array(
                'label' => 'labels.user.lastname',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Merci de renseigner un nom de famille.']),
                    new Length(['max' => 64, 'maxMessage' => 'Le nom ne peut dépasser {{ limit }} caractères.']),
                ]
            ))
            ->add('firstname', TextType::class, array(
                'label' => 'labels.user.firstname',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Merci de renseigner un prénom.']),
                    new Length(['max' => 64, 'maxMessage' => 'Le prénom ne peut dépasser {{ limit }} caractères.']),
                ]
            ))
            // end duplicated
            ->add('newsletter', CheckboxType::class, array(
                'label' => 'labels.user.newsletter',
                'required' => false,
            ))
        ;
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

    public function getBlockPrefix()
    {
        return 'app_user_registration';
    }

}