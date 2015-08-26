<?php

error_reporting(E_ALL|E_STRICT|E_NOTICE);
ini_set('display_error', 'On');
define('BASEDIR', dirname(__FILE__) . '/..');

ini_set('include_path', BASEDIR . '/app/code/' . PATH_SEPARATOR . BASEDIR . '/lib/' . PATH_SEPARATOR . ini_get('include_path'));
// ini_set('include_path', BASEDIR . '/app/code/' . PATH_SEPARATOR . BASEDIR . '/lib/');

require(BASEDIR . '/app/code/SF.php');

try {
    if (!defined('SF_DONT_START')) {
        SF::main('production');
    }
} catch(SF_Exception_Db_EntityNotFound $e) {
    SF::getInstance()->handleAction('static', 'error404');
} catch(SF_Exception_Controller_BadName $e) {
    SF::getInstance()->handleAction('static', 'error404');
} catch(SF_Exception_Exit $e) {
    SF::getInstance()->getResponse()->sendResponse();
} catch(Exception $e) {
//    $ip = SF::getInstance()->getRequest()->getClientIp();
//
//    if ($ip !== '::1' && $ip !== '127.0.0.1') {
//        SF::sendExceptionMail($e);
//        SF::getInstance()->getResponse()->setRedirect('/')->sendResponse();
//    } else {
//        throw $e;
//    }
        
    
    echo get_class($e) . ': ' . $e->getMessage() . ' // ' . $e->getTraceAsString();
//    SF::getInstance()->getResponse()->setRedirect(SF_Helper_ViewPart::getInstance()->getUrl())->sendResponse();
}
