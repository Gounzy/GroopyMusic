<?php

namespace AppBundle\Command;

use AppBundle\Entity\Genre;
use AppBundle\Services\MailDispatcher;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:test_command')
            ->setDescription('Hello PhpStorm')
        ->addOption('mail');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('On y va');

        $logger = $this->getContainer()->get('logger');

        $logger->addDebug("Beginning test command...");

        $mail = $input->getOption('mail');
        if($mail)
            $this->getContainer()->get(MailDispatcher::class)->sendTestEmail();

        $logger->addCritical("Mail is prepared...");

        $genre = new Genre();
        $genre->setLocale('fr');
        $genre->setName('Rap français');
        $genre->mergeNewTranslations();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($genre);
        $em->flush();

        $output->writeln('fini');

        $logger->addError("Test command done !!!!!!!!!!");
    }
}
