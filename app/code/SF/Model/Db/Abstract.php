<?php

abstract class SF_Model_Db_Abstract
{
    protected $_tablename   = '';
    protected $_primaryKey  = 'id';
    
    static protected $_tableCache = array();
    
    /**
     * @var SF_Model_Db_Table
     */
    protected $_table    = null;
    
    public function __construct()
    {
        $this->_init();
    }
    
    protected function _init() 
    {
        if (!isset(self::$_tableCache[$this->_tablename])) {
            $this->_table = new SF_Model_Db_Table();
            $this->_table->setName($this->_tablename);
            $this->_table->setPrimary($this->_primaryKey);
            $this->_table->setRowsetClass('SF_Model_Db_Rowset');
            $this->_table->setRowClass('SF_Model_Db_Row');
            
            self::$_tableCache[$this->_tablename] = $this->_table;
        } else {
            $this->_table = self::$_tableCache[$this->_tablename];
        }
    }
}