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
namespace Nmdev\Bundle\GescomBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class NmdevGescomBundle implements Migration
{

    const DOCUMENT_TABLE_NAME = 'gescom_document';
    const CUSTOMER_BALANCE_TABLE_NAME = 'gescom_customer_balance';
    const DOCUMENT_HISTORY_TABLE_NAME = 'gescom_document_history';

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createDocumentTable($schema);
        $this->createDocumentHistoryTable($schema);
        $this->createCustomerBalanceTable($schema);
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function createDocumentTable(Schema $schema)
    {
        $table = $schema->createTable(self::DOCUMENT_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('order_id', 'integer', ['notnull' => false]);
        $table->addColumn('identifier', 'string', ['length' => 50]);
        $table->addColumn('draft', 'boolean', []);
        $table->addColumn('sent', 'boolean', []);
        $table->addColumn('type', 'string', ['length' => 30]);
        $table->addColumn('path', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        

        $table->addColumn(
            'title',
            'string',
            [
                'oro_options' => [
                    'extend'    => ['is_extend' => true, 'owner' => ExtendScope::OWNER_CUSTOM],
                ]
            ]
        );

        $table->setPrimaryKey(['id']);
        $table->addIndex(['identifier'], 'IDX_05526C098E502F9F', []);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order'),
            ['order_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function createDocumentHistoryTable(Schema $schema)
    {
        $table = $schema->createTable(self::DOCUMENT_HISTORY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('identifier', 'string', ['length' => 50]);
        $table->addColumn('sender', 'string', ['length' => 255]);
        $table->addColumn('recipient', 'string', ['length' => 255]);
        $table->addColumn('opened', 'boolean', []);
        $table->addColumn('message', 'text', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);

        $table->setPrimaryKey(['id']);

        $table->addForeignKeyConstraint(
            $schema->getTable(self::DOCUMENT_TABLE_NAME),
            ['gescom_document_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function createCustomerBalanceTable(Schema $schema)
    {
        $table = $schema->createTable(self::CUSTOMER_BALANCE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_id', 'integer', ['notnull' => false]);
        $table->addColumn('balance', 'float', ['notnull' => false]);
        $table->addColumn('creditLimit', 'float', ['notnull' => false]);
        $table->addColumn('currentPurchaseAmount', 'float', ['length' => 255]);

        $table->setPrimaryKey(['id']);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
