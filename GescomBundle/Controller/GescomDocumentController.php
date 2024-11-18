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

/**
 * @Route("/gescom_document")
 */
class GescomDocumentController extends Controller {

    /**
     * View document
     *
     * @Route("/view", name="gescom.document_view")
     * @Template()
     * @AclAncestor("oro_order_view")
     *
     */
    public function viewAction() {

    }


    /**
     * Lists all purchases
     *
     * @Route("/purchases", name="gescom.document_purchases")
     * @Template("NmdevGescomBundle:GescomDocument:purchases.html.twig")
     * @AclAncestor("oro_order_view")
     *
     */
    public function purchaseAction() {
        return array('gridName' => 'gescom-document-purchases');
    }



    /**
     * Lists all invoices
     *
     * @Route("/invoices", name="gescom.document_invoices")
     * @Template("NmdevGescomBundle:GescomDocument:invoices.html.twig")
     * @AclAncestor("oro_order_view")
     *
     */
    public function invoicesAction() {
        return array('gridName' => 'gescom-document-invoices');
    }
}
