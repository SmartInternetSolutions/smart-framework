<?php

class SF_Model_Db_Table extends Zend_Db_Table_Abstract
{
    public function __construct($config = array(), $definition = null)
    {
        SF::getInstance()->initDatabase();
    }
    
    public function setName($name)
    {
        $this->_name = $name;
        
        $this->_setup();
        $this->init();
        
        return $this;
    }
    
    public function getName()
    {
        return $this->_name;
    }
    
    public function setPrimary($primary)
    {
        $this->_primary = $primary;
        
        return $this;
    }
    
    public function getPrimary()
    {
        return $this->_primary;
    }
}