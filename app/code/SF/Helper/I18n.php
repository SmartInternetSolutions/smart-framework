<?php

class SF_Helper_I18n
{
    protected $_translations = array();
    protected $_session = null;

    public function __construct()
    {
        $this->_session = new Zend_Session_Namespace(get_class($this));

        $this->_reloadTranslations();
    }

//    protected function _checkHost($hostname)
//    {
//        $currentHost = SF::getInstance()->getRequest()->getHttpHost();
//
//        return  ($currentHost === 'www.' . $hostname) ||
//                ($currentHost === $hostname) ||
//                ('www.' . $currentHost === $hostname);
//    }

    protected function _determineLocale()
    {
//        $language = SF::getInstance()->getRequest()->getHeader('Accept-Language');

        $this->_session->locale = SF::getInstance()->getConfig('i18n')->{'default-locale'};
        
        Zend_Locale::setDefault($this->_session->locale);
        setlocale(LC_ALL, $this->_session->locale);
    }

    public function getLocale()
    {
//        if (empty($this->_session->locale)) {
            $this->_determineLocale();
//        }

        return $this->_session->locale;
    }

    public function setLocale($locale)
    {
        if (!preg_match('/^[a-z]{2}_[A-Z]{2}$/', $locale)) {
            return $this;
        }

        $this->_session->locale = $locale;

        $this->_determineLocale();
        $this->_reloadTranslations();

        return $this;
    }

    public function _reloadTranslations()
    {
        $fn = SF::getInstance()->getBasePath() . '/app/etc/' . $this->getLocale() . '.csv';

        if (!file_exists($fn)) {
            return;
        }

        $f = fopen($fn, 'rt');

        while ($row = fgetcsv($f)) {
            if (isset($row[0]) && isset($row[1])) {
                $this->_translations[$row[0]] = $row[1];
            }
        }

        fclose($f);
    }

	static protected $_instance = null;

	/**
	 * @return SF_Helper_I18n
	 */
    static public function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    public function translate($in)
    {
        $out = isset($this->_translations[$in]) ? $this->_translations[$in] : $in;
        
        $args = func_get_args();
        
        for($i = 1, $j = func_num_args(); $i != $j; $i++) {
            $out = str_replace('{' . ($i - 1). '}', $args[$i], $out);
        }
        
        return $out;
    }
    
    public function getTranslationMap()
    {
        return $this->_translations;
	}
}
