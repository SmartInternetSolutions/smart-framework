<?php

/**
 * frontend state model
 * 
 * contains
 *  - current request
 *  - current response
 *  - current session
 * 
 */
class SF_Model_Frontend_State
{
    protected $_request, $_response, $_session;
    
    public static function create(Zend_Controller_Request_Http $request, Zend_Controller_Response_Http $response, Zend_Session_Namespace $session)
    {
        $state = new self; 
        
        $state->_request = $request;
        $state->_response = $response;
        $state->_session = $session;
        
        return $state;
    }
    
    public function getRequest()
    {
        return $this->_request;
    }
    
    public function getResponse()
    {
        return $this->_response;
    }
    
    public function getSession()
    {
        return $this->_session;
    }
}