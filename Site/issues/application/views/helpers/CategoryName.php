<?php

class Zend_View_Helper_CategoryName extends Zend_View_Helper_Abstract
{
    private $_categories = array();
    
    public function CategoryName($category_id)
    {
        if (count($this->_categories) == 0)
        {
            //Load the categories
            $c = new Bugify_Categories();
            $this->_categories = $c->fetchAll();
        }
        
        $name = 'None';
        
        if ($category_id > 0)
        {
            foreach ($this->_categories as $category)
            {
                if ($category->getCategoryId() == $category_id)
                {
                    $name = $this->view->escape($category->getName());
                    break;
                }
            }
        }
        
        return $name;
    }
}
