<?php

namespace AppBundle\Form;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use AppBundle\Entity\CounterPart;
use Doctrine\ORM\EntityRepository;
use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class CounterPartType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('translations', TranslationsType::class, [
                'label' => false,
                'locales' => ['fr'],
                'fields' => [
                    'name' => [
                        'field_type' => TextType::class,
                        'label' => 'Nom',
                    ],
                    'description' => [
                        'field_type' => TextareaType::class,
                        'label' => 'Description',
                    ],
                ],
            ])
            ->add('maximumAmount', IntegerType::class, array(
                'required' => true,
                'label' => 'Nombre en stock au total',
            ))
            ->add('maximumAmountPerPurchase', NumberType::class, array(
                'required' => true,
                'label' => "Nombre max par commande",
            ));

        if (!$options['sold']) {
            $builder
                ->add('isChildEntry', CheckboxType::class, array(
                    'required' => false,
                    'label' => "Il s'agit d'un ticket enfant",
                ))
                ->add('price', NumberType::class, array(
                    'required' => false,
                    'label' => 'Prix (en euros) (soit 0 €, soit 1 € ou plus)',
                ))
                ->add('thresholdIncrease', NumberType::class, array(
                    'required' => true,
                    'label' => "Poids (dans le sold out, mais aussi dans le seuil du financement participatif)",
                ))
                ->add('freePrice', CheckboxType::class, array(
                    'attr' => ['class' => 'free-price-checkbox'],
                    'required' => false,
                    'label' => "Le prix doit être librement choisi par les acheteurs"
                ))
                ->add('minimumPrice', NumberType::class, array(
                    'required' => false,
                    'label' => "Prix minimum (en euros) (1 € ou plus)",
                ));
            if ($options['has_venue']) {
                $config = $options['config'];
                $blks = $config->getBlocks();
                if ($options['creation']) {
                    $builder
                        ->add('accessEverywhere', CheckboxType::class, array(
                            'required' => false,
                            'label' => 'Ce ticket donne accès à l\'entièreté des sièges de la salle',
                            'attr' => ['class' => 'give-access-everywhere give-access-everywhere-cb'],
                        ))
                        ->add('venueBlocks', EntityType::class, array(
                            'required' => false,
                            'label' => 'Bloc de la salle pour lesquelles le ticket donne accès',
                            'multiple' => true,
                            'expanded' => true,
                            'class' => 'AppBundle\Entity\YB\Block',
                            'choices' => $blks,
                        ));
                } else {
                    $builder
                        ->add('accessEverywhere', CheckboxType::class, array(
                            'required' => false,
                            'label' => 'Ce ticket donne accès à l\'entièreté des sièges de la salle',
                            'attr' => ['class' => 'give-access-everywhere'],
                        ))
                        ->add('venueBlocks', EntityType::class, array(
                            'required' => false,
                            'label' => 'Bloc de la salle pour lesquelles le ticket donne accès',
                            'multiple' => true,
                            'expanded' => true,
                            'class' => 'AppBundle\Entity\YB\Block',
                            'choices' => $blks,
                        ));
                }
            }
            $id = $options['campaign_id'];
            if ($options['has_sub_events'])
                $builder
                    ->add('subEvents', EntityType::class, array(
                        'required' => false,
                        'label' => 'Dates auxquelles ce ticket donne accès',
                        'multiple' => true,
                        'expanded' => true,
                        'class' => 'AppBundle\Entity\YB\YBSubEvent',
                        'query_builder' => function (EntityRepository $er) use ($id) {
                            return $er->createQueryBuilder('s')
                                ->innerJoin('s.campaign', 'c')
                                ->where('c.id = :id')
                                ->orderBy('s.date', 'ASC')
                                ->setParameter('id', $id);
                        },
                    ));
        }
    }

    public function validate(CounterPart $counterPart, ExecutionContextInterface $context)
    {
        if ($counterPart->getFreePrice()) {
            if ($counterPart->getMinimumPrice() == null || $counterPart->getMinimumPrice() < 1) {
                $context->addViolation('Les prix des tickets doivent être au minimum de 1 €.');
            }
            $counterPart->setPrice($counterPart->getMinimumPrice());
        } else {
            if (!$counterPart->isFree() && $counterPart->getPrice() == null || $counterPart->getPrice() < 0 || ($counterPart->getPrice() > 0 && $counterPart->getPrice() < 1)) {
                $context->addViolation('Les prix des tickets doivent être au minimum de 1 €, ou alors de 0 € (pour tickets gratuits).');
            }
            $counterPart->setMinimumPrice($counterPart->getPrice());
        }
        $absolute_max_amount = 1000;
        if ($counterPart->getMaximumAmountPerPurchase() < 1 || $counterPart->getMaximumAmountPerPurchase() > $absolute_max_amount) {
            $context->addViolation('La quantité max de chaque type de ticket par commande doit être minimum de 1, maximum de ' . $absolute_max_amount . '.');
            $counterPart->setMaximumAmountPerPurchase($absolute_max_amount);
        }
        if (count($counterPart->getContractArtist()->getConfig()->getBlocks()) < 0) {
            $counterPart->setAccessEverywhere(true);
        } else {
            if ($counterPart->getAccessEverywhere() === false && count($counterPart->getVenueBlocks()) === 0) {
                $context->addViolation('Si le ticket ne donne pas accès à toute la salle, vous devez sélectionner au moins un bloc.');
            }
        }
        if (!$counterPart->canOverpassVenueCapacity()) {
            if ($counterPart->isCapacityMaxReach()) {
                $context->addViolation("Le nombre de ticket en stock est supérieur aux nombre de places dans la salle !");
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CounterPart::class,
            'constraints' => array(
                new Assert\Callback(array($this, 'validate'))
            ),
            'campaign_id' => null,
            'has_sub_events' => false,
            'has_venue' => false,
            'config' => null,
            'creation' => false,
            'sold' => false
        ]);
    }

    public function getBlockPrefix()
    {
        return 'app_bundle_counter_part_type';
    }
}
