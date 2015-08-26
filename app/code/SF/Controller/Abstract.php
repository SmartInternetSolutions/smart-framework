<?php

abstract class SF_Controller_Abstract extends SF_Helper_ViewPart implements SF_Interface_Controller
{
    const URL_BASE_STATIC = 'http://static.sfpage.local';
	/**
	 * @var SF_Block_Abstract
	 */
	protected $_view = null;

	protected function _prepareView()
	{
		$this->_view = new SF_Block_Html();
		$this->_view->setBlock('header', new SF_Block_Html_Header());
		$this->_view->setBlock('content', new SF_Block_Html());
		$this->_view->setBlock('footer', new SF_Block_Html_Footer());

		return $this;
	}

	/**
	 * @return SF_Block_Abstract
	 */
	public function getView()
	{
		if ($this->_view === null) {
            $this->_prepareView ();
		}

		return $this->_view;
	}

	public function redirect($uri)
	{
        $this->getResponse()
            ->clearAllHeaders()
            ->setHttpResponseCode(301)
            ->setHeader('Location', $uri, true)
            ->setBody('redirected to ' . $uri);
	}

    public function preHandle()
    {
        return true;
    }

    public function postHandle()
    {
    }

    protected function _sealProtectedArea()
    {
        $request = $this->getRequest();

        $hostname = $request->getHttpHost();
        $referrer = $request->getServer('HTTP_REFERER');

        if ($referrer === null || parse_url($referrer, PHP_URL_HOST) !== $hostname) {
            $this->getResponse()
                ->setHttpResponseCode(403)
                ->setBody('wrong or invalid referrer.');

			exit();
        }
    }

    protected function _sendAttachment($mime, $filename, $content)
    {
        $this->getResponse()
            ->setHeader('Content-Disposition', 'attachment;filename=' . $filename)
            ->setHeader('Content-Type', $mime)
            ->setBody($content);

        throw new SF_Exception_Exit();
    }
}