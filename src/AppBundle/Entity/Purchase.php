<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Purchase
 *
 * @ORM\Table(name="purchase")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PurchaseRepository")
 */
class Purchase
{
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
     * @ORM\ManyToOne(targetEntity="ContractFan", inversedBy="purchases")
     * @ORM\JoinColumn(nullable=false)
     */
    private $contractFan;

    /**
     * @ORM\ManyToOne(targetEntity="CounterPart")
     * @ORM\JoinColumn(nullable=false)
     */
    private $counterpart;

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
}
