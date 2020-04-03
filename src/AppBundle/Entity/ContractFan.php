<?php

namespace AppBundle\Entity;

use AppBundle\Entity\YB\YBContractArtist;
use AppBundle\Entity\YB\YBInvoice;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ContractFan
 *
 * @ORM\Table(name="contract_fan")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContractFanRepository")
 */
class ContractFan
{
    const ORDERS_DIRECTORY = 'pdf/orders/';
    const TICKETS_DIRECTORY = 'pdf/tickets/';
    const YB_TICKETS_DIRECTORY = 'yb/tickets/';
    const VOTES_TO_REFUND = 2;

    public function __toString()
    {
        $str = '';

        for ($i = 0; $i < $this->purchases->count(); $i++) {
            if ($i > 0) {
                $str .= ', ';
            }
            $str .= $this->purchases->get($i);
        }

        return $str;
    }

    public function __construct(BaseContractArtist $ca)
    {
        $this->contractArtist = $ca;
        $this->purchases = new ArrayCollection();

        foreach ($ca->getCounterParts() as $cp) {
            $purchase = new Purchase();
            $purchase->setCounterpart($cp);
            $this->addPurchase($purchase);
        }

        $this->amount = 0;
        $this->counterparts_sent = false;
        $this->date = new \DateTime();
        $this->refunded = false;
        $this->tickets = new ArrayCollection();
        $this->user_rewards = new ArrayCollection();
        $this->ticket_rewards = new ArrayCollection();
    }

    public function getState() {
        if($this->refunded) {
            return 'Remboursé';
        }

        elseif($this->isPaid()) {
            return 'Payé';
        }

        else {
            return '';
        }
    }

    public function initAmount() {
        $this->amount = array_sum(array_map(function(Purchase $purchase) {
            return $purchase->getAmount();
        }, $this->purchases->toArray()));
    }

    public function isPaid()
    {
        return $this->getPaid();
    }

    public function isRefunded()
    {
        return $this->getRefunded();
    }

    public function isRefundReady() {
        return count($this->asking_refund) >= self::VOTES_TO_REFUND;
    }

    public function isAskedRefundBy(User $user) {
        return $this->asking_refund->contains($user);
    }

    public function isAskedRefundByOne() {
        return count($this->asking_refund) >= 1;
    }

    public function isOneStepFromBeingRefunded() {
        return self::VOTES_TO_REFUND - count($this->asking_refund) == 1;
    }

    public function getThresholdIncrease() {
        return array_sum(array_map(function(Purchase $purchase) {
            return $purchase->getThresholdIncrease();
        }, $this->purchases->toArray()));
    }

    public function generateBarCode()
    {
        if (empty($this->barcode_text))
            $this->barcode_text = 'cf' . $this->id . uniqid();
    }

    public function generateTickets()
    {
        $this->generateBarCode();
        if (empty($this->tickets)) {
            foreach ($this->purchases as $purchase) {
                /** @var Purchase $purchase */
                for ($j = 1; $j < $purchase->getQuantity(); $j++) {
                    $counterPart = $purchase->getCounterpart();
                    $this->addTicket(new Ticket($this, $counterPart, $j));
                }
            }
        }
    }

    public function getDisplayName() {
        if($this->getPhysicalPerson() == null) {
            return 'anonyme' ;
        }
        return $this->getPhysicalPerson()->getDisplayName();
    }

    public function getEmail() {
        if($this->getPhysicalPerson() == null) {
            return 'anonyme' ;
        }
        return $this->getPhysicalPerson()->getEmail();
    }

    /** @return PhysicalPersonInterface */
    public function getPhysicalPerson() {
        if($this->getContractArtist() instanceof YBContractArtist) {
            if ($this->getCart() === null){
                return null;
            }
            return $this->getCart()->getYbOrder();
        }
        else {
            return $this->getUser();
        }
    }

    public function getOrderFileName()
    {
        return $this->getBarcodeText() . '.pdf';
    }

    public function getPdfPath()
    {
        return self::ORDERS_DIRECTORY . $this->getOrderFileName();
    }

    public function getTicketsPath()
    {
        if($this->contractArtist instanceof YBContractArtist)
            return self::YB_TICKETS_DIRECTORY . $this->getTicketsFileName();
        else
            return self::TICKETS_DIRECTORY . $this->getTicketsFileName();
    }

    public function getTicketsFileName()
    {
        return $this->getBarcodeText() . '-tickets.pdf';
    }

    public function getAmountWithoutReduction()
    {
        return array_sum(array_map(function (Purchase $purchase) {
            return $purchase->getAmount();
        }, $this->purchases->toArray()));
    }

    public function getPaid()
    {
        return $this->cart->getPaid();
    }

    public function getUserExport() {
        if($this->getUser() != null) {
            return $this->getUser()->getDisplayName() . ' (#' . $this->getUser()->getId() . ')';
        }
        return '';
    }

    public function getCounterPartsQuantity()
    {
        return array_sum(array_map(function (Purchase $purchase) {
            return $purchase->getQuantity();
        }, $this->purchases->toArray()));
    }

    public function getPurchaseWithNoBookingQuantity(){
        $quantity = 0;
        /** @var YBContractArtist $campaign */ $campaign = $this->contractArtist;
        /** @var Purchase $purchase */
        foreach ($this->purchases as $purchase){
            if ($purchase->getCounterpart()->hasOnlyFreeSeatingBlocks($campaign->getConfig()->getBlocks())){
                $quantity += $purchase->getQuantity();
            }
        }
        return $quantity;
    }

    public function getCounterPartsQuantityOrganic()
    {
        return $this->getCounterPartsQuantity() - $this->getCounterPartsQuantityPromotional();
    }

    public function getCounterPartsQuantityPromotional()
    {
        return array_sum(array_map(function (Purchase $purchase) {
            return $purchase->getNbFreeCounterparts();
        }, $this->purchases->toArray()));
    }

    public function getNbReducedCounterPart()
    {
        return array_sum(array_map(function (Purchase $purchase) {
            return $purchase->getNbReducedCounterparts();
        }, $this->purchases->toArray()));
    }

    public function getUser()
    {
        return $this->getCart()->getUser();
    }

    public function getFan()
    {
        return $this->getUser();
    }

    public function calculatePromotions()
    {
        foreach ($this->purchases as $purchase) {
            $purchase->calculatePromotions();
        }
    }

    public function isEligibleForPromotion(Promotion $promotion)
    {
        return $this->date >= $promotion->getStartDate() && $this->date <= $promotion->getEndDate();
    }

    public function setUserRewards($user_rewards)
    {
        $this->user_rewards = $user_rewards;
        return $this;
    }

    public function giveOutReward()
    {
        $givedReward = [];
        $index = 0;
        foreach ($this->user_rewards as $user_reward) {
            $index = 0;
            foreach ($this->purchases as $purchase) {
                if ($user_reward instanceof ReductionReward) {
                    for ($i = 0; $i < $this->$purchase->getNbReducedCounterparts(); $i++) {
                        if ($givedReward[$index] == null) {
                            $givedReward[$index] = [];
                        }
                        $givedReward[$index] = array_push($givedReward[$index], "Réduction x1");
                        $index = $index + 1;
                    }
                } else if ($user_reward instanceof InvitationReward) {
                    $j = 1;
                    while ($j <= $purchase->getQuantity() && $j <= $user_reward->getRemainUse()) {

                    }
                } else if ($user_reward instanceof ConsomableReward) {

                }
            }
        }
    }

    public function getPurchasesExport($details = false) {
        $exportList = array();
        $i = 1;
        foreach ($this->getPurchases() as $key => $val) {
            /** @var $val Purchase */

            $str = '';
            if($details) {
                $str .= $this->getDisplayName() . ' - ' . $val->getAmount() . '€ - ';
            }
            $str .= $val->__toString();
            $exportList[] = $str;
            $i++;
        }
        return join(', ', $exportList);
    }

    public function getTicketsExport() {
        return join(', ', array_map(function(Ticket $ticket) { return $ticket->getBarcodeText(); }, $this->tickets->toArray()));
    }

    public function getPaymentExport() {
        return $this->getPayment()->getChargeId();
    }

    public function getChargeId() {
        return $this->getPayment()->getChargeId();
    }

    public function getContractArtistExport() {
        return $this->getContractArtist()->__toString();
    }

    public function getToppings() {
        $toppings = [];
        foreach($this->purchases as $purchase) {
            /** @var Purchase $purchase */
            $pps = $purchase->getPurchasePromotions();
            foreach($pps as $pp) {
                /** @var Purchase_Promotion $pp */
                $t = $pp->getToppings()->toArray();
                $toppings = array_merge($toppings, $t);
            }
        }
        return $toppings;
    }

    public function exceedsFestivalDaysTolerance(&$message) {
        $festivaldays = array();
        $realdays = array();
        foreach($this->getPurchases() as $purchase) {
            /** @var Counterpart $counterpart */
            $counterpart = $purchase->getCounterPart();
            if(count($counterpart->getFestivaldays()) > 0) {
                if(!$counterpart->isCombo()) {
                    if(!array_key_exists($counterpart->getFestivaldays()->first()->getId(),$festivaldays)) {
                        $festivaldays[$counterpart->getFestivaldays()->first()->getId()] = $counterpart->getFestivaldays()->first()->getSolosAvailable();
                        $realdays[$counterpart->getFestivaldays()->first()->getId()] = $counterpart->getFestivaldays()->first();
                    }
                    $festivaldays[$counterpart->getFestivaldays()->first()->getId()] -= $purchase->getQuantityOrganic() * $counterpart->getThresholdIncrease();
                }
                else {
                    if(!array_key_exists('combo',$festivaldays)) {
                        $festivaldays['combo'] = $counterpart->getFestivaldays()->first()->getCombosAvailable();
                    }
                    $festivaldays['combo'] -= $purchase->getQuantityOrganic() * $counterpart->getThresholdIncrease() / 2;
                }
            }
        }

        foreach($festivaldays as $key=>$day) {
            if($day < 0) {
                if($key == 'combo') {
                    $message = 'En effet, les tickets "combo" sont trop nombreux (' . ceil(abs($day)) . ' de trop). ';
                }
                else {
                    $message = 'En effet, les tickets pour le jour "' . $realdays[$key]->__toString() . '" sont trop nombreux (' . ceil(abs($day)) . ' de trop). ';
                }
                return true;
            }
        }

        return false;
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
     * @var Cart
     *
     * @ORM\ManyToOne(targetEntity="Cart", inversedBy="contracts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $cart;

    /**
     * @ORM\ManyToOne(targetEntity="BaseContractArtist", inversedBy="contractsFan", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $contractArtist;

    /**
     * @ORM\OneToMany(targetEntity="Purchase", mappedBy="contractFan", cascade={"all"})
     */
    private $purchases;

    /**
     * @ORM\Column(name="counterparts_sent", type="boolean")
     */
    private $counterparts_sent;

    /**
     * @ORM\Column(name="barcode_text", type="string", length=255, nullable=true)
     */
    private $barcode_text;

    /**
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(name="refunded", type="boolean")
     */
    private $refunded;

    /**
     * @ORM\OneToOne(targetEntity="Payment", mappedBy="contractFan")
     */
    private $payment;

    /**
     * @ORM\OneToMany(targetEntity="Ticket", mappedBy="contractFan", cascade={"all"})
     */
    private $tickets;

    /**
     * @ORM\ManyToMany(targetEntity="User_Reward", inversedBy="contractFans", cascade={"persist"})
     */
    private $user_rewards;

    /**
     * @ORM\Column(name="amount", type="float")
     */
    private $amount;

    /**
     * @ORM\OneToMany(targetEntity="RewardTicketConsumption", mappedBy="contractFan",cascade={"all"})
     */
    private $ticket_rewards;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="User")
     * @ORM\JoinColumn(name="contract_fan_refund_request")
     */
    private $asking_refund;

    /**
     * @var YBInvoice
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\YB\YBInvoice", inversedBy="contracts_fan")
     * @ORM\JoinColumn(nullable=true)
     */
    private $invoice;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set contractArtist
     *
     * @param \AppBundle\Entity\ContractArtist $contractArtist
     *
     * @return ContractFan
     */
    public function setContractArtist(\AppBundle\Entity\ContractArtist $contractArtist)
    {
        $this->contractArtist = $contractArtist;

        return $this;
    }

    /**
     * Get contractArtist
     *
     * @return \AppBundle\Entity\BaseContractArtist
     */
    public function getContractArtist()
    {
        return $this->contractArtist;
    }

    /**
     * Add purchase
     *
     * @param \AppBundle\Entity\Purchase $purchase
     *
     * @return ContractFan
     */
    public function addPurchase(\AppBundle\Entity\Purchase $purchase)
    {
        $this->purchases[] = $purchase;
        $purchase->setContractFan($this);

        return $this;
    }

    /**
     * Remove purchase
     *
     * @param \AppBundle\Entity\Purchase $purchase
     */
    public function removePurchase(\AppBundle\Entity\Purchase $purchase)
    {
        $this->purchases->removeElement($purchase);
    }

    /**
     * Get purchases
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPurchases()
    {
        return $this->purchases;
    }

    /**
     * Set cart
     *
     * @param \AppBundle\Entity\Cart $cart
     *
     * @return ContractFan
     */
    public function setCart(\AppBundle\Entity\Cart $cart)
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * Get cart
     *
     * @return \AppBundle\Entity\Cart
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * Set counterpartsSent
     *
     * @param boolean $counterpartsSent
     *
     * @return ContractFan
     */
    public function setcounterpartsSent($counterpartsSent)
    {
        $this->counterparts_sent = $counterpartsSent;

        return $this;
    }

    /**
     * Get counterpartsSent
     *
     * @return boolean
     */
    public function getcounterpartsSent()
    {
        return $this->counterparts_sent;
    }

    /**
     * Set barcodeText
     *
     * @param string $barcodeText
     *
     * @return ContractFan
     */
    public function setBarcodeText($barcodeText)
    {
        $this->barcode_text = $barcodeText;

        return $this;
    }

    /**
     * Get barcodeText
     *
     * @return string
     */
    public function getBarcodeText()
    {
        return $this->barcode_text;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return ContractFan
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
     * Set refunded
     *
     * @param boolean $refunded
     *
     * @return ContractFan
     */
    public function setRefunded($refunded)
    {
        $this->refunded = $refunded;
        if($refunded) {
            foreach($this->purchases as $purchase) {
                $purchase->setRefunded(true);
            }
        }
        return $this;
    }

    /**
     * Get refunded
     *
     * @return boolean
     */
    public function getRefunded()
    {
        return $this->refunded;
    }

    /**
     * Set payment
     *
     * @param \AppBundle\Entity\Payment $payment
     *
     * @return ContractFan
     */
    public function setPayment(\AppBundle\Entity\Payment $payment = null)
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * Get payment
     *
     * @return \AppBundle\Entity\Payment
     */
    public function getPayment()
    {
        $payment = $this->cart->getPayment();
        return $payment == null ? $this->payment : $payment;
    }

    /**
     * Add ticket
     *
     * @param \AppBundle\Entity\Ticket $ticket
     *
     * @return ContractFan
     */
    public function addTicket(\AppBundle\Entity\Ticket $ticket)
    {
        if($this->tickets == null) {
            $this->tickets = new ArrayCollection();
        }

        $this->tickets[] = $ticket;

        return $this;
    }

    /**
     * Remove ticket
     *
     * @param \AppBundle\Entity\Ticket $ticket
     */
    public function removeTicket(\AppBundle\Entity\Ticket $ticket)
    {
        $this->tickets->removeElement($ticket);
    }

    /**
     * Get tickets
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTickets()
    {
        return $this->tickets;
    }

    /**
     * Add userReward
     *
     * @param \AppBundle\Entity\User_Reward $userReward
     *
     * @return ContractFan
     */
    public function addUserReward(\AppBundle\Entity\User_Reward $userReward)
    {
        $this->user_rewards[] = $userReward;
        return $this;
    }

    /**
     * Remove userReward
     *
     * @param \AppBundle\Entity\User_Reward $userReward
     */
    public function removeUserReward(\AppBundle\Entity\User_Reward $userReward)
    {
        $this->user_rewards->removeElement($userReward);
    }

    /**
     * Get userRewards
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserRewards()
    {
        return $this->user_rewards;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return ContractFan
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        if($this->amount > 0)
            return $this->amount;

        return array_sum(array_map(function(Purchase $purchase) {
            return $purchase->getAmount();
        }, $this->purchases->toArray()));
    }

    /**
     * Add ticketReward
     *
     * @param \AppBundle\Entity\RewardTicketConsumption $ticketReward
     *
     * @return ContractFan
     */
    public function addTicketReward(\AppBundle\Entity\RewardTicketConsumption $ticketReward)
    {
        if (!$this->ticket_rewards->contains($ticketReward)) {
            $this->ticket_rewards[] = $ticketReward;
            $ticketReward->setContractFan($this);
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
            $ticketReward->setContractFan(null);
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
     * Add askingRefund
     *
     * @param \AppBundle\Entity\User $askingRefund
     *
     * @return ContractFan
     */
    public function addAskingRefund(\AppBundle\Entity\User $askingRefund)
    {
        $this->asking_refund[] = $askingRefund;

        return $this;
    }

    /**
     * Remove askingRefund
     *
     * @param \AppBundle\Entity\User $askingRefund
     */
    public function removeAskingRefund(\AppBundle\Entity\User $askingRefund)
    {
        $this->asking_refund->removeElement($askingRefund);
    }

    /**
     * Get askingRefund
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAskingRefund()
    {
        return $this->asking_refund;
    }

    /**
     * @return YBInvoice
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * @param YBInvoice $invoice
     * @return $this
     */
    public function setInvoice(YBInvoice $invoice)
    {
        $this->invoice = $invoice;
        return $this;
    }
}
