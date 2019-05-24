<?php

namespace AppBundle\Entity\YB;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class CustomTicket
 * @package AppBundle\Entity\YB
 * @ORM\Table(name="yb_custom_tickets")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\YB\CustomTicketRepository")
 */
class CustomTicket {

    /**
     * CustomTicket constructor.
     * @param bool $imageAdded
     * @param bool $venueMapAdded
     * @param bool $publicTransportTextInfosAdded
     * @param string $publicTransportTextInfos
     * @param bool $customInfosAdded
     * @param string $customInfos
     * @param bool $commuteAdded
     */
    public function __construct(YBContractArtist $campaign){
        $this->campaign = $campaign;
        $this->stations = new ArrayCollection();
    }

    public function __toString(){
        return $this->id . ' ' . $this->campaign;
    }

    public function constructor($imageAdded, $venueMapAdded, $publicTransportTextInfosAdded, $publicTransportTextInfos, $customInfosAdded, $customInfos, $commuteAdded){
        $this->imageAdded = $imageAdded;
        $this->venueMapAdded = $venueMapAdded;
        $this->publicTransportTextInfosAdded = $publicTransportTextInfosAdded;
        $this->publicTransportTextInfos = $publicTransportTextInfos;
        $this->customInfosAdded = $customInfosAdded;
        $this->customInfos = $customInfos;
        $this->commuteAdded = $commuteAdded;
        $this->previewMode = false;
    }

    public function constructNull(){
        $this->imageAdded = false;
        $this->venueMapAdded = false;
        $this->publicTransportTextInfosAdded = false;
        $this->publicTransportTextInfos = '';
        $this->customInfosAdded = false;
        $this->customInfos = '';
        $this->commuteAdded = false;
        $this->commuteSNCBAdded = false;
        $this->commuteSTIBAdded = false;
        $this->commuteTECAdded = false;
        $this->stations = new ArrayCollection();
        $this->previewMode = false;
        $this->mapsImagePath = null;
    }

    public function toFullString(){
        return
            $this->imageAdded . ' ' .
            $this->venueMapAdded . ' ' .
            $this->publicTransportTextInfosAdded . ' ' .
            $this->publicTransportTextInfos . ' ' .
            $this->customInfosAdded . ' ' .
            $this->customInfos . ' ' .
            $this->commuteAdded . ' ' .
            $this->previewMode;
    }

    public function getMapQuestUrl($key){
        if ($this->campaign->getVenue() !== null) {
            $mapsAdress = $this->campaign->getVenue()->getAddress()->getLatitude() . ',' . $this->campaign->getVenue()->getAddress()->getLongitude();
            $base_url = 'https://www.mapquestapi.com/staticmap/v5/map?center=' . $mapsAdress . '&locations=';
            $base_url = $base_url . $mapsAdress . '|marker-red';
            $this->stations = array_values($this->stations);
            for ($i = 0; $i < count($this->stations); $i++) {
                $color = $this->getColorFromType($this->stations[$i]->getType());
                $base_url = $base_url . '||' . $this->stations[$i]->getLatitude() . ',' . $this->stations[$i]->getLongitude() . '|marker-' . ($i + 1) . '-' . $color;
            }
            $url = $base_url . '&size=210,200&zoom=13&key=' . $key;
            file_put_contents('url.txt', $url);
            $formatted_url = str_replace(' ', '', $url);
            file_put_contents('url2.txt', $formatted_url);
            return $formatted_url;
        } else {
            return "";
        }
    }

    private function getColorFromType($type){
        switch ($type){
            case 'SNCB' : return 'blue';
            case 'STIB' : return 'green';
            default : return 'black';
        }
    }

    /**
     * @return array|ArrayCollection
     */
    public function getSortedStations(){
        $stationsArr = $this->stations->toArray();
        usort($stationsArr, function(PublicTransportStation $s1, PublicTransportStation $s2){
            if ($s1->getDistance() === $s2->getDistance()){
                return 0;
            } else if ($s1->getDistance() < $s2->getDistance()){
                return -1;
            } else {
                return 1;
            }
        });
        return $stationsArr;
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var YBContractArtist
     *
     * @ORM\OneToOne(targetEntity="AppBundle\Entity\YB\YBContractArtist", cascade={"all"}, inversedBy="customTicket")
     * @ORM\JoinColumn(nullable=true)
     */
    private $campaign;

    /**
     * @var boolean
     *
     * @ORM\Column(name="image_added", type="boolean")
     */
    private $imageAdded;

    /**
     * @var boolean
     *
     * @ORM\Column(name="venue_map_added", type="boolean")
     */
    private $venueMapAdded;

    /**
     * @var boolean
     *
     * @ORM\Column(name="commute_text_added", type="boolean")
     */
    private $publicTransportTextInfosAdded;

    /**
     * @var string
     *
     * @ORM\Column(name="commute_text", type="string", length=300, nullable=true)
     */
    private $publicTransportTextInfos;

    /**
     * @var boolean
     *
     * @ORM\Column(name="custom_text_added", type="boolean")
     */
    private $customInfosAdded;

    /**
     * @var string
     *
     * @ORM\Column(name="custom_text", type="string", length=300, nullable=true)
     */
    private $customInfos;

    /**
     * @var boolean
     *
     * @ORM\Column(name="commute_added", type="boolean")
     */
    private $commuteAdded;

    /**
     * @var boolean
     *
     * @ORM\Column(name="sncb_infos_added", type="boolean")
     */
    private $commuteSNCBAdded;

    /**
     * @var boolean
     *
     * @ORM\Column(name="stib_infos_added", type="boolean")
     */
    private $commuteSTIBAdded;

    /**
     * @var boolean
     *
     * @ORM\Column(name="tec_infos_added", type="boolean")
     */
    private $commuteTECAdded;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\YB\PublicTransportStation", cascade={"persist"})
     */
    private $stations;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $mapsImagePath;

    /**
     * @var boolean
     * @ORM\Column(name="previewMode", type="boolean")
     */
    private $previewMode = false;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return YBContractArtist
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param YBContractArtist $campaign
     */
    public function setCampaign($campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * @return boolean
     */
    public function isImageAdded()
    {
        return $this->imageAdded;
    }

    /**
     * @param boolean $imageAdded
     */
    public function setImageAdded($imageAdded)
    {
        $this->imageAdded = $imageAdded;
    }

    /**
     * @return boolean
     */
    public function isVenueMapAdded()
    {
        return $this->venueMapAdded;
    }

    /**
     * @param boolean $venueMapAdded
     */
    public function setVenueMapAdded($venueMapAdded)
    {
        $this->venueMapAdded = $venueMapAdded;
    }

    /**
     * @return boolean
     */
    public function isPublicTransportTextInfosAdded()
    {
        return $this->publicTransportTextInfosAdded;
    }

    /**
     * @param boolean $publicTransportTextInfosAdded
     */
    public function setPublicTransportTextInfosAdded($publicTransportTextInfosAdded)
    {
        $this->publicTransportTextInfosAdded = $publicTransportTextInfosAdded;
    }

    /**
     * @return string
     */
    public function getPublicTransportTextInfos()
    {
        return $this->publicTransportTextInfos;
    }

    /**
     * @param string $publicTransportTextInfos
     */
    public function setPublicTransportTextInfos($publicTransportTextInfos)
    {
        $this->publicTransportTextInfos = $publicTransportTextInfos;
    }

    /**
     * @return boolean
     */
    public function isCustomInfosAdded()
    {
        return $this->customInfosAdded;
    }

    /**
     * @param boolean $customInfosAdded
     */
    public function setCustomInfosAdded($customInfosAdded)
    {
        $this->customInfosAdded = $customInfosAdded;
    }

    /**
     * @return string
     */
    public function getCustomInfos()
    {
        return $this->customInfos;
    }

    /**
     * @param string $customInfos
     */
    public function setCustomInfos($customInfos)
    {
        $this->customInfos = $customInfos;
    }

    /**
     * @return boolean
     */
    public function isCommuteAdded()
    {
        return $this->commuteAdded;
    }

    /**
     * @param boolean $commuteAdded
     */
    public function setCommuteAdded($commuteAdded)
    {
        $this->commuteAdded = $commuteAdded;
    }

    /**
     * @return boolean
     */
    public function isCommuteSNCBAdded()
    {
        return $this->commuteSNCBAdded;
    }

    /**
     * @param boolean $commuteSNCBAdded
     */
    public function setCommuteSNCBAdded($commuteSNCBAdded)
    {
        $this->commuteSNCBAdded = $commuteSNCBAdded;
    }

    /**
     * @return boolean
     */
    public function isCommuteSTIBAdded()
    {
        return $this->commuteSTIBAdded;
    }

    /**
     * @param boolean $commuteSTIBAdded
     */
    public function setCommuteSTIBAdded($commuteSTIBAdded)
    {
        $this->commuteSTIBAdded = $commuteSTIBAdded;
    }

    /**
     * @return boolean
     */
    public function isCommuteTECAdded()
    {
        return $this->commuteTECAdded;
    }

    /**
     * @param boolean $commuteTECAdded
     */
    public function setCommuteTECAdded($commuteTECAdded)
    {
        $this->commuteTECAdded = $commuteTECAdded;
    }

    /**
     * @return mixed
     */
    public function getStations()
    {
        return $this->stations;
    }

    /**
     * @param mixed $stations
     */
    public function setStations($stations)
    {
        $this->stations = $stations;
    }

    /**
     * @return string
     */
    public function getMapsImagePath()
    {
        return $this->mapsImagePath;
    }

    /**
     * @param string $mapsImagePath
     */
    public function setMapsImagePath($mapsImagePath)
    {
        $this->mapsImagePath = $mapsImagePath;
    }

    public function isPreviewMode(){
        return $this->previewMode;
    }

    public function setPreviewMode($previewMode){
        $this->previewMode = $previewMode;
    }

}