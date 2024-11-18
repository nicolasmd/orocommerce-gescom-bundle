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
namespace Nmdev\Bundle\GescomBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;


class TransactionType extends AbstractType {

    const NAME = 'nmdev_gescombundle_transaction';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
            ->add('payment_method', ChoiceType::class, [
                'choices' => [
                    'Chèque' => 'cheque',
                    'Carte bleue' => 'carte',
                    'Virement bancaire' => 'virement',
                    'Espèces' => 'especes'
                ],
                'empty_data' => 'cheque',
                'choices_as_values' => true,
                'label' => 'Mode de paiement'
            ])
            ->add('amount', NumberType::class, [
                'required' => true,
                'label' => 'Montant'
            ])
            ->add('reference', null, [
                'required' => true,
                'label' => 'Référence'
            ])
            ->add(
                'entity_identifier',
                CustomerSelectType::class,
                array('required' => true, 'label' => 'Compte client', 'mapped' => false)
            )
            ->add('created_at', OroDateType::class, [
                'label' => 'Date d\'encaissement',
                'data' => new \DateTime('now')
            ])
            ->add('owner', HiddenType::class)
        ;


    }

    /**
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(
            array(
                //'data_class' => 'Oro\Bundle\PaymentBundle\Entity\PaymentTransaction'
            )
        );
    }


    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

}
