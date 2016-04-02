<?php

class Zend_View_Helper_Milestones extends Zend_View_Helper_Abstract
{
    public function Milestones()
    {
        $cache_id   = 'Milestones';
        $cache      = Zend_Registry::get('cache');
        $milestones = array();
        
        if (($milestones = $cache->load($cache_id)) === false)
        {
            //Load the Milestones
            $p = new Bugify_Milestones();
            $result = $p->fetchAll();
            
            //Process the milestones (attach issue counts etc)
            $h = new Bugify_Helpers_Issues();
            $milestones = $h->addIssueCountsForMilestones($result);
            
            //Save the milestones to cache
            $cache->save($cache_id, $milestones, array('Milestones', 'IssueCount'));
        }
        
        return $milestones;
    }
}
