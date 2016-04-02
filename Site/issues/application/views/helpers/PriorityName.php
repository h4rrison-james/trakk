<?php

class Zend_View_Helper_PriorityName extends Zend_View_Helper_Abstract
{
    public function PriorityName($priority_id)
    {
        //Load the priorities
        $i = new Bugify_Issues();
        $priorities = $i->getPriorities();
        
        $name = 'Unknown';
        
        foreach ($priorities as $key => $val)
        {
            if ($key == $priority_id)
            {
                $name = $val;
                break;
            }
        }
        
        return $name;
    }
}
