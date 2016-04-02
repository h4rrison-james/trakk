<?php

class Zend_View_Helper_IssueStates extends Zend_View_Helper_Abstract
{
    public function IssueStates()
    {
        $i = new Bugify_Issues();
        $states = $i->getStates();
        
        return $states;
    }
}
