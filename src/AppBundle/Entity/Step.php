<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Step
 *
 * @ORM\Table(name="step")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\StepRepository")
 */
class Step
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="num", type="smallint")
     */
    private $num;

    /**
     * @ORM\ManyToOne(targetEntity="Phase", inversedBy="steps")
     */
    private $phase;

    /**
     * @ORM\ManyToOne(targetEntity="StepType", inversedBy="steps")
     */
    private $type;

    /**
     * @ORM\Column(name="deadline_duration", type="integer")
     */
    private $deadline_duration;

    /**
     * @ORM\OneToMany(targetEntity="CounterPart", mappedBy="step")
     */
    private $counterParts;

    /**
     * @ORM\Column(name="amount", type="integer")
     */
    private $amount;

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
     * Set name
     *
     * @param string $name
     *
     * @return Step
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
     * Set num
     *
     * @param integer $num
     *
     * @return Step
     */
    public function setNum($num)
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Get num
     *
     * @return int
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * Set phase
     *
     * @param \AppBundle\Entity\Phase $phase
     *
     * @return Step
     */
    public function setPhase(\AppBundle\Entity\Phase $phase = null)
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
     * Set type
     *
     * @param \AppBundle\Entity\StepType $type
     *
     * @return Step
     */
    public function setType(\AppBundle\Entity\StepType $type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return \AppBundle\Entity\StepType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set deadlineDuration
     *
     * @param integer $deadlineDuration
     *
     * @return Step
     */
    public function setDeadlineDuration($deadlineDuration)
    {
        $this->deadline_duration = $deadlineDuration;

        return $this;
    }

    /**
     * Get deadlineDuration
     *
     * @return integer
     */
    public function getDeadlineDuration()
    {
        return $this->deadline_duration;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->counterParts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add counterPart
     *
     * @param \AppBundle\Entity\CounterPart $counterPart
     *
     * @return Step
     */
    public function addCounterPart(\AppBundle\Entity\CounterPart $counterPart)
    {
        $this->counterParts[] = $counterPart;
        $counterPart->setStep($this);

        return $this;
    }

    /**
     * Remove counterPart
     *
     * @param \AppBundle\Entity\CounterPart $counterPart
     */
    public function removeCounterPart(\AppBundle\Entity\CounterPart $counterPart)
    {
        $this->counterParts->removeElement($counterPart);
    }

    /**
     * Get counterParts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCounterParts()
    {
        return $this->counterParts;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return Step
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }
}
