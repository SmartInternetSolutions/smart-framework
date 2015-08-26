<?php

class SF_Model_Frontend_Router
{
    protected $_rewriteRules = array();
    protected $_regexRewriteRules = array();
    protected $_redirectRules = array();

    public function registerRewriteRule($source, $destination)
    {
        $this->_rewriteRules[$source] = $destination;

        return $this;
    }

    public function registerRegexRewriteRule($source, $destination)
    {
        $this->_regexRewriteRules[$source] = $destination;

        return $this;
    }

    public function registerRedirectRule($source, $destination)
    {
        $this->_redirectRules[$source] = $destination;

        return $this;
    }

    public function tryExecuteRedirects($source)
    {
        if (isset($this->_redirectRules[$source])) {
            SF::getInstance()->getResponse()->setRedirect($this->_redirectRules[$source], 301);

            return true;
        }

        return false;
    }

    // FIXME: respect ? and #
    public function resolveUrl($url)
    {
        if (isset($this->_rewriteRules[$url])) {
            return $this->_rewriteRules[$url];
    }

        foreach ($this->_regexRewriteRules as $pattern => $target) {
            $matches = array();

            if (preg_match($pattern, $url, $matches)) {
                $search = array();
                $replace = array();
                
                foreach ($matches as $num => $data) {
                    $search[$num] = '$' . $num;
                    $replace[$num] = $data;
                }
                
                return str_replace($search, $replace, $target);
            }
        }
        
        return isset($this->_rewriteRules[$url]) ?  : $url;
    }

    // FIXME: respect ? and #
    public function rewriteUrl($url)
    {
        foreach ($this->_rewriteRules as $source => $destination) {
            if ($url === $destination) {
                return $source;
            }
        }

        return $url;
    }
}
