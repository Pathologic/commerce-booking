<?php

namespace Pathologic\Commerce\Booking;

use Helpers\Lexicon;
use Pathologic\EvolutionCMS\MODxAPI\modResource;

class Plugin
{
    protected $modx;
    protected $params = [];

    public function __construct(\DocumentParser $modx, array $params = [])
    {
        $this->modx = $modx;
        $this->params = $params;
    }

    public function OnPageNotFound()
    {
        if (function_exists('ci') && ci()->has('booking') && !empty($_GET['q']) && is_scalar($_GET['q']) && strpos($_GET['q'],
                'booking/reservations') === 0) {
            $begin = isset($_POST['begin']) && is_scalar($_POST['begin']) ? $_POST['begin'] : false;
            $end = isset($_POST['end']) && is_scalar($_POST['end']) ? $_POST['end'] : false;
            $id = (int) ($_POST['id'] ?? 0);
            $out = [];
            if ($id && $begin && $end) {
                $out = ci()->booking->getReservations($id, $begin, $end);
            }

            echo json_encode($out);
            die();
        }
    }

    public function OnCartChanged()
    {
        $cart = ci()->carts->getCart($this->params['instance']);
        if ($cart) {
            $items = $cart->getItems();
            foreach ($items as &$item) {
                if (isset($item['meta']['begin']) && isset($item['meta']['end'])) {
                    $item['count'] = 1;
                    $item['meta']['type'] = 'booking';
                }
            }
            $cart->setItems($items);
        }
    }

    public function OnBeforeCartItemAdding()
    {
        if (isset($this->params['item']['meta']['begin']) && isset($this->params['item']['meta']['end'])) {
            $begin = $this->params['item']['meta']['begin'];
            $end = $this->params['item']['meta']['end'];
            $this->params['item']['count'] = 1;
            if (!ci()->booking->isAvailable($this->params['item']['id'], $begin, $end)) {
                $this->params['prevent'] = true;
            } else {
                $dates = [$begin, $end];
                usort($dates, function ($a, $b) {
                    return strtotime($a) - strtotime($b);
                });
                [$this->params['item']['meta']['begin'], $this->params['item']['meta']['end']] = $dates;
                $model = ci()->booking->getSetting('model', '\modResource');
                $doc = new $model($this->modx);
                $doc->edit($this->params['item']['id']);
                $price = (float) $doc->get(ci()->booking->getSetting('priceTv', 'price'));
                $dateFormat = ci()->booking->getSetting('dateFormat', 'd.m.Y');
                $date1 = \DateTime::createFromFormat($dateFormat, $this->params['item']['meta']['begin']);
                $date2 = \DateTime::createFromFormat($dateFormat, $this->params['item']['meta']['end']);
                $interval = $date1->diff($date2);
                $price = $price * $interval->days;
                $this->modx->invokeEvent('OnBookingCalculatePrice', [
                    'price'   => &$price,
                    'itemObj' => $doc,
                    'item'    => &$this->params['item'],
                    'days'    => $interval->days
                ]);
                $this->params['item']['price'] = $price;
            }
        }
    }

    public function OnCommerceInitialized()
    {
        $modx = $this->modx;
        $params = $this->params;
        $ci = ci();
        if (!$ci->has('booking')) {
            $ci->set('booking', function ($ci) use ($modx, $params) {
                return new Manager($modx, $params);
            });
        }
        ci()->commerce->getUserLanguage('booking');
    }

    public function OnBeforeOrderProcessing()
    {
        $FL = $this->params['FL'];
        $FL->lexicon->fromFile(ci()->booking->getSetting('lexicon', 'frontend'), '', 'assets/modules/booking/lang/');
        $cartInstance = $FL->getCFGDef('cartName', 'products');
        $cart = ci()->carts->getCart($cartInstance);
        if ($cart) {
            $items = $cart->getItems();
            foreach ($items as &$item) {
                if (!isset($item['meta']['type']) || $item['meta']['type'] !== 'booking') {
                    continue;
                }
                if (!ci()->booking->isAvailable($item['id'], $item['meta']['begin'], $item['meta']['end'])) {
                    $this->params['prevent'] = true;
                    $item['notavailable'] = 1;
                    $FL->addMessage('[%form.notavailable%]');
                    break;
                } else {
                    $item['meta']['hash'] = ci()->commerce->generateRandomString(32);
                }
            }
            $cart->setItems($items);
        }
    }

    public function OnOrderSaved()
    {
        if ($this->params['mode'] == 'new') {
            $model = new Model($this->modx);
            foreach ($this->params['items'] as $item) {
                if (!isset($item['meta']['type']) || $item['meta']['type'] !== 'booking') {
                    continue;
                }
                $lexicon = new Lexicon($this->modx);
                $lexicon->fromFile(ci()->booking->getSetting('lexicon', 'frontend'), '', 'assets/modules/booking/lang/');
                $model->create([
                    'orderid'     => $this->params['order_id'],
                    'docid'       => $item['id'],
                    'begin'       => $item['meta']['begin'],
                    'end'         => $item['meta']['end'],
                    'hash'        => $item['meta']['hash'],
                    'description' => $lexicon->get('booking.description') . $this->params['order_id']
                ])->save();
            };
        }
    }

    public function OnBeforeOrderHistoryUpdate()
    {
        if ($this->params['status_id'] == ci()->booking->getSetting('canceledStatus')) {
            $this->modx->query("DELETE FROM {$this->modx->getFullTableName('reservations')} WHERE `orderid` = {$this->params['order_id']}");
        }
    }
}
