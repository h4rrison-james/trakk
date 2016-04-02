<?php

class Zend_View_Helper_Priorities extends Zend_View_Helper_Abstract
{
    public function Priorities()
    {
        $i = new Bugify_Issues();
        $priorities = $i->getPriorities();
        
        return $priorities;
    }
}
