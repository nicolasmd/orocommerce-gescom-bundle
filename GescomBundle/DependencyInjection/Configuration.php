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
namespace Nmdev\Bundle\GescomBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('nmdev_gescom');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        SettingsBuilder::append(
            $rootNode,
            [
                'quote_id'     => ['value' => 1],
                'invoice_id'     => ['value' => 1],
                'order_id'     => ['value' => 1],
                'credit_id'     => ['value' => 1],
                'quote_prefix'     => ['value' => 'DEV'],
                'invoice_prefix'     => ['value' => 'FAC'],
                'order_prefix'     => ['value' => 'BDC'],
                'credit_prefix'     => ['value' => 'AVO'],
                'client_code'     => ['value' => 'VOTRE_CODE'],
                'supervision_url'     => ['value' => 'https://your_url'],
                'mode'     => ['value' => 'test'],
                'email_test'     => ['value' => 'you@yourdomain.com'],
                'email_quote_send' => ['value' => 'email_quote_send'],
                'email_quote_relaunch' => ['value' => 'email_quote_relaunch'],
                'email_order_send' => ['value' => 'email_order_send'],
                'email_invoice_send' => ['value' => 'email_invoice_send'],
                'email_payment_pending' => ['value' => 'email_payment_pending'],
                'email_payment_first_after' => ['value' => 'email_payment_first_after'],
                'email_payment_second_after' => ['value' => 'email_payment_second_after'],
                'email_customer_balance_send' => ['value' => 'email_customer_balance_send'],
                'sender_email' => ['value' => 'you@yourdomain.com'],
            ]
        );

        return $treeBuilder;
    }
}
