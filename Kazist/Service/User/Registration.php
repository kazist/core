<?php

namespace Kazist\Service\User;

defined('KAZIST') or exit('Not Kazist Framework');

use Kazist\KazistFactory;
use Kazist\Service\Email\Email;
use Kazist\Service\Database\Query;
use Kazist\Event\UserEvent;

/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
class Registration {

    public $user_id = 0;
    public $is_valid = true;
    public $container = '';
    public $request = '';

    public function __construct() {
        global $sc;
        $this->container = $sc;
        $this->request = $this->container->get('request');
    }

    public function registerUser($user_obj, $is_valid = false) {

        $user_id = '';
        $factory = new KazistFactory();
        $email_verification = $factory->getSetting('users_users_email_verification');

        $is_new = (!$user_obj->id) ? true : false;
        $this->user_id = ($user_obj->id) ? $user_obj->id : false;

        if ($user_obj->password != '') {
            $user_obj->password_raw = $user_obj->password;
            $user_obj->password = md5($user_obj->password);
        }

        if (!$is_valid) {
            $is_valid = $this->validateRegistration($user_obj);
        }

        if ($is_new) {
            $this->container->get('dispatcher')->dispatch('user.before.registration', new UserEvent($user_obj));
        }

        if ($is_valid) {

            if (!$email_verification) {
                $user_obj->published = 1;
                $user_obj->is_verified = 1;
            }

            unset($user_obj->password_again);
            $user_id = $factory->saveRecordByEntity('#__users_users', $user_obj);

            $this->addRegisteredUserGroup($user_id);

            if ($is_new && $user_id) {
                $user_obj->id = $user_id;
                $user_obj = $this->sendVerificationEmail($user_obj);
            }
        }

        if ($is_new) {
            $this->container->get('dispatcher')->dispatch('user.after.registration', new UserEvent($user_obj));
        }

        return $user_id;
    }

    public function sendVerificationEmail($user) {

        $email = new Email();
        $factory = new KazistFactory();

        $http_host = $this->request->server->get('HTTP_HOST');
        $request_scheme = $this->request->server->get('REQUEST_SCHEME');

        $user->verification = uniqid();
        $email->priority = 1;

        $factory->saveRecord('#__users_users', $user);

        $email_arr = json_decode(json_encode($user), true);
        $email_arr['email_title'] = 'User Account Verification';
        $email_arr['verification_url'] = $factory->generateUrl('user_verification', array('verification' => $user->verification), 0);

        $email->sendDefinedLayoutEmail('users.users.verify', $user->email, $email_arr);

        return $user;
    }

    public function validateRegistration($user_obj, $is_register) {

        $factory = new KazistFactory();

        if (!$this->isValidCaptcha($is_register)) {
            $this->is_valid = false;
        }

        if (trim($user_obj->name) === '') {
            $msg = 'Name Is Empty.';
            $factory->enqueueMessage($msg, 'error');
            $this->is_valid = false;
        }

        if (!$this->isValidEmail($user_obj->email)) {
            $this->is_valid = false;
        }

        if ($this->userNameExist($user_obj->username, $is_register)) {
            $this->is_valid = false;
        }

        if ($this->userFailedCharacterSize($user_obj->username, $is_register)) {
            $this->is_valid = false;
        }

        if ($this->userNameNotAlnum($user_obj->username, $is_register)) {
            $this->is_valid = false;
        }

        if ($this->emailExist($user_obj->email, $is_register)) {
            $this->is_valid = false;
        }

        return $this->is_valid;
    }

    public function isValidCaptcha($is_register) {

        if (!$is_register) {
            return true;
        }

        $factory = new KazistFactory();
        $session = $this->container->get('session');

        $form = $this->request->get('form');
        $captcha = $form['captcha'];
        $captcha_session = $session->get('captcha-digit');

        if ($captcha == $captcha_session) {
            return true;
        }

        $msg = 'Captcha Is Invalid.';
        $factory->enqueueMessage($msg, 'error');

        return false;
    }

    public function isValidName($name) {

        $factory = new KazistFactory();

        if ($name == '' || $name == ' ' || $name == null) {
            $msg = 'Name is not Valid.';
            $factory->enqueueMessage($msg, 'error');
            return false;
        }

        return true;
    }

    public function isValidEmail($email) {

        $factory = new KazistFactory();

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Email is not Valid.';
            $factory->enqueueMessage($msg, 'error');
            return false;
        }

        return true;
    }

    public function userFailedCharacterSize($username, $is_register) {

        $factory = new KazistFactory();

        if (count($username) > 16) {
            $msg = 'Username should be less than 16 Character.';
            $factory->enqueueMessage($msg, 'error');
            return true;
        }

        $sub_username = substr($username, 0, 8);

        if (count($sub_username) > 8) {
            $query = new Query();
            $query->select('uu.*');
            $query->from('#__users_users', 'uu');
            $query->where('uu.username LIKE :username');
            $query->setParameter('username', $sub_username . '%');
            $record = $query->loadObject();

            if (is_object($record)) {
                $msg = 'The First 8 Character of you Username should be unique.';
                $factory->enqueueMessage($msg, 'error');
                return true;
            }
        }

        return false;
    }

    public function userNameNotAlnum($username, $is_register) {

        $factory = new KazistFactory();

        if (!ctype_alnum($username)) {
            $msg = 'Username should be Alphanumeric.';
            $factory->enqueueMessage($msg, 'error');
            return true;
        }

        return false;
    }

    public function userNameExist($username, $is_register) {

        $factory = new KazistFactory();


        $query = new Query();
        $query->select('uu.*');
        $query->from('#__users_users', 'uu');
        $query->where('uu.username=:username');
        $query->setParameter('username', $username);

        if (!$is_register) {
            $query->andWhere('id<>:id');
            $query->setParameter('id', (int) $this->user_id);
        }
        $record = $query->loadObject();

        if (is_object($record)) {
            $msg = 'Username exist.';
            $factory->enqueueMessage($msg, 'error');
            return true;
        }

        return false;
    }

    public function emailExist($email, $is_register) {

        $factory = new KazistFactory();

        $query = new Query();
        $query->select('*');
        $query->from('#__users_users', 'uu');
        $query->where('email=:email');
        $query->setParameter('email', $email);
        if (!$is_register) {
            $query->andWhere('id<>:id');
            $query->setParameter('id', (int) $this->user_id);
        }

        $record = $query->loadObject();

        if (is_object($record)) {
            $msg = 'Email exist.';
            $factory->enqueueMessage($msg, 'error');
            return true;
        }

        return false;
    }

    public function addRegisteredUserGroup($user_id) {

        $factory = new KazistFactory;

        $group_id = $this->getDefaultUserGroup();

        if ($group_id) {

            $data_obj = new \stdClass();

            $data_obj->user_id = $user_id;
            $data_obj->group_id = $group_id;

            $where_arr = array('user_id = :user_id', 'group_id = :group_id');

            $factory->saveRecord('#__users_users_groups', $data_obj, $where_arr, $data_obj);
        }
    }

    public function getDefaultUserGroup() {

        $factory = new KazistFactory;
        $db = $factory->getDatabase();

        $query = new Query();

        $query->select('ug.*');
        $query->where('ug.`registered_default`=1');
        $query->from('#__users_groups', 'ug');

        $default_group = $query->loadObject();

        return $default_group->id;
    }

}
