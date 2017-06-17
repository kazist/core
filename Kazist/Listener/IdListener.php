<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of HiddenListener
 *
 * @author sbc
 */

namespace Kazist\Listener;

defined('KAZIST') or exit('Not Kazist Framework');

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Kazist\Service\Form\InputEvent;

class IdListener implements EventSubscriberInterface {

    public $container = '';

    public function onFieldValidation(InputEvent $event) {

        global $sc;

        $this->container = $sc;

        $form_data = $event->getFormData();
        $input_name = $event->getInputName();
        $mysql_type = $event->getMysqlType();

        $value = $form_data[$input_name];

        if (is_null($value) || is_numeric($value)) {
            return true;
        } else {
            return false;
        }
    }

    public static function getSubscribedEvents() {
        return array('field.id.validation' => 'onFieldValidation');
    }

}
