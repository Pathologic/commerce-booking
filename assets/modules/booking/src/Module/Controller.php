<?php

namespace Pathologic\Commerce\Booking\Module;

use Pathologic\Commerce\Booking\Model;

class Controller
{
    use FiltersTrait;

    protected $modx;
    protected $model;

    public function __construct(\DocumentParser $modx)
    {
        $this->modx = $modx;
        $this->model = new Model($modx);
    }

    public function list()
    {
        $config = [
            'controller'     => 'onetable',
            'table'          => 'reservations',
            'idType'         => 'documents',
            'ignoreEmpty'    => 1,
            'display'        => 25,
            'offset'         => 0,
            'sortBy'         => 'id',
            'selectFields'   => 'c.*',
            'sortDir'        => 'desc',
            'returnDLObject' => true
        ];
        $this->addFilters($config);
        $this->addDynamicConfig($config);
        $dl = $this->modx->runSnippet('DocLister', $config);
        $total = $dl->getChildrenCount();
        $docs = $dl->getDocs();
        $ids = $products = [];
        foreach ($docs as $doc) {
            $ids[] = $doc['docid'];
        }
        $ids = array_unique($ids);
        if (!empty($ids)) {
            $ids = implode(',', $ids);
            $q = $this->modx->db->query("SELECT `id`, `pagetitle` FROM {$this->modx->getFullTableName('site_content')} WHERE `id` IN ({$ids})");
            while ($row = $this->modx->db->getRow($q)) {
                $products[$row['id']] = $row['pagetitle'];
            }
        }
        foreach ($docs as &$doc) {
            $doc['item_title'] = $products[$doc['docid']];
            if ($doc['updatedon'] === '0000-00-00 00:00:00') {
                $doc['updatedon'] = null;
            }
        }

        return ['rows' => array_values($docs), 'total' => $total];
    }

    protected function addDynamicConfig(&$config)
    {
        if (isset($_POST['rows'])) {
            $config['display'] = (int) $_POST['rows'];
        }
        $offset = isset($_POST['page']) ? (int) $_POST['page'] : 1;
        $offset = $offset ? $offset : 1;
        $offset = $config['display'] * abs($offset - 1);
        $config['offset'] = $offset;
        if (isset($_POST['sort'])) {
            $config['sortBy'] = '`' . preg_replace('/[^A-Za-z0-9_\-]/', '', $_POST['sort']) . '`';
        }
        if (isset($_POST['order']) && in_array(strtoupper($_POST['order']), ["ASC", "DESC"])) {
            $config['sortDir'] = $_POST['order'];
        }
    }

    public function delete()
    {
        $out = ['status' => false];

        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : [];
        $out['status'] = $this->model->delete($ids);

        return $out;
    }

    public function get()
    {
        $out = ['status' => false];
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($this->model->edit($id)->getID()) {
            $out['status'] = true;
            $out['fields'] = $this->model->toArray();
        }

        return $out;
    }

    protected function getFormParams()
    {
        return [
            'formid'         => 'reservation',
            'api'            => 1,
            'noemail'        => 1,
            'protectSubmit'  => 0,
            'submitLimit'    => 0,
            'filters'        => [
                'description' => ['strip_tags', 'trim', 'removeExtraSpaces'],
                'docid'       => ['castInt'],
            ],
            'rules'          => [
                'docid'       => [
                    'required' => '[%form.required.docid%]'
                ],
                'description' => [
                    'required' => '[%form.required.description%]'
                ],
                'begin'       => [
                    'required' => '[%form.required.date%]',
                    'date'     => [
                        'params'  => 'Y-m-d',
                        'message' => '[%form.error.date%]'
                    ]
                ],
                'end'         => [
                    'required' => '[%form.required.date%]',
                    'date'     => [
                        'params'  => 'Y-m-d',
                        'message' => '[%form.error.date%]'
                    ]
                ],
            ],
            'prepare'        => function ($modx, $data, $FormLister, $name) {
                $FormLister->lexicon->fromFile('module', '', 'assets/modules/booking/lang/');
            },
            'prepareProcess' => function ($modx, $data, $FormLister, $name) {
                $id = (int) $FormLister->getField('id');
                $model = new Model($modx);
                if ($id && $id == $model->edit($id)->getID()) {
                    $model->fromArray($data);
                } else {
                    $model->create($data);
                }
                $timeBegin = $timeEnd = 0;
                if (!empty($data['begin'])) {
                    $timeBegin = strtotime($data['begin']);
                }
                if (!empty($data['end'])) {
                    $timeEnd = strtotime($data['end']);
                }
                $dates = [$timeBegin, $timeEnd];
                usort($dates, function ($a, $b) {
                    return $a - $b;
                });
                [$timeBegin, $timeEnd] = $dates;
                $model->set('begin', date('Y-m-d', $timeBegin));
                $model->set('end', date('Y-m-d', $timeEnd));

                if(!$id || ($id && ($model->isChanged('docid') || $model->isChanged('begin') || $model->isChanged('end')))) {
                    if (!ci()->booking->isAvailable($data['docid'], $data['begin'], $data['end'], 'Y-m-d', [$id])) {
                        $FormLister->setValid(false);
                        $FormLister->addMessage('[%form.notavailable%]');

                        return;
                    }
                }
                $result = $model->save(true, false);

                if (!$result) {
                    $FormLister->setValid(false);
                    $FormLister->addMessage('[%form.failed%]');
                }
            }
        ];
    }

    public function create()
    {
        return $this->modx->runSnippet('FormLister', $this->getFormParams());
    }

    public function update()
    {
        $params = $this->getFormParams();
        $params['rules']['id'] = [
            'required' => '',
            'greater'  => [
                'params'  => 0,
                'message' => ''
            ]
        ];

        return $this->modx->runSnippet('FormLister', $params);
    }

    public function calendar()
    {
        $out = [];
        $year = isset($_POST['year']) && is_scalar($_POST['year']) ? (int) $_POST['year'] : 0;
        $id = isset($_POST['id']) && is_scalar($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($year) {
            $begin = $year . '-01-01';
            $end = $year . '-12-31';
            $where = $id ? "`docid` = {$id} AND" : '';
            $q = $this->modx->db->query("SELECT `begin`, `end`, `id`, `description` FROM {$this->modx->getFullTableName('reservations')} WHERE {$where} ((`begin` >= '{$begin}' AND `begin` <= '{$end}') OR (`end` >= '{$begin}' AND `end` <= '{$end}'))");
            while ($row = $this->modx->db->getRow($q)) {
                $out[] = array_values($row);
            }
        }

        return $out;
    }

    public function reservations()
    {
        $id = (int) ($_POST['id'] ?? 0);
    }

    public function objects()
    {
        $config = [
            'controller'     => 'site_content',
            'idType'         => 'documents',
            'ignoreEmpty'    => true,
            'makeUrl'        => false,
            'display'        => 15,
            'selectFields'   => 'c.id,c.pagetitle',
            'orderBy'        => 'c.pagetitle asc',
            'returnDLObject' => true
        ];
        $value = isset($_POST['value']) && is_scalar($_POST['value']) ? (int) $_POST['value'] : 0;
        $search = isset($_POST['search']) && is_scalar($_POST['search']) ? $this->modx->db->escape($_POST['search']) : '';
        $where = [];
        $templateIds = ci()->booking->getSetting('itemTemplates');
        if ($templateIds) {
            $templateIds = \APIhelpers::cleanIDs($templateIds);
            if ($templateIds) {
                $templateIds = implode(',', $templateIds);
                $where[] = "`c`.`template` IN ({$templateIds})";
            }
        }
        if (!empty($search)) {
            $where[] = "`c`.`pagetitle` LIKE '%{$search}%'";
        }
        $config['addWhereList'] = implode(' AND ', $where);
        $docs = $this->modx->runSnippet('DocLister', $config)->getDocs();
        if ($value) {
            $q = $this->modx->db->query("SELECT `id`, `pagetitle` FROM {$this->modx->getFullTableName('site_content')} WHERE `id`={$value}");
            if ($row = $this->modx->db->getRow()) {
                $docs[$row['id']] = $row;
            }
        }

        return array_values($docs);
    }
}
