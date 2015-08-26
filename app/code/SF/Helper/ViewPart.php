<?php

class SF_Helper_ViewPart
{
	static protected $_instance = null;

	/**
	 * @return SF_Helper_ViewPart
	 */
    static public function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

	public function __()
	{
        return call_user_func_array(array(SF_Helper_I18n::getInstance(), 'translate'), func_get_args());
	}

	public function isAjax()
	{
        return $this->getController()->getRequest()->getParam('extension') === 'json';
	}

	public function isCsv()
	{
        return $this->getController()->getRequest()->getParam('extension') === 'csv';
	}

    public function formatNiceDateHtml($timestamp, $noHtmlAbbr = false)
    {
        $dateTime = (!$timestamp instanceof DateTime) ? new DateTime($timestamp) : $timestamp;

        if ($noHtmlAbbr) {
            return $this->formatNiceDate($timestamp);
        }

        return  '<abbr title="' . $dateTime->format('c') . '" class="dt sf-datetime">' .
                /* FIXME: $this->_escape */ ($this->formatNiceDate($timestamp)) .
                '</abbr>';
    }

    public function formatNiceDate($dateTime)
    {
        if (!$dateTime instanceof DateTime) {
            $dateTime = new DateTime($dateTime);
        }

        if ($dateTime->getTimestamp() === false) {
            return '';
        }
        
        try {
            $dateTime = new Zend_Date($dateTime->getTimestamp());
        } catch (Exception $e) {
            return '';
        }
        $delta = time() - $dateTime->getTimestamp();

        if ($delta > 86400 * 2 || $delta < 0) {
            $format = Zend_Date::DATE_MEDIUM;
        } else if ($delta > 86400) {
            return $this->__('about a day ago');
        } else if ($delta > 3600) {
            $hrs = floor($delta / 3600);
            return $hrs == 1 ?
                $this->__('about an hour ago') :
                $this->__('{0} hours ago', $hrs);
        } else if ($delta > 300) {
            $mins = floor($delta / 60);
            return $mins == 1 ?
                $this->__('about a minute ago') :
                $this->__('{0} minutes ago', $mins);
        } else {
            return $this->__('couple of seconds ago');
        }

        return $dateTime->toString($format);
    }

    public function formatNumber($number, $decimals = 0)
    {
        return number_format($number, $decimals, '.', ',');
    }
    
    public function formatCurrency($number, $decimals = 2, $currency = 'EUR')
    {
        return $this->formatNumber($number, $decimals, '.', ',') . ' ' . $currency;
    }

	public function getBasePath()
	{
		return BASEDIR;
	}

    public function getBaseUrl()
    {
        return rtrim('http://' . $this->getRequest()->getHttpHost() . SF::getInstance()->getBaseUrl(), '/');
    }

	/**
	 *
	 * @return SF_Controller_Abstract
	 */
	public function getController()
	{
		return Zend_Registry::get('current_controller');
	}

	/**
	 * @return Zend_Controller_Request_Http
	 */
	public function getRequest()
	{
		return SF::getInstance()->getRequest();
	}

	/**
	 * @return Zend_Controller_Response_Http
	 */
	public function getResponse()
	{
		return SF::getInstance()->getResponse();
	}

	public function getCurrentUri()
	{
	    return $this->getRequest()->getRequestUri();
	}
    
    public function getCurrentRoute()
    {
        $parts = array($this->getRequest()->getControllerName(), $this->getRequest()->getActionName());
        
        if (($id = $this->getRequest()->getParam('id')) !== null) {
            $parts[] = $id;
        }
        
        return implode('/', $parts);
    }

    /**
     * @deprecated use URL_TYPE_HTML instead
     */
    const URL_TYPE_DEFAULT  = 'html';
    
    const URL_TYPE_HTML     = 'html';
    
    const URL_TYPE_JSON     = 'json';
    const URL_TYPE_XML      = 'xml';
    const URL_TYPE_CSV      = 'csv';
    
    const URL_TYPE_JS       = 'js';
    const URL_TYPE_CSS      = 'css';

    const URL_TYPE_ACTION   = 'action';

    protected function _compileUrlRightPart($controller = 'index', $action = 'index', $id = null, $type = self::URL_TYPE_DEFAULT)
    {
        $baseUrl = ''; // FIXME: probably broken here. Actually in SF 1.3.0 imagespace branch, it's calling ZF's getBaseUrl method.

        if ($controller === '*') {
            $controller = $this->getRequest()->getControllerName();
        }

        if ($action === '*') {
            $action = $this->getRequest()->getActionName();
        }

        if ($action === 'index') {
            if ($controller !== 'index') {
                return $baseUrl . $controller . '/';
            } else {
                return $baseUrl;
            }
        }

        // FIXME: respect / with $baseUrl..
        if ($id === null) {
            $url = '/' . $controller . '/' . $action . '.' . $type;
        } else {
            $id = urlencode($id);

        	$in = '/' . $controller . '/' . $action . '.' . $type;

        	$guessOne = SF::getInstance()->getRouter()->rewriteUrl($in);

        	if ($in === $guessOne) {
            	$url = '/' . $controller . '/' . $action . '/' . $id . '.' . $type;
        	} else {
        		$url = $guessOne . '?id=' . $id;
        	}
        }

        return ltrim(rtrim($baseUrl, '/') . SF::getInstance()->getRouter()->rewriteUrl($url), '/');
    }
    
    public function getUrlByRoute($route)
    {
        $parts = explode('/', trim($route, '/'));
        
        return $this->getUrl(
            isset($parts[0]) ? $parts[0] : 'index', 
            isset($parts[1]) ? $parts[1] : 'index', 
            isset($parts[2]) ? $parts[2] : null
        );
    }

    public function getUrl($controller = 'index', $action = 'index', $id = null, $type = self::URL_TYPE_DEFAULT)
    {
        $url = SF::getInstance()->getBaseUrl() . $this->_compileUrlRightPart($controller, $action, $id, $type);
        
        if ($url === '//') {
            $url = '/';
        }
        
        return $url;
    }
    
    public function getSecureUrl($controller = 'index', $action = 'index', $id = null, $type = self::URL_TYPE_DEFAULT)
    {
        return 'http://' . SF::getInstance()->getRequest()->getHttpHost() . $this->getUrl($controller, $action, $id, $type);
    }

    /**
     * returns resource's current url
     *
     * @since 1.1.2 
     * @since 1.2.2 now in Abstract
     * @since 1.3.2 now in ViewPart
     */
    protected function _resUrl($file, $addmtime = true)
    {
        if (preg_match('/^https?:\/\//', $file)) {
            return $file;
        }

        $fn = SF::getInstance()->getBasePath() . '/public/' . $file;

        $url = SF::getInstance()->getBaseUrl() . $file;

        if (file_exists($fn) && $addmtime) {
            $url .= '?v=' . filemtime($fn);
        }

        return $url;
    }
}