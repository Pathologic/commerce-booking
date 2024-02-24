<?php
include_once 'autoload.php';
$plugin = new Pathologic\Commerce\Booking\Plugin($modx, $params);
$event = $modx->event->name;
if (method_exists($plugin, $event)) {
    $plugin->$event();
}
