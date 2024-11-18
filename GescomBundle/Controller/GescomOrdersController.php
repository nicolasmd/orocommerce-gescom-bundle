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
namespace Nmdev\Bundle\GescomBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Util\Codes;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\InvoiceBundle\Entity\Invoice;
use Oro\Bundle\InvoiceBundle\Entity\InvoiceLineItem;

use Oro\Bundle\EmailBundle\Form\Model\Email;

/**
 * @Route("/gescom_orders")
 */
class GescomOrdersController extends Controller {

    /**
     * Lists all Orders
     *
     * @Route("/", name="gescom.orders_index")
     * @Template("NmdevGescomBundle:GescomOrders:index.html.twig")
     * @AclAncestor("oro_order_view")
     * 
     */
    public function indexAction() {
        return array('gridName' => 'gescom-orders-grid');
    }
    
    /**
     * Lists all open Orders
     *
     * @Route("/open", name="gescom.orders_open")
     * @Template("NmdevGescomBundle:GescomOrders:open.html.twig")
     * @AclAncestor("oro_order_view")
     * 
     */
    public function openAction() {
        return array(
			'gridName' => 'gescom-orders-open-grid',
			'params' => [
				'actionsHideCount' => 10,
				'cellActionsHideCount' => 10
			]
		);
    }

    /**
     * Lists all closed Orders
     *
     * @Route("/closed", name="gescom.orders_closed")
     * @Template("NmdevGescomBundle:GescomOrders:closed.html.twig")
     * @AclAncestor("oro_order_view")
     *
     */
    public function closedAction() {
        return array('gridName' => 'gescom-orders-closed-grid');
    }
    
    
    /**
     * Lists all bdc
     *
     * @Route("/bdc", name="gescom.orders_bdc")
     * @Template("NmdevGescomBundle:GescomOrders:bdc.html.twig")
     * @AclAncestor("oro_order_view")
     * 
     */
    public function bdcAction() {
        return array('gridName' => 'gescom-orders-bdc-grid');
    }
    
    
    /**
     * Lists all Transactions
     *
     * @Route("/quotes", name="gescom.orders_quotes")
     * @Template("NmdevGescomBundle:GescomOrders:quotes.html.twig")
     * @AclAncestor("oro_order_view")
     * 
     */
    public function quotesAction() {
        return array('gridName' => 'gescom-quotes-grid');
    }


    /**
     * Lists all product sales
     *
     * @Route("/product_sales", name="gescom.product_sales")
     * @Template("NmdevGescomBundle:GescomOrders:product_sales.html.twig")
     * @AclAncestor("oro_order_view")
     *
     */
    public function productSalesAction() {
        return array('gridName' => 'gescom-product-sales-grid');
    }



    /**
     * Generates an invoice
     *
     * @Route("/generate_invoice/{id}", name="gescom.generate_invoice", requirements={"id":"\d+"}, defaults={"id":0})
     * @Template()
     * @AclAncestor("oro_order_view")
     *
     */
    public function generateInvoiceAction(
        Order $order,
        Request $request) {
        $entityManager = $this->getDoctrine()->getManager();
        $poNumber = $order->getPoNumber();
		$success = false;
        if (is_null($poNumber)) {
			$poNumber = $this->_getNextPoNumber();
			$order->setPoNumber($poNumber);
			$dueDate = new \DateTime();
			$dueDate->modify('+1 month');
			$order->setPaymentDueDate($dueDate);
			$entityManager->persist($order);
			$entityManager->flush();
			$success = true;
		}
		
		return new JsonResponse(
            array(
                'successful' => $success,
                'message' => ''
            ),
            Codes::HTTP_OK
        );
    }
    
    
    
    /**
     * Send order email
     *
     * @Route("/send_order_email/{id}", name="gescom.send_order_email", requirements={"id":"\d+"}, defaults={"id":0})
     * @Template()
     * @AclAncestor("oro_order_view")
     * 
     */
    public function sendOrderEmailAction(
        Order $order,
        Request $request) {
		$this->_sendEmail((string) 'nmdev_gescom.email_order_send', $order);
		return new JsonResponse(
            array(
                'successful' => true,
                'message' => ''
            ),
            Codes::HTTP_OK
        );
    }
    
    
    /**
     * Send relance email
     *
     * @Route("/send_relance_email/{id}", name="gescom.send_relance_email", requirements={"id":"\d+"}, defaults={"id":0})
     * @Template()
     * @AclAncestor("oro_order_view")
     * 
     */
    public function sendRelanceEmailAction(Order $order, Request $request) {
		$this->_sendEmail('nmdev_gescom.email_payment_first_relaunch', $order);
		return new JsonResponse(
            array(
                'successful' => true,
                'message' => ''
            ),
            Codes::HTTP_OK
        );
    }
    
    
    /**
     * Send invoice email
     *
     * @Route("/send_invoice_email/{id}", name="gescom.send_invoice_email", requirements={"id":"\d+"}, defaults={"id":0})
     * @Template()
     * @AclAncestor("oro_order_view")
     * 
     */
    public function sendInvoiceEmailAction(Order $order, Request $request) {
		$this->_sendEmail('nmdev_gescom.email_invoice_send', $order);
		return new JsonResponse(
            array(
                'successful' => true,
                'message' => ''
            ),
            Codes::HTTP_OK
        );
    }


    /**
     * Send email to customer
     *
     * @param $templateName
     * @param $order
     * @return true
     */
    private function _sendEmail($templateName, $order) {
		$config = $this->container->get('oro_config.global');
                $email = new Email();
        $email->setFrom($config->get('nmdev_gescom.sender_email'));
        $email->setTo($config->get('nmdev_gescom.mode') == 'test' ?
            explode(';', $config->get('nmdev_gescom.email_test')) :
            [$order->getCustomerUser()->getEmail()]);

        $template = $this->getDoctrine()
            ->getManager()
            ->getRepository("OroEmailBundle:EmailTemplate")
            ->findByName((string)$config->get($templateName));

        $templateData = $this->container->get('oro_email.email_renderer')->compileMessage($template, [
            'entity' => $order,
        ]);

        list ($subjectRendered, $templateRendered) = $templateData;
        $email->setSubject($subjectRendered);
        $email->setBody($templateRendered);
        $email->setType('html');
        $this->container->get('oro_email.mailer.processor')->process($email);
		return true;
	}


    /**
     * Generates next purchase order number
     *
     * @return string
     */
    private function _getNextPoNumber() {
		$config = $this->get('oro_config.global');
		$prefix = $config->get('nmdev_gescom.client_code') . '_' .
				$config->get('nmdev_gescom.invoice_prefix') . date('Y');
		$increment = $config->get('nmdev_gescom.invoice_id');
		$orderNumber = $prefix . sprintf('%08d', $increment);
		$config->set('nmdev_gescom.invoice_id', ++$increment);
		$config->flush();
		return $orderNumber;
	}
	
    
}
