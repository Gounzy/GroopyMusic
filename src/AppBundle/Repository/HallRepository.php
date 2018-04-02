<?php

namespace AppBundle\Repository;
use AppBundle\Entity\Province;
use AppBundle\Entity\Step;

/**
 * HallRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class HallRepository extends \Doctrine\ORM\EntityRepository
{
    public function baseQueryBuilder() {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.province', 'pro')
            ->leftJoin('h.step', 's')
            ->leftJoin('h.address', 'a')
            ->leftJoin('h.photos', 'p')
            ->addSelect('p')
            ->addSelect('a')
            ->addSelect('s')
            ->addSelect('pro')
        ;
    }

    public function findVisible() {
        return $this
            ->baseQueryBuilder()
            ->andWhere('h.visible = 1')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findPotential(Step $step, Province $province = null) {
        $qb = $this->baseQueryBuilder()
            ->where('h.visible = 1')
            ->andWhere('h.step = :step')
            ->setParameter('step', $step)
        ;

        if($province != null) {
            $qb->andWhere('h.province = :province')
            ->setParameter('province', $province);
        }

        return $qb->getQuery()->getResult();
    }

}
