<?php

class SF_Block_Html_Header extends SF_Block_Html
{
    protected $_template = 'html/header.phtml';

    protected $_bodyClasses = array();

    public function getBodyClasses()
    {
        $this->_bodyClasses[] = 'controller-' . $this->getRequest()->getControllerName();
        $this->_bodyClasses[] = 'route-' . $this->getRequest()->getControllerName() . '-' . $this->getRequest()->getActionName();

        return $this->_bodyClasses;
    }

    public function addBodyClass($class)
    {
    	if (!is_array($class)) {
    		$class = array($class);
    	}

    	foreach ($class as $cls) {
    		$this->_bodyClasses[] = $cls;
    	}

    	return $this;
    }

    protected $_title = null;

    public function setTitle($title)
    {
        $this->_title = $title;

        return $this;
    }

    public function getTitle()
    {
        return $this->_title;
    }

    public function hasTitle()
    {
        return trim($this->getTitle()) !== '';
    }

    protected $_description = null;

    public function setDescription($desc)
    {
        $this->_description = $desc;

        return $this;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function hasDescription()
    {
        return trim($this->getDescription()) !== '';
    }

	protected $_cssFiles = array();

	public function addCssFiles($css)
	{
		if (!is_array($css)) {
			$css = array($css);
		}

		foreach ($css as $file) {
			if (!in_array($file, $this->_cssFiles)) {
				$this->_cssFiles[] = $file;
			}
		}

		return $this;
	}

	public function getCssFiles()
	{
		return $this->_cssFiles;
	}

	const ROBOTS_NOINDEX 	= 'noindex';
	const ROBOTS_NOFOLLOW 	= 'nofollow';
	const ROBOTS_NOARCHIVE 	= 'noarchive';
	const ROBOTS_NOODP 		= 'noodp';
	const ROBOTS_NOYDIR 	= 'noydir';

	protected $_robots = array();

	public function addRobots($robots)
	{
		if (!is_array($robots)) {
			$robots = array($robots);
		}

		foreach ($robots as $robot) {
			$this->_robots[] = $robot;
		}

		return $this;
	}

	public function getRobots()
	{
		return $this->_robots;
	}

    /**
     * adds requested resources to rendering data for header
     *
     * @since 1.1.2
     */
    protected function _prepare() {
        parent::_prepare();

        $this->addCssFiles(self::_getResourceSet('css'));

        SF_Block_Abstract::registerMarkerObserver('css-additional', array($this, 'handleCssAdditionalMarker'));
    }

    /**
     * injects exterme lately requested css resources into the document
     *
     * @since 1.1.2
     */
    public function handleCssAdditionalMarker()
    {
        $cssFiles = self::_getResourceSet('css');

        $buffer = array();

        foreach ($cssFiles as $cssFile) {
            if (!in_array($cssFile, $this->getCssFiles())) {
                $buffer[] = '        <link href="' . $this->_resUrl($cssFile) . '" rel="stylesheet">';
            }
        }

        return implode(PHP_EOL, $buffer);
    }
}

