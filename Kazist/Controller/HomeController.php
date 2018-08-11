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

use Kazist\Model\BaseModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

defined('KAZIST') or exit('Not Kazist Framework');

class HomeController extends BaseController {

    public function indexAction($offset = 0, $limit = 6) {
       
        $document = $this->container->get('document');
        $document->title = $this->container->getParameter('system.title');
        $response = $this->response($this->html);
        
        return $response;
    }

    public function indexslashAction($offset = 0, $limit = 6) {
        return $this->redirectToRoute('admin.home');
    }

}
