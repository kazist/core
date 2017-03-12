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

defined('KAZIST') or exit('Not Kazist Framework');

use Kazist\KazistFactory;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Kazist\Model\UsersModel;

class UserController extends BaseController {

    public function loginAction() {

        $factory = new KazistFactory();

        $user = $factory->getUser();

        $data_arr['show_title'] = 1;
        $data_arr['show_cancel'] = 1;
        $data_arr['login_using'] = $factory->getSetting('users_users_login_using');


        if ($user->id) {
            return $this->redirectToRoute('admin.home');
        } else {
            $this->html .= $this->render('Kazist:views:user:login.index.twig', $data_arr);

            $response = $this->response($this->html);

            return $response;
        }
    }

    public function userInviteAction($username = '') {

        $session = $this->container->get('session');

        $session->set('user_inviter', $username);

        return $this->redirectToRoute('home');
    }

    public function logoutAction() {

        $session = $this->container->get('session');

        $session->clear();

        return $this->redirectToRoute('home');
    }

    public function registerAction() {

        //$applications = $this->model->getApplicationsTree();
        //$data_arr['applications', $applications);
        $data_arr['show_title'] = 1;
        $data_arr['show_cancel'] = 1;
        $data_arr['registration_view'] = 'register';

        $this->html = $this->render('Kazist:views:user:register.index.twig', $data_arr);

        $response = $this->response($this->html);

        return $response;
    }

    /**
     * Function for initializing Common Code for the system call to work properly.
     */
    public function loginCheckAction() {

        $factory = new KazistFactory();

        $authenticationManager = $this->container->get('security.authenticate');
        $tokenStorage = $this->container->get('security.token_storage');
        $session = $this->container->get('session');

        $form_data = $this->request->request->get('form');

        try {

            $username = $form_data['username'];
            $password = $form_data['password'];
            // Create "unauthenticated" token and authenticate it
            $token = new UsernamePasswordToken($username, $password, 'main', array());
            $token = $authenticationManager->authenticate($token);

            // Store "authenticated" token in the token storage
            $tokenStorage->setToken($token);

            $session->set('security.token', $token);

            $route_str = (WEB_IS_ADMIN) ? 'admin.home' : 'home';

            $user = $token->getUser();
            $tmp_arr = array('id' => $user->id, 'last_date_active' => date('Y-m-d H:i:s'));

            $factory->saveRecordByEntity('#__users_users', $tmp_arr);

            return $this->redirectToRoute($route_str);
        } catch (AuthenticationException $e) {
            //throw new $e;
            $factory->loggingMessage($e->getMessage());
            $route_str = (WEB_IS_ADMIN) ? 'admin.login' : 'login';
            return $this->redirectToRoute($route_str);
        }
    }

    public function loginregisterAction() {

        $data_arr['show_title'] = 1;
        $data_arr['show_cancel'] = 1;
        $data_arr['registration_view'] = 'register';

        $this->twig_paths[] = JPATH_ROOT . 'include/Kazist/views/user';
        $this->html = $this->render('Kazist:views:user:loginregister.index.twig', $data_arr);

        $response = $this->response($this->html);

        return $response;
    }

    public function saveregistrationAction() {

        $factory = new KazistFactory();
        $session = $factory->getSession();

        try {

            $form = $this->request->get('form');
            $return_url = (isset($form['return_url'])) ? $form['return_url'] : '';
            $session->set('session_form', $form);

            if ($return_url <> "") {
                $return_url = base64_decode($return_url);
            } else {
                $return_url = $this->generateUrl('login');
            }

            $this->model = new UsersModel();
            $has_register = $this->model->registerUser($form);

            if (!$has_register) {
                $msg = 'Account Not added due to some errors;';
                $factory->enqueueMessage($msg, 'error');
                $return_url = $this->generateUrl('register');
            } else {
                $session->clear('session_form');
                $msg = 'You need to verify your email address before logging in. We sent a verification code to: ' . $form['email'];
                $factory->enqueueMessage($msg, 'error');
            }

            $this->redirect($return_url);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Error: ' . $e->getMessage()));
        }
    }

    public function forgotFormAction() {

        $data_arr['show_title'] = 1;
        $data_arr['show_cancel'] = 1;

        $this->html = $this->render('Kazist:views:user:forgot.index.twig', $data_arr);

        $response = $this->response($this->html);

        return $response;
    }

    public function forgotAction() {

        $factory = new KazistFactory();


        $form = $this->request->request->get('form');

        $this->model = new UsersModel();
        $return_url = $this->model->forgotUser($form);

        return $this->redirect($return_url);
    }

    public function changeFormAction() {

        $document = $this->container->get('document');
        $user = $document->user;

        if (!$user->id) {
            return $this->redirectToRoute('login');
        }

        $data_arr['show_title'] = 1;
        $data_arr['show_cancel'] = 1;

        $this->html = $this->render('Kazist::views:user:change.index.twig', $data_arr);

        $response = $this->response($this->html);

        return $response;
    }

    public function changeAction() {

        $document = $this->container->get('document');
        $user = $document->user;

        $form = $this->request->get('form');

        if (empty($form)) {
            return $this->redirectToRoute('change_password_form');
        }

        if (!$user->id) {
            return $this->redirectToRoute('login');
        }

        $this->model = new UsersModel();
        $return_url = $this->model->changeUser($form);

        return $this->redirect($return_url);
    }

    public function resendFormAction() {

        $data_arr['show_title'] = 1;
        $data_arr['show_cancel'] = 1;

        $this->html = $this->render('Kazist::views:user:resend.index.twig', $data_arr);

        $response = $this->response($this->html);

        return $response;
    }

    public function resendAction() {

        $form = $this->request->request->get('form');

        $this->model = new UsersModel();
        $return_url = $this->model->resendVerificationCode($form);

        return $this->redirect($return_url);
    }

    public function thankyouAction() {

        $this->html = $this->render('Kazist::views:user:thankyou.index.twig', array());

        $response = $this->response($this->html);

        return $response;
    }

    public function verificationAction() {
        $factory = new KazistFactory();

        $verification = $this->request->query->get('verification');

        if ($verification <> '') {

            $usersModel = new UsersModel();
            $verified = $usersModel->accountVerification($verification);

            if ($verified) {
                $msg = 'Account Verified.';
                $factory->enqueueMessage($msg, 'error');
                $return_url = $this->generateUrl('login');
            } else {
                $msg = 'Invalid Verification Code.';
                $factory->enqueueMessage($msg, 'error');
                $return_url = $this->generateUrl('register');
            }
        } else {
            $msg = 'Invalid Url';
            $factory->enqueueMessage($msg, 'error');
            $return_url = $this->generateUrl('register');
        }

        return $this->redirect($return_url);
    }

}
