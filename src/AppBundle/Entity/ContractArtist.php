<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * ContractArtist
 *
 * @ORM\Table(name="contract_artist")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContractArtistRepository")
 */
class ContractArtist extends BaseContractArtist
{
    const NB_DAYS_OF_CLOSING = 7;

    const STATE_REFUNDED = 'state.refunded';
    const STATE_FAILED = 'state.failed';
    const STATE_SUCCESS_SOLDOUT = 'state.success.soldout';
    const STATE_SUCCESS_SOLDOUT_PENDING = 'state.success.soldout.pending';
    const STATE_SUCCESS_CLOSED = 'state.success.closed';
    const STATE_SUCCESS_ONGOING = 'state.success.ongoing';
    const STATE_SUCCESS_PENDING = 'state.success.pending';
    const STATE_SUCCESS_PASSED = 'state.success.passed';
    const STATE_ONGOING = 'state.ongoing';
    const STATE_PENDING = 'state.pending';

    public function isUncrowdable() {
        return in_array($this->getState(), $this->getUncrowdableStates());
    }

    public function isCrowdable() {
        return !$this->isUncrowdable();
    }

    public function isPending() {
        return in_array($this->getState(), $this->getPendingStates());
    }

    public static function getUncrowdableStates() {
        return [
            self::STATE_REFUNDED,
            self::STATE_FAILED,
            self::STATE_SUCCESS_SOLDOUT,
            self::STATE_SUCCESS_SOLDOUT_PENDING,
            self::STATE_SUCCESS_CLOSED,
            self::STATE_SUCCESS_PASSED,
            self::STATE_PENDING,
        ];
    }

    public static function getPendingStates() {
        return [
            self::STATE_PENDING,
            self::STATE_SUCCESS_PENDING,
            self::STATE_SUCCESS_SOLDOUT_PENDING,
        ];
    }


    public function getTotalNbAvailable() {
        return $this->step->getMaxTickets() - $this->tickets_sold;
    }

    public function getState() {

        $today = new \DateTime();
        $today2 = new \DateTime();

        // Failure & refunded
        if($this->refunded)
            return self::STATE_REFUNDED;

        // Marked as failure
        if($this->failed)
            return self::STATE_FAILED;

        // Marked as success
        if($this->successful)
        {
            // Concert in the future
            if($this->getDateConcert() >= $today) {
                // Sold out
                if ($this->tickets_sold >= $this->step->getMaxTickets())
                    return self::STATE_SUCCESS_SOLDOUT;
                // No more selling
                if ($today2->modify('+' . self::NB_DAYS_OF_CLOSING . ' days') > $this->getDateConcert())
                    return self::STATE_SUCCESS_CLOSED;
                // Successful, in the future, not sold out, not closed => ongoing
                else
                    return self::STATE_SUCCESS_ONGOING;
            }
            // Concert in the passed & successful
            else
                return self::STATE_SUCCESS_PASSED;
        }

        // Crowdfunding is not over yet
        if($this->dateEnd > $today) {
            // But already sold out
            if ($this->tickets_sold >= $this->step->getMaxTickets())
                return self::STATE_SUCCESS_SOLDOUT_PENDING;

            // Or already successful but not sold out and with a need of validation
            if ($this->tickets_sold >= $this->step->getMinTickets())
                return self::STATE_SUCCESS_PENDING;

            // Or simply ongoing
            return self::STATE_ONGOING;
        }

        // Crowdfunding is over but not marked as successful nor failed -> need for admin validation
        return self::STATE_PENDING;
    }

    public function __construct()
    {
        parent::__construct();
        $this->coartists_list = new ArrayCollection();
        $this->tickets_sold = 0;
        $this->tickets_sent = false;
    }

    // Also add as main artist for the concert
    // TODO simplify this process, for now this is needed to make the queries for finding available artists work properly
    // because of inheritance of BaseContractArtist
    public function setArtist(\AppBundle\Entity\Artist $artist)
    {
        parent::setArtist($artist);
        $this->main_artist = $artist;
        return $this;
    }

    public function getCoartists() {
        return array_map(function($elem) {
            return $elem->getArtist();
        }, $this->coartists_list->toArray());
    }

    public function addTicketsSold($quantity) {
        $this->tickets_sold += $quantity;
    }

    public function removeTicketsSold($quantity) {
        $this->tickets_sold -= $quantity;
    }

    public function getDateConcert() {
        if(isset($this->reality) && $this->reality->getDate() != null) {
            return $this->reality->getDate();
        }
        else {
            return $this->preferences->getDate();
        }
    }

    public function getHallConcert() {
        if(isset($this->reality) && $this->reality->getHall() != null) {
            return $this->reality->getHall();
        }
        return null;
    }

    /**
     * @Assert\Callback(groups={"flow_createcontract_step1"})
     */
    public function validateStep(ExecutionContextInterface $context, $payload)
    {
        $available_dates = $this->step->getAvailableDates($this->province);
        if(count($available_dates) == 0) {
            $available_dates = $this->step->getAvailableDates();
            if(count($available_dates) == 0) {
                $context->buildViolation("Il n'est pas possible de trouver une date pour cette catégorie de salle, merci d'essayer plus tard ou de changer de catégorie")
                    ->atPath('step')
                    ->addViolation();
            }
        }
    }

    /**
     * @Assert\Callback(groups={"flow_createcontract_step1"})
     */
    public function validateProvince(ExecutionContextInterface $context, $payload)
    {
        $available_dates = $this->step->getAvailableDates($this->province);
        if(count($available_dates) == 0) {
            $context->buildViolation('Aucune date trouvée dans cette province pour cette catégorie de salle')
                ->atPath('province')
                ->addViolation();
        }
    }

    /**
     * @Assert\Callback(groups={"flow_createcontract_step2"})
     */
    public function validatePreferences(ExecutionContextInterface $context, $payload)
    {
        $step = $this->step;
        $province = $this->province;
        $date = $this->preferences->getDate()->format(Hall::DATE_FORMAT);

        $availableDates = $step->getAvailableDates($province);

        if(!in_array($date, $availableDates)) {
            $context->buildViolation("Date non disponible")
                ->atPath('preferences')
                ->addViolation();
        }
    }

    /**
     * @ORM\OneToMany(targetEntity="ContractArtist_Artist", mappedBy="contract", cascade={"all"}, orphanRemoval=true)
     */
    private $coartists_list;

    /**
     * @var Province
     *
     * @ORM\ManyToOne(targetEntity="Province")
     * @ORM\JoinColumn(nullable=true)
     */
    private $province;

    /**
     * @var Step
     *
     * @ORM\ManyToOne(targetEntity="Step")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $step;

    /**
     * @ORM\Column(name="tickets_sold", type="smallint")
     */
    private $tickets_sold;

    /**
     * @ORM\Column(name="tickets_sent", type="boolean")
     */
    private $tickets_sent;

    /**
     * @ORM\ManyToOne(targetEntity="Artist", inversedBy="contracts")
     */
    private $main_artist;

    /**
     * Set coartistsList
     *
     * @param ArrayCollection $coartistsList
     *
     * @return ContractArtist
     */
    public function setCoartistsList($list)
    {
        if (count($list) > 0) {
            foreach ($list as $elem) {
                $this->addCoartistsList($elem);
            }
        }

        return $this;
    }

    /**
     * Add coartistsList
     *
     * @param \AppBundle\Entity\ContractArtist_Artist $coartistsList
     *
     * @return ContractArtist
     */
    public function addCoartistsList(\AppBundle\Entity\ContractArtist_Artist $coartistsList)
    {
        $this->coartists_list[] = $coartistsList;
        $coartistsList->setContract($this);

        return $this;
    }

    /**
     * Remove coartistsList
     *
     * @param \AppBundle\Entity\ContractArtist_Artist $coartistsList
     */
    public function removeCoartistsList(\AppBundle\Entity\ContractArtist_Artist $coartistsList)
    {
        $this->coartists_list->removeElement($coartistsList);
        $coartistsList->setContract(null)->setArtist(null);
    }

    /**
     * Get coartistsList
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCoartistsList()
    {
        return $this->coartists_list;
    }

    /**
     * Set province
     *
     * @param \AppBundle\Entity\Province $province
     *
     * @return ContractArtist
     */
    public function setProvince(\AppBundle\Entity\Province $province = null)
    {
        $this->province = $province;

        return $this;
    }

    /**
     * Get province
     *
     * @return \AppBundle\Entity\Province
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * Set step
     *
     * @param \AppBundle\Entity\Step $step
     *
     * @return ContractArtist
     */
    public function setStep(\AppBundle\Entity\Step $step)
    {
        $this->step = $step;

        return $this;
    }

    /**
     * Get step
     *
     * @return \AppBundle\Entity\Step
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * Set ticketsSold
     *
     * @param integer $ticketsSold
     *
     * @return ContractArtist
     */
    public function setTicketsSold($ticketsSold)
    {
        $this->tickets_sold = $ticketsSold;

        return $this;
    }

    /**
     * Get ticketsSold
     *
     * @return integer
     */
    public function getTicketsSold()
    {
        return $this->tickets_sold;
    }

    /**
     * Set ticketsSent
     *
     * @param boolean $ticketsSent
     *
     * @return ContractArtist
     */
    public function setTicketsSent($ticketsSent)
    {
        $this->tickets_sent = $ticketsSent;

        return $this;
    }

    /**
     * Get ticketsSent
     *
     * @return boolean
     */
    public function getTicketsSent()
    {
        return $this->tickets_sent;
    }

    /**
     * Set mainArtist
     *
     * @param \AppBundle\Entity\Artist $mainArtist
     *
     * @return ContractArtist
     */
    public function setMainArtist(\AppBundle\Entity\Artist $mainArtist = null)
    {
        $this->main_artist = $mainArtist;

        return $this;
    }

    /**
     * Get mainArtist
     *
     * @return \AppBundle\Entity\Artist
     */
    public function getMainArtist()
    {
        return $this->main_artist;
    }
}
