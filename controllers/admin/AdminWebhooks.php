<?php
/**
 * PrestaShop Webhooks
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file docs/licenses/LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 *
 * @author    Experto PrestaShop <https://www.youtube.com/@ExpertoPrestaShop>
 * @copyright since 2009 Experto PrestaShop
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License ("AFL") v. 3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminWebhooksController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'webhook';
        $this->className = 'Webhook';
        $this->context = Context::getContext();
        $this->lang = false;
        $this->allow_export = false;
        $this->bootstrap = true;

        parent::__construct();

        $this->bulk_actions = [
            'delete' => ['text' => $this->module->l('Delete selected', 'AdminWebhooks'), 'confirm' => $this->module->l('Delete selected items?', 'AdminWebhooks')],
            'enableSelection' => ['text' => $this->module->l('Enable selection', 'AdminWebhooks')],
            'disableSelection' => ['text' => $this->module->l('Disable selection', 'AdminWebhooks')],
        ];

        $resources = WebserviceRequest::getResources();
        $resources = array_filter($resources, function ($resource) {
            return !empty($resource['class']);
        });
        $list = array_column($resources, 'class');
        $entities = array_combine($list, $list);
        $actions = [
            'add' => $this->module->l('Created', 'AdminWebhooks'),
            'update' => $this->module->l('Updated', 'AdminWebhooks'),
            'delete' => $this->module->l('Deleted', 'AdminWebhooks'),
        ];

        $this->fields_list = [
            'action' => [
                'title' => $this->module->l('Action', 'AdminWebhooks'),
                'type' => 'select',
                'list' => $actions,
                'filter_key' => 'a!action',
                'filter_type' => 'string',
            ],
            'entity' => [
                'title' => $this->module->l('Entity', 'AdminWebhooks'),
                'type' => 'select',
                'list' => $entities,
                'filter_key' => 'a!entity',
                'filter_type' => 'string',
            ],
            'url' => [
                'title' => $this->module->l('URL', 'AdminWebhooks'),
                'align' => 'left',
                'orderby' => false,
            ],
            'description' => [
                'title' => $this->module->l('Description', 'AdminWebhooks'),
                'align' => 'left',
                'orderby' => false,
            ],
            'active' => [
                'title' => $this->module->l('Enabled', 'AdminWebhooks'),
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
                'width' => 32,
            ],
        ];

        $this->fields_form = [
            'legend' => [
                'title' => $this->module->l('Webhook', 'AdminWebhooks'),
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $this->module->l('Action', 'AdminWebhooks'),
                    'name' => 'action',
                    'required' => true,
                    'options' => [
                        'query' => [
                            ['id' => 'add', 'name' => $this->module->l('Created', 'AdminWebhooks')],
                            ['id' => 'update', 'name' => $this->module->l('Updated', 'AdminWebhooks')],
                            ['id' => 'delete', 'name' => $this->module->l('Deleted', 'AdminWebhooks')],
                        ],
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ],
                [
                    'type' => 'select',
                    'label' => $this->module->l('Entity', 'AdminWebhooks'),
                    'name' => 'entity',
                    'required' => true,
                    'options' => [
                        'query' => $resources,
                        'id' => 'class',
                        'name' => 'class',
                    ],
                ],
                [
                    'type' => 'text',
                    'label' => $this->module->l('URL', 'AdminWebhooks'),
                    'name' => 'url',
                    'size' => 100,
                    'required' => true,
                    'desc' => $this->module->l('Remote URL.', 'AdminWebhooks'),
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->module->l('Description', 'AdminWebhooks'),
                    'name' => 'description',
                    'rows' => 3,
                    'cols' => 110,
                ],
                [
                    'type' => 'switch',
                    'label' => $this->module->l('Status', 'AdminWebhooks'),
                    'name' => 'active',
                    'required' => false,
                    'class' => 't',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->module->l('Enabled', 'AdminWebhooks'),
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->module->l('Disabled', 'AdminWebhooks'),
                        ],
                    ],
                ],
            ],
            'submit' => [
                'title' => $this->module->l('Save', 'AdminWebhooks'),
            ],
        ];

        $this->actions_available[] = 'test';
        $this->addRowAction('test');
        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        if ($this->display != 'edit' && $this->display != 'add') {
            $this->page_header_toolbar_btn['new_webhook'] = [
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => $this->module->l('Add new', 'AdminWebhooks'),
                'icon' => 'process-icon-new',
            ];
        }
    }

    public function displayTestLink($token, $id, $name = null)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_default.tpl');
        if (!array_key_exists('Test', self::$cache_lang)) {
            self::$cache_lang['Test'] = $this->module->l('Test', 'AdminWebhooks');
        }

        $tpl->assign([
            'href' => $this->context->link->getAdminLink('AdminWebhooks') . '&' . $this->identifier . '=' . $id . '&action=test',
            'action' => self::$cache_lang['Test'],
            'id' => $id,
        ]);

        return $tpl->fetch();
    }

    public function processTest()
    {
        if ($id = (int) Tools::getValue('id_webhook')) {
            $webhook = new Webhook($id);

            $code = Ps_Webhooks::executeUrl($webhook->url, $webhook->action, $webhook->entity, new $webhook->entity, true);

            if ($code < 300 && $code != 0) {
                $this->confirmations[] = sprintf($this->module->l('Webhook connection tested : HTTP %1$d', 'AdminWebhooks'), $code);

                return true;
            } else {
                $this->errors[]  = sprintf($this->module->l('Is not possible to connect to this URL : %s HTTP Error : %d', 'AdminWebhooks'), $webhook->url, $code);

                return false;
            }
        }

        $this->errors[] = $this->module->l('Is not possible to test the connection to this URL', 'AdminWebhooks');

        return false;
    }

    protected function beforeDelete($object)
    {
        $this->module->unregisterHook($object->getHookName());
        return true;
    }

    protected function afterAdd($object)
    {
        $this->module->registerHook($object->getHookName());
        return true;
    }

    protected function afterUpdate($object)
    {
        $this->module->registerHook($object->getHookName());
        return true;
    }
}
