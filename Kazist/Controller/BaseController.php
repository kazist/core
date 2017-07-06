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

abstract class BaseController extends KazistController {

    public $data_arr = array();
    public $return_url = '';

    /**
     * Function for initializing Common Code for the system call to work properly.
     */
    public function __construct() {
        
    }

    public function indexAction($offset = 0, $limit = 10) {


        $records = $this->model->getRecords($offset, $limit);
        $json_object = $this->model->getDetailedJson();

        $this->data_arr['items'] = (count($this->data_arr['items'])) ? $this->data_arr['items'] : $records;
        $this->data_arr['json_object'] = $json_object;
        $this->data_arr['action_type'] = 'table';
        $this->data_arr['show_action'] = (isset($this->data_arr['show_action'])) ? $this->data_arr['show_action'] : true;
        $this->data_arr['show_search'] = (isset($this->data_arr['show_search'])) ? $this->data_arr['show_search'] : true;
        $this->data_arr['show_messages'] = (isset($this->data_arr['show_messages'])) ? $this->data_arr['show_messages'] : true;
        $this->data_arr['total'] = $this->model->getTotal();
      

        $this->html .= $this->render('Kazist:views:table:table.index.twig', $this->data_arr);

        $response = $this->response($this->html);

        return $response;
    }

    public function addAction($id = '') {


        $json_list = $this->model->getDetailedJson();

        $this->data_arr['save_button_text'] = 'Save';
        $this->data_arr['json_object'] = $json_list;
        $this->data_arr['action_type'] = 'edit';
        $this->data_arr['show_action'] = (isset($this->data_arr['show_action'])) ? $this->data_arr['show_action'] : true;
        $this->data_arr['show_messages'] = (isset($this->data_arr['show_messages'])) ? $this->data_arr['show_messages'] : true;

        $this->html .= $this->render('Kazist:views:edit:edit.index.twig', $this->data_arr);

        $response = $this->response($this->html);

        return $response;
    }

    public function editAction($id = '') {

        $document = $this->container->get('document');

        if ($id) {
            $record = $this->model->getRecord($id);
            $this->data_arr['item'] = (count($this->data_arr['item'])) ? $this->data_arr['item'] : $record;
        }

        $document->title = $this->model->renderString($document->title, $this->data_arr);
        $document->description = $this->model->renderString($document->description, $this->data_arr);

        $json_list = $this->model->getDetailedJson();

        $this->data_arr['save_button_text'] = 'Save';
        $this->data_arr['json_object'] = $json_list;
        $this->data_arr['action_type'] = 'edit';
        $this->data_arr['show_action'] = (isset($this->data_arr['show_action'])) ? $this->data_arr['show_action'] : true;
        $this->data_arr['show_messages'] = (isset($this->data_arr['show_messages'])) ? $this->data_arr['show_messages'] : true;


        $this->html .= $this->render('Kazist:views:edit:edit.index.twig', $this->data_arr);

        $response = $this->response($this->html);

        return $response;
    }

    public function detailAction($id = '', $slug = '') {

        $document = $this->container->get('document');

        $record = $this->model->getRecord($id);
        $this->data_arr['item'] = (count($this->data_arr['item'])) ? $this->data_arr['item'] : $record;

        $document->title = $this->model->renderString($document->title, $this->data_arr);
        $document->description = $this->model->renderString($document->description, $this->data_arr);

        $this->model->saveHit($record);

        $json_list = $this->model->getDetailedJson();

        $this->data_arr['item'] = (count($this->data_arr['item'])) ? $this->data_arr['item'] : $record;
        $this->data_arr['json_object'] = $json_list;
        $this->data_arr['action_type'] = 'detail';
        $this->data_arr['show_action'] = (isset($this->data_arr['show_action'])) ? $this->data_arr['show_action'] : true;
        $this->data_arr['show_messages'] = (isset($this->data_arr['show_messages'])) ? $this->data_arr['show_messages'] : true;

        $this->html .= $this->render('Kazist:views:detail:detail.index.twig', $this->data_arr);

        $response = $this->response($this->html);

        return $response;
    }

    public function deleteAction($id = 0) {

        $document = $this->container->get('document');
        $extension_path = $document->extension_path;

        $return_url = $this->request->get('return_url');
        $return_url = ($return_url <> '') ? $return_url : $this->request->get('return_url');

        if (!is_array($form_data)) {
            $form_data = $this->request->request->get('form');
        }

        $this->model->delete();

        if (!isset($form_data) || !$form_data['return_url']) {
            $return_url = ((WEB_IS_ADMIN) ? 'admin.' : '') .
                    str_replace('/', '.', strtolower($extension_path));
            return $this->redirectToRoute($return_url);
        } elseif ($return_url) {
            $return_url = base64_decode($return_url);
            return $this->redirect($return_url);
        } elseif ($form_data['return_url']) {
            $return_url = base64_decode($form_data['return_url']);
            return $this->redirect($return_url);
        }
    }

    public function saveAction($form_data = '') {

        $factory = new \Kazist\KazistFactory();

        $document = $this->container->get('document');
        $extension_path = $document->extension_path;
        $activity = $this->request->query->get('activity');

        if ($extension_path != '') {

            if (!is_array($form_data)) {
                $form_data = $this->request->request->get('form');
            }

            if ($activity == 'savecopy') {
                $record = $this->model->getRecord($form_data['id']);
                $form_data = json_decode(json_encode($record), true);
                unset($form_data['id']);
            }

            $this->model->form_data = $form_data;

            $id = $this->model->save($form_data);

            if ($this->return_url <> '') {
                return $this->redirectToRoute($this->return_url);
            } elseif (!$form_data['return_url']) {

                $return_url = ((WEB_IS_ADMIN) ? 'admin.' : '') .
                        str_replace('/', '.', strtolower($extension_path));

                switch ($activity) {
                    case 'savecopy':
                        return $this->redirectToRoute($return_url . '.edit', array('id' => $id));
                    case 'savenew':
                        return $this->redirectToRoute($return_url . '.add');
                    case 'saveclose':
                        return $this->redirectToRoute($return_url);
                    default:
                        return $this->redirectToRoute($return_url . '.edit', array('id' => $id));
                }

                return $this->redirectToRoute($return_url);
            } elseif ($form_data['return_url']) {

                $return_url = $form_data['return_url'];
                $return_url_decode = base64_decode($return_url);

                if (base64_encode($return_url_decode) == $return_url) {
                    return $this->redirect($return_url);
                } else {
                    return $this->redirect($form_data['return_url']);
                }
            }
        } else {

            $factory->enqueueMessage('Cannot save because class name is not defined on system document object');

            return $this->redirectToRoute('home');
        }
    }

    public function taskAction() {


        $factory = new \Kazist\KazistFactory();

        $document = $this->container->get('document');
        $extension_path = $document->extension_path;

        $activity = $this->request->get('activity');

        switch ($activity) {
            case 'updatestatus':
                echo json_encode($this->model->updateStatus());
                exit;
                break;
            case 'ajaxautocompletedefault':
                echo json_encode($this->model->getAjaxAutocompleteDefault());
                exit;
                break;
            case 'ajaxautocomplete':
                echo json_encode($this->model->getAjaxAutocomplete());
                exit;
                break;
            default:
                break;
        }


        return $response;
    }

}
