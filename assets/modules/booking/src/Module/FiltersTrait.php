<?php

namespace Pathologic\Commerce\Booking\Module;

trait FiltersTrait
{
    protected function addFilters(&$config)
    {
        $where = [];
        $item = isset($_POST['item']) ? (int)$_POST['item'] : 0;
        $begin = !empty($_POST['begin']) && is_scalar($_POST['begin']) ? $this->modx->db->escape($_POST['begin']) : '';
        $end = !empty($_POST['end']) && is_scalar($_POST['end']) ? $this->modx->db->escape($_POST['end']) : '';
        $ids = !empty($_POST['ids']) && is_array($_POST['ids']) ? \APIhelpers::cleanIDs($_POST['ids']) : [];
        if($item) {
            $where[] = "`docid` = {$item}";
        }
        if($begin) {
            $where[] = "(`begin` >= '{$begin}' OR `end` >= '{$begin}')";
        }
        if($end) {
            $where[] = "(`begin` <= '{$end}' OR `end` <= '{$end}')";
        }
        if($ids) {
            $ids = implode(',', $ids);
            $where[] = "`id` IN ({$ids})";
        }
        if($where) {
            $config['addWhereList'] = implode(' AND ', $where);
        }
    }
}
