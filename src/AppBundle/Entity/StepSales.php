<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 11-06-18
 * Time: 09:55
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="step_sales")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\StepSalesRepository")
 */
class StepSales extends BaseStep
{
}