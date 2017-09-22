<?php

/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Kazist\Model;

defined('KAZIST') or exit('Not Kazist Framework');

use Kazist\Model\BaseModel;
use Kazist\KazistFactory;
use Kazist\Service\Session\Session;
use Kazist\Service\System\System;
use Kazist\Service\User\User;
use Kazist\Service\Email\Email;
use Kazist\Service\User\Registration;
use Kazist\Service\User\Login;
use Kazist\Service\Database\Query;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Model to get data for the issue list view
 *
 * @since  1.0
 */
class UsersModel extends BaseModel {

    public function registerUser($form) {

        $user_id = 0;
        $user_obj = new \stdClass();

        $factory = new KazistFactory();
        $registration = new Registration();
        $user = $factory->getUser();

        $user_obj->name = $form['name'];
        $user_obj->username = $form['username'];
        $user_obj->email = $form['email'];
        $user_obj->password = $form['password'];
        $user_obj->password_again = $form['password_again'];
        $user_obj->country_id = $form['country_id'];
        $user_obj->location_id = $form['location_id'];
        $user_obj->phone = $form['phone'];
        $user_obj->address = $form['address'];

        $is_valid = $registration->validateRegistration($user_obj);

        if ($user->id) {
            $user_obj->id = $user->id;
        }

        if ($is_valid) {
            $user_id = $registration->registerUser($user_obj);
        }

        return $user_id;
    }

    /**
     * Delete Function
     *
     * @param   $id
     *
     * @return  boolean
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    public function logoutUser() {
        $session = new Session();
        $session->clear('user');
        $session->clear('user_id');
    }

    public function logUserTimeIp($user) {

        $factory = new KazistFactory();

        /* Save record to user database */
        $tmp_arr = array('id' => $user->id, 'ip' => $_SERVER['REMOTE_ADDR'], 'last_date_active' => date('Y-m-d H:i:s'));
        $factory->saveRecordByEntity('#__users_users', $tmp_arr);

        /* Save record to user logger database */
        $tmp_arr = array('user_id' => $user->id, 'ip' => $_SERVER['REMOTE_ADDR']);
        $factory->saveRecordByEntity('#__users_users_logs', $tmp_arr);
    }

    /**
     * Function for processing verifications that are sent via email
     * 
     * @param type $verification
     * @return boolean
     */
    public function accountVerification($verification) {
        $factory = new KazistFactory();

        $query = new Query();

        $query->select('u.*');
        $query->from('#__users_users', 'u');
        $query->where('verification=:verification');
        $query->setParameter('verification', $verification);
        $user = $query->loadObject();
        // print_r((string)$query); exit;

        if (is_object($user)) {

            $user_obj = new \stdClass();
            $user_obj->id = $user->id;
            $user_obj->is_verified = 1;
            $user_obj->published = 1;
            $factory->saveRecord('#__users_users', $user_obj);

            return true;
        } else {
            return false;
        }
    }

    public function fetchFollow() {
        $tmparray = array();
        $tmpobject = new \stdClass();

        $session = new Session;
        $factory = new KazistFactory();


        $user = $session->get('user');

        $tmpobject->subset_id = $this->request->request->get('subset_id');
        $tmpobject->record_id = $this->request->request->get('record_id');
        $tmpobject->user_id = $user->id;

        $records = $this->getAllFollows($tmpobject->subset_id, $tmpobject->record_id);
        $id = $this->getFollowRecord($tmpobject);

        if (!empty($records)) {
            $tmparray['successful'] = true;
            $tmparray['users'] = $records;
        } else {
            $tmparray['successful'] = false;
        }

        $tmparray['follow_text'] = (!$id) ? 'User' : 'Remove User';
        $tmparray['has_right'] = (!$id) ? false : true;

        return json_encode($tmparray);
    }

    public function getUsersList() {

        $tmpobject = new \stdClass;
        $factory = new KazistFactory();


        //$db = $this->getDb();

        $limit = 8;
        $offset = $this->request->request->get('offset', 0);

        $query = new Query();
        $query->select('COUNT(*)');
        $query = $this->prepareUsersQuery($query);
        $total = $query->loadResult();
        $query->clear();

        $query = new Query();
        $query->select('uu.*, mm.title as media_title, mm.file as media_file');
        $query = $this->prepareUsersQuery($query);

        $query->setFirstResult($offset);
        $query->setMaxResults($limit);

        $records = $query->loadObjectList();

        $tmpobject->records = $records;
        $tmpobject->successful = (!empty($records)) ? true : false;
        $tmpobject->offset = $offset;
        $tmpobject->limit = $limit;
        $tmpobject->total = $total;

        return json_encode($tmpobject);
    }

    public function prepareUsersQuery($query) {
        $db = $this->getDb();
        $factory = new KazistFactory();


        $user_ids = $this->request->request->get('user_ids', null, null);

        $query->from('#__users_users', 'uu');
        $query->leftjoin('uu', '#__media_media', 'mm', '  uu.avatar=mm.id');
        if (!empty($user_ids)) {
            $query->where(' uu.id NOT IN(' . implode(',', $user_ids) . ')');
        }
        //print_r((string)$query); exit;
        return $query;
    }

    public function resendVerificationCode($form) {

        $factory = new KazistFactory();
        $registration = new Registration();

        $return_url = $this->generateUrl('login');


        $user = $this->getUserByEmail($form['email']);

        if (is_object($user)) {

            $registration->sendVerificationEmail($user);

            $msg = 'Your Account is now reset. Open your email and click reset Link.';
            $factory->enqueueMessage($msg, 'success');
            $return_url = $this->generateUrl('login');
        } else {

            $msg = 'Reseting Your Account Failed. User Account does not exist.';
            $factory->enqueueMessage($msg, 'error');
            $return_url = $this->generateUrl('forgot_password');
        }

        return $return_url;
    }

    public function changeUser($form) {

        $email = new Email();
        $factory = new KazistFactory();
        $user = $factory->getUser();
        $authenticationManager = $this->container->get('security.authenticate');

        if ($form['return_url']) {

            $return_url = $form['return_url'];
            $return_url_decode = base64_decode($return_url);
            if (base64_encode($return_url_decode) == $return_url) {
                $return_url = $return_url_decode;
            } else {
                $return_url = $form['return_url'];
            }
        }

        if ($form['new_password'] != $form['new_password_again']) {

            $msg = 'You New Password did not Match with Confirm New Password. ';
            $factory->enqueueMessage($msg, 'error');
            $return_url = $this->generateUrl('change_password');
        } elseif (is_object($user)) {

            $token = new UsernamePasswordToken($user->username, $form['current_password'], 'main', array());

            try {
                $token = $authenticationManager->authenticate($token);

                if ($token->isAuthenticated()) {

                    $user_obj = new \stdClass();
                    $user_obj->id = $user->id;
                    $user_obj->password = md5($form['new_password']);
                    $factory->saveRecord('#__users_users', $user_obj);

                    $msg = 'Your Password Was Change Successfully.';
                    $factory->enqueueMessage($msg, 'info');
                    $return_url = ($form['return_url']) ? $return_url : $this->generateUrl('home');
                } else {
                    $msg = 'The password that in "Current Password" is Wrong. ';
                    $factory->enqueueMessage($msg, 'error');
                    $return_url = ($form['return_url']) ? $return_url : $this->generateUrl('change_password');
                }
            } catch (\Exception $exc) {
                $msg = 'The password that you provided as "Current Password" is Wrong. ';
                $factory->enqueueMessage($msg, 'error');
                $return_url = ($form['return_url']) ? $return_url : $this->generateUrl('change_password');
            }
        } else {
            $msg = 'You are currently logged out. Kindly Login again to Change You Password. ';
            $factory->enqueueMessage($msg, 'error');
            $return_url = $this->generateUrl('login');
        }

        return $return_url;
    }

    public function forgotUser($form) {

        $factory = new KazistFactory();
        $email = new Email();
        $email->priority = 1;
        $return_url = $this->generateUrl('login');

        $user = $this->getUserByEmail($form['email']);

        if (!is_object($user)) {
            $msg = 'Your account Does Not Exist.  Please Register.';
            $factory->enqueueMessage($msg, 'error');
            $return_url = $this->generateUrl('social.members.register');
        } elseif (!$user->is_verified) {

            $msg = 'Your account is not yet verified. Please Verify your Account First or resend Verification Code.';
            $factory->enqueueMessage($msg, 'error');
            $return_url = $this->generateUrl('resend_verification');
        } elseif (is_object($user)) {

            $password = substr(md5(rand(0, 1000000)), 0, 7);

            $user->salt = '';
            $user->password_raw = $password;
            $user->password = md5($password);
            $factory->saveRecord('#__users_users', $user);

            $user_arr = (array) $user;
            $user_arr['login_url'] = $this->generateUrl('login', null, 0);
            $email->sendDefinedLayoutEmail('users.users.forgot', $form['email'], $user_arr);

            $msg = 'We have changed you password. Check Your email for the new password';
            $factory->enqueueMessage($msg, 'success');
            $return_url = $this->generateUrl('login');
        } else {

            $msg = 'Reseting Your Account Failed. ';
            $factory->enqueueMessage($msg, 'error');
            $return_url = $this->generateUrl('forgot_password');
        }

        return $return_url;
    }

    public function getUserByEmail($email) {

        $query = new Query();

        $query->select('u.*');
        $query->from('#__users_users', 'u');
        $query->where('u.email=:email');
        $query->setParameter('email', trim($email));

        $user = $query->loadObject();

        return $user;
    }

    public function fetchCaptcha() {

        $factory = new KazistFactory();
        $session = $factory->getSession();

        // Adapted for The Art of Web: www.the-art-of-web.com
        // Please acknowledge use of this code by including this header.
        // initialise image with dimensions of 120 x 30 pixels

        $image = @imagecreatetruecolor(200, 50) or die("Cannot Initialize new GD image stream");

        // set background to white and allocate drawing colours
        $background = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
        imagefill($image, 0, 0, $background);
        $linecolor = imagecolorallocate($image, 0xCC, 0xCC, 0xCC);
        $textcolor = imagecolorallocate($image, 0x33, 0x33, 0x33);

        // draw random lines on canvas
        for ($i = 0; $i < 6; $i++) {
            imagesetthickness($image, rand(1, 3));
            imageline($image, 0, rand(0, 30), 250, rand(0, 30), $linecolor);
        }

        session_start();

        // add random digits to canvas
        $digit = '';
        for ($x = 15; $x <= 120; $x += 20) {
            $digit .= ($num = rand(0, 9));
            imagechar($image, rand(4, 5), $x, rand(2, 14), $num, $textcolor);
        }

        // record digits in session variable
        $session->set('captcha-digit', $digit);

        // display image and clean up
        header('Content-type: image/png');
        imagepng($image);
        imagedestroy($image);
    }

    public function fetchUser() {

        $factory = new KazistFactory;
        $db = $factory->getDatabase();


        $keyword = $this->request->request->get('keyword');

        $query = new Query();
        $query->select('u.id, u.username, u.name, u.phone, u.email');
        $query->from('#__users_users', 'u');

        if ($keyword <> '') {
            $query->where('u.email = :keyword');
            $query->orWhere('u.username = :keyword');
            $query->orWhere('u.phone = :keyword');
            $query->orWhere('u.name = :keyword');
            $query->setParameter('keyword', $keyword);
        } else {
            $query->where('1=-1');
        }
        $user = $query->loadObject();

        unset($user->password);

        return $user;
    }

    public function updateYesNo() {

        $factory = new KazistFactory();

        $item_id = $this->request->request->get('item_id');
        $item_status = $this->request->request->get('item_status');
        $item_field = $this->request->request->get('item_field');

        if ($item_field == 'is_verified') {

            $user = $factory->getRecord('#__users_users', 'uu', array('id=:id'), array('id' => $item_id));

            if (!$user->is_verified) {
                $this->accountVerification($user->verification);
            }

            $data_obj = new \stdClass();
            $data_obj->id = $item_id;
            $data_obj->published = ($item_status) ? 0 : 1;
            $factory->saveRecord('#__users_users', $data_obj);
        } else {
            parent::updateYesNo();
        }
    }

}
