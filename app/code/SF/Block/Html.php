<?php

class SF_Block_Html extends SF_Block_Abstract
{
    /**
     * marks block class name and template name as comment in html
     *
     * @since 1.1.2
     * @var type
     */
    public static $commentBlock = false;

	protected function _escape($str)
	{
		return htmlspecialchars($str, ENT_QUOTES);
	}

    protected static function _staticResUrl($file, $addmtime = true)
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
    
    /**
     * returns resource's current url
     *
     * @since 1.1.2
     */
    protected function _resUrl($file, $addmtime = true)
    {
        return static::_staticResUrl($file, $addmtime);
    }

	protected function _render()
	{
        if (trim($this->_template) !== '') {
            if (self::$commentBlock) {
                $id = get_class($this) . ':' . $this->_template;

                echo '<!-- BEGIN: ' . $id . ' -->';
            }

            include($this->_getResolvedTemplatePath());

            if (self::$commentBlock) {
                echo '<!-- END: ' . $id . ' -->';
            }
        } else if (self::$commentBlock) {
            echo '<!-- MARK: ' . get_class($this) . ' -->';
        }
    }

    /**
     * @since 1.1.1-is
     * @var type
     */
    protected function _escapeAndDecorateLinks($str, $allowedProtocols = null)
    {
        if ($allowedProtocols === null) {
            return $this->_decorateLinks($this->_escape($str));
        }

        return $this->_decorateLinks($this->_escape($str), $allowedProtocols);
    }

    /**
     * @since 1.1.1-is
     * @var type
     */
    protected function _decorateLinks_r($link)
    {
        $link = $link[1];

        return '<a rel="nofollow" class="sf-link-outgoing sf-ugc-link" href="' . $this->_escape($link) . '">' . $this->_escape($link) . '</a>';
    }

    /**
     * @since 1.1.1-is
     * @var type
     */
    protected function _decorateLinks($str, $allowedProtocols = array('https?', 'ftp'))
    {
        $str = preg_replace_callback('/((' . implode('|', $allowedProtocols) . '):\/\/[^\s]+)/', array($this, '_decorateLinks_r'), $str);

        return $str;
    }

    /**
     * resource map
     *
     * @since 1.1.2
     * @var type
     */
    private static $_resourceMap = array();

    private static function _includeResource($res, $type)
    {
        if (!isset(self::$_resourceMap[$type])) {
            self::$_resourceMap[$type] = array();
        }

        self::$_resourceMap[$type][$res] = $res;

        if (preg_match('/^https?:\/\//', $res)) {
            return true;
        }

        return file_exists(SF::getInstance()->getBasePath() . '/public/' . $res);
    }

    /**
     * requires js file
     *
     * @since 1.1.2
     */
    protected static function _requireResource($res, $type)
    {
        if (!self::_includeResource($res, $type)) {
            throw new SF_Exception_Resource_Missing($type . ' resource missing: ' . $res);
        }
    }

    /**
     * returns resource set for desired type
     *
     * @since 1.1.2
     */
    protected static function _getResourceSet($type)
    {
        return isset(self::$_resourceMap[$type]) ? self::$_resourceMap[$type] : array();
    }

    /**
     * includes js file
     *
     * @since 1.1.2
     */
    protected static function _includeJs($js)
    {
        return self::_includeResource('js/' . $js, 'js');
    }

    /**
     * requires js file
     *
     * @since 1.1.2
     */
    protected static function _requireJs($js)
    {
        if (!self::_includeJs($js)) {
            throw new SF_Exception_Resource_Missing('javascript resource missing: ' . $js);
        }
    }

    /**
     * includes css file
     *
     * @since 1.1.2
     */
    protected static function _includeCss($css)
    {
        return self::_includeResource('css/' . $css, 'css');
    }

    /**
     * requires css file
     *
     * @since 1.1.2
     */
    protected static function _requireCss($css)
    {
        if (!self::_includeCss($css)) {
            throw new SF_Exception_Resource_Missing('stylesheet resource missing: ' . $css);
        }
    }
}

// statics
SF_Block_Html::$commentBlock = (SF::getInstance()->getConfig('debug')->template->{'comment-block'} === 'true');
