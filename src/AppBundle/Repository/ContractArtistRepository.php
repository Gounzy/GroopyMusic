<?php

namespace AppBundle\Repository;

use AppBundle\Command\KnownOutcomeContractCommand;
use AppBundle\Entity\Artist;
use AppBundle\Entity\ContractArtist;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ContractArtistRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ContractArtistRepository extends OptimizedRepository implements ContainerAwareInterface
{
    private $container;
    public function setContainer(ContainerInterface $container = null) {
        $this->container = $container;
    }

    public function __construct(EntityManager $em, Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);
    }

    public function initShortName()
    {
        $this->short_name = 'c';
    }

    public function baseQueryBuilder() {
        return $this->createQueryBuilder('c')
            ->join('c.artist', 'a')
            ->join('c.step', 's')
            ->join('c.preferences', 'p')
            ->leftJoin('c.reality', 'r')
            ->leftJoin('s.counterParts', 'cp')
            ->leftJoin('a.genres', 'g')
            ->addSelect('a')
            ->addSelect('s')
            ->addSelect('r')
            ->addSelect('p')
            ->addSelect('cp')
            ->addSelect('g')
            ->orderBy('r.date', 'ASC')
            ->addOrderBy('p.date', 'ASC')
        ;
    }

    public function queryVisible($prevalidation = false) {
        return $this->createQueryBuilder('c')
            ->join('c.artist', 'a')
            ->join('c.step', 's')
            ->join('c.preferences', 'p')
            ->leftJoin('c.reality', 'r')
            ->leftJoin('s.counterParts', 'cp')
            ->leftJoin('a.genres', 'g')
            ->addSelect('a')
            ->addSelect('s')
            ->addSelect('r')
            ->addSelect('p')
            ->addSelect('cp')
            ->addSelect('g')
            ->orderBy('r.date', 'ASC')
            ->addOrderBy('p.date', 'ASC')
            ->where('c.failed = 0')
            ->andWhere('c.test_period = :prevalidation')
            ->andWhere('(r.date is not null AND r.date >= :yesterday) OR (p.date >= :yesterday)')
            ->setParameter('prevalidation', $prevalidation)
            ->setParameter('yesterday', new \DateTime('yesterday'))
        ;
    }

    public function findEligibleForTicketGeneration() {
        return $this->queryVisible()
            ->andWhere('c.tickets_sent = 1')
            ->andWhere('c.successful = 1')
            ->getQuery()
            ->getResult()
        ;
    }

    // Don't type-hint user here as it creates a bug
    public function findInPreValidationContracts($user = null, $rolesManager = null) {
        if($user == null || $rolesManager == null) {
            return [];
        }

        return array_filter(
            $this->queryVisible(true)->getQuery()->getResult(),

            function(ContractArtist $contractArtist) use ($user, $rolesManager) {
                return  $rolesManager->userHasRole($user, 'ROLE_ADMIN') ||
                        $user->owns($contractArtist->getArtist());
            }
        );
    }

    public function findNewContracts($max) {
        return $this->queryVisible()
            ->orderBy('p.date', 'desc')
            ->setMaxResults($max)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findSuccessful() {
        return $this->queryVisible()
            ->leftJoin('c.contractsFan', 'cf')
            ->addSelect('cf')
            ->andWhere('c.successful = 1 OR c.tickets_sold >= c.min_tickets')
            ->andWhere('c.failed = 0')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Returns 0-$limit contracts for which the deadline is not passed AND not enough money is raised at the moment
     */
    public function findNotSuccessfulYet($limit = null) {
        $qb = $this->queryVisible()
            //->andWhere('c.dateEnd > :now')
            ->andWhere('c.tickets_sold < c.min_tickets')
            ->andWhere('c.successful = 0')
            // TODO modify r.date --> concert date (new field)
            ->orderBy('p.date', 'asc')
        ;

        if($limit != null) {
            $qb->setMaxResults($limit);
        }

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Returns 0-$limit contracts for which there are tickets to buy
     */
    public function findVisible($limit = null) {
        $qb = $this->queryVisible()
        ;

        if($limit != null) {
            $qb->setMaxResults($limit);
        }

        return $qb
            ->orderBy('p.date', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findCurrentForArtist(Artist $artist) {
        return $this->queryVisible()
            ->andWhere('a = :artist')
            ->setParameter('artist', $artist)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @see KnownOutcomeContractCommand
     */
    public function findPending() {
        return $this->queryVisible()
            ->andWhere('c.dateEnd < :now')
            ->andWhere('c.successful = 0')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @see KnownOutcomeContractCommand
     */
    public function findNewlySuccessful() {
        return $this->queryVisible()
            ->andWhere('c.successful = 0') // Not marked as successful yet
            ->andWhere('c.tickets_sold >= s.min_tickets')
            ->getQuery()
            ->getResult()
        ;
    }
}
