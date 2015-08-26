<?php

class SF_Helper_Twitter implements IteratorAggregate
{
    protected $_iterator = null;
    protected $_cache = null;

    public function __construct()
    {
        $this->_cache = SF_Helper_Cache::factory('twitter-queries');
    }

    static public function factory($query)
    {
        $inst = new self($query);

        $inst->search($query);

        return $inst;
    }

    public function search($query)
    {
        $this->_iterator = $this->_cache->read($query);

        if ($this->_iterator === null) {
            $this->_iterator = new Zend_Feed_Atom('http://search.twitter.com/search.atom?q=' . urlencode($query));
            $this->_cache->write($query, $this->_iterator);
        }

        return $this;
    }

    public function getIterator()
    {
        return $this->_iterator;
    }
}