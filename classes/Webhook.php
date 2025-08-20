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

class Webhook extends ObjectModel
{
    public $action;
    public $entity;
    public $description;
    public $url;
    public $active = true;

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

    public static function getIdsByActionEntity($hook)
    {
        $sql = 'SELECT id_webhook
            FROM ' . _DB_PREFIX_ . 'webhook
            WHERE CONCAT(entity, action) = "' . pSQL(strtolower($hook)) . '" AND active = 1';
        $data = Db::getInstance()->executeS($sql);
        return array_column($data, 'id_webhook');
    }

    public function getHookName()
    {
        return 'actionObject' . ucfirst($this->entity) . ucfirst($this->action) . 'After';
    }
}
