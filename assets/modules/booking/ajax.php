<?php


use Pathologic\Commerce\Booking\Module\Controller;

define('MODX_API_MODE', true);
define('IN_MANAGER_MODE', 'true');

include_once(__DIR__ . "/../../../index.php");
$modx->db->connect();
if (empty ($modx->config)) {
    $modx->getSettings();
}
if (!isset($_SESSION['mgrValidated'])) {
    $modx->sendErrorPage();
}
include_once 'autoload.php';
$modx->invokeEvent('OnManagerPageInit');

$mode = (isset($_REQUEST['mode']) && is_scalar($_REQUEST['mode'])) ? $_REQUEST['mode'] : '';
$controller = new Controller($modx);
if (!empty($mode) && method_exists($controller, $mode)) {
    $out = call_user_func_array([$controller, $mode], []);
} else {
    $out = call_user_func_array([$controller, 'list'], []);
}

echo is_array($out) ? json_encode($out) : $out;
exit;
