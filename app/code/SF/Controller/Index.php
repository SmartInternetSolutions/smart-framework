<?php

class SF_Controller_Index extends SF_Controller_Abstract
{
	/**
	 * @var SF_Block_Abstract
	 */
	protected $_view = null;

	protected function _prepareView()
	{
		$this->_view = new SF_Block_Html();
		$this->_view->setBlock('header', new SF_Block_Html_Header());
		$this->_view->setBlock('content', new SF_Block_Html_Page_Home());
		$this->_view->setBlock('footer', new SF_Block_Html_Footer());

		return $this;
	}

	public function handleIndex()
	{
		$this->getView()
			->getBlock('header');

		$this->getView()->render();
	}
}
