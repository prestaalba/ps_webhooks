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

require dirname(__FILE__) . '/vendor/autoload.php';

class Ps_Webhooks extends Module
{
    public function __construct()
    {
        $this->name = 'ps_webhooks';
        $this->author = 'Experto PrestaShop';
        $this->version = '1.0.0';
        $this->tab = 'others';
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('PrestaShop Webhooks');
        $this->description = $this->l('Trigger real-time notifications on PrestaShop events attaching object data in JSON format to the request.');

        if (!function_exists('curl_init')) {
            $this->warning = $this->l('To be able to use this module, please activate cURL (PHP extension).');
        }
    }

    public function install()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminWebhooks';
        $tab->id_parent = Tab::getIdFromClassName('AdminAdvancedParameters');
        $tab->module = $this->name;
        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            $tab->name[$language['id_lang']] = $this->l('Webhooks');
        }
        $tab->add();

        $query = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'webhook (
            `id_webhook` int(10) unsigned NOT NULL Key AUTO_INCREMENT,
            `action` varchar(10) NOT NULL,
            `entity` varchar(100) NOT NULL,
            `description` text,
            `url` varchar(200) NOT NULL,
            `active` tinyint(1) NOT NULL DEFAULT 1
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
        Db::getInstance()->execute($query);

        return parent::install();
    }

    public function uninstall()
    {
        $id_tab = Tab::getIdFromClassName('AdminWebhooks');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
        }
        Db::getInstance()->execute('DROP table ' . _DB_PREFIX_ . 'webhook');

        return parent::uninstall();
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminWebhooks'));
    }

    public function __call($name, $params)
    {
        if (strpos($name, 'hookActionObject') !== false) {
            return $this->executeHook(str_replace(['hookActionObject', 'After'], '', $name), $params[0]);
        }
    }

    public function executeHook($hook_name, $params)
    {
        $ids_webhooks = Webhook::getIdsByActionEntity($hook_name);
        if (!$ids_webhooks) {
            return;
        }

        foreach ($ids_webhooks as $id_webhook) {
            $webhook = new Webhook($id_webhook);
            self::executeUrl($webhook->url, $webhook->action, $webhook->entity, $params['object']);
        }
    }

    /**
     * Send HTTP request to the specified URL to trigger the webhook
     * Security: Strict parameter validation and disabling of insecure SSL options
     * @param string $url The target URL for the webhook
     * @param string $action The action performed (add, update, delete)
     * @param string $entity The type of entity concerned
     * @param object $object The object to send
     * @param bool $test If true, performs a connection test
     * @return mixed HTTP code if test=true, otherwise null
     */
    public static function executeUrl($url, $action, $entity, $object, $test = false)
    {
        // Security: Validate that the entity is a valid class inheriting from ObjectModel
        if (!class_exists($entity) || !is_subclass_of($entity, 'ObjectModel')) {
            // If the class is not valid, we don't continue to prevent dynamic code execution
            return;
        }

        // Security: Filter sensitive data before sending
        $object_vars = get_object_vars($object);
        // Exclude sensitive fields that should not be sent
        unset($object_vars['passwd']);
        unset($object_vars['secure_key']);
        unset($object_vars['password']);

        $curl = curl_init($url);
        $curl_options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 3,
            // Security: Enable SSL verification to prevent MITM attacks
            CURLOPT_SSL_VERIFYHOST => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_POST => true,
            // Security: Send filtered object data instead of raw object
            CURLOPT_POSTFIELDS => json_encode(['action' => $action, 'entity' => $entity, 'data' => $object_vars]),
        ];
        curl_setopt_array($curl, $curl_options);

        if ($test) {
            $curl_options = [
                CURLOPT_NOBODY => false,
                CURLOPT_FORBID_REUSE => false,
            ];
            curl_setopt_array($curl, $curl_options);
        }

        curl_exec($curl);

        if ($test) {
            return curl_getinfo($curl, CURLINFO_HTTP_CODE);
        }

        curl_close($curl);
    }
}
