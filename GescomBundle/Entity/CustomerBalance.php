<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @author      Nicolas Marchand <contact@nicolasmarchand.dev>
 * @copyright   Copyright 2018 Nicolas Marchand
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Nmdev\Bundle\GescomBundle\Entity;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Nmdev\Bundle\GescomBundle\Model\ExtendCustomerBalance;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;


/**
 * CustomerBalance
 *
 * @ORM\Table(name="gescom_customer_balance")
 * @ORM\Entity(repositoryClass="Nmdev\Bundle\GescomBundle\Repository\CustomerBalanceRepository")
 * @Config(
 *     defaultValues={
 *         "entity"={
 *             "label"="Soldes clients",
 *             "plural_label"="Soldes clients"
 *         },
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class CustomerBalance extends ExtendCustomerBalance {

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;


    /**
     * @var float
     *
     * @ORM\Column(name="initial_balance", type="float", nullable=true)
     */
    private $initialBalance;


    /**
     * @var float
     *
     * @ORM\Column(name="balance", type="float", nullable=true)
     */
    private $balance;

    /**
     * @var float
     *
     * @ORM\Column(name="credit_limit", type="float", nullable=true)
     */
    private $creditLimit;

    /**
     * @var float
     *
     * @ORM\Column(name="current_purchase_amount", type="float", nullable=true)
     */
    private $currentPurchaseAmount;


    /**
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     */
    private $customer;




    /**
     * Get id
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }


    /**
     * Set initial balance
     *
     * @param string $initialBalance
     *
     * @return CustomerBalance
     */
    public function setInitialBalance($initialBalance) {
        $this->initialBalance = $initialBalance;

        return $this;
    }

    /**
     * Get initial balance
     *
     * @return string
     */
    public function getInitialBalance() {
        return $this->initialBalance;
    }


    /**
     * Set balance
     *
     * @param string $balance
     *
     * @return CustomerBalance
     */
    public function setBalance($balance) {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Get balance
     *
     * @return string
     */
    public function getBalance() {
        return $this->balance;
    }


    /**
     * Set CreditLimit
     *
     * @param string $creditLimit
     *
     * @return CustomerBalance
     */
    public function setCreditLimit($creditLimit) {
        $this->creditLimit = $creditLimit;

        return $this;
    }

    /**
     * Get CreditLimit
     *
     * @return string
     */
    public function getCreditLimit() {
        return $this->creditLimit;
    }


    /**
     * Set CurrentPurchaseAmount
     *
     * @param string $currentPurchaseAmount
     *
     * @return CustomerBalance
     */
    public function setCurrentPurchaseAmount($currentPurchaseAmount) {
        $this->currentPurchaseAmount = $currentPurchaseAmount;

        return $this;
    }

    /**
     * Get CurrentPurchaseAmount
     *
     * @return string
     */
    public function getCurrentPurchaseAmount() {
        return $this->currentPurchaseAmount;
    }


    /**
     * Set customer
     *
     * @return CustomerBalance
     */
    public function setCustomer(Customer $customer) {
        $this->customer = $customer;
        return $this;
    }

    /**
     * Get customer
     *
     * @return Customer
     */
    public function getCustomer() {
        return $this->customer;
    }


}
