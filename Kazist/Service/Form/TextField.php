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

class TextField Extends BaseField {

    public function getEditData($field, $item) {

        $field_name = $field['name'];

        if ($field['parameters']['save']['saving_type'] == 'multiple') {

            $save_table_name = $field['parameters']['save']['save_table_name'];
            $save_field_name = $field['parameters']['save']['save_field_name'];
            $foreign_field_name = $field['parameters']['save']['foreign_field_name'];

            if ($save_table_name == '') {
                return (is_object($item)) ? $item->$field_name : '';
            }

            $table_alias = $this->getTableAlias($save_table_name);

            $tmp_array = array();

            $query = new Query();
            $query->from('#__' . $save_table_name, $table_alias);
            $query->select('*');
            if ($item->id) {
                $query->where($foreign_field_name . ' = ' . $item->id);
            } else {
                $query->where('1=-1');
            }

            $records = $query->loadObjectList();

            foreach ($records as $record) {
                $data = new \stdClass();
                $data->value = $record->$save_field_name;
                $data->id = $record->id;
                $tmp_array[] = $data;
            }

            $data = $tmp_array;
        } else {
            $data = parent::getEditData($field, $item);
        }

        return $data;
    }

}
