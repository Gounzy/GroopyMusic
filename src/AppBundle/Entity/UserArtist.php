<?php
/**
 * Created by PhpStorm.
 * User: Gonzague
 * Date: 05-02-17
 * Time: 18:46
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use PUGX\MultiUserBundle\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_artist")
 * @UniqueEntity(fields = "username", targetClass = "AppBundle\Entity\User", message="fos_user.username.already_used")
 * @UniqueEntity(fields = "email", targetClass = "AppBundle\Entity\User", message="fos_user.email.already_used")
 */
class UserArtist extends User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="artistname", type="string", length=67)
     */
    private $artistname;


    /**
     * Set artistname
     *
     * @param string $artistname
     *
     * @return UserArtist
     */
    public function setArtistname($artistname)
    {
        $this->artistname = $artistname;

        return $this;
    }

    /**
     * Get artistname
     *
     * @return string
     */
    public function getArtistname()
    {
        return $this->artistname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     *
     * @return UserArtist
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     *
     * @return UserArtist
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }
}
