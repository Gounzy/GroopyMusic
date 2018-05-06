<?php

namespace AppBundle\Repository;

/**
 * StepRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class StepRepository extends \Doctrine\ORM\EntityRepository
{
    public function findOrderedStepsWithoutPhases()
    {
        return $this->createQueryBuilder('s')
            ->join('s.phase', 'p')
            ->leftJoin('s.halls', 'h', 'WITH', 'h.visible = 1')
            ->addSelect('h')
            ->where('s.visible = 1')
            ->orderBy('p.num', 'asc')
            ->addOrderBy('s.num', 'asc')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recovers all step
     *
     * @return array step array
     */
    public function getStepsForSelect()
    {
        return $this->getEntityManager()->createQuery(
            'SELECT s,st
                  FROM AppBundle:BaseStep s
                  LEFT JOIN s.translations st
                  ')
            ->getResult();
    }

}
