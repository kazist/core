<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PasswordListener
 *
 * @author sbc
 */

namespace Kazist\Listener;

defined('KAZIST') or exit('Not Kazist Framework');

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Kazist\Service\Form\InputEvent;

class PasswordListener implements EventSubscriberInterface {

    public $container = '';

    public function onPasswordValidation(InputEvent $event) {
        
        global $sc;

        $this->container = $sc;

        $form_data = $event->getFormData();
        $input_name = $event->getInputName();
        $mysql_type = $event->getMysqlType();

        return true;
    }

    public static function getSubscribedEvents() {
        return array('input.password.validation' => 'onPasswordValidation');
    }

}
