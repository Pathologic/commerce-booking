<?php

namespace Pathologic\Commerce\Booking;

use Helpers\Lexicon;

class Module
{
    protected $modx;
    protected $lexicon;
    protected $params = [];
    protected $DLTemplate;

    public function __construct($modx)
    {
        $this->modx = $modx;
        $this->params = $modx->event->params;
        $this->DLTemplate = \DLTemplate::getInstance($this->modx);
        $this->lexicon = new Lexicon($modx);
        $this->lexicon->fromFile('module', '', 'assets/modules/booking/lang/');
        $model = new Model($modx);
        $model->createTable();
    }

    public function prerender()
    {
        $tpl = MODX_BASE_PATH . 'assets/modules/booking/tpl/module.tpl';
        $output = '';
        if (is_readable($tpl)) {
            $output = file_get_contents($tpl);
        }

        return $output;
    }

    public function render()
    {
        $output = $this->prerender();
        $ph = [
            'lang'        => $this->modx->getConfig('lang_code'),
            'lexicon'     => '<script>const lang = ' . json_encode($this->lexicon->getLexicon()). ';</script>',
            'connector'   => $this->modx->config['site_url'] . 'assets/modules/booking/ajax.php',
            'site_url'    => $this->modx->config['site_url'],
            'theme'       => $this->modx->config['manager_theme'],
            'manager_url' => MODX_MANAGER_URL,
        ];
        $output = $this->DLTemplate->parseChunk('@CODE:' . $output, $ph);
        $output = $this->lexicon->parse($output);

        return $output;
    }
}
