<?php
include_once MODX_BASE_PATH . 'assets/modules/booking/autoload.php';
$plugin = new Pathologic\Commerce\Booking\Plugin($modx, $params);
$event = $modx->event->name;
if (method_exists($plugin, $event)) {
    $plugin->$event();
}
