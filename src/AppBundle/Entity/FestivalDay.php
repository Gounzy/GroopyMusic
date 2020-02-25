<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\FestivalDayRepository")
 * @ORM\Table(name="festivalday")
 **/
class FestivalDay
{
    const TOTAL_COMBOS_AVAILABLE = 500;
    const TOTAL_SOLOS_AVAILABLE = 2000;

    public function __construct()
    {
        $this->performances = new ArrayCollection();
        $this->festivals = new ArrayCollection();
        $this->lineups = new ArrayCollection();
        $this->tickets_sold = 0;
    }

    public function __toString()
    {
        if($this->getFestival() == null) {
            return 'Nouveau jour de festival';
        }
        return $this->getFestival()->__toString() . ' (jour : ' . $this->date->format('d/m/Y') . ')';
    }

    private $artist_perfs = null;
    public function getArtistPerformances() {
        if($this->artist_perfs != null) {
            return $this->artist_perfs;
        }

        if($this->lineups->count() == 0) {
            $this->artist_perfs = $this->getPerformances()->toArray();
        }
        else {
            $perfs = [];
            foreach($this->lineups as $lineup) {
                /** @var LineUp $lineup */
                $ps = $lineup->getPerformances()->toArray();
                $perfs = array_merge($perfs, $ps);
            }
            $this->artist_perfs = $perfs;
        }
        return $this->artist_perfs;
    }

    public function getFestival() {
        if(!empty($this->festivals))
            return $this->festivals->first();

        return null;
    }

    public function getMaxTickets() {
        $normal_soldout = array_sum(array_map(function(CounterPart $counterPart) {
            return $counterPart->getMaximumAmount();
        }, $this->getCounterParts()->toArray()));

        $global_soldout = $this->global_soldout == null ? $normal_soldout : $this->global_soldout;
        return min($global_soldout, $normal_soldout);
    }

    public function getTotalBookedTickets() {
        return $this->tickets_sold;
    }

    public function getTicketsSoldMajored() {
        return min($this->getTicketsSold(), $this->getMaxTickets());
    }

    public function getTotalNbAvailable() {
        return $this->getMaxTickets() - $this->getTotalBookedTickets();
    }

    public function updateTicketsSold(Purchase $purchase) {
        $this->addTicketsSold($purchase->getThresholdIncrease());
    }

    public function updateHalfTicketsSold(Purchase $purchase) {
        $this->addTicketsSold($purchase->getThresholdIncrease() / 2);
    }

    public function addTicketsSold($quantity) {
        $this->tickets_sold += $quantity;
    }

    public function removeTicketsSold($quantity) {
        $this->tickets_sold -= $quantity;
    }

    public static function sortPerformancesAsc($performances) {
        if( count( $performances) < 2 ) {
            return $performances;
        }
        $left = $right = array( );
        reset( $performances);

        $pivot_key  = key( $performances );
        $pivot  = array_shift( $performances );

        foreach( $performances as $k => $v ) {
            if($pivot->getTime() == null || $v->getTime() < $pivot->getTime() )
                $left[$k] = $v;
            else
                $right[$k] = $v;
        }
        return array_merge(self::sortPerformancesAsc($left), array($pivot_key => $pivot), self::sortPerformancesAsc($right));
    }


    public function getPerformancesAsc() {
        $performances = $this->getArtistPerformances();
        return self::sortPerformancesAsc($performances);
    }

    public function hasLineUps() {
        return !$this->lineups->isEmpty();
    }

    public function hasSuccessfulLineUps() {
        foreach($this->lineups as $lu) {
            /** @var LineUp $lu */
            if($lu->isSuccessful()) {
                return true;
            }
        }
        return false;
    }

    public function getCombosAvailable() {
        $combosSold = 0;
        foreach($this->getCounterparts() as $cp) {
            if($cp->isCombo()) {
                $combosSold += $cp->getNbSold() * $cp->getThresholdIncrease() / 2;
            }
        }
        return floor(self::TOTAL_COMBOS_AVAILABLE - $combosSold);
    }

    public function getSolosAvailable() {
        $solosSold = 0;
        foreach($this->getCounterparts() as $cp) {
            if(!$cp->isCombo()) {
                $solosSold += $cp->getNbSold() * $cp->getThresholdIncrease();
            }
        }
        return floor(self::TOTAL_SOLOS_AVAILABLE - $solosSold);
    }

    public function atLeastOneLineUpConfirmed() {
        return count($this->getConfirmedLineUps()) > 0;
    }

    public function allLineUpsConfirmed() {
       return count($this->getConfirmedLineUps()) == count($this->lineups);
    }

    public function allLineUpsCancelled() {
        return !$this->atLeastOneLineUpConfirmed();
    }

    protected $confirmedLineUps = null;
    public function getConfirmedLineUps() {
        if($this->confirmedLineUps == null) {
            $this->confirmedLineUps = array_filter($this->lineups->toArray(), function (LineUp $lineup) {
                return $lineup->isSuccessful();
            });
        }
        return $this->confirmedLineUps;
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime $date
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var Hall
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Hall")
     * @ORM\JoinColumn(nullable=true)
     */
    private $hall;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\ArtistPerformance", mappedBy="festivalday")
     */
    private $performances;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="LineUp", mappedBy="festivalDay")
     */
    private $lineups;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\CounterPart", mappedBy="festivaldays")
     */
    private $counterparts;

    /**
     * @ORM\ManyToMany(targetEntity="ContractArtist", mappedBy="festivaldays", cascade={"persist"})
     */
    private $festivals;

    /**
     * @ORM\Column(name="tickets_sold", type="float")
     */
    private $tickets_sold;

    /**
     * @ORM\Column(name="global_soldout", type="smallint")
     */
    private $global_soldout;

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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return FestivalDay
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set hall
     *
     * @param \AppBundle\Entity\Hall $hall
     *
     * @return FestivalDay
     */
    public function setHall(\AppBundle\Entity\Hall $hall = null)
    {
        $this->hall = $hall;

        return $this;
    }

    /**
     * Get hall
     *
     * @return \AppBundle\Entity\Hall
     */
    public function getHall()
    {
        return $this->hall;
    }

    /**
     * Add performance
     *
     * @param \AppBundle\Entity\ArtistPerformance $performance
     *
     * @return FestivalDay
     */
    public function addPerformance(\AppBundle\Entity\ArtistPerformance $performance)
    {
        $this->performances[] = $performance;

        return $this;
    }

    /**
     * Remove performance
     *
     * @param \AppBundle\Entity\ArtistPerformance $performance
     */
    public function removePerformance(\AppBundle\Entity\ArtistPerformance $performance)
    {
        $this->performances->removeElement($performance);
    }

    /**
     * Get performances
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPerformances()
    {
        return $this->performances;
    }

    /**
     * Add counterpart
     *
     * @param \AppBundle\Entity\CounterPart $counterpart
     *
     * @return FestivalDay
     */
    public function addCounterpart(\AppBundle\Entity\CounterPart $counterpart)
    {
        $this->counterparts[] = $counterpart;

        return $this;
    }

    /**
     * Remove counterpart
     *
     * @param \AppBundle\Entity\CounterPart $counterpart
     */
    public function removeCounterpart(\AppBundle\Entity\CounterPart $counterpart)
    {
        $this->counterparts->removeElement($counterpart);
    }

    /**
     * Get counterparts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCounterparts()
    {
        return $this->counterparts;
    }

    /**
     * Add festival
     *
     * @param \AppBundle\Entity\ContractArtist $festival
     *
     * @return FestivalDay
     */
    public function addFestival(\AppBundle\Entity\ContractArtist $festival)
    {
        $this->festivals[] = $festival;
        $festival->addFestivalday($this);

        return $this;
    }

    /**
     * Remove festival
     *
     * @param \AppBundle\Entity\ContractArtist $festival
     */
    public function removeFestival(\AppBundle\Entity\ContractArtist $festival)
    {
        $this->festivals->removeElement($festival);
        $festival->removeFestivalday($this);
    }

    /**
     * Get festivals
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFestivals()
    {
        return $this->festivals;
    }

    /**
     * Set ticketsSold
     *
     * @param float $ticketsSold
     *
     * @return FestivalDay
     */
    public function setTicketsSold($ticketsSold)
    {
        $this->tickets_sold = $ticketsSold;

        return $this;
    }

    /**
     * Get ticketsSold
     *
     * @return float
     */
    public function getTicketsSold()
    {
        return $this->tickets_sold;
    }

    /**
     * Set globalSoldout
     *
     * @param integer $globalSoldout
     *
     * @return FestivalDay
     */
    public function setGlobalSoldout($globalSoldout)
    {
        $this->global_soldout = $globalSoldout;

        return $this;
    }

    /**
     * Get globalSoldout
     *
     * @return integer
     */
    public function getGlobalSoldout()
    {
        return $this->global_soldout;
    }

    /**
     * Add lineup
     *
     * @param \AppBundle\Entity\LineUp $lineup
     *
     * @return FestivalDay
     */
    public function addLineup(\AppBundle\Entity\LineUp $lineup)
    {
        $this->lineups[] = $lineup;

        return $this;
    }

    /**
     * Remove lineup
     *
     * @param \AppBundle\Entity\LineUp $lineup
     */
    public function removeLineup(\AppBundle\Entity\LineUp $lineup)
    {
        $this->lineups->removeElement($lineup);
    }

    /**
     * Get lineups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLineups()
    {
        return $this->lineups;
    }
}
