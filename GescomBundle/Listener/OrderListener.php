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
namespace Nmdev\Bundle\GescomBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\SalesBundle\Entity\Customer;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Nmdev\Bundle\GescomBundle\Services\PaymentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Oro\Bundle\OrderBundle\Entity\Order;

class OrderListener
{

    /** @var EntityManager **/
    private EntityManager $entityManager;

    /** @var WorkflowManager **/
    private WorkflowManager $workflowManager;

    /** @var Container **/
    private Container $container;

    /**
     * @param Container $container
     * @param EntityManager $entityManager
     * @param WorkflowManager $workflowManager
     * @param PaymentManager $paymentManager
     */
    public function __construct(
        Container $container,
        EntityManager $entityManager,
        WorkflowManager $workflowManager,
        PaymentManager $paymentManager) {
        $this->container= $container;
        $this->entityManager = $entityManager;
        $this->workflowManager = $workflowManager;
        $this->paymentManager = $paymentManager;
    }

    /**
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if($entity instanceof Order) {
            $this->_setApplicableWorkflow($entity);

        }

        if($entity instanceof PaymentTransaction) {
            if($entity->getEntityClass() == Order::class) {
                $order = $this->entityManager
                    ->getRepository('OroOrderBundle:Order')
                    ->find($entity->getEntityIdentifier());
                if(!is_null($order)) {
                    $this->paymentManager->calculateCustomerBalance($order);
                }
            }
        }

    }

    /**
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if($entity instanceof PaymentTransaction) {
            if($entity->getEntityClass() == Order::class) {
                $order = $this->entityManager
                    ->getRepository('OroOrderBundle:Order')
                    ->find($entity->getEntityIdentifier());
                if(!is_null($order)) {
                    $this->paymentManager->calculateCustomerBalance($order);
                }
            }
        }
    }

    /**
     * @param Order $order
     * @return void
     */
    private function _setApplicableWorkflow(Order $order)
    {
        if(is_null($this->workflowManager->getWorkflowItem($order, 'order_bdc'))) {
            $workflowItem = $this->workflowManager->startWorkflow('order_bdc', $order);
            $this->workflowManager->transit($workflowItem, 't_start');
        }
    }



}
