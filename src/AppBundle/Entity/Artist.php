<?php
/**
 * Created by PhpStorm.
 * User: Gonzague
 * Date: 05-02-17
 * Time: 18:46
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sonata\TranslationBundle\Model\Gedmo\AbstractPersonalTranslatable;
use Gedmo\Mapping\Annotation as Gedmo;
use Sonata\TranslationBundle\Model\Gedmo\TranslatableInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="artist")
 * @Gedmo\TranslationEntity(class="AppBundle\Entity\Translations\ArtistTranslation")
 */
class Artist extends AbstractPersonalTranslatable implements TranslatableInterface
{
    public function __toString()
    {
        return $this->artistname;
    }

    public function __construct(Phase $phase)
    {
        $this->phase = $phase;
    }

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="AppBundle\Entity\Translations\ArtistTranslation",
     *     mappedBy="object",
     *     cascade={"persist", "remove"}
     * )
     */
    protected $translations;

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
     * @ORM\ManyToOne(targetEntity="Phase")
     * @ORM\JoinColumn(nullable=false)
     */
    private $phase;

    /**
     * @ORM\ManyToMany(targetEntity="Genre")
     */
    private $genres;

    /**
     * @ORM\Column(name="short_description", type="string", length=255)
     * @Gedmo\Translatable
     */
    private $short_description;

    /**
     * @ORM\Column(name="biography", type="text")
     * @Gedmo\Translatable
     */
    private $biography;

    /**
     * @ORM\OneToMany(targetEntity="Artist_User", mappedBy="artist")
     */
    private $artists_user;

    /**
     * @ORM\OneToMany(targetEntity="ArtistOwnershipRequest", mappedBy="artist", cascade={"persist"})
     */
    private $ownership_requests;

    // Form only
    public $ownership_requests_form;

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
     * Set phase
     *
     * @param \AppBundle\Entity\Phase $phase
     *
     * @return UserArtist
     */
    public function setPhase(\AppBundle\Entity\Phase $phase)
    {
        $this->phase = $phase;

        return $this;
    }

    /**
     * Get phase
     *
     * @return \AppBundle\Entity\Phase
     */
    public function getPhase()
    {
        return $this->phase;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add genre
     *
     * @param \AppBundle\Entity\Genre $genre
     *
     * @return Artist
     */
    public function addGenre(\AppBundle\Entity\Genre $genre)
    {
        $this->genres[] = $genre;

        return $this;
    }

    /**
     * Remove genre
     *
     * @param \AppBundle\Entity\Genre $genre
     */
    public function removeGenre(\AppBundle\Entity\Genre $genre)
    {
        $this->genres->removeElement($genre);
    }

    /**
     * Get genres
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGenres()
    {
        return $this->genres;
    }

    /**
     * Set shortDescription
     *
     * @param string $shortDescription
     *
     * @return Artist
     */
    public function setShortDescription($shortDescription)
    {
        $this->short_description = $shortDescription;

        return $this;
    }

    /**
     * Get shortDescription
     *
     * @return string
     */
    public function getShortDescription()
    {
        return $this->short_description;
    }

    /**
     * Set biography
     *
     * @param string $biography
     *
     * @return Artist
     */
    public function setBiography($biography)
    {
        $this->biography = $biography;

        return $this;
    }

    /**
     * Get biography
     *
     * @return string
     */
    public function getBiography()
    {
        return $this->biography;
    }

    /**
     * Add artistsUser
     *
     * @param \AppBundle\Entity\Artist_User $artistsUser
     *
     * @return Artist
     */
    public function addArtistsUser(\AppBundle\Entity\Artist_User $artistsUser)
    {
        $this->artists_user[] = $artistsUser;

        return $this;
    }

    /**
     * Remove artistsUser
     *
     * @param \AppBundle\Entity\Artist_User $artistsUser
     */
    public function removeArtistsUser(\AppBundle\Entity\Artist_User $artistsUser)
    {
        $this->artists_user->removeElement($artistsUser);
    }

    /**
     * Get artistsUser
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getArtistsUser()
    {
        return $this->artists_user;
    }

    /**
     * Remove translation
     *
     * @param \AppBundle\Entity\Translations\ArtistTranslation $translation
     */
    public function removeTranslation(\AppBundle\Entity\Translations\ArtistTranslation $translation)
    {
        $this->translations->removeElement($translation);
    }

    /**
     * Add ownershipRequest
     *
     * @param \AppBundle\Entity\ArtistOwnershipRequest $ownershipRequest
     *
     * @return Artist
     */
    public function addOwnershipRequest(\AppBundle\Entity\ArtistOwnershipRequest $ownershipRequest)
    {
        $this->ownership_requests[] = $ownershipRequest;
        $ownershipRequest->setArtist($this);

        return $this;
    }

    /**
     * Remove ownershipRequest
     *
     * @param \AppBundle\Entity\ArtistOwnershipRequest $ownershipRequest
     */
    public function removeOwnershipRequest(\AppBundle\Entity\ArtistOwnershipRequest $ownershipRequest)
    {
        $this->ownership_requests->removeElement($ownershipRequest);
    }

    /**
     * Get ownershipRequests
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOwnershipRequests()
    {
        return $this->ownership_requests;
    }

    /**
     * Add ownershipRequest
     *
     * @param \AppBundle\Entity\ArtistOwnershipRequest $ownershipRequest
     *
     * @return Artist
     */
    public function addOwnershipRequestForm(\AppBundle\Entity\ArtistOwnershipRequest $ownershipRequest)
    {
        $this->ownership_requests_form[] = $ownershipRequest;
        $ownershipRequest->setArtist($this);

        return $this;
    }

    /**
     * Remove ownershipRequest
     *
     * @param \AppBundle\Entity\ArtistOwnershipRequest $ownershipRequest
     */
    public function removeOwnershipRequestForm(\AppBundle\Entity\ArtistOwnershipRequest $ownershipRequest)
    {
        $this->ownership_requests_form->removeElement($ownershipRequest);
    }
}
