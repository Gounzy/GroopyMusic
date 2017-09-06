<?php
/**
 * Created by PhpStorm.
 * User: Gonzague
 * Date: 15-03-17
 * Time: 23:03
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\CounterPart;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Entity\Phase;
use AppBundle\Entity\Step;
use AppBundle\Entity\StepType;

class LoadPhaseSteps extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        // Phase 1
        $phase1 = new Phase();
        $phase1->setNum(1);
        $phase1->setName("Initiation");

        $step11 = new Step();
        $step11->setNum(1)->setDeadlineDuration(30)->setRequiredAmount(300)->setName("Concert 1")->setDescription("Blablabla");
        $step12 = new Step();
        $step12->setNum(2)->setDeadlineDuration(35)->setRequiredAmount(500)->setName("Concert 2")->setDescription("Blablabla");


        $phase1->addStep($step11);
        $phase1->addStep($step12);

        // Phase 2
        $phase2 = new Phase();
        $phase2->setNum(2)->setName("Expertise");

        $step21 = new Step();
        $step21->setNum(1)->setDeadlineDuration(40)->setRequiredAmount(800)->setName("Concert 3")->setDescription("Blablabla");
        $step22 = new Step();
        $step22->setDeadlineDuration(50)->setRequiredAmount(1000)->setNum(2)->setName("Concert 4")->setDescription("Blablabla");

        $phase2->addStep($step21);
        $phase2->addStep($step22);

        // Step type : for example "Concert"
        $st1 = new StepType();
        $st1->setName("Concert")->setDescription("blablabla");
        $st1->addStep($step11)->addStep($step12)->addStep($step21)->addStep($step22);

        // Counterparts
        $cp11 = new CounterPart();
        $cp11->setStep($step11)
             ->setPrice(10)
            ->setMaximumAmount(50)
            
            ->setDescription("Description")
            ->setName("Place de concert normale")
        ;

        $cp12 = new CounterPart();
        $cp12->setStep($step11)
            ->setPrice(15)
            ->setMaximumAmount(10)
            
            ->setDescription("Description")
            ->setName("Place de concert VIP")
        ;

        $cp21 = new CounterPart();
        $cp21->setStep($step12)
            ->setPrice(30)
            ->setMaximumAmount(100)
            
            ->setDescription("Description")
            ->setName("Place de concert normale")
        ;

        $manager->persist($st1);
        $st1->mergeNewTranslations();
        $manager->persist($phase1);
        $phase1->mergeNewTranslations();
        $manager->persist($phase2);
        $phase2->mergeNewTranslations();

        $manager->persist($cp11);
        $cp11->mergeNewTranslations();
        $manager->persist($cp12);
        $cp12->mergeNewTranslations();
        $manager->persist($cp21);
        $cp21->mergeNewTranslations();

        $step11->mergeNewTranslations(); $step12->mergeNewTranslations(); $step21->mergeNewTranslations(); $step22->mergeNewTranslations();

        $manager->flush();

        $this->addReference('phase1', $phase1);
        $this->addReference('phase2', $phase2);

        $this->addReference('step11', $step11);
        $this->addReference('step12', $step12);
    }

    public function getOrder()
    {
        // the order in which fixtures will be loaded
        // the lower the number, the sooner that this fixture is loaded
        return 1;
    }
}