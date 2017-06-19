<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GoogleListener
 *
 * @author sbc
 */

namespace Kazist\Listener;

defined('KAZIST') or exit('Not Kazist Framework');

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Kazist\Service\System\Template\Template;
use Kazist\Service\System\Template\Assets;
use Kazist\Service\System\Template\Debugger;
use Kazist\Service\System\Cron;
use Kazist\Service\System\System;
use Kazist\Service\Email\EmailSender;
use Kazist\KazistFactory;

class KazistKernelListener implements EventSubscriberInterface {

    public $container = '';

    public function onKernelRequest(GetResponseEvent $event) {
        global $sc;

        $this->container = $sc;

        $request = $event->getRequest();

        $is_redirect = $this->container->get('session')->get('is_redirect');

        if (!$is_redirect) {
            $this->container->get('session')->getFlashBag()->clear();
            gc_collect_cycles();
        }

        $this->container->get('session')->remove('is_redirect');
        $this->container->get('session')->remove('template.appended');
    }

    public function onKernelFinishRequest(FinishRequestEvent $event) {

        global $sc;
        $factory = new KazistFactory();

        $this->container = $sc;

        $request = $event->getRequest();

        $this->container->get('session')->remove('twig_context');
        $this->container->get('session')->remove('kazist_assets');
        $this->container->get('session')->remove('follow_exist');
        $this->container->get('session')->remove('media_exist');
        //  

        try {
            if (!connection_aborted()) {
                $this->kernelTerminateActions($request);
            }
        } catch (\Exception $ex) {
            $factory->loggingMessage($ex->getMessage());
        }
    }

    public function onKernelResponse(FilterResponseEvent $event) {

        global $sc;

        $this->container = $sc;

        $response = $event->getResponse();
        $request = $event->getRequest();

        $type = $response->headers->get('content-type');

        $response_content = $response->getContent();

        $response_content = $this->loadPropareImageUrl($response_content);

        if ($type !== 'application/json' && $response->getStatusCode() == 200) {

            $response_content = $this->loadTemplate($response_content, $request, $response->getStatusCode());
            $response_content = $this->manageAssets($response_content, $request);
            $response_content = $this->loadDebugger($response_content, $request);

            $response->setContent($response_content);
        }
    }

    public function onKernelTerminate(PostResponseEvent $event) {
        
    }

    public function loadPropareImageUrl($response_content, $request) {

        $system = new System();

        $response_content = $system->processImagesUrl($response_content);

        //print_r($response_content); exit;

        return $response_content;
    }

    public function loadTemplate($response_content, $request) {

        $notheme = $request->get('notheme');

        if ($notheme == '') {
            $template = new Template($this->container, $request);

            $response_content = $template->applyTemplateToResponse($response_content);
        }

        return $response_content;
    }

    public function manageAssets($response_content, $request) {

        $assets = new Assets($this->container, $request);

        $response_content = $assets->manageAssets($response_content);

        return $response_content;
    }

    public function loadDebugger($response_content, $request) {

        if ($this->container->getParameter('system.debugging')) {
            $degugger = new Debugger($this->container, $request);

            $response_content = $degugger->loadDebugger($response_content);
        }
        return $response_content;
    }

    public function kernelTerminateActions($request) {

        global $sc;

        $this->container = $sc;

        $factory = new KazistFactory();
        $emailSender = new EmailSender($this->container, $request);
        $system = new System($this->container, $request);
        $cron = new Cron($this->container, $request);

        $emailSender->sendEmailList(1);

        if ($factory->getSetting('system_cron_run_page_load')) {

            $cron_list = $cron->getCronList();

            if (!empty($crons)) {
                foreach ($crons as $key => $cron) {
                    $cron_str = $cron->getCronStr($cron);
                    $cron->updateNextRunTime($single_cron->id, $cron_str);
                }
            }

            foreach ($cron_list as $single_cron) {
                if ($single_cron->unique_name) {
                    $system->callCurlByUniqueName($single_cron->unique_name);
                    $cron->updateCompleted($single_cron->id);
                }
            }
        }

        return;
    }

    public static function getSubscribedEvents() {
        return array('kernel.finish_request' => 'onKernelFinishRequest', 'kernel.response' => 'onKernelResponse', 'kernel.request' => 'onKernelRequest', 'kernel.terminate' => 'onKernelTerminate');
    }

}
