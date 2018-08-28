<?php

namespace AppBundle\Form;

use AppBundle\Entity\ContractArtist;
use AppBundle\Entity\VIPInscription;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class VIPInscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contractArtist', EntityType::class, array(
                'label' => 'Festival',
                'class' => ContractArtist::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->queryVisible();
                },
            ))
            ->add('lastName', TextType::class, array(
                'constraints' => [new NotBlank()],
                'label' => 'Nom',
            ))
            ->add('firstName', TextType::class, array(
                'constraints' => [new NotBlank()],
                'label' => 'PréNom',
            ))
            ->add('email', EmailType::class, array(
                'constraints' => [new Email()],
                'label' => 'Adresse e-mail',
            ))
            ->add('company', TextType::class, array(
                'constraints' => [new NotBlank()],
                'label' => 'Entreprise / Groupe / Société / Média',
            ))
            ->add('function', TextType::class, array(
                'constraints' => [new NotBlank()],
                'label' => 'Fonction',
            ))
            ->add('commentary', TextareaType::class, array(
                'required' => false,
                'label' => 'Commentaire (facultatif)'
            ))
            ->add('submit', SubmitType::class, array(
                'label' => "<i class='fas fa-paper-plane'></i> Envoyer",
                'attr' => ['class' => 'btn btn-primary'],
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
           'data_class' => VIPInscription::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'app_bundle_vipinscription_type';
    }
}
