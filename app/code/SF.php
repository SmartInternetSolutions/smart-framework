<?php

final class SF
{
    const PRODUCT_NAME      = 'Smart Framework';
    const PRODUCT_VERSION   = '1.3.2';

	private $_request = null;
	private $_response = null;
    private $_router = null;
    private $_observer = null;
	private $_session = null;
	private $_config = null;

	public function autoload($className)
	{
        // PHP makes me cry. cannot supress file not found warning on include
        // and allow warnings while executing it... so we need to check it
        // existance on our own.

        $fn = str_replace('_', '/', $className) . '.php';

        $paths = explode(PATH_SEPARATOR, get_include_path());

        $paths[] = $this->getBasePath() . '/app/code';
        $paths[] = $this->getBasePath() . '/lib';

        foreach ($paths as $path) {
            $pfn = $path . '/' . $fn;

            if (file_exists($pfn)) {
                require_once ($pfn);
            }
        }
    }

	/**
	 *
	 * @return Zend_Config
	 */
	public function getConfig($node = null)
	{
        if ($node !== null) {
            return $this->_config->{$node};
        }

		return $this->_config;
	}

	public function getBasePath()
	{
		return BASEDIR;
	}

    /**
     * @since 1.1.2
     */
    public function getBaseUrl()
    {
        return $this->getRequest()->getBaseUrl() . '/';
    }

    private $_databaseReady     = false;
    private $_databaseAdapter   = null;

    public function initDatabase()
    {
        if (!$this->_databaseReady) {
            $params = iterator_to_array($this->_config->database->params) ;
            $params['driver_options'] = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8;');

            $this->_databaseAdapter = Zend_Db::factory($this->_config->database->adapter, $params);
            Zend_Db_Table::setDefaultAdapter($this->_databaseAdapter);

            $this->_databaseReady = true;
        }

        return $this;
    }

    /**
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDatabaseAdapter()
    {
        if ($this->_databaseAdapter === null) {
            $this->initDatabase();
        }

        return $this->_databaseAdapter;
    }

    public function emitPhpErrorException($errno, $errstr, $errfile, $errline, array $errcontext)
    {
    	if (error_reporting() > 0) {
        	throw new SF_Exception_PHP_Error($errstr . ' in ' . $errfile . ' on line ' . $errline);
    	}
    }

	public function start($deployment = 'staging')
	{
		// class autoloader
		spl_autoload_register(array($this, 'autoload'));

        // error handling
        set_error_handler(array($this, 'emitPhpErrorException'));

		// do not expose xml warnings
		libxml_use_internal_errors(true);

		// load config
        $configname = getenv('SF_CONFIG');
		$this->_config = new Zend_Config_Xml($this->getBasePath() . '/app/etc/' . ($configname ? $configname : 'config') . '.xml', $deployment);

        // reset module states
        $this->_initializedModules = array(
            'backend' => array(),
            'frontend' => array()
        );
        
        // setup backend
        $this->_setupBackend();

		// setup frontend classes
		$this->_setupFrontend();

		return $this;
	}

    public function getProductName()
    {
        return (string) $this->getConfig('product')->name;
    }

    public function getProductUrl()
    {
        return (string) $this->getConfig('product')->url;
    }

    public function getProductOwner()
    {
        return (string) $this->getConfig('product')->owner;
    }

    public function getProductVersion()
    {
        return (string) $this->getConfig('product')->version;
    }

    /**
     * @since 1.1.2
     * @return string
     */
    public function getProductCode()
    {
        return (string) $this->getConfig('product')->code;
    }

	static $_instance = null;

	/**
	 *
	 * @return SF
	 */
	static public function getInstance()
	{
	    return self::$_instance;
	}

	static public function main($deployment = 'staging', $handleAction = true)
	{
	    self::$_instance = new self;
		self::$_instance->start($deployment);

        if ($handleAction) {
            self::$_instance->prepareRequest();
            self::$_instance->handleAction();
        }

		return self::$_instance;
	}

    private $_moduleNamespaces = array();
    private $_modules = array();

    private function _setupBackend()
    {
        if ($this->_observer === null) {
            $this->_observer = new SF_Model_Observer();
        }

        foreach ($this->getConfig('modules') as $moduleName => $module) {
            $_name = (string) ($moduleName);
            $this->_moduleNamespaces[] = $_name;

            $className = $moduleName . '_Module';

            $moduleInstance = new $className;
            $moduleInstance->setup();

            $this->_modules[$_name] = $moduleInstance;
        }

        foreach ($this->_modules as $module) {
            if ($module instanceof SF_Interface_Module_V101) {
                $module->initBackend();
            }
            
            $this->_initializedModules['backend'][] = get_class($module);
        }
    }

	private function _setupFrontend()
	{
        if ($this->_router === null) {
            $this->_router = new SF_Model_Frontend_Router();
        }

        foreach ($this->_modules as $module) {
            if ($module instanceof SF_Interface_Module_V101) {
                $module->initFrontend();
            }
            
            $this->_initializedModules['frontend'][] = get_class($module);
        }
	}

    public function prepareRequest()
    {
        $this->_prepareRequest();
        
        return $this;
    }
    
    private function _prepareRequest()
    {
        if ($this->_request === null) {
			$this->_request = new Zend_Controller_Request_Http();
		}

		if ($this->_response === null) {
			$this->_response = new Zend_Controller_Response_Http();
		}
        
        $url = $this->getRouter()->resolveUrl($this->getRequest()->getParam('uri', '/'));

        $matches = array();
        if (preg_match('/^\/([^\.\/]+)?\/([^\.\/]+?)\/([^\.]+)\.([^\.]+)?$/', $url, $matches)) {
            $this->getRequest()
                ->setControllerName($matches[1])
                ->setActionName($matches[2])
                ->setParam('id', $matches[3])
                ->setParam('extension', $matches[4]);

            /**
             * @deprecated since version 1.2.2
             */
            switch ($matches[4]) {
                case 'csv':
                    $this->getRequest()->setParam('csv', 1);
                    break;

                case 'json':
                case 'ajax':
                    $this->getRequest()->setParam('ajax', 1);
                    break;
            }
        } else if (preg_match('/^\/([^\.\/]+)?\/([^\.\/]+)\.([^\.]+)$/', $url, $matches)) {
            $this->getRequest()
                ->setControllerName($matches[1])
                ->setActionName(trim($matches[2]) !== '' ? $matches[2] : 'index')
                ->setParam('extension', $matches[3]);
        } else if (preg_match('/^\/([^\.\/]+)?\/$/', $url, $matches)) {
            $this->getRequest()
                ->setControllerName($matches[1])
                ->setActionName('index');
        } else if ($url === '/') {
            $this->getRequest()
                ->setControllerName('index')
                ->setActionName('index');
        } else {
            $this->getRequest()
                ->setControllerName(null)
                ->setActionName(null);
        }
    }

    /**
     *
     * @deprecated since version 1.3.0
     * @return Zend_Session_Namespace
     */
    public function getSession()
    {
        if ($this->_session === null) {
			$this->_session = new Zend_Session_Namespace($this->getProductCode());
		}
        
        return $this->_session;
    }

	/**
     * @deprecated since version 1.3.0
	 * @return Zend_Controller_Request_Http
	 */
	public function getRequest()
	{
		return $this->_request;
	}

	/**
     * @deprecated since version 1.3.0
	 * @return Zend_Controller_Response_Http
	 */
	public function getResponse()
	{
		return $this->_response;
	}

    /**
     * @return SF_Model_Frontend_Router
     */
    public function getRouter()
    {
        return $this->_router;
    }

    /**
     * @return SF_Model_Observer
     */
    public function getObserver()
    {
        return $this->_observer;
    }

    protected function _uncamelcase_r($m) 
    {
        return ($m[1] === '_' ? '_' : '') .  strtoupper($m[2]);
    }
    
	protected function _uncamelcase($str)
	{
        return preg_replace_callback('/([-_])([a-z])/', array($this, '_uncamelcase_r'), $str);
	}

    /**
     * 
     * @deprecated since version 1.2.3
     * @return \SF
     */
	public function addLog($msg)
	{
		return $this;
	}

    /**
     * @deprecated since version 1.3.0
     * @return string
     */
	public function getCurrentController()
	{
		$currentRoute = Zend_Registry::get('current_route');

		return $currentRoute[0];
	}

    /**
     * @deprecated since version 1.3.0
     * @return string
     */
	public function getCurrentAction()
	{
		$currentRoute = Zend_Registry::get('current_route');

		return $currentRoute[1];
	}

    private $_responseHasBeenSent = false;
    
    /**
     * @deprecated since version 1.3.0
     * @return string
     */
    public function sendResponse()
    {
        if ($this->_responseHasBeenSent) {
            return;
        }
        
        $this->_responseHasBeenSent = true;
        
//        $this->dispatchEvent('frontend.dispatch.response.pre', $eventData);

        if ($this->getResponse()->canSendHeaders()) {
            $this->getResponse()->setHeader('X-Powered-By', get_class($this) . '/' . self::PRODUCT_VERSION);
        }
        
        $this->getResponse()->sendResponse();

//        $this->dispatchEvent('frontend.dispatch.response.post', $eventData);
    }
    
	public function handleAction($controller = null, $action = null, $actionArgs = array())
	{
        Zend_Registry::set('current_route', array($controller, $action));

        if ($this->getRouter()->tryExecuteRedirects($this->getRequest()->getRequestUri())) {
            $this->getResponse()->sendResponse();

            return;
        }

        if ($controller === null) {
            $controller = $this->getRequest()->getControllerName();
        }

        if ($action === null) {
            $action = $this->getRequest()->getActionName();
        }
        
        if ($controller === null || $action === null) {
            throw new SF_Exception_Controller_BadName('invalid controller/action defined');
        }

//        $eventData = array(
//            'controller'    => $controller,
//            'action'        => $action,
//            'actionArgs'    => $actionArgs
//        );

		$controller = $this->_uncamelcase($controller);
		$action = $this->_uncamelcase($action);

		$action[0] = strtoupper($action[0]);
		$controller[0] = strtoupper($controller[0]);

        $class_name = null;

        foreach ($this->_moduleNamespaces as $ns) {
            $_class_name = $ns . '_Controller_' . $controller;

            if (class_exists($_class_name)) {
                $class_name = $_class_name;
                break;
            }
        }

        if ($class_name === null) {
            throw new SF_Exception_Controller_BadName('unknown controller ' . $controller);
        }

        $model = new $class_name;

        if (!$model instanceof SF_Interface_Controller) {
            throw new SF_Exception_Controller_BadName('unknown controller ' . $controller);
        }

        $cb = array($model, 'handle' . $action);

        if (!is_callable($cb)) {
            throw new SF_Exception_Controller_BadName('action not found');
        }

        Zend_Registry::set('current_controller', $model);

        $this->getResponse()->clearAllHeaders()->clearBody();

        if (ob_get_level() !== 0) {
        	ob_clean(); // in case we arrived here second time
        }

        if ($model->preHandle()) {
            call_user_func_array($cb, $actionArgs);

            $model->postHandle();
        } else {
            return;
        }

        $this->sendResponse();
	}

    public function dispatchEvent($eventname, array $data = array())
    {
        $this->_observer->dispatchEvent($eventname, $data);
    }

    public function observeEvent($eventname, array $callback)
    {
        $this->_observer->observeEvent($eventname, $callback);
    }

	static public function sendExceptionMail(Exception $e)
	{
        $content = '';

        $content .= 'Exception: ' . "\n";
        $content .= $e->getMessage();
        $content .= "\n\n";

        $content .= 'Back Trace: ' . "\n";
        $content .= $e->getTraceAsString();
        $content .= "\n\n";

        $content .= 'Request: ' . "\n";
        $content .= '... URI: ' . SF::getInstance()->getRequest()->getRequestUri() . "\n";
        $content .= '... Remote IP: ' .  SF::getInstance()->getRequest()->getClientIp(true) . "\n";
        $content .= '... Params: ' . print_r(SF::getInstance()->getRequest()->getParams(), true) . "\n";
        $content .= "\n\n";

        $content .= 'Environment: ' . "\n";
        $content .= '... Locale: ' . SF_Helper_I18n::getInstance()->getLocale();
        $content .= "\n\n";

        $mail = new Zend_Mail();
        $mail->setSubject('Uncaught Exception');
        $mail->setBodyText($content);
        $mail->addTo('sfapp@chrisnew.de', 'CHRiSNEW');
        $mail->send();
	}
}
