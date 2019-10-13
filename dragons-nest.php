<?php
/**
 * Created by gcomte
 * Date: 10/10/19
 * Time: 2:51 PM
 */

define('VOUCHER_GET_KEY', 'voucher');

if (!isset($_GET[VOUCHER_GET_KEY])) {
    exit;
}

define('WORDPRESS_LOAD_FILE', 'wp-load.php');
define('PLUGIN_ABSOLUTE_PATH', dirname(__FILE__) . '/');
define('DRAGONS_NEST_CLASS_RELATIVE_PATH', 'include/class-dragons-nest.php');
define('DRAGONS_NEST_CLASS_ABSOLUTE_PATH', PLUGIN_ABSOLUTE_PATH . DRAGONS_NEST_CLASS_RELATIVE_PATH);
define('FUNCTIONS_ABSOLUTE_PATH', PLUGIN_ABSOLUTE_PATH . 'functions.php');

// load wordpress
require_once(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . WORDPRESS_LOAD_FILE);

require_once(DRAGONS_NEST_CLASS_ABSOLUTE_PATH);

$voucher = $_GET[VOUCHER_GET_KEY];

$settings = get_ice_dragon_paywall_settings();
$paymentConfirmationSecret = $settings[IceDragonConstants::SETTINGS_KEY_HMAC_SECRET];

$dragonsNest = new DragonsNest();
$dragonsNest->registerIceDragonCookie($voucher, $paymentConfirmationSecret);