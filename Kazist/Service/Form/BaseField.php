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

class BaseField {

    public $table_alias = '';

    public function getEditData($field, $item) {

        $field_name = $field['name'];

        if ($field['parameters']['save']['saving_type'] == 'multiple') {
            $data = $this->getSavedRecords($field, $item);
        } else {
            $data = $item->$field_name;
        }

        $data = ($data <> '') ? $data : $field['default_value'];

        return $data;
    }

    public function getDetailData($field, $item) {

        $field_name = $field['name'];

        if ($field['parameters']['save']['saving_type'] == 'multiple') {
            $data = $this->getSavedRecords($field, $item);
        } else {
            $data = $item->$field_name;
        }

        $data = ($data <> '') ? $data : $field['default_value'];

        return $data;
    }

    public function getOptionData($field, $item) {

        $factory = new KazistFactory();
        $option_arr = array();

        $field_name = $field['name'];


        if ($field['parameters']['source']['data_source'] == 'custom') {
            $option_arr = $field['parameters']['source']['customs'];
        }
        if ($field['parameters']['source']['data_source'] == 'db_table' && $field['html_type'] <> 'autocomplete') {

            $option_arr = array();

            $display_fields = $field['parameters']['source']['display_field'];
            $join_field = $field['parameters']['source']['join_field'];
            $join_table_name = $field['parameters']['source']['join_table_name'];
            $table_alias = $this->getTableAlias($join_table_name);
            $new_field_name = $join_field . '_' . $field_name;

            $factory->ordering = 'ASC';
            $query = $factory->getQueryBuilder($join_table_name, $table_alias, null, null, $display_fields);

            $data_list = $query->loadObjectList();


            foreach ($data_list as $data) {
                $display_data = '';

                foreach ($display_fields as $display_field) {

                    $json = $factory->getJson($join_table_name);

                    if (isset($data->$display_field)) {

                        $single_field = $json['fields'][$display_field];
                        $single_display_fields = $single_field['parameters']['source']['display_field'];

                        if (!empty($single_display_fields)) {
                            foreach ($single_display_fields as $single_display_field) {

                                $single_field_name = $display_field . '_' . $single_display_field;

                                if (isset($data->$single_field_name)) {
                                    $display_data .= $data->$single_field_name;
                                }
                            }
                        } else {
                            $display_data .= $data->$display_field;
                        }
                    }
                }

                if ($display_data <> '') {
                    $option_arr[] = array('value' => $data->$join_field, 'text' => $display_data);
                }
            }
        }

        return $option_arr;
    }

    public function saveFieldData($field, $item) {
        
    }

    public function saveMultiples($field, $form_data, $foreign_id) {

        $ids = array();

        if (isset($form_data[$field['name']]['value'])) {

            foreach ($form_data[$field['name']]['value'] as $key => $tmp_data) {
                if ($tmp_data <> '') {
                    $id = $form_data[$field['name']]['id'][$key];
                    $ids[] = $this->saveMultiplesPerRecord($field, $tmp_data, $foreign_id, $id);
                }
            }
        } else {
            foreach ($form_data[$field['name']] as $key => $tmp_data) {
                $ids[] = $this->saveMultiplesPerRecord($field, $tmp_data, $foreign_id);
            }
        }

        $this->deleteMissingMultiples($ids, $field, $foreign_id);
    }

    public function deleteMissingMultiples($ids, $field, $foreign_id) {

        $foreign_field_name = $field['parameters']['save']['foreign_field_name'];

        $factory = new KazistFactory();

        if (!empty($ids)) {
            $save_table_name = $field['parameters']['save']['save_table_name'];

            $where_arr = array('id NOT IN (' . implode(', ', $ids) . ')', $foreign_field_name . ' = :' . $foreign_field_name);
            $parameter_arr = array($foreign_field_name => $foreign_id);

            $factory->deleteRecords('#__' . $save_table_name, $where_arr, $parameter_arr);
        }
    }

    public function saveMultiplesPerRecord($field, $field_data, $foreign_id, $id = '') {

        $factory = new KazistFactory();

        $save_table_name = $field['parameters']['save']['save_table_name'];
        $save_field_name = $field['parameters']['save']['save_field_name'];
        $foreign_field_name = $field['parameters']['save']['foreign_field_name'];

        $data = array();
        $data[$save_field_name] = $field_data;
        $data[$foreign_field_name] = $foreign_id;
        if ($id) {
            $data['id'] = $id;
        }

        $where_arr = array();
        $where_arr[] = $save_field_name . '=:' . $save_field_name;
        $where_arr[] = $foreign_field_name . '=:' . $foreign_field_name;

        return $factory->saveRecordByEntity($save_table_name, $data, $where_arr, $data);
    }

    public function getSavedRecords($field, $item) {

        $tmp_array = array();
        $factory = new KazistFactory();

        $field_name = $field['name'];

        $save_table_name = $field['parameters']['save']['save_table_name'];
        $save_field_name = $field['parameters']['save']['save_field_name'];
        $foreign_field_name = $field['parameters']['save']['foreign_field_name'];

        if ($save_table_name == '') {
            return (is_object($item)) ? $item->$field_name : '';
        }

        $table_alias = $factory->getTableAlias($foreign_field_name);

        $query = new Query();
        $query->from('#__' . $save_table_name, $table_alias);
        $query->select($save_field_name);
        if ($item->id) {
            $query->where($foreign_field_name . '=' . $item->id);
        } else {
            $query->where('1=-1');
        }

        $records = $query->loadObjectList();

        foreach ($records as $key => $record) {
            $tmp_array[] = $record->$save_field_name;
        }

        return $tmp_array;
    }

    public function getTableAlias($table_name = '') {

        $table_alias_arr = array();
        $table_name_arr = explode('_', str_replace('#__', '', $table_name));

        if (!empty($table_name_arr)) {
            foreach ($table_name_arr as $key => $term) {
                $table_alias_arr[] = substr($term, 0, 1);
            }
        }

        $this->table_alias = implode('', $table_alias_arr);

        return $this->table_alias;
    }

}
