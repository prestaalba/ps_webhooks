<?php
/**
 * PrestaShop Webhooks - MCP Tools
 *
 * @author    Experto PrestaShop <https://www.youtube.com/@ExpertoPrestaShop>
 * @copyright since 2009 Experto PrestaShop
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License ("AFL") v. 3.0
 */

namespace ExpertoPrestaShop\Mcp;

if (!defined('_PS_VERSION_')) {
    exit;
}

class WebhookMcpTools
{
    #[\PhpMcp\Server\Attributes\McpTool(
        name: 'create_webhook',
        description: 'Create a new webhook to trigger notifications on PrestaShop events (add, update, delete) for specific entities.'
    )]
    #[\PhpMcp\Server\Attributes\Schema(
        properties: [
            'action' => ['type' => 'string', 'enum' => ['add', 'update', 'delete'], 'description' => 'Action that triggers the webhook'],
            'entity' => ['type' => 'string', 'description' => 'Entity class name (e.g., Product, Order, Customer)'],
            'url' => ['type' => 'string', 'description' => 'Remote URL to send the webhook notification'],
            'description' => ['type' => 'string', 'description' => 'Optional description of the webhook', 'default' => ''],
            'active' => ['type' => 'boolean', 'description' => 'Enable or disable the webhook', 'default' => true]
        ],
        required: ['action', 'entity', 'url']
    )]
    public function createWebhook(string $action, string $entity, string $url, string $description = '', bool $active = true): string
    {
        try {
            $webhook = new \Webhook();
            $webhook->action = $action;
            $webhook->entity = $entity;
            $webhook->url = $url;
            $webhook->description = $description;
            $webhook->active = $active;

            if ($webhook->add()) {
                $module = \Module::getInstanceByName('ps_webhooks');
                $module->registerHook($webhook->getHookName());
                
                return json_encode([
                    'success' => true,
                    'id_webhook' => $webhook->id,
                    'message' => 'Webhook created successfully'
                ]);
            }

            return json_encode(['success' => false, 'error' => 'Failed to create webhook']);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[\PhpMcp\Server\Attributes\McpTool(
        name: 'list_webhooks',
        description: 'List all configured webhooks with their details (action, entity, URL, status).'
    )]
    public function listWebhooks(): string
    {
        try {
            $webhooks = \Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'webhook');
            
            return json_encode([
                'success' => true,
                'webhooks' => $webhooks,
                'count' => count($webhooks)
            ]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[\PhpMcp\Server\Attributes\McpTool(
        name: 'update_webhook',
        description: 'Update an existing webhook configuration.'
    )]
    #[\PhpMcp\Server\Attributes\Schema(
        properties: [
            'id_webhook' => ['type' => 'integer', 'description' => 'Webhook ID to update'],
            'action' => ['type' => 'string', 'enum' => ['add', 'update', 'delete'], 'description' => 'Action that triggers the webhook'],
            'entity' => ['type' => 'string', 'description' => 'Entity class name'],
            'url' => ['type' => 'string', 'description' => 'Remote URL'],
            'description' => ['type' => 'string', 'description' => 'Description'],
            'active' => ['type' => 'boolean', 'description' => 'Enable or disable']
        ],
        required: ['id_webhook']
    )]
    public function updateWebhook(int $id_webhook, ?string $action = null, ?string $entity = null, ?string $url = null, ?string $description = null, ?bool $active = null): string
    {
        try {
            $webhook = new \Webhook($id_webhook);
            if (!\Validate::isLoadedObject($webhook)) {
                return json_encode(['success' => false, 'error' => 'Webhook not found']);
            }

            if ($action !== null) $webhook->action = $action;
            if ($entity !== null) $webhook->entity = $entity;
            if ($url !== null) $webhook->url = $url;
            if ($description !== null) $webhook->description = $description;
            if ($active !== null) $webhook->active = $active;

            if ($webhook->update()) {
                $module = \Module::getInstanceByName('ps_webhooks');
                $module->registerHook($webhook->getHookName());
                
                return json_encode(['success' => true, 'message' => 'Webhook updated successfully']);
            }

            return json_encode(['success' => false, 'error' => 'Failed to update webhook']);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[\PhpMcp\Server\Attributes\McpTool(
        name: 'delete_webhook',
        description: 'Delete a specific webhook by ID.'
    )]
    #[\PhpMcp\Server\Attributes\Schema(
        properties: [
            'id_webhook' => ['type' => 'integer', 'description' => 'Webhook ID to delete']
        ],
        required: ['id_webhook']
    )]
    public function deleteWebhook(int $id_webhook): string
    {
        try {
            $webhook = new \Webhook($id_webhook);
            if (!\Validate::isLoadedObject($webhook)) {
                return json_encode(['success' => false, 'error' => 'Webhook not found']);
            }

            $module = \Module::getInstanceByName('ps_webhooks');
            $module->unregisterHook($webhook->getHookName());
            
            if ($webhook->delete()) {
                return json_encode(['success' => true, 'message' => 'Webhook deleted successfully']);
            }

            return json_encode(['success' => false, 'error' => 'Failed to delete webhook']);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[\PhpMcp\Server\Attributes\McpTool(
        name: 'test_webhook',
        description: 'Test webhook connection by sending a test request to verify the URL is reachable.'
    )]
    #[\PhpMcp\Server\Attributes\Schema(
        properties: [
            'id_webhook' => ['type' => 'integer', 'description' => 'Webhook ID to test']
        ],
        required: ['id_webhook']
    )]
    public function testWebhook(int $id_webhook): string
    {
        try {
            $webhook = new \Webhook($id_webhook);
            if (!\Validate::isLoadedObject($webhook)) {
                return json_encode(['success' => false, 'error' => 'Webhook not found']);
            }

            \Module::getInstanceByName('ps_webhooks');
            $code = \Ps_Webhooks::executeUrl($webhook->url, $webhook->action, $webhook->entity, new $webhook->entity, true);

            if ($code >= 200 && $code < 300) {
                return json_encode([
                    'success' => true,
                    'http_code' => $code,
                    'message' => 'Webhook connection tested successfully'
                ]);
            }

            return json_encode([
                'success' => false,
                'http_code' => $code,
                'error' => 'Failed to connect to webhook URL'
            ]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    #[\PhpMcp\Server\Attributes\McpTool(
        name: 'get_available_entities',
        description: 'Get list of available PrestaShop entities that can be used for webhooks (Product, Order, Customer, etc.).'
    )]
    public function getAvailableEntities(): string
    {
        try {
            $resources = \WebserviceRequest::getResources();
            $resources = array_filter($resources, function ($resource) {
                return !empty($resource['class']);
            });
            $entities = array_column($resources, 'class');

            return json_encode([
                'success' => true,
                'entities' => array_values($entities),
                'count' => count($entities)
            ]);
        } catch (\Exception $e) {
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
