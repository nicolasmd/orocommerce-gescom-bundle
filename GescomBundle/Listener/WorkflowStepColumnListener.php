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

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowDefinitionSelectType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowStepSelectType;

use Oro\Bundle\WorkflowBundle\Datagrid\WorkflowStepColumnListener as BaseWorkflowStepColumnListener;

class WorkflowStepColumnListener extends BaseWorkflowStepColumnListener
{
    
    /**
     * @param DatagridInterface $datagrid
     * @param string $filter
     * @param string $repositoryMethod
     */
    protected function applyFilter(DatagridInterface $datagrid, $filter, $repositoryMethod)
    {
        $parameters = $datagrid->getParameters();
        $filters = $parameters->get('_filter', []);
        
        $default = $datagrid->getConfig()->offsetGetByPath('[filters]', []);
        if(array_key_exists('default', $default)) {
            $default = $datagrid->getConfig()->offsetGetByPath('[filters][default]', []);
            if(array_key_exists($filter, $default) && array_key_exists('value', $default[$filter])) {
                $filters[$filter] = $default[$filter];
            }
        }
        
        if (array_key_exists($filter, $filters) && array_key_exists('value', $filters[$filter])) {
            $rootEntity = $datagrid->getConfig()->getOrmQuery()->getRootEntity($this->entityClassResolver);
            $rootEntityAlias = $datagrid->getConfig()->getOrmQuery()->getRootAlias();
            $items = $this->getWorkflowItemRepository()
                ->$repositoryMethod($rootEntity, (array)$filters[$filter]['value']);
            /** @var OrmDatasource $datasource */
            $datasource = $datagrid->getDatasource();
            $qb = $datasource->getQueryBuilder();
            $param = $qb->getParameter('filteredWorkflowItemIds');
                if ($param === null) {
                $qb->andWhere($qb->expr()->in($rootEntityAlias, ':filteredWorkflowItemIds'))
                    ->setParameter('filteredWorkflowItemIds', $items);
            } else {
                $qb->setParameter('filteredWorkflowItemIds', array_intersect((array)$param->getValue(), $items));
            }
            unset($filters[$filter]);
            $parameters->set('_filter', $filters);
        }

        // Remove workflow step
        $config = $datagrid->getConfig();
        $this->removeWorkflowFilters($config, [$filter]);
    }



    /**
     * @param DatagridConfiguration $config
     * @param array $workflowStepColumns
     */
    protected function removeWorkflowFilters(DatagridConfiguration $config, array $workflowStepColumns)
    {
        $paths = [
            '[filters][default]'
        ];

        foreach ($paths as $path) {
            $columns = $config->offsetGetByPath($path, []);
            foreach ($workflowStepColumns as $column) {
                if (!empty($columns[$column])) {
                     unset($columns[$column]);
                }
            }
            $config->offsetSetByPath($path, $columns);
        }
    }



    /**
     * @param DatagridConfiguration $config
     * @param string $rootEntity
     * @param string $rootEntityAlias
     */
    protected function addWorkflowStep(DatagridConfiguration $config, $rootEntity, $rootEntityAlias)
    {
        // add column
        $columns = $config->offsetGetByPath('[columns]', []);
        $columns[self::WORKFLOW_STEP_COLUMN] = [
            'label' => 'oro.workflow.workflowstep.grid.label',
            'type' => 'twig',
            'frontend_type' => 'html',
            'template' => 'OroWorkflowBundle:Datagrid:Column/workflowStep.html.twig',
            'className' => 'statusColumn'
        ];
        $config->offsetSetByPath('[columns]', $columns);

        $isManyWorkflows = $this->isEntityHaveMoreThanOneWorkflow($rootEntity);

        // add filter (only if there is at least one filter)
        $filters = $config->offsetGetByPath('[filters][columns]', []);
        if ($filters) {
            if ($isManyWorkflows) {
                $filters[self::WORKFLOW_FILTER] = [
                    'label' => 'oro.workflow.workflowdefinition.entity_label',
                    'type' => 'entity',
                    'data_name' => self::WORKFLOW_STEP_COLUMN,
                    'options' => [
                        'field_type' => WorkflowDefinitionSelectType::NAME,
                        'field_options' => [
                            'workflow_entity_class' => $rootEntity,
                            'multiple' => true
                        ]
                    ]
                ];
            }

            $filters[self::WORKFLOW_STEP_FILTER] = [
                'label' => 'oro.workflow.workflowstep.grid.label',
                'type' => 'entity',
                'data_name' => self::WORKFLOW_STEP_COLUMN . '.id',
                'options' => [
                    'field_type' => WorkflowStepSelectType::NAME,
                    'field_options' => [
                        'workflow_entity_class' => $rootEntity,
                        'multiple' => true,
                        'translatable_options' => false
                    ]
                ]
            ];
            $config->offsetSetByPath('[filters][columns]', $filters);
        }
    }


}
