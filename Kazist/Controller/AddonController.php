<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of BaseController
 *
 * @author sbc
 */

namespace Kazist\Controller;

defined('KAZIST') or exit('Not Kazist Framework');

abstract class AddonController extends KazistController {

    public function indexAction($offset = 0, $limit = 10) {

        $this->html .= 'Hello World';

        $response = $this->response($this->html);

        return $response;
    }

}
