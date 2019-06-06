<?php

namespace AppBundle\Entity;

use AppBundle\Entity\YB\Block;
use AppBundle\Entity\YB\Booking;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Purchase
 *
 * @ORM\Table(name="purchase")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PurchaseRepository")
 */
class Purchase
{
    const MAX_QTY = 100000;

    public function __construct()
    {
        $this->quantity = 0;
        $this->nb_free_counterparts = 0;
        $this->nb_reduced_counterparts = 0;
        $this->reducedPrice = 0;
        $this->purchase_promotions = new ArrayCollection();
        $this->ticket_rewards = new ArrayCollection();
        $this->bookings = new ArrayCollection();
    }

    public function __toString()
    {
        $str = '';

        $str .= $this->counterpart . ' (x' . $this->quantity . ')';

        if(!empty($this->getArtists() && count($this->getArtists()) > 0)) {
            $str .= ' pour ';
            $i = 0;
            foreach($this->getArtists() as $artist) {
                if($i > 0) {
                    $str .= ', ';
                }
                $str .= $artist->getArtistname();
                $i++;
            }
        }

        $str .= $this->getActuallyAppliedPromotionsString();

        return $str;
    }

    public function getDisplayWithAmount() {
        return $this->__toString() . ' - ' . $this->getAmount() . ' €';
    }


    public function getThresholdIncrease() {
        return $this->getQuantity() * $this->getCounterpart()->getThresholdIncrease();
    }

    public function getOrganicThresholdIncrease() {
        return $this->getQuantityOrganic() * $this->getCounterpart()->getThresholdIncrease();
    }

    public function getPromotionalThresholdIncrease() {
        return $this->getQuantityPromotional() * $this->getCounterpart()->getThresholdIncrease();

    }

    public function getPromotions()
    {
        return array_map(function (Purchase_Promotion $p_promotion) {
            return $p_promotion->getPromotion();
        }, $this->purchase_promotions->toArray());
    }

    public function getActuallyAppliedPromotions()
    {
        return array_map(function (Purchase_Promotion $p_promotion) {
            return $p_promotion->getPromotion();
        }, array_filter($this->purchase_promotions->toArray(), function (Purchase_Promotion $p_promotion) {
            return $p_promotion->getNbFreeCounterParts() > 0;
        }));
    }

    public function getActuallyAppliedPromotionsString()
    {
        $string = '';
        foreach ($this->getActuallyAppliedPromotions() as $promotion) {
            $string .= ' - ' . $promotion;
        }

        return $string;
    }

    public function addQuantity($q)
    {
        $this->quantity = $this->quantity + $q;
        if ($this->quantity > self::MAX_QTY) {
            $this->quantity = self::MAX_QTY;
        }
    }

    public function getAmount()
    {
        return $this->getQuantityOrganic() * $this->getUnitaryPrice() ;
    }

    public function getUnitaryPrice() {
        if($this->counterpart->getFreePrice()) {
            return $this->free_price_value;
        }
        else {
            return $this->counterpart->getPrice();
        }
    }

    /**
     * @return ContractArtist
     */
    public function getContractArtist()
    {
        return $this->contractFan->getContractArtist();
    }

    public function calculatePromotions()
    {
        $organicsTotal = $this->getQuantityOrganic();

        $organicsLeft = $organicsTotal;

        foreach($this->getActuallyAppliedPromotions() as $promo) {
            $organicsLeft -= $promo->getNbOrganicNeeded();
        }

        foreach ($this->getContractArtist()->getPromotionsDecr() as $promotion) {
            /** @var Promotion $promotion */
            if ($this->contractFan->isEligibleForPromotion($promotion) && !in_array($promotion, $this->getPromotions())) {
                // Tickets toppings
                $toppings = [];
                while($organicsLeft >= $promotion->getNbNeededTopping()) {
                    $organicsLeft -= $promotion->getNbNeededTopping();
                    $topping = $promotion->getTopping();
                    if($topping != null) {
                        $toppings[] = $topping;
                    }
                }
                // End tickets toppings

                // Additional tickets
                $new_promotional_counterparts = $promotion->getNbPromotional() * (floor($organicsLeft / $promotion->getNbOrganicNeeded()));
                $this->nb_free_counterparts += $new_promotional_counterparts;
                $this->addQuantity($new_promotional_counterparts);
                $organicsLeft %= $promotion->getNbOrganicNeeded();
                // End additional tickets

                $pp = new Purchase_Promotion($this, $promotion, $new_promotional_counterparts, $toppings);
                $this->addPurchasePromotion($pp);
            }
        }
    }

    public function getQuantityOrganic()
    {
        return $this->quantity - $this->getQuantityPromotional();
    }

    public function getQuantityPromotional()
    {
        return $this->getNbFreeCounterparts();
    }

    public function getNbFreeCounterparts()
    {
        return $this->nb_free_counterparts;
    }

    public function getReducedAmount()
    {
        if ($this->nb_reduced_counterparts > $this->getQuantityOrganic()) {
            return ($this->reducedPrice * $this->nb_reduced_counterparts) + (($this->nb_reduced_counterparts - $this->getQuantityOrganic()) * $this->counterpart->getPrice());
        } else {
            return ($this->reducedPrice * $this->nb_reduced_counterparts) + (($this->getQuantityOrganic() - $this->nb_reduced_counterparts) * $this->counterpart->getPrice());
        }
    }

    public function getToppings() {
        $toppings = [];
        foreach($this->purchase_promotions as $pp) {
            foreach($pp->getToppings() as $topping) {
                $toppings[] = $topping;
            }
        }
        return $toppings;
    }

    public function getInvoice(){
        return $this->getContractFan()->getInvoice();
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
     * @var int
     *
     * @ORM\Column(name="quantity", type="smallint")
     */
    private $quantity;

    /**
     * @var ContractFan
     *
     * @ORM\ManyToOne(targetEntity="ContractFan", inversedBy="purchases")
     * @ORM\JoinColumn(nullable=false)
     */
    private $contractFan;

    /**
     * @var CounterPart
     *
     * @ORM\ManyToOne(targetEntity="CounterPart")
     * @ORM\JoinColumn(nullable=false)
     */
    private $counterpart;

    /**
     * @ORM\Column(name="nb_free_counterparts", type="smallint")
     */
    private $nb_free_counterparts;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Purchase_Promotion", mappedBy="purchase", cascade={"all"})
     */
    private $purchase_promotions;

    /**
     * @ORM\Column(name="reducedPrice", type="float", nullable=true)
     */
    private $reducedPrice;

    /**
     * @ORM\Column(name="nb_reduced_counterparts", type="smallint")
     */
    private $nb_reduced_counterparts;

    /**
     * @ORM\OneToMany(targetEntity="RewardTicketConsumption", mappedBy="purchase", cascade={"all"})
     */
    private $ticket_rewards;

    /**
     * @ORM\Column(name="free_price_value", type="float", nullable=true)
     */
    private $free_price_value;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Artist")
     */
    private $artists;

    /**
<<<<<<< HEAD
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\YB\Booking", mappedBy="purchase", cascade={"persist", "remove"}, orphanRemoval=TRUE)
     */
    private $bookings;

    /**
=======
>>>>>>> master
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     *
     * @return Purchase
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return int
     */
    public function getQuantity()
    {
        if ($this->quantity >= 3 && $this->nb_free_counterparts == 0) {
            $this->calculatePromotions();
        }
        return $this->quantity;
    }

    /**
     * Set contractFan
     *
     * @param \AppBundle\Entity\ContractFan $contractFan
     *
     * @return Purchase
     */
    public function setContractFan(\AppBundle\Entity\ContractFan $contractFan)
    {
        $this->contractFan = $contractFan;

        return $this;
    }

    /**
     * Get contractFan
     *
     * @return \AppBundle\Entity\ContractFan
     */
    public function getContractFan()
    {
        return $this->contractFan;
    }

    /**
     * Set counterpart
     *
     * @param \AppBundle\Entity\CounterPart $counterpart
     *
     * @return Purchase
     */
    public function setCounterpart(\AppBundle\Entity\CounterPart $counterpart)
    {
        $this->counterpart = $counterpart;
        $this->free_price_value = $counterpart->getMinimumPrice();

        return $this;
    }

    /**
     * Get counterpart
     *
     * @return \AppBundle\Entity\CounterPart
     */
    public function getCounterpart()
    {
        return $this->counterpart;
    }

    /**
     * Set nbFreeCounterparts
     *
     * @param integer $nbFreeCounterparts
     *
     * @return Purchase
     */
    public function setNbFreeCounterparts($nbFreeCounterparts)
    {
        $this->nb_free_counterparts = $nbFreeCounterparts;

        return $this;
    }

    /**
     * Add purchasePromotion
     *
     * @param \AppBundle\Entity\Purchase_Promotion $purchasePromotion
     *
     * @return Purchase
     */
    public function addPurchasePromotion(\AppBundle\Entity\Purchase_Promotion $purchasePromotion)
    {
        $this->purchase_promotions[] = $purchasePromotion;

        return $this;
    }

    /**
     * Remove purchasePromotion
     *
     * @param \AppBundle\Entity\Purchase_Promotion $purchasePromotion
     */
    public function removePurchasePromotion(\AppBundle\Entity\Purchase_Promotion $purchasePromotion)
    {
        $this->purchase_promotions->removeElement($purchasePromotion);
    }

    /**
     * Get purchasePromotions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPurchasePromotions()
    {
        return $this->purchase_promotions;
    }

    /**
     * Set reducedPrice
     *
     * @param float $reducedPrice
     *
     * @return Purchase
     */
    public function setReducedPrice($reducedPrice)
    {
        $this->reducedPrice = $reducedPrice;

        return $this;
    }

    /**
     * Get reducedPrice
     *
     * @return float
     */
    public function getReducedPrice()
    {
        return $this->reducedPrice;
    }

    /**
     * Set nbReducedCounterparts
     *
     * @param integer $nbReducedCounterparts
     *
     * @return Purchase
     */
    public function setNbReducedCounterparts($nbReducedCounterparts)
    {
        $this->nb_reduced_counterparts = $nbReducedCounterparts;

        return $this;
    }

    /**
     * Get nbReducedCounterparts
     *
     * @return integer
     */
    public function getNbReducedCounterparts()
    {
        return $this->nb_reduced_counterparts;
    }

    /**
     * Add ticketReward
     *
     * @param \AppBundle\Entity\RewardTicketConsumption $ticketReward
     *
     * @return Purchase
     */
    public function addTicketReward(\AppBundle\Entity\RewardTicketConsumption $ticketReward)
    {
        if (!$this->ticket_rewards->contains($ticketReward)) {
            $this->ticket_rewards[] = $ticketReward;
            $ticketReward->setPurchase($this);
        }
        return $this;
    }

    /**
     * Remove ticketReward
     *
     * @param \AppBundle\Entity\RewardTicketConsumption $ticketReward
     */
    public function removeTicketReward(\AppBundle\Entity\RewardTicketConsumption $ticketReward)
    {
        if ($this->ticket_rewards->contains($ticketReward)) {
            $this->ticket_rewards->removeElement($ticketReward);
            $ticketReward->setPurchase(null);
        }
    }

    /**
     * Get ticketRewards
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTicketRewards()
    {
        return $this->ticket_rewards;
    }

    /**
     * @return mixed
     */
    public function getFreePriceValue()
    {
        return $this->free_price_value;
    }

    /**
     * @param mixed $free_price_value
     */
    public function setFreePriceValue($free_price_value)
    {
        $this->free_price_value = $free_price_value;
    }

    /**
     * Add artist
     *
     * @param \AppBundle\Entity\Artist $artist
     *
     * @return Purchase
     */
    public function addArtist(\AppBundle\Entity\Artist $artist)
    {
        $this->artists[] = $artist;

        return $this;
    }

    /**
     * Remove artist
     *
     * @param \AppBundle\Entity\Artist $artist
     */
    public function removeArtist(\AppBundle\Entity\Artist $artist)
    {
        $this->artists->removeElement($artist);
    }

    /**
     * Get artists
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getArtists()
    {
        return $this->artists;
    }

    /**
     * @return mixed
     */
    public function getBookings()
    {
        return $this->bookings;
    }

    /**
     * @param mixed $bookings
     */
    public function setBookings($bookings)
    {
        $this->bookings = $bookings;
    }
}
