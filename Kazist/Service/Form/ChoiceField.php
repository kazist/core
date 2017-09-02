<?php

namespace Kazist\Service\Form;

defined('KAZIST') or exit(DIE_MSG);

use Kazist\KazistFactory;
use Kazist\Service\Database\Query;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ChoiceField Extends BaseField {

    public function getDetailData($field, $item) {

        $field_name = $field['name'];
        $data = '';

        if ($field['parameters']['source']['data_source'] == 'db_table') {

            $displayfields = $field['parameters']['source']['display_field'];
            $separator = (count($displayfields) > 1) ? ' | ' : '';
            foreach ($displayfields as $key => $displayfield) {
                $field_name = $field_name . '_' . $displayfield;
                $data = $item->$field_name . $separator;
            }
        } else {

            $customs = $field['parameters']['source']['customs'];

            foreach ($customs as $key => $custom) {
                if ($custom['value'] == $item->$field_name) {
                    $data = $custom['text'];
                    break;
                }
            }
        }

        $data = ($data <> '') ? $data : $field['default_value'];

        return $data;
    }

}
