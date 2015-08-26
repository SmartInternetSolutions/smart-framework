<?php

class SF_Controller_Error extends SF_Controller_Abstract
{
	public function handleIndex()
	{
        $this->redirect($this->getUrl());
	}
    
	public function handle404()
	{
        $this->getResponse()
            ->setBody('404')
            ->setHttpResponseCode(404);
    }
    
	public function handleException(Exception $e)
	{
        $this->getResponse()
            ->setBody('500, ' . get_class($e) . ': ' . $e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>')
            ->setHttpResponseCode(500);
	}
}
