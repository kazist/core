<?php

namespace Kazist\Service\Form;

defined('KAZIST') or exit(DIE_MSG);

use Kazist\KazistFactory;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class DefaultField {

    public function getEditData($field, $item) {

        switch ($field['html_type']) {
            case 'media':

                $mediafield = new MediaField();
                $data = $mediafield->getEditData($field, $item);
                break;
            case 'text':
                $textfield = new TextField();
                $data = $textfield->getEditData($field, $item);
                break;
            default:

                $basefield = new BaseField();
                $data = $basefield->getEditData($field, $item);
                break;
        }

        return $data;
    }

    public function getDetailData($field, $item) {

        switch ($field['html_type']) {
            case 'media':
                $mediafield = new MediaField();
                $data = $mediafield->getDetailData($field, $item);
                break;
            case 'text':
                $textfield = new TextField();
                $data = $textfield->getDetailData($field, $item);
                break;
            case 'select':
            case 'recordpicker':
            case 'radio':
            case 'autocomplete':
                $choicefield = new ChoiceField();
                $data = $choicefield->getDetailData($field, $item);
                break;
            default:
                $basefield = new BaseField();
                $data = $basefield->getDetailData($field, $item);
                break;
        }

        return $data;
    }

    public function getOptionData($field, $item) {
        
        switch ($field['html_type']) {
            case 'media':
                $mediafield = new MediaField();
                $data = $mediafield->getOptionData($field, $item);
                break;
            default:
                $basefield = new BaseField();
                $data = $basefield->getOptionData($field, $item);
                break;
        }

        return $data;
    }

    public function saveMultiples($field, $form_data, $id) {

        $basefield = new BaseField();
        $data = $basefield->saveMultiples($field, $form_data, $id);

        return $data;
    }

}
