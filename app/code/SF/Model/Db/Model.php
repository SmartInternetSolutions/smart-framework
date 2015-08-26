<?php

class SF_Model_Db_Model extends SF_Model_Db_Abstract
{
    protected $_isNew     = false;
    protected $_isDeleted = false;

    protected $_modTable  = array();
    
    protected function _init()
    {
        parent::_init();

        $this->_row = $this->_table->createRow();
    }

    protected function _postDelete()
    {
    }

    protected function _preDelete()
    {
    }

    protected function _postSave()
    {
    }

    protected function _preSave()
    {
    }

    /**
     * @var SF_Model_Db_Row
     */
    protected $_row = null;

    public function save()
    {
       if (!$this->getId()) {
           $this->_isNew = true;
       }

       $this->_preSave();

       $this->_row->save();

       $this->_postSave();

       $this->_modTable = array();

       return $this;
    }

    protected function _get($key)
    {
    	if (!isset($this->_row[$key])) {
    		return null;
    	}

        return $this->_row[$key];
    }

    protected function _set($key, $value)
    {
        // only reset if it's really necessary
        if (trim($this->_get($key)) !== trim($value)) {
            $this->_modTable[$key]  = $this->_row[$key];
            $this->_row[$key]       = $value;
        }

        return $this;
    }

    public function delete()
    {
        $this->_preDelete();

        $this->_row->delete();

        $this->_isDeleted = true;

        $this->_postDelete();

        return $this;
    }

    static protected $_objectCache = array();

    /**
     * @param string $_id
     * @param string $field
     * @return static
     * @throws Exception
     */
    static public function loadCached($_id, $field = null)
    {
        $id = get_called_class() . '-' . $_id;

        if (!isset(self::$_objectCache[$id])) {
            try {
                self::$_objectCache[$id] = new static;
                self::$_objectCache[$id]->load($_id, $field);
            } catch(Exception $e) {
                unset(self::$_objectCache[$id]);
                throw $e;
            }
        }

        return self::$_objectCache[$id];
    }

    public function load($id, $field = null)
    {
        if ($field === null) {
            $field = $this->_primaryKey;
        }

        if ($id instanceof Zend_Db_Expr) {
            $select = $this->_table->select()->where($id);
        } else {
            if ($field instanceof Zend_Db_Expr) {
                $select = $this->_table->select()->where($field, $id);
            } else if ($id === null) {
                $select = $this->_table->select()->where($field . ' IS NULL');
            } else {
                $select = $this->_table->select()->where($field . ' = ?', $id);
            }
        }

        $this->setRow($this->_table->fetchRow($select));

        if (!$this->_row) {
            throw new SF_Exception_Db_EntityNotFound('Could not load ' . get_class($this) . '! id = ' . $id . ', field = ' . $field);
        }

        return $this;
    }

    public function loadWithData(array $data)
    {
    	$this->setRow($data);

    	return $this;
    }

    public function setRow($row)
    {
        $this->_row = $row;

        // adding to model's cache.
        if (isset($row[$this->_primaryKey]) && $row[$this->_primaryKey] > 0) {
            self::$_objectCache[$row[$this->_primaryKey]] = $this;
        }

        $this->_reset();

        return $this;
    }

    private $_collection = null;
    
    public function setCollection(SF_Model_Db_Collection $collection)
    {
        $this->_collection = $collection;
        
        return $this;
    }
    
    public function getCollection()
    {
        return $this->_collection;
    }
    
    public function hasCollection()
    {
        return $this->_collection !== null;
    }

    public function getId()
    {
        return $this->_get($this->_primaryKey);
    }

    public function isNew()
    {
        return $this->_isNew;
    }

    public function isDeleted()
    {
        return $this->_isDeleted;
    }

    public function isModified($field = null)
    {
        return $field === null ? count($this->_modTable) > 0 : isset($this->_modTable[$field]);
    }

    public function toArray()
    {
        return $this->_row->toArray();
    }

    public function equalsTo(SF_Model_Db_Model $other = null) {
        return $other !== null && $other->getId() == $this->getId();
    }
    
    protected function _reset()
    {
        $this->_isNew = false;
        $this->_modTable = array();

        return $this;
    }
}