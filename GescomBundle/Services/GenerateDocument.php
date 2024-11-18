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
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Nmdev\Bundle\GescomBundle\Entity\GescomDocument;

class GenerateDocument
{
    /** @var EntityManager **/
    private EntityManager $entityManager;

    /** @var WorkflowManager **/
    private WorkflowManager $workflowManager;

    /** @var Container **/
    private Container $container;

    public function __construct(
        Container $container,
        EntityManager $entityManager,
        WorkflowManager $workflowManager)
    {
        $this->container= $container;
        $this->entityManager = $entityManager;
        $this->workflowManager = $workflowManager;

        $this->config = $this->container->get('oro_config.global');
        $this->taxManager = $this->container->get('oro_tax.manager.tax_value_manager');
    }

    /**
     * @param Order $order
     * @return void
     */
    public function createPurchaseInvoice(Order $order)
    {
        $document = $this->_getDocument($order, 'purchase');
        $document
                ->setDraft(1)
                ->setIdentifier('BDC_tmp' . $order->getId())
                ->setSent(0);
        $data = $this->_getOrderData($order, 'BDC');
        $path = $this->_curlExec($data);
        $document->setPath($path);
        $this->entityManager->persist($document);
        $this->entityManager->flush();
    }

    /**
     * @param Order $order
     * @return void
     */
    public function validatePurchaseInvoice(Order $order)
    {
        $document = $this->_getDocument($order, 'purchase');
        $document
            ->setDraft(0)
            ->setSent(0);
        $data = $this->_getOrderData($order, 'BDC');
        $path = $this->_curlExec($data);
        $document->setPath($path);
        $this->entityManager->persist($document);
        $this->entityManager->flush();
    }

    /**
     * @param Order $order
     * @return void
     */
    public function createInvoice(Order $order)
    {
        $document = $this->_getDocument($order, 'invoice');
        $document
            ->setDraft(0)
            ->setIdentifier($order->getId())
            ->setSent(0);
        $data = $this->_getOrderData($order, 'FAC');
        $path = $this->_curlExec($data);
        $document->setPath($path);

        $this->entityManager->persist($document);
        $this->entityManager->flush();
    }


    /**
     * @param $data
     * @return bool|string
     */
    private function _curlExec($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$this->config->get('nmdev_gescom.supervision_url'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $serverOutput = curl_exec ($ch);
        curl_close ($ch);
        return $serverOutput;
    }

    /**
     * @param $order
     * @param $type
     * @return GescomDocument
     */
    private function _getDocument($order, $type) {
        $document = $this->entityManager
            ->getRepository('NmdevGescomBundle:GescomDocument')
            ->findOneBy([
                'order' => $order,
                'type' => $type
            ]);
        if(is_null($document)) {
            $document = new GescomDocument();
            $document
                ->setType($type)
                ->setSent(0)
                ->setOrder($order);
        }
        return $document;
    }


    /**
     * @param Order $order
     * @param $type
     * @return array
     */
    private function _getOrderData(Order $order, $type)
    {
        $referenceNumber = null;
        switch($type) {
            case 'FAC':
                $referenceNumber = $order->getPoNumber();
                break;
            case 'BDC':
                $referenceNumber = $order->getIdentifier();
                break;
        }

        $customerBillingAddress = $order->getBillingAddress();
        $data = [
            'id' => 'otvd',
            'type' => $type,
            'reference' => $referenceNumber
        ];

        $data = $data + [
            'clientName' => $customerBillingAddress->getOrganization() != '' && !is_null($customerBillingAddress->getOrganization()) ?
                $customerBillingAddress->getOrganization() :
                $customerBillingAddress->getFirstName() . ' ' . $customerBillingAddress->getLastName(),
            'clientAddress1' => $customerBillingAddress->getStreet() != '' ? $customerBillingAddress->getStreet() : '-',
            'clientCity' => $customerBillingAddress->getCity(),
            'clientPostalCode' => $customerBillingAddress->getPostalCode(),
            'clientOrganization' => $customerBillingAddress->getOrganization(),
            'clientCountry' => $customerBillingAddress->getCountry()->getName(),
            'items' => [],
        ];

        foreach ($order->getLineItems() as $item) {
            $totalTax = 0;
            $taxValues = $this->taxManager->getTaxValue(OrderLineItem::class, $item->getId())
                ->getResult()->getTaxes();

            foreach ($taxValues as $taxValue) {
                $totalTax += $taxValue->getTaxAmount();
            }

            $productName = $item->getProduct()->getName();
            $data['items'][] = [
                'sku' => '' . $item->getProduct()->getSku(),
                'name' => '' . $productName,
                'description' => $item->getComment(),
                'quantity' => $item->getQuantity(),
                'amount' => round($item->getValue(), 4),
                'vat' => round($taxValue , 4),
                'total' => round($totalTax + $item->getValue(), 4),
                'discount' => 0,
            ];
        }

        if(count($data['items']) == 0) {
            $productName = 'Commande n° ' . $referenceNumber;
            $data['items'][] = [
                'name' => '' . $productName,
                'description' => '',
                'quantity' => 1,
                'amount' => round($order->getSubtotal(), 4),
                'vat' => round($this->taxManager->getTaxValue(Order::class, $order->getId()), 4),
                'total' => round($order->getTotal(), 4),
                'discount' => 0,
            ];
        }


        $totalDiscount = $order->getTotalDiscounts();
        $data['totalDiscount'] = round($totalDiscount, 2);
        $data['createdAt'] = $order->getCreatedAt()->format("d-m-Y");
        $data['dueDate'] = $order->getPaymentDueDate() ?
            $order->getPaymentDueDate()->format("d-m-Y") :
            $order->getCreatedAt()->modify('+30 days')->format("d-m-Y");
        return $data;
    }


}