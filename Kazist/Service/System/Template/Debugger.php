<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Kazist\Service\System\Template;

/**
 * Description of Template
 *
 * @author sbc
 */
use Kazist\Service\Database\Query;
use Kazist\KazistFactory;
use Kazist\Model\KazistModel;

class Debugger {

    public $container = '';
    public $request = '';

//put your code here
    function __construct($container, $request) {
        $this->container = $container;
        $this->request = $request;
    }

    public function loadDebugger($response_content) {

        $debugbar = $this->container->get('debugger');

        $debugbarRenderer = $debugbar->getJavascriptRenderer();

        $debugbarRenderer->setEnableJqueryNoConflict(false);
        $debugbarRenderer->disableVendor('jquery');
        $debugbarRenderer->disableVendor('fontawesome');

        $debugbarRenderer->setBaseUrl(WEB_ROOT . 'assets/debugger');

        $header = $debugbarRenderer->renderHead();
        $body = $debugbarRenderer->render();

        $response_content = str_replace('</body>', $header . "\n" . '</body>', $response_content);
        $response_content = str_replace('</body>', $body . "\n" . '</body>', $response_content);

        return $response_content;
    }

}
