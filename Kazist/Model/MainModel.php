<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of LeapYear
 *
 * @author sbc
 */

namespace Kazist\Model;

defined('KAZIST') or exit('Not Kazist Framework');

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Query\Expr\Join;
use Kazist\Service\Database\Query;

class MainModel extends BaseModel {
    /* xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx Setting xxxxxxxxxxxxxxxxxxx */

    public function getSetting($path) {

        $settings = array();
        $path_arr = explode('.', $path);
        $path_arr = array_map('ucfirst', $path_arr);

        $new_path = JPATH_ROOT . 'applications/' . implode('/', $path_arr) . '/Code/setting.json';

        if (file_exists($new_path)) {
            $settings = json_decode(file_get_contents($new_path), true);
        }

        $setting = $this->prepareSetting($settings);


        return $setting;
    }

    public function prepareSetting($settings) {

        if (!empty($settings)) {
            foreach ($settings as $key => $setting) {

                $settings[$key]['options'] = array();

                if ($setting['source']['custom'] <> '') {
                    $settings[$key]['options'] = $setting['source']['custom'];
                }

                if ($setting['source'] <> '') {
                    $settings[$key]['options'] = array_merge($settings[$key]['options'], $this->getSettingOptions($setting));
                }

                $settings[$key]['default'] = $this->getSettingDefault($setting);
            }
        }


        return $settings;
    }

    public function getSettingDefault($setting) {

        $name = $setting['name'];

        $record = $this->getQueryedRecord('#__system_settings', 'ss', array('ss.name=:name'), array('name' => $name));

        return $record->value;
    }

    public function getSettingOptions($setting) {

        $tmp_array = array();
        $query = new Query();

        $table = $setting['source']['table']['name'];
        $wheres = $setting['source']['table']['where'];
        $orders = $setting['source']['table']['order'];
        $value = $setting['source']['table']['value'];
        $text = $setting['source']['table']['text'];

        if (is_array($setting) && $table <> '') {

            $query->select($text . ', ' . $value);
            $query->from($table);

            foreach ($wheres as $key => $where) {
                (!$key) ? $query->where($where) : $query->andWhere($where);
            }

            foreach ($orders as $key => $order) {
                $order_arr = explode(' ', $order);
                (!$key) ? $query->orderBy($order_arr[0], $order_arr[1]) : $query->addOrderBy($order_arr[0], $order_arr[1]);
            }
        
            $records = $query->loadObjectList();

            foreach ($records as $key => $record) {
                $tmp_array[] = array('value' => $record->$value, 'text' => $record->$text);
            }
        }

        return $tmp_array;
    }

    public function saveSettings($form) {

        foreach ($form as $key => $item) {

            $record = $this->getQueryedRecord('#__system_settings', 'ss', array('ss.name=:name'), array('name' => $key));

            if (is_object($record)) {
                $record->value = $item;
            } else {
                $record = new \stdClass();
                $record->name = $key;
                $record->value = $item;
            }

            $this->saveRecord('#__system_settings', $record);
        }
    }

    /* xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx Language xxxxxxxxxxxxxxxxxxx */

    public function getLanguages($path) {

        $languages = array();
        $modified_languages = array();
        $path_arr = explode('.', $path);
        $path_arr = array_map('ucfirst', $path_arr);

        $new_path = JPATH_ROOT . 'applications/' . implode('/', $path_arr) . '/Code/language.json';
        $modified_path = JPATH_ROOT . 'files/languages/' . str_replace('.', '/', $path) . '/language.json';

        if (file_exists($new_path)) {
            $languages = json_decode(file_get_contents($new_path), true);
        }

        if (file_exists($modified_path)) {
            $modified_languages = json_decode(file_get_contents($modified_path), true);
        }

        $languages = array_merge($languages, $modified_languages);

        //print_r($modified_languages);exit;
        return $languages;
    }

    public function saveLanguages($form) {

        $dir_path = JPATH_ROOT . 'files/languages/' . str_replace('.', '/', $form['path']);

        $this->makeDir($dir_path);

        $file_path = $dir_path . '/language.json';

        unset($form['path']);
        unset($form['return_url']);

        file_put_contents($file_path, json_encode($form, JSON_PRETTY_PRINT));
    }

}
