<?php

class SF_Helper_Cache
{
    const LIMIT_TIME = 3600;

    protected $_cacheId = 'unknown';
    protected $_timeLimit = self::LIMIT_TIME;

    /**
     * @param string $cacheId
     * @return SF_Helper_Cache
     */
    public function setCacheId($cacheId)
    {
        $this->_cacheId = $cacheId;

        return $this;
    }

    /**
     * sets time expiry
     * @param unknown_type $timeLimit
     * @return SF_Helper_Cache
     */
    public function setTimeLimit($timeLimit)
    {
        $this->_timeLimit = $timeLimit;

        return $this;
    }

    /**
     *
     * Enter description here ...
     * @param string $query
     */
    protected function _createCachePath($query)
    {
        $str = md5($query);

        return '/var/cache/' . $this->_cacheId . '/' . $str[0] . '/' . $str[1] . '/' . $str;
    }

    /**
     *
     * Enter description here ...
     * @param string $query
     */
    public function read($query)
    {
        $path = $this->_createCachePath($query);
        $fpath = SF::getInstance()->getBasePath() . $path;

        if (!file_exists($fpath) || abs(time() - filemtime($fpath)) > $this->_timeLimit) {
            return null;
        }

        return unserialize(file_get_contents($fpath));
    }

    /**
     * Enter description here ...
     *
     * @param string $query
     * @param mixed $data
     * @return SF_Helper_Cache
     */
    public function write($query, $data)
    {
        $path = $this->_createCachePath($query);
        $fpath = SF::getInstance()->getBasePath() . $path;
        @mkdir(dirname($fpath), 0777, true);
        file_put_contents($fpath, serialize($data));

        return $this;
    }

    static public function factory($cacheId, $timeLimit = null)
    {
        $cache = new self;
        $cache->setCacheId($cacheId);

        if ($timeLimit !== null) {
            $this->setTimeLimit($timeLimit);
        }

        return $cache;
    }
}
