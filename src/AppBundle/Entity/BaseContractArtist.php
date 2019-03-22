<?php

namespace AppBundle\Entity;
use AppBundle\Entity\YB\YBContractArtist;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use Sonata\TranslationBundle\Model\TranslatableInterface;

/**
 * @ORM\Table(name="base_contract_artist")
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"concert" = "ContractArtist", "sales" = "ContractArtistSales", "pot" = "ContractArtistPot", "yb" = "AppBundle\Entity\YB\YBContractArtist", "default" = "BaseContractArtist"})
 */
class BaseContractArtist implements TranslatableInterface
{
    use ORMBehaviors\Translatable\Translatable;

    const VOTES_TO_REFUND = 1;
    const NB_PROMO_DAYS = 7;
    const NB_TEST_PERIOD_DAYS = 20;
    const PHOTOS_DIR = 'images/festivals/';

    public function __construct() {
        $this->accept_conditions = false;
        $this->reminders_artist = 0;
        $this->reminders_admin = 0;
        $this->date = new \DateTime();
        $this->collected_amount = 0;
        $this->failed = false;
        $this->successful = false;
        $this->cart_reminder_sent = false;
        $this->refunded = false;
        $this->asking_refund = new ArrayCollection();
        $this->test_period = false;
        $this->promotions = new ArrayCollection();
        $this->no_threshold = false;
        $this->counterParts = new ArrayCollection();
        $this->global_soldout = null;
        $this->threshold = 0;
        $this->counterparts_sold = 0;
        $this->threshold = 0;
        $this->reminders = [];
    }

    public function __call($method, $arguments)
    {
        try {
            return $this->proxyCurrentLocaleTranslation($method, $arguments);
        } catch(\Exception $e) {
            $method = 'get' . ucfirst($method);
            return $this->proxyCurrentLocaleTranslation($method, $arguments);
        }
    }

    public function getDefaultLocale() {
        return 'fr';
    }

    public function setLocale($locale)
    {
        $this->setCurrentLocale($locale);
        return $this;
    }

    public function getLocale()
    {
        return $this->getCurrentLocale();
    }

    public function __toString()
    {
        $str ='';

        if($this->isYB()) {
            $str .= '[Ticked-it] ';
        }

        $str .= $this->getTitle();


        return $str;
    }

    public function isYB() {
        return $this instanceof YBContractArtist;
    }

    public static function getWebPath(Photo $photo) {
        return self::PHOTOS_DIR . $photo->getFilename();
    }

    public function isInTestPeriod() {
        return $this->test_period;
    }

    public function endTestPeriod() {
        $this->test_period = false;
        $this->generateStartDate();
        $this->generateDateEnd();
        foreach($this->promotions as $promotion) {
            $endDate = clone $this->start_date;
            $endDate->add(new \DateInterval('P' . self::NB_PROMO_DAYS.'D'));
            $promotion->setEndDate($endDate);
        }
    }

    public function getCurrentTestDayNb() {
        return $this->isInTestPeriod() ? (new \DateTime())->diff($this->date)->d + 1 : 0;
    }

    public function generateDateEnd() {
        $deadline = clone $this->start_date;
        $deadline->modify('+30 days')->setTime(23, 59, 59);
        $this->dateEnd = $deadline;
    }

    public function generateTestPeriodAndPromotion() {
        $this->generateStartDate();
        $this->generatePromotion();
    }

    public function generateStartDate() {
        $this->start_date = $this->isInTestPeriod() ? (new \DateTime())->add(new \DateInterval('P'.self::NB_TEST_PERIOD_DAYS.'D')) : (new \DateTime());
    }

    // TODO handle case where test period lasts > x weeks
    public function generatePromotion() {
        $promo = new Promotion(Promotion::TYPE_THREE_PLUS_ONE);

        $startDate = clone $this->date;
        $endDate = clone $this->start_date;

        $endDate->add(new \DateInterval('P' . (self::NB_PROMO_DAYS - 1) .'D'));

        $promo->setStartDate($startDate)->setEndDate($endDate);
        $this->addPromotion($promo);
    }

    public function getCurrentPromotions() {
        $now = new \DateTime();
        return array_filter($this->promotions->toArray(), function(Promotion $promotion) use ($now) {
            return $promotion->getStartDate() <= $now && $promotion->getEndDate() >= $now;
        });
    }

    public function getCurrentPromotionsVisible() {
        return array_filter($this->getCurrentPromotions(), function(Promotion $promotion) {
            return !$promotion->isHidden();
        });
    }

    // Facilitates admin list export
    public function getPromotionsExport() {
        $exportList = array();
        $i = 1;
        foreach ($this->promotions as $key => $val) {
            /** @var Promotion $val */
            $exportList[] = $i . ') ' . $val;
            $i++;
        }
        return '<pre>' . join(PHP_EOL, $exportList) . '</pre>';
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

    public function hasThreshold() {
        return !$this->no_threshold;
    }

    public function addAmount($amount) {
        $this->collected_amount += $amount;
    }

    public function getFirstCounterPart() {
        foreach($this->getCounterParts() as $cp) {
            return $cp;
        }
        return null;
    }

    public function getNbPurchasable(CounterPart $cp) {
        return min($this->getNbAvailable($cp), $cp->getMaximumAmountPerPurchase());
    }
    
    public function getNbAvailable(CounterPart $cp) {
        $nb = $cp->getMaximumAmount();

        foreach($this->contractsFan as $cf) {
            /** @var ContractFan $cf */
            if($cf->getPaid() && !$cf->getRefunded()) {
                foreach ($cf->getPurchases() as $purchase) {
                    if ($purchase->getCounterPart()->getId() == $cp->getId()) {
                        $nb -= $purchase->getQuantity();
                    }
                }
            }
        }

        if($nb <= 0) return 0;
        return $nb;
    }

    protected $contractsFanPaid = null;
    public function getContractsFanPaid() {
        if($this->contractsFanPaid == null) {
            $this->contractsFanPaid = array_filter($this->contractsFan->toArray(), function(ContractFan $contractFan) {
                return $contractFan->isPaid() && ($this->failed || !$contractFan->isRefunded());
            });
        }

        return $this->contractsFanPaid;

    }

    public function getNbCounterPartsPaid() {
        return array_sum(array_map(function(ContractFan $elem) {return $elem->getCounterPartsQuantity();}, $this->getContractsFanPaid()));
    }

    public function getNbCounterPartsObtainedByPromotion() {
        return array_sum(array_map(function(ContractFan $contractFan) {
            return $contractFan->getCounterPartsQuantityPromotional();
        }, $this->getContractsFanPaid()));
    }

    public function getNbCounterPartsSoldOrganic() {
        return array_sum(array_map(function(ContractFan $contractFan) {
            return $contractFan->getCounterPartsQuantityOrganic();
        }, $this->getContractsFanPaid()));
    }

    public function cantAddPurchase($quantity, CounterPart $cp) {
        return $this->getNbAvailable($cp) < $quantity;
    }

    public function getAllArtists() {
        return $this->artist;
    }

    public function getArtistProfiles() {
        $result = [];

        foreach($this->getAllArtists() as $artist) {
            foreach($artist->getArtistsUser() as $artist_user)
                $result[] = $artist_user->getUser();
        }

        return $result;
    }

    // Returns only fans who paid their tickets and didn't get refunded
    public function getFanProfiles() {
        $fans = [];

        foreach($this->getContractsFanPaid() as $cf) {
            /** @var ContractFan $cf */
            if(!in_array($cf->getUser(), $fans))
                $fans[] = $cf->getUser();
        }
        return $fans;
    }

    // Returns all fans who at least started an order on this event
    public function getAllFanProfiles() {
        $fans = [];

        foreach($this->contractsFan as $cf) {
            /** @var ContractFan $cf */
            if(!in_array($cf->getUser(), $fans))
                $fans[] = $cf->getUser();
        }
        return $fans;
    }

    public function getPhysicalPersons() {
        $ph = [];

        foreach($this->getContractsFanPaid() as $cf) {
            if(!in_array($cf->getPhysicalPerson(), $ph)) {
                $ph[] = $cf->getPhysicalPerson();
            }
        }
        return $ph;
    }

    public function getCounterParts() {
        if($this->counterParts->count() == 0 && $this->step != null) {
            return $this->step->getCounterParts();
        }
        else {
            return $this->counterParts;
        }
    }

    public function calculateCounterPartsSold() {
        $this->counterparts_sold = 0;
        if($this instanceof ContractArtist) {
            foreach ($this->getFestivaldays() as $festivalday) {
                $festivalday->setTicketsSold(0);
            }
        }
        foreach($this->getContractsFanPaid() as $cf) {
            $this->updateCounterPartsSold($cf);
        }
    }

    public function updateCounterPartsSold(ContractFan $cf) {
        $this->addCounterPartsSold($cf->getThresholdIncrease());
        foreach($cf->getPurchases() as $purchase) {
            /** @var Purchase $purchase */
            $festivaldays = $purchase->getCounterpart()->getFestivaldays();
            if($festivaldays != null && !empty($festivaldays)) {
                if(count($festivaldays) > 1) {
                    foreach ($festivaldays as $festivalday) {
                        $festivalday->updateHalfTicketsSold($purchase);
                    }
                }
                else {
                    if($festivaldays[0] != null) {
                        $festivaldays[0]->updateTicketsSold($purchase);
                    }

                }
            }
        }
    }

    public function addCounterPartsSold($quantity) {
        $this->counterparts_sold += $quantity;
    }

    public function removeCounterPartsSold($quantity) {
        $this->counterparts_sold -= $quantity;
    }

    // Unmapped
    protected $state;

     // Step entity
    /** @var BaseStep */
    protected $step;

    // Discriminator
    protected $type;

    // Conditions approval (user form only)
    protected $accept_conditions;

    // Deadline calculation (@deprecated)
    protected $theoritical_deadline;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    protected $date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_end", type="datetime", nullable=true)
     */
    protected $dateEnd;

    /**
     * @var Artist
     *
     * @ORM\ManyToOne(targetEntity="Artist", inversedBy="base_contracts")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $artist;

    /**
     * @ORM\Column(name="motivations", type="text", nullable=true)
     */
    protected $motivations;

    /**
     * @ORM\Column(name="reminders_artist", type="smallint")
     */
    protected $reminders_artist;

    /**
     * @ORM\Column(name="collected_amount", type="float")
     */
    protected $collected_amount;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="ContractFan", mappedBy="contractArtist", cascade={"persist"})
     */
    protected $contractsFan;

    /**
     * @ORM\Column(name="failed", type="boolean")
     */
    protected $failed;

    /**
     * @ORM\Column(name="successful", type="boolean")
     */
    protected $successful;

    /**
     * @ORM\Column(name="refunded", type="boolean")
     */
    protected $refunded;

    /**
     * @ORM\ManyToMany(targetEntity="User")
     */
    protected $asking_refund;

    /**
     * @ORM\Column(name="reminders_admin", type="smallint")
     */
    protected $reminders_admin;

    /**
     * @ORM\Column(name="last_reminder_admin", type="datetime", nullable=true)
     */
    protected $last_reminder_admin;

    /**
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     */
    protected $start_date;

    /**
     * @ORM\Column(name="test_period", type="boolean")
     */
    protected $test_period;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="Promotion", cascade={"all"}, inversedBy="contracts")
     */
    protected $promotions;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="VIPInscription", mappedBy="contract_artist")
     */
    protected $vip_inscriptions;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="VolunteerProposal", mappedBy="contract_artist")
     */
    protected $volunteer_proposals;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_success", type="datetime", nullable=true)
     */
    protected $date_success;

    /**
     * @var bool
     * @ORM\Column(name="no_threshold", type="boolean")
     */
    protected $no_threshold;

    /**
     * @ORM\OneToMany(targetEntity="CounterPart", mappedBy="contractArtist", cascade={"all"})
     */
    protected $counterParts;

    /**
     * @ORM\Column(name="global_soldout", type="integer", nullable=true)
     */
    protected $global_soldout;

    /**
     * @ORM\OneToOne(targetEntity="Photo", cascade={"all"})
     * @ORM\JoinColumn(nullable=true)
     */
    protected $photo;

    /**
     * @ORM\ManyToMany(targetEntity="Photo", cascade={"all"})
     */
    protected $campaign_photos;

    /**
     * @var int
     * @ORM\Column(name="threshold", type="smallint", nullable=true)
     */
    protected $threshold;

    /**
     * @var float
     * @ORM\Column(name="counterparts_sold", type="float")
     */
    protected $counterparts_sold;

    /**
     * @var array
     * @ORM\Column(name="reminders", type="array")
     */
    protected $reminders;

    /**
     * @var string
     * @ORM\Column(name="facebook_url", type="string", length=255, nullable=true)
    */
    protected $facebook_url;

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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return BaseContractArtist
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
     * Set dateEnd
     *
     * @param \DateTime $dateEnd
     *
     * @return BaseContractArtist
     */
    public function setDateEnd($dateEnd)
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    /**
     * Get dateEnd
     *
     * @return \DateTime
     */
    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    /**
     * Set artist
     *
     * @param \AppBundle\Entity\Artist $artist
     *
     * @return BaseContractArtist
     */
    public function setArtist(\AppBundle\Entity\Artist $artist)
    {
        $this->artist = $artist;

        return $this;
    }

    /**
     * Get artist
     *
     * @return \AppBundle\Entity\Artist
     */
    public function getArtist()
    {
        return $this->artist;
    }

    /**
     * @return mixed
     */
    public function getTheoriticalDeadline()
    {
        return $this->theoritical_deadline;
    }

    /**
     * @param mixed $theoritical_deadline
     */
    public function setTheoriticalDeadline($theoritical_deadline)
    {
        $this->theoritical_deadline = $theoritical_deadline;
    }

    /**
     * @return boolean
     */
    public function getAcceptConditions()
    {
        return $this->accept_conditions;
    }

    /**
     * @param boolean $accept_conditions
     */
    public function setAcceptConditions($accept_conditions)
    {
        $this->accept_conditions = $accept_conditions;
    }

    /**
     * Set motivations
     *
     * @param string $motivations
     *
     * @return BaseContractArtist
     */
    public function setMotivations($motivations)
    {
        $this->motivations = $motivations;

        return $this;
    }

    /**
     * Get motivations
     *
     * @return string
     */
    public function getMotivations()
    {
        return $this->motivations;
    }

    /**
     * Set reminders
     *
     * @param integer $reminders
     *
     * @return BaseContractArtist
     */
    public function setRemindersArtist($reminders)
    {
        $this->reminders_artist = $reminders;

        return $this;
    }

    /**
     * Get reminders
     *
     * @return integer
     */
    public function getRemindersArtist()
    {
        return $this->reminders_artist;
    }

    /**
     * Set collectedAmount
     *
     * @param integer $collectedAmount
     *
     * @return BaseContractArtist
     */
    public function setCollectedAmount($collectedAmount)
    {
        $this->collected_amount = $collectedAmount;

        return $this;
    }

    /**
     * Get collectedAmount
     *
     * @return integer
     */
    public function getCollectedAmount()
    {
        return $this->collected_amount;
    }

    /**
     * Set failed
     *
     * @param boolean $failed
     *
     * @return BaseContractArtist
     */
    public function setFailed($failed)
    {
        $this->failed = $failed;

        return $this;
    }

    /**
     * Get failed
     *
     * @return boolean
     */
    public function getFailed()
    {
        return $this->failed;
    }

    /**
     * Set successful
     *
     * @param boolean $successful
     *
     * @return BaseContractArtist
     */
    public function setSuccessful($successful)
    {
        $this->successful = $successful;

        return $this;
    }

    /**
     * Get successful
     *
     * @return boolean
     */
    public function getSuccessful()
    {
        return $this->successful;
    }

    /**
     * Add contractsFan
     *
     * @param \AppBundle\Entity\ContractFan $contractsFan
     *
     * @return BaseContractArtist
     */
    public function addContractsFan(\AppBundle\Entity\ContractFan $contractsFan)
    {
        $this->contractsFan[] = $contractsFan;

        return $this;
    }

    /**
     * Remove contractsFan
     *
     * @param \AppBundle\Entity\ContractFan $contractsFan
     */
    public function removeContractsFan(\AppBundle\Entity\ContractFan $contractsFan)
    {
        $this->contractsFan->removeElement($contractsFan);
    }

    /**
     * Get contractsFan
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getContractsFan()
    {
        return $this->contractsFan;
    }

    /**
     * Set refunded
     *
     * @param boolean $refunded
     *
     * @return BaseContractArtist
     */
    public function setRefunded($refunded)
    {
        $this->refunded = $refunded;

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
     * Add askingRefund
     *
     * @param \AppBundle\Entity\User $askingRefund
     *
     * @return BaseContractArtist
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
     * Set remindersAdmin
     *
     * @param integer $remindersAdmin
     *
     * @return BaseContractArtist
     */
    public function setRemindersAdmin($remindersAdmin)
    {
        $this->reminders_admin = $remindersAdmin;

        return $this;
    }

    /**
     * Get remindersAdmin
     *
     * @return integer
     */
    public function getRemindersAdmin()
    {
        return $this->reminders_admin;
    }


    /**
     * Set lastReminderAdmin
     *
     * @param \DateTime $lastReminderAdmin
     *
     * @return BaseContractArtist
     */
    public function setLastReminderAdmin($lastReminderAdmin)
    {
        $this->last_reminder_admin = $lastReminderAdmin;

        return $this;
    }

    /**
     * Get lastReminderAdmin
     *
     * @return \DateTime
     */
    public function getLastReminderAdmin()
    {
        return $this->last_reminder_admin;
    }

    /**
     * @return mixed
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * @param mixed $start_date
     * @return BaseContractArtist
     */
    public function setStartDate($start_date)
    {
        $this->start_date = $start_date;
    }

    /**
     * @return mixed
     */
    public function getTestPeriod()
    {
        return $this->test_period;
    }

    /**
     * @param mixed $test_period
     */
    public function setTestPeriod($test_period)
    {
        $this->test_period = $test_period;
    }

    /**
     * Add promotion
     *
     * @param \AppBundle\Entity\Promotion $promotion
     *
     * @return BaseContractArtist
     */
    public function addPromotion(\AppBundle\Entity\Promotion $promotion)
    {
        $this->promotions[] = $promotion;

        return $this;
    }

    /**
     * Remove promotion
     *
     * @param \AppBundle\Entity\Promotion $promotion
     */
    public function removePromotion(\AppBundle\Entity\Promotion $promotion)
    {
        $this->promotions->removeElement($promotion);
    }

    /**
     * Get promotions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPromotions()
    {
        return $this->promotions;
    }

    public function getPromotionsDecr() {
        $promotions = $this->promotions->toArray();
        usort($promotions, function(Promotion $a, Promotion $b) {
            if($a->getNbOrganicNeeded() > $b->getNbOrganicNeeded()) {
                return -1;
            }
            elseif($a->getNbOrganicNeeded() == $b->getNbOrganicNeeded()) {
                return 0;
            }
            return 1;
        });
        return $promotions;
    }

    /**
     * Add vipInscription
     *
     * @param \AppBundle\Entity\VIPInscription $vipInscription
     *
     * @return BaseContractArtist
     */
    public function addVipInscription(\AppBundle\Entity\VIPInscription $vipInscription)
    {
        $this->vip_inscriptions[] = $vipInscription;

        return $this;
    }

    /**
     * Remove vipInscription
     *
     * @param \AppBundle\Entity\VIPInscription $vipInscription
     */
    public function removeVipInscription(\AppBundle\Entity\VIPInscription $vipInscription)
    {
        $this->vip_inscriptions->removeElement($vipInscription);
    }

    /**
     * Get vipInscriptions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVipInscriptions()
    {
        return $this->vip_inscriptions;
    }

    public function removeCounterPart(CounterPart $counterpart) {
        $this->counterParts->removeElement($counterpart);
        $counterpart->setContractArtist(null);
        return $this;
    }

    /**
     * Set dateSuccess
     *
     * @param \DateTime $dateSuccess
     *
     * @return BaseContractArtist
     */
    public function setDateSuccess($dateSuccess)
    {
        $this->date_success = $dateSuccess;

        return $this;
    }

    /**
     * Get dateSuccess
     *
     * @return \DateTime
     */
    public function getDateSuccess()
    {
        return $this->date_success;
    }

    /**
     * @return bool
     */
    public function isNoThreshold()
    {
        return $this->no_threshold;
    }
    public function hasNoThreshold() { return $this->isNoThreshold(); }

    /**
     * @param bool $no_threshold
     */
    public function setNoThreshold($no_threshold)
    {
        $this->no_threshold = $no_threshold;
    }

    /**
     * Get noThreshold
     *
     * @return boolean
     */
    public function getNoThreshold()
    {
        return $this->no_threshold;
    }

    /**
     * Add counterPart
     *
     * @param \AppBundle\Entity\CounterPart $counterPart
     *
     * @return BaseContractArtist
     */
    public function addCounterPart(\AppBundle\Entity\CounterPart $counterPart)
    {
        $this->counterParts[] = $counterPart;
        $counterPart->setContractArtist($this);

        return $this;
    }

    /**
     * Set globalSoldout
     *
     * @param integer $globalSoldout
     *
     * @return BaseContractArtist
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
     * Set photo
     *
     * @param \AppBundle\Entity\Photo $photo
     *
     * @return BaseContractArtist
     */
    public function setPhoto(\AppBundle\Entity\Photo $photo = null)
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get photo
     *
     * @return \AppBundle\Entity\Photo
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * Add campaignPhoto
     *
     * @param \AppBundle\Entity\Photo $campaignPhoto
     *
     * @return BaseContractArtist
     */
    public function addCampaignPhoto(\AppBundle\Entity\Photo $campaignPhoto)
    {
        $this->campaign_photos[] = $campaignPhoto;

        return $this;
    }

    /**
     * Remove campaignPhoto
     *
     * @param \AppBundle\Entity\Photo $campaignPhoto
     */
    public function removeCampaignPhoto(\AppBundle\Entity\Photo $campaignPhoto)
    {
        $this->campaign_photos->removeElement($campaignPhoto);
    }

    /**
     * Get campaignPhotos
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCampaignPhotos()
    {
        return $this->campaign_photos;
    }

    /**
     * Set threshold
     *
     * @param integer $threshold
     *
     * @return BaseContractArtist
     */
    public function setThreshold($threshold)
    {
        $this->threshold = $threshold;

        return $this;
    }

    /**
     * Get threshold
     *
     * @return integer
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * Set counterpartsSold
     *
     * @param float $counterpartsSold
     *
     * @return BaseContractArtist
     */
    public function setCounterpartsSold($counterpartsSold)
    {
        $this->counterparts_sold = $counterpartsSold;

        return $this;
    }

    /**
     * Get counterpartsSold
     *
     * @return float
     */
    public function getCounterpartsSold()
    {
        return $this->counterparts_sold;
    }

    /**
     * Add volunteerProposal
     *
     * @param \AppBundle\Entity\VolunteerProposal $volunteerProposal
     *
     * @return BaseContractArtist
     */
    public function addVolunteerProposal(\AppBundle\Entity\VolunteerProposal $volunteerProposal)
    {
        $this->volunteer_proposals[] = $volunteerProposal;

        return $this;
    }

    /**
     * Remove volunteerProposal
     *
     * @param \AppBundle\Entity\VolunteerProposal $volunteerProposal
     */
    public function removeVolunteerProposal(\AppBundle\Entity\VolunteerProposal $volunteerProposal)
    {
        $this->volunteer_proposals->removeElement($volunteerProposal);
    }

    /**
     * Get volunteerProposals
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVolunteerProposals()
    {
        return $this->volunteer_proposals;
    }

    /**
     * @return array
     */
    public function getReminders() {
        return $this->reminders;
    }

    /**
     * @param array $reminders
     * @return $this
     */
    public function setReminders($reminders){
        $this->reminders = $reminders;
        return $this;
    }

    /**
     * @param string $reminder
     * @return $this
     */
    public function addReminder($reminder) {
        if(!in_array($reminder, $this->reminders)) {
            $this->reminders[] = $reminder;
        }
        return $this;
    }

    // todo what is this?????
    public function getDateEvent(){

    }

    /**
     * @return string
     */
    public function getFacebookUrl() {
        return $this->facebook_url;
    }

    /**
     * @param $facebookUrl
     * @return $this
     */
    public function setFacebookUrl($facebookUrl) {
        $this->facebook_url = $facebookUrl;
        return $this;
    }
}
