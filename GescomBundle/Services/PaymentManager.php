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
namespace Nmdev\Bundle\GescomBundle\Services;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Nmdev\Bundle\GescomBundle\Entity\CustomerBalance;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\CustomerBundle\Entity\Customer;

class PaymentManager
{
    /** @var EntityManager **/
    private $entityManager;

    /** @var WorkflowManager **/
    private $workflowManager;

    /** @var Container **/
    private $container;

    public function __construct(
        Container $container,
        EntityManager $entityManager,
        WorkflowManager $workflowManager)
    {
        $this->container= $container;
        $this->entityManager = $entityManager;
        $this->workflowManager = $workflowManager;

        $this->config = $this->container->get('oro_config.global');
    }

    /**
     * @param Order $order
     * @return false|void
     * @throws \Exception
     */
    public function setPaymentDueDate(Order $order)
    {
        if(is_null($order->getPaymentDueDate())) {
            // Conditions de paiement
            $associationProvider = $this->container->get('oro_payment_term.provider.payment_term_association');
            $customer = $order->getCustomer();
            $paymentTerm = $associationProvider->getPaymentTerm($order);
            if(is_null($paymentTerm)) {
                $paymentTerm = $associationProvider->getPaymentTerm($customer);
            }

            if(is_null($paymentTerm)) {
                return false;
            }

            $nbDays = intval($paymentTerm->getDaysBeforePayment()) > 0 ? intval($paymentTerm->getDaysBeforePayment()) : 30;
            $term = new \DateTime();
            $term->add(new \DateInterval('P'.$nbDays.'D'));

            if($paymentTerm->getEndOfMonth()) {
                $term->modify('last day of this month');
            }

            // Due amount
            $order->setDueAmount($order->getTotal());

            // @todo : gérer la facturation groupée
            $order->setPaymentDueDate($term);
            $this->entityManager->persist($order);
            $this->entityManager->flush();
        }
    }

    /**
     * @param Order $order
     * @return void
     */
    public function calculateCustomerBalance(Order $order)
    {
        $customer = $order->getCustomer();
        $customerBalance = $this->entityManager
                ->getRepository('NmdevGescomBundle:CustomerBalance')
                ->findOneBy([
                    'customer' => $customer
                ]);

        if(is_null($customerBalance)) {
            $customerBalance = new CustomerBalance();
            $customerBalance->setBalance(0);
            $customerBalance->setInitialBalance(0);
        }
        // Calcul de la balance
        $paymentTransactions = $this->entityManager
            ->getRepository('OroPaymentBundle:PaymentTransaction')
            ->findBy([
                'entityClass' => Customer::class,
                'successful' => 1
            ]);
        $balance = $customerBalance->getInitialBalance();
        foreach($paymentTransactions as $paymentTransaction) {
            switch($paymentTransaction->getAction()) {
                case 'debit':
                    $balance -= $paymentTransaction->getAmount();
                    break;
                case 'credit':
                    $balance += $paymentTransaction->getAmount();
                    break;
            }
        }
        $customerBalance->setBalance($balance);

        // Calcul de currentPurchaseamount : bons de commande validés, non facturés
        $paymentTransactions = $this->entityManager
            ->getRepository('OroPaymentBundle:PaymentTransaction')
            ->findBy([
                'entityClass' => Order::class,
                'action' => 'purchase',
                'successful' => 1,
                'active' => 0 // BDC validé
            ]);
        $currentPurchaseAmount = 0;
        foreach($paymentTransactions as $paymentTransaction) {
            $relatedTransaction = $this->entityManager
                ->getRepository('OroPaymentBundle:PaymentTransaction')
                ->findOneBy([
                    'entityClass' => Order::class,
                    'successful' => 1,
                    'action' => 'invoice'
                ]);
            if(is_null($relatedTransaction)) {
                $currentPurchaseAmount += $paymentTransaction->getAmount();
            }
        }
        $customerBalance->setCurrentPurchaseAmount($currentPurchaseAmount);

        $this->entityManager->persist($customerBalance);
        $this->entityManager->flush();
    }

    /**
     * @param Order $order
     * @param string $reference
     * @param string $paymentMethod
     * @param float $amount
     * @return void
     */
    public function savePartialPayment(
        Order $order,
        string $reference,
        string $paymentMethod,
        float $amount)
    {
        $customerTransaction = $this->_saveCustomerCredit($order, $reference, $paymentMethod, $amount);
        $this->processTransaction($customerTransaction, $order);
    }

    /**
     * @param Order $order
     * @param string $paymentMethod
     * @param string $reference
     * @return void
     */
    public function saveTotalPayment(
        Order $order,
        string $paymentMethod,
        string $reference)
    {
        $customerTransaction = $this->_saveCustomerCredit($order, $reference, $paymentMethod, $order->getTotal());
        $this->processTransaction($customerTransaction, $order);
    }

    /**
     * @param Order $order
     * @return void
     */
    public function closeOrder(Order $order)
    {
        if($order->getDueAmount() == 0) {
            $invoiceTransaction = $this->entityManager
                ->getRepository('OroPaymentBundle:PaymentTransaction')
                ->findOneBy([
                    'entityClass' => Order::class,
                    'entityIdentifier' => $order->getId(),
                    'action' => 'invoice',
                    'successful' => 1,
                    'active' => 1
                ]);
            if(!is_null($invoiceTransaction)) {
                $invoiceTransaction->setActive(0);
                $this->entityManager->persist($invoiceTransaction);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * @param PaymentTransaction $customerTransaction
     * @param Order|null $order
     * @return true
     */
    public function processTransaction(PaymentTransaction $customerTransaction, Order $order = null)
    {
        if(!is_null($order)) {
            // Paiement partiel
            if($order->getDueAmount() > $customerTransaction->getAmount()) {
                $order->setDueAmount($order->getDueAmount() - $customerTransaction->getAmount());
                $customerTransaction->setActive(0);
            }

            // Paiement du solde
            if($order->getDueAmount() <= $customerTransaction->getAmount()) {
                $reste = $customerTransaction->getAmount() - $order->getDueAmount();
                $order->setDueAmount(0);
                $customerTransaction->setActive(0);
                // transit order
                $workflowItem = $this->workflowManager->getWorkflowItem($order, 'order_bdc');
                $this->workflowManager->transit($workflowItem, 't_fermer_commande');
            }
        }
        return true;
    }

    /**
     * @param Order $order
     * @param string $reference
     * @param string $paymentMethod
     * @param float $amount
     * @return PaymentTransaction
     */
    private function _saveCustomerCredit(
        Order $order,
        string $reference,
        string $paymentMethod,
        float $amount)
    {
        $customerTransaction = new PaymentTransaction();
        $customerTransaction
            ->setEntityClass(Customer::class)
            ->setEntityIdentifier($order->getCustomer()->getId())
            ->setAction('credit')
            ->setAmount($amount)
            ->setActive(1)
            ->setSuccessful(1)
            ->setReference($reference)
            ->setCurrency('EUR')
            ->setPaymentMethod($paymentMethod);
        $this->entityManager->persist($customerTransaction);
        $this->entityManager->flush();
        return $customerTransaction;
    }

    /**
     * @param Order $order
     * @return void
     */
    public function generatePurchaseTransaction(Order $order)
    {
        $purchaseTransaction = $this->entityManager
            ->getRepository('OroPaymentBundle:PaymentTransaction')
            ->findOneBy([
                'entityClass' => Order::class,
                'entityIdentifier' => $order->getId(),
                'action' => 'purchase',
                'successful' => 1
            ]);
        if(is_null($purchaseTransaction)) {
            $purchaseTransaction = new PaymentTransaction();
            $purchaseTransaction
                ->setEntityClass(Order::class)
                ->setEntityIdentifier($order->getId())
                ->setAction('purchase')
                ->setAmount($order->getTotal())
                ->setActive(1)
                ->setSuccessful(1)
                ->setReference(null)
                ->setCurrency('EUR')
                ->setPaymentMethod('');
            $this->entityManager->persist($purchaseTransaction);
            $this->entityManager->flush();
        }
    }

    /**
     * @param Order $order
     * @return void
     */
    public function validatePurchaseTransaction(Order $order)
    {
        $purchaseTransaction = $this->entityManager
            ->getRepository('OroPaymentBundle:PaymentTransaction')
            ->findOneBy([
                'entityClass' => Order::class,
                'entityIdentifier' => $order->getId(),
                'action' => 'purchase',
                'successful' => 1
            ]);
        if(!is_null($purchaseTransaction)) {
            $purchaseTransaction->setActive(0);
            $this->entityManager->persist($purchaseTransaction);
            $this->entityManager->flush();
        }
    }

    /**
     * @param Order $order
     * @return void
     */
    public function generateInvoiceTransaction(Order $order)
    {
        $orderTransaction = new PaymentTransaction();
        $orderTransaction
            ->setEntityClass(Order::class)
            ->setEntityIdentifier($order->getId())
            ->setAction('invoice')
            ->setAmount($order->getTotal())
            ->setActive(1)
            ->setSuccessful(1)
            ->setReference(null)
            ->setCurrency('EUR')
            ->setPaymentMethod('');
        $this->entityManager->persist($orderTransaction);

        $customerTransaction = new PaymentTransaction();
        $customerTransaction
            ->setEntityClass(Customer::class)
            ->setEntityIdentifier($order->getCustomer()->getId())
            ->setAction('debit')
            ->setAmount($order->getTotal())
            ->setActive(1)
            ->setSuccessful(1)
            ->setReference(null)
            ->setCurrency('EUR')
            ->setPaymentMethod('');
        $this->entityManager->persist($customerTransaction);

        $this->entityManager->flush();
    }

}