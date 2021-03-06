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
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Kazist\Model\UsersModel;

class UserController extends BaseController {

    public function doubleauthAction() {

        $factory = new KazistFactory();

        $session = $factory->getSession();

        $return_url = $this->request->get('return_url');

        $doubleauth_code = $session->get('doubleauth_code');
        $user_doubleauth_code = $session->get('user_doubleauth_code');

        $data_arr['return_url'] = base64_decode($return_url);
        $data_arr['show_title'] = 1;
        $data_arr['show_cancel'] = 1;
        $data_arr['login_using'] = $factory->getSetting('users_users_login_using');

        if ($doubleauth_code == '' || $doubleauth_code <> $user_doubleauth_code) {
            $this->html .= $this->render('Kazist:views:user:doubleauth.index.twig', $data_arr);

            $response = $this->response($this->html);

            return $response;
        } else {
            return $this->redirect($return_url);
        }
    }

    public function doubleauthCheckAction() {

        $session = $this->container->get('session');

        $form_data = $this->request->request->get('form');

        $session->set('user_doubleauth_code', $form_data['doubleauth_code']);

        return $this->redirect($form_data['return_url']);
    }

    public function loginAction() {

        $factory = new KazistFactory();

        $session = $factory->getSession();
        $user = $factory->getUser();

        $max_login_attempts = $factory->getSetting('users_users_login_attempts');
        $login_attempts = $session->get('login_attempts');

        $data_arr['max_login_attempts'] = $max_login_attempts;
        $data_arr['login_attempts'] = $login_attempts;
        $data_arr['show_title'] = 1;
        $data_arr['show_cancel'] = 1;
        $data_arr['login_using'] = $factory->getSetting('users_users_login_using');


        if ($user->id) {
            $session->remove('login_attempts');
            return $this->redirectToRoute('admin.home');
        } else {
            if ($login_attempts && $login_attempts >= $max_login_attempts) {
                $factory->enqueueMessage('Maximum Login Attempt Reached.Change your password and try again or login after one hour.');
                return $this->redirectToRoute('home');
            }

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

    public function remoteLoginAction() {

        $factory = new KazistFactory();
        
         $this->model = new UsersModel();

        $authenticationManager = $this->container->get('security.authenticate');
        $tokenStorage = $this->container->get('security.token_storage');
        $session = $this->container->get('session');

        $session->clear();
        
        $route_str = (WEB_IS_ADMIN) ? 'admin.login' : 'login';

        $accesskey = $this->request->query->get('accesskey');

        $accesskey_obj = $factory->getRecord('#__users_accesskeys', 'ua', array('accesskey=:accesskey'), array('accesskey' => $accesskey));
        if (!is_object($accesskey_obj)) {
            $factory->loggingMessage('Access Key Does not exist.');
            return $this->redirectToRoute($route_str);
        }

        $user = $factory->getRecord('#__users_users', 'uu', array('id=:id'), array('id' => (int) $accesskey_obj->user_id));
        if (!is_object($user)) {
            $factory->loggingMessage('User record not found.');
            return $this->redirectToRoute($route_str);
        }

        $username = $user->username;
        $password = $accesskey;

        try {
            
            $frontend_redirector = $factory->getSetting('users_users_frontend_login_redirector', 'home');
            $backend_redirector = $factory->getSetting('users_users_backend_login_redirector', 'admin.home');

            $session->set('is_remotelogin', true);
            
            // Create "unauthenticated" token and authenticate it
            $token = new UsernamePasswordToken($username, $password, 'main', array());
            $token = $authenticationManager->authenticate($token);

            // Store "authenticated" token in the token storage
            $tokenStorage->setToken($token);

            $session->set('security.token', $token);

            $route_str = (WEB_IS_ADMIN) ? $backend_redirector : $frontend_redirector;

            $user = $token->getUser();
            $this->model->logUserTimeIp($user);

            return $this->redirectToRoute($route_str);
        } catch (AuthenticationException $e) {
            //throw new $e;
            $factory->loggingMessage($e->getMessage());

            return $this->redirectToRoute($route_str);
        }
    }

    /**
     * Function for initializing Common Code for the system call to work properly.
     */
    public function loginCheckAction() {

        $factory = new KazistFactory();

        $this->model = new UsersModel();

        $authenticationManager = $this->container->get('security.authenticate');
        $tokenStorage = $this->container->get('security.token_storage');
        $session = $this->container->get('session');

        $login_attempts = $session->get('login_attempts');

        $form_data = $this->request->request->get('form');

        $session->set('login_attempts', $login_attempts + 1);

        $frontend_redirector = $factory->getSetting('users_users_frontend_login_redirector', 'home');
        $backend_redirector = $factory->getSetting('users_users_backend_login_redirector', 'admin.home');

        try {

            $username = $form_data['username'];
            $password = $form_data['password'];
            // Create "unauthenticated" token and authenticate it
            $token = new UsernamePasswordToken($username, $password, 'main', array());
            $token = $authenticationManager->authenticate($token);

            // Store "authenticated" token in the token storage
            $tokenStorage->setToken($token);

            $session->set('security.token', $token);

            $route_str = (WEB_IS_ADMIN) ? $backend_redirector : $frontend_redirector;

            $user = $token->getUser();
            $this->model->logUserTimeIp($user);

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

            return $this->redirect($return_url);
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
