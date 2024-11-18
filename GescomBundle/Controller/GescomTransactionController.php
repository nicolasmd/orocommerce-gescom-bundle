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

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Util\Codes;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * @Route("/gescom_transaction")
 */
class GescomTransactionController extends Controller {

    /**
     * Lists all Transactions
     *
     * @Route("/", name="gescom.transaction_index")
     * @Template("NmdevGescomBundle:GescomTransaction:index.html.twig")
     * @AclAncestor("oro_order_view")
     * 
     */
    public function indexAction() {
        return array('gridName' => 'gescom-transactions-grid');
    }
    
    
    /**
     * Creates a new Transaction
     *
     * @Route("/create", name="gescom.transaction_create")
     * @Template("NmdevGescomBundle:GescomTransaction:update.html.twig")
     */
    public function createAction(Request $request) {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setActive(1);
        $paymentTransaction->setCurrency('EUR');
        $paymentTransaction->setOwner(new User());
        $paymentTransaction->setEntityIdentifier(0);
        return $this->update($paymentTransaction, $request);
    }


    /**
     * Lists all pending Payments
     *
     * @Route("/pending", name="gescom.invoices_pending")
     * @Template("NmdevGescomBundle:GescomInvoices:pending.html.twig")
     * @AclAncestor("oro_order_view")
     *
     */
    public function paymentsAction() {
        return array('gridName' => 'gescom-pending-payments-grid');
    }


   /**
     * Delete a transaction
     *
     * @Route("/delete/{id}", name="gescom.transaction_delete")
     * @Template()
     * @Acl(
     *      id="oro_order_create",
     *      type="entity",
     *      class="OroOrderBundle:Order",
     *      permission="CREATE"
     * )
     */
    public function deleteAction(PaymentTransaction $paymentTransaction) {
        $messages = [];
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($paymentTransaction);
        $entityManager->flush();
        return new JsonResponse(
            array(
                'successful' => true,
                'message' => implode("\n<br />", $messages)
            ),
            Codes::HTTP_OK
        );
    }


   /**
     * Edits an existing Transaction entity.
     *
     * @Route("/update/{id}", name="gescom.transaction_update", requirements={"id":"\d+"}, defaults={"id":0})
     * @Template()
     * @Acl(
     *      id="oro_order_create",
     *      type="entity",
     *      class="OroOrderBundle:Order",
     *      permission="CREATE"
     * )
     */
    public function updateAction(PaymentTransaction $paymentTransaction, Request $request) {
        return $this->update($paymentTransaction, $request);
    }


    /**
     * @param PaymentTransaction $paymentTransaction
     * @param Request $request
     * @return array
     */
    private function update(PaymentTransaction $paymentTransaction, Request $request) {
        $form = $this->createForm('Nmdev\Bundle\GescomBundle\Form\Type\TransactionType', $paymentTransaction);
        $form->handleRequest($request);
        $saved = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $data = $request->request->all();
            if(isset($data['nmdev_gescombundle_transaction'])) {
                $paymentTransaction->setPaymentMethod($data['nmdev_gescombundle_transaction']['payment_method']);
                $paymentTransaction->setAmount(str_replace(',', '.', $data['nmdev_gescombundle_transaction']['amount']));
                $paymentTransaction->setActive(0);
                $paymentTransaction->setCurrency('EUR');
                $paymentTransaction->setOwner();
                $paymentTransaction->setSuccessful(true);
                $paymentTransaction->setReference($data['nmdev_gescombundle_transaction']['reference']);
                $paymentTransaction->setAction(PaymentMethodInterface::PURCHASE);
                $paymentTransaction->setEntityClass('Oro\Bundle\CustomerBundle\Entity\Customer');
                $d = $data['nmdev_gescombundle_transaction']['created_at'];
                $paymentTransaction->setCreatedAt(new \DateTime("$d"));
                $paymentTransaction->setEntityIdentifier(intval($data['nmdev_gescombundle_transaction']['entity_identifier']));

                $entityManager->persist($paymentTransaction);
                $entityManager->flush();
                $saved = true;
            }
        }

        return [
            'entity' => $paymentTransaction,
            'form' => $form->createView(),
            'saved' => $saved
        ];
    }
    
}
