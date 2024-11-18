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
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class OrderManager
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
     */
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
     * @return string
     */
    public function generatePoNumber(Order $order)
    {
        $config = $this->container->get('oro_config.global');
        $prefix = $config->get('nmdev_gescom.client_code') . '_' .
            $config->get('nmdev_gescom.order_prefix') . date('Y');
        $increment = $config->get('nmdev_gescom.order_id');
        $orderNumber = $prefix . sprintf('%08d', $increment);
        $config->set('nmdev_gescom.order_id', ++$increment);
        $config->flush();
        $order->setPoNumber($orderNumber);
        $this->entityManager->persist($order);
        $this->entityManager->flush();
        return $orderNumber;
    }

    /**
     * @param Order $order
     * @return string
     */
    public function generateInvoiceNumber(Order $order)
    {
        $config = $this->container->get('oro_config.global');
        $prefix = $config->get('nmdev_gescom.client_code') . '_' .
            $config->get('nmdev_gescom.invoice_prefix') . date('Y');
        $increment = $config->get('nmdev_gescom.invoice_id');
        $orderNumber = $prefix . sprintf('%08d', $increment);
        $config->set('nmdev_gescom.invoice_id', ++$increment);
        $config->flush();
        $order->setIdentifier($orderNumber);
        $this->entityManager->persist($order);
        $this->entityManager->flush();
        return $orderNumber;
    }


}