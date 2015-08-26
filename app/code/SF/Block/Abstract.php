<?php

abstract class SF_Block_Abstract extends SF_Helper_ViewPart
{
    /**
	 * child blocks
	 * @var ArrayObject
	 */
	protected $_children = array();

	/**
	 * @var string
	 */
	protected $_template = null;

    private static $_themeName = null;
    private static $_defaultThemeName = null;

    public function __construct()
	{
		$this->_children = new ArrayObject();
	}
    /**
	 * setter for _template
	 * @param string $file
	 * @return Frontend SF_Block_Abstract
	 */
	public function setTemplate($file)
	{
		$this->_template = $file;
		return $this;
	}
    
    /**
     * @since 1.2.2
     * @param string $themeName
     */
    public static final function setThemeName($themeName)
    {
        self::$_themeName = $themeName;
    }
    
    /**
     * @since 1.3.1
     * @param string $defaultThemeName
     */
    public static final function setDefaultThemeName($defaultThemeName)
    {
        self::$_defaultThemeName = $defaultThemeName;
    }
    
    protected function _getResolvedTemplatePath($template = null)
    {
        if ($template === null) {
            $template = $this->_template;
        }
        
        $basePath = $this->getBasePath() . '/app/design/';
        
        if (self::$_themeName !== null) {
            if (file_exists($basePath . self::$_themeName . '/' . $template)) {
                $basePath .= self::$_themeName . '/';
            } else {
                $basePath .= self::$_defaultThemeName . '/';
            }
        }
            
        return $basePath . $template;
    }
    
    public function setBlock($name, SF_Block_Abstract $obj)
	{
		$this->_children[$name] = $obj;

		return $this;
	}

	public function getBlock($name)
	{
		return $this->hasBlock($name) ? $this->_children[$name] : null;
	}

	public function hasBlock($name)
	{
		return isset ($this->_children[$name]);
	}

	protected function _render()
	{
	}

	protected function _prepare()
	{
	}

    protected function _toHtml()
    {
        ob_start();
        $this->_render();
        return ob_get_clean();
    }

	final public function render()
	{
        $buffer = '';
        
		if ($this->_template == null) {
			foreach ($this->_children as $row) {
				$buffer .= $row->toHtml();
			}
		} else {
			$buffer .= $this->_toHtml();
		}

        $buffer = $this->_replaceMarkers($buffer);

        $this->getResponse()->setBody($buffer);
	}

	final public function renderAsText()
	{
        $buffer = '';

		if ($this->_template == null) {
			foreach ($this->_children as $row) {
				$buffer .= $row->toText();
			}
		} else {
			$buffer .= $this->toText();
		}

        $buffer = $this->_replaceMarkers($buffer);

        $this->getResponse()->setBody($buffer);
	}

    public function toHtml()
    {
        $buffer = '';

		if ($this->_template == null) {
			foreach ($this->_children as $row) {
				$buffer .= $row->toHtml();
			}

            return $buffer;
		} else {
            ob_start();

            $this->_prepare();
            $this->_render();

            return ob_get_clean();
        }
    }

    public function toText()
    {
        if ($this->_template !== null) {
            $fn = $this->_getResolvedTemplatePath(str_replace('.phtml', '.ptxt', $this->_template));

            if (file_exists($fn)) {
                ob_start();
                include($fn);
                return ob_get_clean();
            }

            return '';
        } else {
            $buffer = '';

            foreach ($this->_children as $row) {
                $buffer .= $row->toText();
            }

            return $buffer;
        }
    }

    public function getChildHtml($name = '')
    {
        if ($name === '') {
            $buffer = '';

            foreach ($this->_children as $row) {
                $buffer .= $row->toHtml();
            }

            return $buffer;
        } else {
            if (isset($this->_children[$name])) {
                return $this->_children[$name]->toHtml();
            }
        }

        return null;
    }

	protected $_parent = null;

	public function getParent()
	{
	    return $this->_parent;
	}

	public function setParent(SF_Block_Abstract $obj)
	{
	    $this->_parent = $obj;

	    return $this;
	}

	public function isController($controller)
	{
		return $this->getRequest()->getControllerName() === $controller;
	}

	public function isAction($action)
	{
		return $this->getRequest()->getActionName() === $action;
	}

    public function isRoute($route)
    {
        $routeParts = explode('/', $route, 3);

        if ($routeParts[0] !== '*' && !$this->isController($routeParts[0])) {
            return false;
        }

        if (count($routeParts) > 1) {
            if ($routeParts[1] !== '*' && !$this->isAction($routeParts[1])) {
                return false;
            }

            if (count($routeParts) > 2 && $this->getRequest()->get('id') !== $routeParts[2]) {
                return false;
            }
        }

        return true;
    }

    private static $_markerObserverMap = array();

    /**
     * registers marker observer
     *
     * @since 1.1.2
     * @param type $marker
     * @param type $callable
     */
    public static function registerMarkerObserver($marker, $callable)
    {
        if (!isset(self::$_markerObserverMap[$marker])) {
            self::$_markerObserverMap[$marker] = array();
        }

        self::$_markerObserverMap[$marker][] = $callable;
    }

    /**
     * replaces <!-- MARKER('name') --> with corresponding observer output
     *
     * @todo doesn't work if rendering tree has been flushed partially
     * @since 1.1.2
     * @param type $buffer
     */
    private function _replaceMarkers($buffer)
    {
        return preg_replace_callback('/\\<!-- MARKER\\(\'([^\']+)\'\\) --\\>/', array($this, '_replaceMarkers_r'), $buffer);
    }

    private function _replaceMarkers_r($a)
    {
        $a = $a[1];

        if (!array_key_exists($a, self::$_markerObserverMap)) {
            return '';
        }

        $observers = self::$_markerObserverMap[$a];
        $buffer = '';

        foreach ($observers as $observer) {
            $buffer .= call_user_func($observer);
        }

        return $buffer;
    }
}
