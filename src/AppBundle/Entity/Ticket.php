<?php
/**
 * Created by PhpStorm.
 * User: Gonzague
 * Date: 30-11-17
 * Time: 15:51
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ticket
 *
 * @ORM\Table(name="ticket")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TicketRepository")
 */
class Ticket
{
    public function __construct($cf, $counterPart, $num, $price, PhysicalPersonInterface $physicalPerson = null, $contractArtist = null)
    {
        $this->contractFan = $cf;
        $this->counterPart = $counterPart;
        $this->price = $price;
        $this->validated = false;
        $this->rewards = new ArrayCollection();

        if ($cf != null) {
            $this->barcode_text = $cf->getBarcodeText() . '' . $num;
            $this->contractArtist = $cf->getContractArtist();
            $this->name = $cf->getPhysicalPerson()->getDisplayName();
        } else {
            $this->barcode_text = $this->generateBarCode($num);
            $this->contractArtist = $contractArtist;
            $this->name = $physicalPerson->getDisplayName();
        }
    }

    /**
     * @param $num
     * @return string
     */
    private function generateBarCode($num)
    {
        return 'ph' . rand(1, $num) . substr(md5(uniqid()), 0, 15) . $num;
    }

    /**
     * @return bool
     */
    public function isValidated()
    {
        return $this->getValidated();
    }

    /**
     * @return bool
     */
    public function isRefunded()
    {
        return $this->contractFan != null && $this->contractFan->getRefunded();
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
     * @var string
     *
     * @ORM\Column(name="barcode_text", type="string", length=255)
     */
    private $barcode_text;

    /**
     * @var ContractFan
     *
     * @ORM\ManyToOne(targetEntity="ContractFan", inversedBy="tickets")
     * @ORM\JoinColumn(nullable=true)
     */
    private $contractFan;

    /**
     * @var CounterPart
     *
     * @ORM\ManyToOne(targetEntity="CounterPart")
     */
    private $counterPart;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float")
     */
    private $price;

    /**
     * @var bool
     *
     * @ORM\Column(name="validated", type="boolean")
     */
    private $validated;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var BaseContractArtist
     *
     * @ORM\ManyToOne(targetEntity="BaseContractArtist")
     */
    private $contractArtist;

    /**
     * @ORM\OneToMany(targetEntity="RewardTicketConsumption", mappedBy="ticket", cascade={"all"}, orphanRemoval=true)
     */
    private $rewards;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_validated", type="datetime", nullable=true)
     */
    private $date_validated;

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
     * Set barcodeText
     *
     * @param string $barcodeText
     *
     * @return Ticket
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
     * Set contractFan
     *
     * @param \AppBundle\Entity\ContractFan $contractFan
     *
     * @return Ticket
     */
    public function setContractFan(\AppBundle\Entity\ContractFan $contractFan = null)
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
     * Set counterPart
     *
     * @param \AppBundle\Entity\CounterPart $counterPart
     *
     * @return Ticket
     */
    public function setCounterPart(\AppBundle\Entity\CounterPart $counterPart = null)
    {
        $this->counterPart = $counterPart;

        return $this;
    }

    /**
     * Get counterPart
     *
     * @return \AppBundle\Entity\CounterPart
     */
    public function getCounterPart()
    {
        return $this->counterPart;
    }

    /**
     * Set price
     *
     * @param integer $price
     *
     * @return Ticket
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set validated
     *
     * @param boolean $validated
     *
     * @return Ticket
     */
    public function setValidated($validated)
    {
        $this->validated = $validated;

        return $this;
    }

    /**
     * Get validated
     *
     * @return boolean
     */
    public function getValidated()
    {
        return $this->validated;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Ticket
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set contractArtist
     *
     * @param \AppBundle\Entity\BaseContractArtist $contractArtist
     *
     * @return Ticket
     */
    public function setContractArtist(\AppBundle\Entity\BaseContractArtist $contractArtist = null)
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
     * Add reward
     *
     * @param \AppBundle\Entity\RewardTicketConsumption $reward
     *
     * @return Ticket
     */
    public function addReward(\AppBundle\Entity\RewardTicketConsumption $reward)
    {
        if (!$this->rewards->contains($reward)) {
            $this->rewards[] = $reward;
            $reward->setTicket($this);
        }

        return $this;
    }

    /**
     * Remove reward
     *
     * @param \AppBundle\Entity\RewardTicketConsumption $reward
     */
    public function removeReward(\AppBundle\Entity\RewardTicketConsumption $reward)
    {
        if ($this->rewards->contains($reward)) {
            $this->rewards->removeElement($reward);
            $reward->setTicket(null);
        }
    }

    /**
     * Get rewards
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRewards()
    {
        return $this->rewards;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateValidated()
    {
        return $this->date_validated;
    }

    /**
     * @param \DateTime $date_validated
     */
    public function setDateValidated(\DateTime $date_validated)
    {
        $this->date_validated = $date_validated;
    }
}
