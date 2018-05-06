<?php
/**
 * Created by PhpStorm.
 * User: Jean-François Cochar
 * Date: 23/03/2018
 * Time: 16:00
 */

namespace AppBundle\Repository;

/**
 * User_RewardRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class User_RewardRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * Recovers all active rewards from @param $user
     *
     * @param $user
     * @return array user_reward array
     */
    public function getActiveUserRewards($user)
    {
        return $this->getEntityManager()->createQuery(
            'SELECT ur,r,tr,rw,bca,a,cp,s,u
                  FROM AppBundle:User_Reward ur
                  LEFT JOIN ur.reward r
                  LEFT JOIN r.translations tr
                  LEFT JOIN r.restrictions rw
                  LEFT JOIN ur.base_contract_artists bca
                  LEFT JOIN ur.artists a
                  LEFT JOIN ur.counter_parts cp
                  LEFT JOIN ur.base_steps s
                  LEFT JOIN ur.user u 
                  WHERE u.id = ?1
                  AND ur.active = 1
                  AND ur.limit_date > ?2
                  ORDER BY ur.creation_date DESC
                  ')
            ->setParameter(1, $user->getId())
            ->setParameter(2, new \DateTime())
            ->getResult();
    }

    /**
     * Recovers all possible rewards for an event
     *
     * @param $user
     * @param $contractArtist
     * @return array user_reward array
     */
    public function getPossibleActiveRewards($user, $contractArtist)
    {
        return $this->getEntityManager()->createQuery(
            'SELECT ur,r,tr,rw,bca,a,a,cp,s,u
                  FROM AppBundle:User_Reward ur
                  LEFT JOIN ur.reward r
                  LEFT JOIN r.translations tr
                  LEFT JOIN r.restrictions rw
                  LEFT JOIN ur.base_contract_artists bca
                  LEFT JOIN ur.artists a
                  LEFT JOIN ur.counter_parts cp
                  LEFT JOIN ur.base_steps s
                  LEFT JOIN ur.user u 
                  WHERE u.id = ?1
                  AND ur.active = 1
                  AND ur.limit_date > ?6
                  AND (bca.id = ?2 
                  OR a.id = ?3 
                  OR s.id = ?4
                  OR cp.id IN ( ?5 )
                  OR ur.id IN(SELECT ur2.id
                              FROM AppBundle:User_Reward ur2
                              LEFT JOIN ur2.base_contract_artists bca2
                              LEFT JOIN ur2.artists a2
                              LEFT JOIN ur2.counter_parts cp2
                              LEFT JOIN ur2.base_steps s2
                              GROUP BY ur2.id
                              HAVING (COUNT(bca2.id) = 0 
                              AND COUNT(a2.id) = 0 
                              AND COUNT(cp2.id) = 0
                              AND COUNT(s2.id) = 0))
                              )
                  ')
            ->setParameter(1, $user->getId())
            ->setParameter(2, $contractArtist->getId())
            ->setParameter(3, $contractArtist->getArtist()->getId())
            ->setParameter(4, $contractArtist->getStep()->getId())
            ->setParameter(5, array_map(function ($elem) {
                return $elem->getId();
            }, $contractArtist->getStep()->getCounterParts()->toArray()))
            ->setParameter(6, new \DateTime())
            ->getResult();
    }

}