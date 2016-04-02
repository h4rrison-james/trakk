<?php

class Zend_View_Helper_Projects extends Zend_View_Helper_Abstract
{
    public function Projects()
    {
        $cache_id = 'Projects';
        $cache    = Zend_Registry::get('cache');
        $projects = array();
        
        if (($projects = $cache->load($cache_id)) === false)
        {
            //Load the projects
            $p = new Bugify_Projects();
            $result = $p->fetchAll();
            
            //Process the projects (attach issue counts etc)
            $h = new Bugify_Helpers_Issues();
            $projects = $h->addIssueCountsForProjects($result);
            
            //Save the projects to cache
            $cache->save($cache_id, $projects, array('Projects', 'IssueCount'));
        }
        
        return $projects;
    }
}
