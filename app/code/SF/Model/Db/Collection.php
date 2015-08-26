<?php

class SF_Model_Db_Collection extends SF_Model_Db_Abstract implements Iterator, Countable
{
    protected $_data        = null;
    protected $_model       = 'SF_Model_Db_Model';

    protected $_select      = null;

    public function __clone() {
        $this->_select = clone $this->_getSelect();
    }

    /**
     * @return Zend_Db_Select
     */
    protected function _getSelect()
    {
        if ($this->_select === null) {
            $this->_select = $this->_table->select()
                ->from($this->_tablename)
                ->setIntegrityCheck(false);
        }

        return $this->_select;
    }

    public function getSelect()
    {
        return $this->_getSelect();
    }

    public function addFieldToFilter($cond, $value = null, $type = null)
    {
        if (is_array($value) && isset($value['bindings'])) {
            $this->_getSelect()->where($cond)->bind(array_merge($this->_getSelect()->getBind(), $value['bindings']));
        } else {
        	$this->_getSelect()->where($cond, $value, $type);
        }

        return $this;
    }

    public function setSort($field, $direction = 'ASC')
    {
        if ($field instanceof Zend_Db_Expr) {
            $this->_getSelect()->order($field);
        } else {
            $this->_getSelect()->order($field . ' ' . $direction);
        }

        return $this;
    }

    protected function _prepareCollection()
    {
    }

    protected $_limitCount = null;

    public function getLimitCount()
    {
        return $this->_limitCount;
    }

    protected $_limitOffset = null;

    public function getLimitOffset()
    {
        return $this->_limitOffset;
    }

    public function limit($count = 30, $offset = 0)
    {
        $this->_limitCount = $count;
        $this->_limitOffset = $offset;

        $this->_getSelect()->limit($count, $offset);

        return $this;
    }

    public function resetLimit()
    {
        $this->_getSelect()
            ->reset(Zend_Db_Select::LIMIT_COUNT)
            ->reset(Zend_Db_Select::LIMIT_OFFSET);

        return $this;
    }

    public function load()
    {
        if ($this->_data === null) {
            $this->_prepareCollection();

            $this->_data = $this->_table->fetchAll($this->_select);
            $this->_select = null;
        }

        return $this;
    }

    public static function createByIds(array $ids, $preserveOrder = false)
    {
        $collection = new static;
        $collection->loadByIds($ids, $preserveOrder);
        
        return $collection;
    }
    
    public function loadByIds(array $ids, $preserveOrder = false)
    {
        if (count($ids) === 0) {
            return $this;
        }

        $this->addFieldToFilter($this->_tablename . '.' . $this->_primaryKey . ' in (?)', $ids);
        
        if ($preserveOrder) {
            $this->setSort(new Zend_Db_Expr('find_in_set(' . $this->_tablename . '.' . $this->_primaryKey . ', \'' . implode(',', $ids) . '\')'));
        }
        
        $this->load();

        return $this;
    }

    public function delete()
    {
        $db = SF::getInstance()->getDatabaseAdapter();
        $db->beginTransaction();

        try {
            foreach ($this as $entry) {
                $entry->delete();
            }
        } catch(Exception $e) {
            $db->rollBack();
            throw $e;
        }

        $db->commit();

        return $this;
    }

    public function save()
    {
        $db = SF::getInstance()->getDatabaseAdapter();
        $db->beginTransaction();

        try {
            foreach ($this as $entry) {
                $entry->save();
            }
        } catch(Exception $e) {
            $db->rollBack();
            throw $e;
        }

        $db->commit();

        return $this;
    }

    public function reset()
    {
        $this->_data = null;

        return $this;
    }

    protected $_modelCache = array();

    /**
     *
     * @param SF_Model_Db_Row $row
     * @return SF_Model_Db_Model
     */
    protected function _wrapWithModel($row)
    {
        $hash = spl_object_hash($row);

        if (!isset($this->_modelCache[$hash])) {
            $this->_modelCache[$hash] = new $this->_model;
            $this->_modelCache[$hash]->setRow($row);
            $this->_modelCache[$hash]->setCollection($this);

        }

        return $this->_modelCache[$hash];
    }

    public function current ()
    {
        return $this->_wrapWithModel($this->_data->current());
    }

    public function next ()
    {
        $this->_data->next();

        return $this;
    }

    public function key ()
    {
        return $this->_data->key();
    }

    public function valid ()
    {
        return $this->_data->valid();
    }

    public function rewind ()
    {
        $this->load();

        $this->_data->rewind();

        return $this;
    }

    public function countLimitLess()
    {
        $select = clone $this->_getSelect();
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);
        $select->reset(Zend_Db_Select::GROUP);
        $select->columns(new Zend_Db_Expr('COUNT(DISTINCT ' . $this->_tablename . '.' . $this->_primaryKey . ') AS c'));
        return (int) $this->_table->fetchRow($select)->offsetGet('c');
    }

    // returns result runned from query with COUNT(*) in SELECT part
    public function count()
    {
        $select = clone $this->_getSelect();
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);
        $select->reset(Zend_Db_Select::GROUP);
        $select->columns(new Zend_Db_Expr('COUNT(DISTINCT ' . $this->_tablename . '.' . $this->_primaryKey . ') AS c'));
        return (int) $this->_table->fetchRow($select)->offsetGet('c');
    }

    public function size()
    {
        return $this->_data ? count($this->_data) : 0;
    }
}