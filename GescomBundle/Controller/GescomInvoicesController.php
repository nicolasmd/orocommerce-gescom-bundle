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
 * @Route("/gescom_invoices")
 */
class GescomInvoicesController extends Controller {

    /**
     * Lists all customer balances
     *
     * @Route("/", name="gescom.invoices_index")
     * @Template("NmdevGescomBundle:GescomInvoices:index.html.twig")
     * @AclAncestor("oro_order_view")
     * 
     */
    public function indexAction() {
        return array('gridName' => 'gescom-invoices-grid');
    }
    
    
    /**
     * View invoice
     *
     * @Route("/view/{id}", name="gescom.invoice_view", requirements={"id":"\d+"}, defaults={"id":0})
     * 
     */
    public function viewAction() {
       
    }
    
}
