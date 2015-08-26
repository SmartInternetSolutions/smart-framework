<?php

class SF_Block_Html_Menu extends SF_Block_Html
{
    protected $_template = 'html/menu.phtml';

    static protected $_menuEntryMap = array();

    static public function registerMenuEntry($menuId, $data)
    {
        if (!isset(self::$_menuEntryMap[$menuId])) {
            self::$_menuEntryMap[$menuId] = array();
        }

        $data = array_merge(array(
            'controller'    => 'index',
            'action'        => 'index',
            'routes'        => array(),
            'label'         => 'Menu Item',
            'count'         => null,
            'data'          => array(),
            'items'         => array(),
            'href'          => null
        ), $data);

        self::$_menuEntryMap[$menuId][] = $data;
    }

    protected function _decorateItems(array $inputItems)
    {
        $items = array();

        $action     = $this->getRequest()->getActionName();
        $controller = $this->getRequest()->getControllerName();

        foreach ($inputItems as $item) {
            $_item = new stdClass();

            $_item->isActive = (
                $item['controller'] === $controller &&
                $item['action'] === $action
//                    true
            );

            if (!$_item->isActive) {
                foreach ($item['routes'] as $route) {
                    if ($this->isRoute($route)) {
                        $_item->isActive = true;

                        break;
                    }
                }
            }

            if ($item['href'] === null) {
                $_item->url = $this->getUrl($item['controller'], $item['action'], isset($item['id']) ? $item['id'] : null);
            } else {
                $_item->url = $item['href'];
            }

            $_item->label = $item['label'];

            $_item->count = $item['count'];
            $_item->data = $item['data'];

            $_item->items = $this->_decorateItems($item['items']);

            $items[] = $_item;
        }

        return $items;
    }

    protected function _getMenuItems($menuId)
    {
        return $this->_decorateItems(isset(self::$_menuEntryMap[$menuId]) ? self::$_menuEntryMap[$menuId] : array());
    }
}

