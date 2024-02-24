<?php

namespace Pathologic\Commerce\Booking;

use Commerce\SettingsTrait;

class Manager
{
    use SettingsTrait;

    protected $modx;
    protected $model;
    protected $messages = [];

    public function __construct(\DocumentParser $modx, $params = [])
    {
        $this->modx = $modx;
        $this->model = new Model($modx);
        $this->setSettings($params);
    }

    public function isAvailable($id, string $begin, string $end, $dateFormat = '', $ignoreReservations = [])
    {
        $out = false;
        $id = (int) $id;
        if (!$this->checkDate($begin, $dateFormat) || !$this->checkDate($end, $dateFormat)) {
            return false;
        }
        $dates = [$begin, $end];
        usort($dates, function ($a, $b) {
            return strtotime($a) - strtotime($b);
        });
        [$begin, $end] = $dates;
        $templates = $this->getSetting('itemTemplates');
        $templates = \APIhelpers::cleanIDs($templates);
        $model = ci()->booking->getSetting('model', '\modResource');
        $doc = new $model(evo());
        $doc->edit($id);
        if ($doc->getID() && $doc->get('published') && !$doc->get('deleted') && in_array($doc->get('template'),
                $templates)) {
            $begin = date('Y-m-d', strtotime($begin));
            $end = date('Y-m-d', strtotime($end));
            $where = "`docid` = {$id} AND (('{$begin}' >= `begin` AND '{$begin}' < `end`) OR ('{$end}' > `begin` AND '{$end}' <= `end`) OR (`begin` >= '{$begin}' AND `end` <= '{$end}'))";
            if(!empty($ignoreReservations)) {
                $ids = \APIhelpers::cleanIDs($ignoreReservations);
                if($ids) {
                    $ids = implode(',', $ids);
                    $where .= " AND `id` NOT IN({$ids})";
                }
            }
            $q = $this->modx->db->query("SELECT COUNT(*) FROM {$this->modx->getFullTableName('reservations')} WHERE {$where}");
            $out = (int) $this->modx->db->getValue($q) == 0;
            $this->modx->invokeEvent('OnBookingItemCheck', [
                'itemObj'   => $doc,
                'begin'     => $begin,
                'end'       => $end,
                'available' => &$out,
            ]);
        }

        return $out;
    }

    public function getReservations($id, string $begin, string $end, $dateFormat = '')
    {
        $id = (int) $id;
        $out = [];
        if (!$id || !$this->checkDate($begin, $dateFormat) || !$this->checkDate($end, $dateFormat)) {
            return $out;
        }
        $begin = date('Y-m-d', strtotime($begin));
        $end = date('Y-m-d', strtotime($end));
        $q = $this->modx->db->query("SELECT `begin`, `end` FROM {$this->modx->getFullTableName('reservations')} WHERE `docid` = {$id} AND ((`begin` >= '{$begin}' AND `begin` <= '{$end}') OR (`end` >= '{$begin}' AND `end` <= '{$end}'))");
        while ($row = $this->modx->db->getRow($q)) {
            $out[] = [$row['begin'], $row['end']];
        }

        return $out;
    }

    protected function checkDate(string $date, $dateFormat = '')
    {
        if (empty($dateFormat)) {
            $dateFormat = ci()->booking->getSetting('dateFormat', 'd.m.Y');
        }
        $d = \DateTime::createFromFormat($dateFormat, $date);

        return $d && $d->format($dateFormat) == $date;
    }
}
