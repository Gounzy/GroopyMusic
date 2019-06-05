<?php

namespace AppBundle\Repository\YB;

use AppBundle\Entity\User;
use AppBundle\Entity\YB\Organization;

class VenueConfigRepository extends \Doctrine\ORM\EntityRepository{

    public function findAllOpened(){
        return $this->createQueryBuilder('vc')
            ->join('vc.venue', 'v')
            ->where('vc.venue IS NOT NULL')
            ->andWhere('v.accept_venue_temp = 0')
            ->getQuery()
            ->getResult();
    }

}