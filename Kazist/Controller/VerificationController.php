<?php

/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Users\Components\Users\Controllers;

defined('KAZIST') or exit('Not Kazist Framework');

use Users\Components\Users\Models\UsersModel;
use Kazist\Controller\KazistController;
use Kazist\Service\KaziFactory;

/**
 * Dashboard Controller class for the Application
 *
 * @since  1.0
 */
class VerificationController extends KazistController {

    public function verification() {

        $factory = new KaziFactory();
        

        $verification = $this->request->request->get('verification');


        if ($verification <> '') {

            $usersModel = new UsersModel();
            $verified = $usersModel->accountVerification($verification);
            
            if ($verified) {
                $msg = 'Account Verified.';
                $factory->enqueueMessage($msg, 'error');
                $return_url = $this->generateUrl('users.users&subset=users&view=login');
            } else {
                $msg = 'Invalid Verification Code.';
                $factory->enqueueMessage($msg, 'error');
                $return_url = $this->generateUrl('social.member&subset=member&view=edit');
            }
        } else {
            $msg = 'Invalid Url';
            $factory->enqueueMessage($msg, 'error');
            $return_url = $this->generateUrl('social.member&subset=member&view=edit');
        }

        $this->getApplication()->redirect($return_url);
    }

}
