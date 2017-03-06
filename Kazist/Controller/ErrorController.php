<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of LeapYearController
 *
 * @author sbc
 */

namespace Kazist\Controller;

defined('KAZIST') or exit('Not Kazist Framework');

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Debug\Exception\FlattenException;
use Kazist\KazistFactory;

class ErrorController extends BaseController {

    public function exceptionAction(FlattenException $exception) {

        $factory = new KazistFactory();

        $msg = $tmp_msg = '';
        $tmp_msg .= 'Something went wrong! (' . $exception->getMessage() . ')';
        $tmp_msg .= '<br>';
        $tmp_msg .= $exception->getMessage() . ' ' . $exception->getFile() . ' ' . $exception->getLine();

        $msg .= $tmp_msg . '<table class="table">';
        $msg .= '<tr>';
        $msg .= '<td>File</td>';
        $msg .= '<td>Line</td>';
        $msg .= '</tr>';
        foreach ($exception->getTrace() as $key => $trace) {
            $msg .= '<tr>';
            $msg .= '<td>' . $trace['file'] . ' </td><td> ' . $trace['line'] . '</td>';
            $msg .= '</tr>';
        }
        $msg .='</table>';

        $factory->loggingMessage($msg);

        return new Response($tmp_msg, $exception->getStatusCode());
    }

}
