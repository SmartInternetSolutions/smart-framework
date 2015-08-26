<?php

class SF_Controller_Static extends SF_Controller_Abstract
{
	public function handleIndex()
	{
	}

	protected function _renderStatic($tmpl)
	{
		$static = new SF_Block_Html_Page_Static();
		$static->setTemplate('static/' . $tmpl);

		$this->getView()->setBlock('content', $static);

		$this->getView()->render();
	}

	public function handleError()
	{
	    $this->getView()->getBlock('header')->setTitle($this->__('Error'));

        $this->_renderStatic('error.phtml');
	}
}
