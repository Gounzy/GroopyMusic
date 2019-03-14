<?php

namespace XBundle\Form;

use AppBundle\Entity\Artist;
use AppBundle\Repository\ArtistRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use XBundle\Entity\Project;
use XBundle\Entity\Tag;
use XBundle\Form\ImageType;

class ProjectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, array(
                'label' => 'Titre du projet',
                'constraints' => [
                    new Assert\NotBlank(),
                ]
            ))
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                $project = $event->getData();
                $event->getForm()->add('artist', EntityType::class, array(
                    'class' => Artist::class,
                    'choice_label' => 'artistname',
                    'query_builder' => function (ArtistRepository $ar) use ($project) {
                        return $ar->baseQueryBuilder()
                            ->innerJoin('a.artists_user', 'au')
                            ->where('au.user = :user')
                            ->setParameter('user', $project->getUser())
                            ->andWhere('a.deleted = 0');
                    },
                    'label' => 'Artiste associé',
                    'placeholder' => '',
                    'empty_data' => null,
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ));
            })
            ->add('description', TextareaType::class, array(
                'label' => 'Description',
                'constraints' => [
                    new Assert\NotBlank(),
                ]
            ))
            ->add('dateEnd', DateTimeType::class, array(
                'label' => 'Date de clôture du financement participatif',
                'disabled' => $options['is_edit'],
                'constraints' => [
                    new Assert\NotBlank(),
                ]
            ))
            ->add('threshold', IntegerType::class, array(
                'label' => 'Montant à atteindre (en €)',
                'disabled' => $options['is_edit'],
                'required' => false,
                'constraints' => [
                    new Assert\GreaterThanOrEqual(['value' => 0])
                ]
            ))
            ->add('tag', EntityType::class, array(
                'label' => 'Catégorie',
                'class' => Tag::class,
                'choice_label' => 'name',
                'placeholder' => '',
                'empty_data' => null,
                'constraints' => [
                    new Assert\NotBlank(),
                ]
            ))
            ->add('coverpic', ImageType::class, array(
                'label' => 'Photo de couverture',
                'required' => false
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Enregistrer'
            ))
        ;

        if ($options['creation']) {
            $builder
                ->add('noThreshold', CheckboxType::class, array(
                    'label' => 'Pas de seuil de validation',
                    'required' => false
                ))
            ;
        }
    }


    public function validate(Project $project, ExecutionContextInterface $context)
    {

        if(!$project->getNoThreshold()) {
            if($project->getThreshold() <= 0) {
                $context->addViolation('Puisque le projet à un seuil de validation, il faut préciser ce seuil, qui doit être supérieur à 0');
            }
        }

        if($project->getDateEnd() != null) {
            if ($project->getDateEnd() < $project->getDateCreation()) {
                $context->addViolation('La date de clôture du financement du projet doit être dans le futur');
            }
        }
    }

    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Project::class,
            'constraints' => array(
                new Assert\Callback(array($this, 'validate'))
            ),
            'creation' => false,
            'is_edit'=> false,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'xbundle_project_type';
    }


}
