<?php

/**
 * 
 */
interface SF_Interface_Controller_V2
{
    public function preHandle(SF_Model_Frontend_State $state);
    public function postHandle(SF_Model_Frontend_State $state);
}
