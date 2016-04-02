<?php

class Zend_View_Helper_StateName extends Zend_View_Helper_Abstract
{
    public function StateName($state_id)
    {
        //Load the states
        $i = new Bugify_Issues();
        $states = $i->getStates();
        
        $name = 'Unknown';
        
        foreach ($states as $key => $val)
        {
            if ($key == $state_id)
            {
                $name = $val;
                break;
            }
        }
        
        return $name;
    }
}
