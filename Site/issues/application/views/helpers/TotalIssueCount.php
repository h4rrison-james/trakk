<?php

class Zend_View_Helper_TotalIssueCount extends Zend_View_Helper_Abstract
{
    /**
     * Total number of open issues across all assignees
     */
    public function TotalIssueCount()
    {
        $count    = 0;
        $cache_id = 'TotalIssueCount';
        $cache    = Zend_Registry::get('cache');
        
        if (($count = $cache->load($cache_id)) === false)
        {
            //Load the issue count for this user
            $i = new Bugify_Issues();
            $count = $i->fetchIssueCount();
            
            //Save the count to cache
            $cache->save($cache_id, $count, array('IssueCount'));
        }
        
        return $count;
    }
}
