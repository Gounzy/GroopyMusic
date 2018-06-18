<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
* @ORM\Table(name="contract_artist_sales")
* @ORM\Entity(repositoryClass="AppBundle\Repository\ContractArtistSalesRepository")
*/
class ContractArtistSales extends BaseContractArtist {

    use ORMBehaviors\Translatable\Translatable;

    const STATE_REFUNDED = 'state.refunded';
    const STATE_FAILED = 'state.failed';
    const STATE_ONGOING = 'state.ongoing';
    const STATE_PASSED = 'state.passed';

    const UNCROWDABLE_STATES = [self::STATE_REFUNDED, self::STATE_FAILED, self::STATE_PASSED];
    const SUCCESSFUL_STATES = [self::STATE_ONGOING];

    public function __call($method, $arguments)
    {
        try {
            return $this->proxyCurrentLocaleTranslation($method, $arguments);
        } catch (\Exception $e) {
            $method = 'get' . ucfirst($method);
            return $this->proxyCurrentLocaleTranslation($method, $arguments);
        }
    }

    public function getDefaultLocale()
    {
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
        return $this->step->__toString();
    }

    public function getState() {
        if(isset($this->state)) {
            return $this->state;
        }
        if($this->refunded) {
            return self::STATE_REFUNDED;
        }
        if($this->failed) {
            return self::STATE_FAILED;
        }
        if($this->dateEnd < (new \DateTime())) {
            return self::STATE_PASSED;
        }
        else {
            return self::STATE_ONGOING;
        }
    }

    public function isCrowdable() {
        return !in_array($this->getState(), self::UNCROWDABLE_STATES);
    }

    public function isUnCrowdable() {
        return !$this->isCrowdable();
    }

    public function getSuccessfulStates() {
        return self::SUCCESSFUL_STATES;
    }

    public function getTotalNbAvailable() {
        return PHP_INT_MAX;
    }

    public function isSoldOut() {
        return false;
    }

    /**
     * @var StepSales
     *
     * @ORM\ManyToOne(targetEntity="StepSales")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $step;

    /**
     * @return StepSales
     */
    public function getStep(): StepSales
    {
        return $this->step;
    }

    /**
     * @param StepSales $step
     */
    public function setStep(StepSales $step)
    {
        $this->step = $step;
    }
}