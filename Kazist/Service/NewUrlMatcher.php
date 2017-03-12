<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Kazist\Service;

defined('KAZIST') or exit('Not Kazist Framework');

use Symfony\Component\Routing;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Matcher\UrlMatcher;

/**
 * Class for implementing custom url matching
 */
class NewUrlMatcher extends UrlMatcher {

    /**
     * Matching Function:- function that extends matching
     * 
     * @params $pathinfo
     * 
     * @return $pathinfo
     */
    public function match($pathinfo) {

    
        try {

            $result = parent::match($pathinfo);
 
        } catch (\Exception $e) {

                $this->routes->add('generato', new Routing\Route('/generato', array(
                    '_controller' => 'Generator\\Code\\Controllers\\GeneratorController::indexAction',
                )));
  
            try {

                $result = parent::match($pathinfo);
                
            } catch (\Exception $e) {
                throw $e;
            }
        }


        return $result;
    }

}
