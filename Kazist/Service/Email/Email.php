<?php

namespace Kazist\Service\Email;

defined('KAZIST') or exit('Not Kazist Framework');

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Kazist\KazistFactory;
use Kazist\StringModification;
use Kazist\Service\Database\Query;

//use Kazist\Service\Email\Phpmailer\PHPMailer;


class Email {

    var $sql_limit = 40;
    var $sql_offset = 0;
    var $is_html = true;
    var $debug_exit = false;
    var $use_template = true;
    var $parameter_name = '';
    var $parameters = array();
    var $container = "";
    var $priority = 3;
    var $send_date = "";

    public function __construct() {
        global $sc;
        $this->container = $sc;
        $this->send_date = date('Y-m-d H:i:s', strtotime('- 3 days'));
    }

    /**
     * 
     * @param string $cc
     * @param string $name
     * @return \Kazist\Service\Email\Email
     */
    public function addCC($cc, $name = '') {

        $string_modifier = new StringModification;

        // If the carbon copy recipient is an array, add each recipient... otherwise just add the one
        $cc = $string_modifier->cleanLine($cc);
        $name = $string_modifier->cleanLine($name);

        $this->parameters['cc'][] = array('email' => $cc, 'name' => $name);

        return $this;
    }

    /**
     * 
     * @param array $cc
     * @return \Kazist\Service\Email\Email
     */
    public function addCCes($cc) {
        // If the carbon copy recipient is an array, add each recipient... otherwise just add the one
        if (is_array($cc)) {
            foreach ($cc as $to) {
                $this->addCC($to['recipient'], $to['name']);
            }
        }

        return $this;
    }

    /**
     * 
     * @param string $bcc
     * @param string $name
     * @return \Kazist\Service\Email\Email
     */
    public function addBCC($bcc, $name = '') {

        $string_modifier = new StringModification;

        // If the blind carbon copy recipient is an array, add each recipient... otherwise just add the one
        $bcc = $string_modifier->cleanLine($bcc);
        $name = $string_modifier->cleanLine($name);

        $this->parameters['bcc'][] = array('email' => $bcc, 'name' => $name);

        return $this;
    }

    /**
     * 
     * @param string $bcc
     * @param string $name
     * @return \Kazist\Service\Email\Email
     */
    public function addBCCes($bcc) {
        // If the blind carbon copy recipient is an array, add each recipient... otherwise just add the one
        if (is_array($bcc)) {
            foreach ($bcc as $to) {
                $this->addBCC($to['recipient'], $to['name']);
            }
        }

        return $this;
    }

    /**
     * 
     * @param array $attachment
     * @param string $name
     * @param string $encoding
     * @param string $type
     * @return \Kazist\Service\Email\Email
     */
    public function addAttachment($attachment, $name = '', $encoding = 'base64', $type = 'application/octet-stream') {
        // If the file attachments is an array, add each file... otherwise just add the one
        if (isset($attachment)) {
            if (is_array($attachment)) {
                foreach ($attachment as $file) {
                    $this->parameters['attachment'][] = array('file' => $file, 'name' => $name, 'encoding' => $encoding, 'type' => $type);
                }
            } else {
                $this->parameters['attachment'][] = array('file' => $file, 'name' => $name, 'encoding' => $encoding, 'type' => $type);
            }
        }

        return $this;
    }

    /**
     * 
     * @param string $replyto
     * @param string $name
     * @return \Kazist\Service\Email\Email
     */
    public function addReplyTo($replyto, $name = '') {

        $string_modifier = new StringModification;

        // Take care of reply email addresses
        $replyto = $string_modifier->cleanLine($replyto);
        $name = $string_modifier->cleanLine($name);

        $this->parameters['replyto'][] = array('replyto' => $replyto, 'name' => $name);

        return $this;
    }

    /**
     * 
     * @param array $replyto
     * @return \Kazist\Service\Email\Email
     */
    public function addReplyTos($replyto) {
        // Take care of reply email addresses
        if (is_array($replyto)) {
            foreach ($replyto as $to) {
                $this->addReplyTo($replyto['recipient'], $replyto['name']);
            }
        }

        return $this;
    }

    /**
     * 
     * @param string $recipient
     * @param string $name
     * @return \Kazist\Service\Email\Email
     */
    public function addRecipient($recipient, $name = '') {

        $string_modifier = new StringModification;

        // If the recipient is an array, add each recipient... otherwise just add the one
        $recipient = $string_modifier->cleanLine($recipient);
        $name = $string_modifier->cleanLine($name);

        if ($name <> '' && $recipient <> '') {
            $this->parameters['recipient'][] = array($recipient => $name);
        } else {
            $this->parameters['recipient'][] = $recipient;
        }
        return $this;
    }

    /**
     * 
     * @param array $recipients
     * @return \Kazist\Service\Email\Email
     */
    public function addRecipients($recipients) {
        // If the recipient is an array, add each recipient... otherwise just add the one
        if (is_array($recipient)) {
            foreach ($recipient as $to) {
                $this->addRecipient($to['recipient'], $to['name']);
            }
        }

        return $this;
    }

    /**
     * 
     * @param string $from_email
     * @param string $from_name
     * @param bool $auto same as reply
     * @return \Kazist\Service\Email\Email
     */
    public function setSender($from_email, $from_name = '', $auto = 1) {

        $string_modifier = new StringModification;

        $from_email = $string_modifier->cleanLine($from_email);
        $from_name = $string_modifier->cleanLine($from_name);

        $this->parameters['sender'] = array('from_email' => $from_email, 'from_name' => $from_name);

        return $this;
    }

    /**
     * 
     * @param string $subject
     * @return \Kazist\Service\Email\Email
     */
    public function setSubject($subject) {

        $string_modifier = new StringModification;

        $subject = $string_modifier->cleanLine($subject);

        $this->parameters['subject'] = $subject;

        return $this;
    }

    /**
     * 
     * @param string $content
     * @return \Kazist\Service\Email\Email
     */
    public function setBody($content) {

        $string_modifier = new StringModification;

        $body = $string_modifier->cleanText($content);

        $this->parameters['body'] = $body;
        return $this;
    }

    public function clearMailerObject($clear_addr = true, $clear_reci = true, $clear_attach = true, $clear_bcc = true, $clear_cc = true) {

        if ($clear_addr) {
            $this->parameters['address'] = '';
        }
        if ($clear_reci) {
            $this->parameters['recipient'] = '';
        }
        if ($clear_attach) {
            $this->parameters['attachment'] = '';
        }
        if ($clear_bcc) {
            $this->parameters['bcc'] = '';
        }
        if ($clear_cc) {
            $this->parameters['cc'] = '';
        }
    }

    public function sendEmail($subject, $body, $recipient, $parameters = array(), $attachments = array(), $priority = 0, $template_id = '') {


        $factory = new KazistFactory();

        $data_obj = new \stdClass();
        $data_obj->subject = $subject;
        $data_obj->body = $body;
        $data_obj->recipients = json_encode($recipient);
        $data_obj->parameters = json_encode($parameters);
        $data_obj->attachments = json_encode($attachments);
        $data_obj->priority = ($priority) ? $priority : $this->priority;
        $data_obj->email_type = 'direct';
        $data_obj->uniq_name = session_id();
        $data_obj->send_date = $this->send_date;

        $factory->saveRecord('#__notification_emails', $data_obj);
    }

    public function prepareBody($subject, $body, $parameters) {

        $template_body = $body;

        $tmp_array = array();
        $tmp_array['layout'] = $body;
        $tmp_array['email_title'] = (isset($parameters['email_title']) && $parameters['email_title'] <> '') ? $parameters['email_title'] : $subject;
        $tmp_array['recipient'] = $parameters['recipient'];

        if ($this->use_template) {

            $template = $this->getTemplate();
            $template_body = $template->template;
        }

        if ($template->template <> '') {
            $body = $this->processParameters($template_body, $tmp_array);
        }

        return $body;
    }

    /**
     * Function for sending email:
     * @subject string $subject
     * @body string $body
     * @recipients array/string $recipients
     * @parameters array/string $parameters
     * @attachements array $attachments
     * @priority int $priority
     * $recipients and $parameters can be either array or sql base64 encode string.
     * if $parameters has recipient field $recipients list will be ignored.
     */
    public function sendPreparedEmail($subject, $body, $recipients = array(), $parameters = array(), $attachments = array(), $priority = 0, $template_id = '', $email_id = '') {


        $factory = new KazistFactory();

        $data_obj = new \stdClass();
        $data_obj->subject = $subject;
        $data_obj->body = $body;
        $data_obj->recipients = json_encode($recipients);
        $data_obj->parameters = json_encode($parameters);
        $data_obj->attachments = json_encode($attachments);
        $data_obj->priority = ($priority) ? $priority : $this->priority;
        $data_obj->email_type = 'prepared';
        $data_obj->uniq_name = session_id();
        $data_obj->send_date = $this->send_date;

        $factory->saveRecord('#__notification_emails', $data_obj);
    }

    /**
     * Function for sending email:
     * @action string $action
     * @path string $path
     * @recipients array/string $recipients
     * @parameters array/string $parameters
     * @attachements array $attachments
     * @priority int $priority
     * $recipients and $parameters can be either array or sql base64 encode string.
     * if $parameters has recipient field $recipients list will be ignored.
     */
    public function sendDefinedLayoutEmail($unique_name, $recipients = array(), $parameters = array(), $attachments = array(), $priority = 0, $template_id = '', $email_id = '') {

        $factory = new KazistFactory();

        $layout = $this->getLayout($unique_name);

        $this->priority = ($priority) ? $priority : $this->priority;
         
        if (!empty($layout)) {

            $subject = $layout->subject;
            $body = $layout->body;

            if ($layout->published) {
                $this->sendPreparedEmail($subject, $body, $recipients, $parameters, $attachments, $priority, $email_id);
            } else {
                $msg = 'Error:Layout [' . $unique_name . '] is Unpublished.';
            }
        } else {
            $msg = 'Error:Layout [' . $unique_name . '] Does Not Exist';
            $factory->enqueueMessage($msg);
        }
    }

    /**
     * 
     * @param string $html
     * @param array $parameters
     * @return string
     */
    public function processParameters($html, $parameters) {

        $factory = new KazistFactory();

        $parameters = (isset($parameters[0])) ? array('items' => $parameters) : $parameters;

        try {
            $new_html = $factory->renderString($html, $parameters);
        } catch (\Exception $ex) {
            throw new $ex;
        }

        return $new_html;
    }

    public function getLayout($unique_name) {

        $query = new Query();
        $query->select('*');
        $query->from('#__notification_templates_layouts');
        $query->where('unique_name=:unique_name');
        $query->setParameter('unique_name', $unique_name);

        $record = $query->loadObject();

        if (is_object($record)) {
            return $record;
        } else {
            return false;
        }
    }

    public function getTemplate($template_id = '') {

        $query = new Query();
        $query->select('*');
        $query->from('#__notification_templates');
        $query->andWhere('is_default=1 OR published=1');
        if ($template_id) {
            $query->where('id=' . $template_id);
        }

        $record = $query->loadObject();

        if (is_object($record)) {
            return $record;
        } else {
            return false;
        }
    }

    public function getEmailFromArray($recipient) {

        $email = '';

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $email = $recipient;
        } elseif (isset($recipient['recipient'])) {
            $email = $recipient['recipient'];
        } elseif (isset($recipient['email'])) {
            $email = $recipient['email'];
        }

        return (filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : '';
    }

    /**
     * 
     * @param array $recipients
     * @return array
     */
    public function getRecipients($recipients) {

        $tmp_recipients = array();

        if ($recipients == '') {
            $tmp_recipients = $recipients;
        } elseif (!is_array($recipients) && filter_var(trim($recipients, ' '), FILTER_VALIDATE_EMAIL)) {
            $tmp_recipients[] = $recipients;
        } elseif (is_array($recipients)) {
            $tmp_recipients = $recipients;
        } elseif (is_object($recipients)) {
            $tmp_recipients[] = $recipients;
        } elseif (base64_encode(base64_decode($recipients, true)) === $recipients) {
            $query = base64_decode($recipients);
            $tmp_recipients = $this->getQueryRecords($query);
        }

        $new_recipients = json_decode(json_encode($tmp_recipients), true);

        return $new_recipients;
    }

    /**
     * 
     * @param array $parameters
     * @return array
     */
    public function getParameters($parameters) {

        $tmp_parameters = array();

        if ($parameters == '') {
            $tmp_parameters = $parameters;
        } elseif (is_object($parameters)) {
            $tmp_parameters = (array) $parameters;
        } elseif (is_array($parameters)) {
            $tmp_parameters = $parameters;
        } else {
            $query = base64_decode($parameters);
            $tmp_parameters = $this->getQueryRecords($query, true);
        }


        $new_parameters = json_decode(json_encode($tmp_parameters), true);

        return $new_parameters;
    }

    /**
     * 
     * @param type $tmp_parameters
     * @return boolean
     */
    public function checkHasRecipient($tmp_parameters) {

        $has_recipient = false;

        if ($tmp_parameters <> '' && is_array($tmp_parameters)) {
            if (isset($tmp_parameters[0]['recipient']) ||
                    isset($tmp_parameters['recipient']) ||
                    isset($tmp_parameters[0]['email']) ||
                    isset($tmp_parameters['email'])
            ) {
                $has_recipient = true;
            }
        }

        return $has_recipient;
    }

    /**
     * 
     * @param type $query
     * @return array
     */
    public function getQueryRecords($query_str, $is_object_arr = false) {

        $factory = new KazistFactory();

        if (!strpos($query_str, 'LIMIT') && !strpos($query_str, 'OFFSET')) {
            $query_str .= ' LIMIT ' . $this->sql_limit . ' OFFSET  ' . $this->sql_offset;
        } elseif (strpos($query_str, 'LIMIT') && !strpos($query_str, 'OFFSET')) {
            $query_str .= ' OFFSET  ' . $this->sql_offset;
        } elseif (!strpos($query_str, 'LIMIT') && strpos($query_str, 'OFFSET')) {
            $query_str .= ' LIMIT ' . $this->sql_limit;
        }

        $factory->loggingMessage('Email Query :- ' . $query_str);

        $query = new Query();
        $query->setQuery($query_str);


        $records = $query->loadObjectList();

        return $records;
    }

    /**
     * 
     * @param type $sendmail
     * @return boolean
     */
    public function useSendmail($sendmail = null) {
        $this->Sendmail = $sendmail;

        if (!empty($this->Sendmail)) {
            $this->IsSendmail();

            return true;
        } else {
            $this->IsMail();

            return false;
        }
    }

    public function prepareRecipientList() {
        $recipients = array();

        $factory = new KazistFactory();
        $db = $factory->getDatabase();

        $query = $db->getQuery(true);
        $query->select('email');
        $query->from('#__users_users');
        $db->setQuery($query);
        $records = $db->loadObjectList();

        if (!empty($records)) {
            foreach ($records as $record) {
                $recipients[] = $record->email;
            }
        }

        return $recipients;
    }

    public function saveEmailToHarvester($email) {

        $factory = new KazistFactory();
        $user = $factory->getUser();

        $group_id = $this->getDefaultHarvesterGroup();

        $email_arr = explode('@', $email);
        //  $group_id = $this->getHarvesterGroup();

        if ($group_id) {

            $email_obj = new \stdClass();
            $email_obj->email = $email;
            $exist_obj = clone $email_obj;
            $email_obj->user_id = $user->id;
            $email_obj->name = $email_arr[0];

            $subscriber = $factory->getRecord('#__notification_subscribers', 'ns', array('ns.email=:email'), $exist_obj);

            if (!is_object($subscriber)) {
                $email_obj->published = 1;
                $subscriber_id = $factory->saveRecord('#__notification_subscribers', $email_obj);
            } else {
                $subscriber_id = $subscriber->id;
            }


            $subscriber_obj = new \stdClass();
            $subscriber_obj->subscriber_id = $subscriber_id;
            $subscriber_obj->group_id = $group_id;

            $factory->saveRecordByEntity('#__notification_groups_subscribers', $subscriber_obj, array('ngs.subscriber_id=:subscriber_id', 'ngs.group_id=:group_id'), $subscriber_obj);
        }
    }

    public function getDefaultHarvesterGroup() {

        $query = new Query();
        $query->select('ng.*');
        $query->from('#__notification_groups AS ng');
        $query->orderBy('ng.id', 'ASC');
        $group = $query->loadObject();

        return $group->id;
    }

    public function getHarvesterGroup() {

        $factory = new KazistFactory();

        $document = $this->container->get('document');

        $query = new Query();
        $query->select('ng.*');
        $query->from('#__notification_groups AS ng');
        $query->where('ng.subset_id=' . $document->subset_id);
        $group = $query->loadObject();

        if (isset($group->id) && $group->id) {
            return $group->id;
        } else {

            $query = new Query();
            $query->select('nsh.*');
            $query->from('#__notification_subscribers_harvesters AS nsh');
            $query->where('nsh.subset_id=' . $document->subset_id);
            $harvester = $query->loadObject();

            if (is_object($harvester)) {

                $data_obj = new \stdClass();

                $data_obj->subset_id = $harvester->subset_id;
                $exist_obj = clone $data_obj;
                $data_obj->title = $harvester->title;
                $data_obj->name = ($harvester->name <> '') ? $harvester->name : $harvester->title;
                $data_obj->description = ($harvester->description <> '') ? $harvester->description : $harvester->title;
                $data_obj->published = 1;

                return $factory->saveRecord('#__notification_groups', $data_obj, array('subset_id=:subset_id'), $exist_obj);
            }
        }
    }

    public function canReceiveEmails($email) {

        $factory = new KazistFactory();

        $sending_email = $factory->getSetting('notification_subscribers_sending_email');

        $query = new Query();
        $query->select('ns.email, '
                . '(SELECT COUNT(*) FROM #__notification_subscribers_blacklist WHERE subscriber_id=ns.id) AS blacklist, '
                . ' (SELECT COUNT(*) FROM #__notification_subscribers_whitelist WHERE subscriber_id=ns.id) AS whitelist '
        );
        $query->from('#__notification_subscribers AS ns');
        $query->where('email=:email');
        $query->setParameter('email', $email);

        $record = $query->loadObject();

        if ($record->whitelist) {
            return true;
        } elseif ($record->blacklist) {
            return false;
        } elseif ($sending_email) {
            return true;
        }

        return false;
    }

}
