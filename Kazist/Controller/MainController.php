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

use Kazist\Model\MainModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

defined('KAZIST') or exit('Not Kazist Framework');

class MainController extends BaseController {

    public function indexAction() {

        $response = $this->response($this->html);

        return $response;
    }

    public function settingAction() {

        $path = $this->request->query->get('path');

        $this->model = new MainModel();

        $settings = $this->model->getSetting($path);

        $path_arr = explode('.', $path);

        $data_arr['settings'] = $settings;
        $data_arr['path'] = $path;
        $data_arr['root'] = $path_arr[0];

        $this->html .= $this->render('Kazist:views:main:setting.index.twig', $data_arr);

        $response = $this->response($this->html);

        return $response;
    }

    public function languageAction() {

        $path = $this->request->query->get('path');

        $this->model = new MainModel();

        $languages = $this->model->getLanguages($path);

        $path_arr = explode('.', $path);

        $data_arr['languages'] = $languages;
        $data_arr['path'] = $path;
        $data_arr['root'] = $path_arr[0];

        $this->html .= $this->render('Kazist:views:main:language.index.twig', $data_arr);

        $response = $this->response($this->html);

        return $response;
    }

    public function permissionAction() {

        $this->html = 'permission';

        $response = $this->response($this->html);

        return $response;
    }

    public function savesettingAction() {

        $form = $this->request->request->get('form');

        $this->model = new MainModel();

        $this->model->saveSettings($form);

        return $this->redirect($form['return_url']);
    }

    public function savelanguageAction() {
        
        $form = $this->request->request->get('form');

        $this->model = new MainModel();

        $this->model->saveLanguages($form);

        return $this->redirect($form['return_url']);
    }

    public function savepermissionAction() {

        $this->html = 'save permission';

        $response = $this->response($this->html);

        return $response;
    }

}
