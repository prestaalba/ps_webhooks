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

/**
 * Webhook class
 */
class Webhook extends ObjectModel
{
    /**
     * @var string Action type
     */
    public $action;

    /**
     * @var string Entity name
     */
    public $entity;

    /**
     * @var string Description of the webhook
     */
    public $description;

    /**
     * @var string URL to send the webhook
     */
    public $url;

    /**
     * @var bool Active status of the webhook
     */
    public $active = true;

    /**
     * @var array Definition of the webhook object
     */
    public static $definition = [
        'table' => 'webhook',
        'primary' => 'id_webhook',
        'fields' => [
            'action' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 10],
            'entity' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 100],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'description' => ['type' => self::TYPE_STRING],
            'url' => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 200, 'validate' => 'isAbsoluteUrl'],
        ],
    ];

    /**
     * Get webhook IDs by action and entity
     *
     * @param string $hook Hook name to search for
     * @return array List of webhook IDs
     */
    public static function getIdsByActionEntity($hook)
    {
        $sql = 'SELECT id_webhook
            FROM ' . _DB_PREFIX_ . 'webhook
            WHERE CONCAT(entity, action) = "' . pSQL(strtolower($hook)) . '" AND active = 1';
        $data = Db::getInstance()->executeS($sql);
        return array_column($data, 'id_webhook');
    }

    /**
     * Get the hook name for this webhook
     *
     * @return string Hook name
     */
    public function getHookName()
    {
        return 'actionObject' . ucfirst($this->entity) . ucfirst($this->action) . 'After';
    }
}
