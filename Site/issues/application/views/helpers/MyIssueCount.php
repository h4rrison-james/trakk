<?php

class Zend_View_Helper_MyIssueCount extends Zend_View_Helper_Abstract
{
    /**
     * This shows the number of issues assigned to the specified user id.
     */
    public function MyIssueCount($username)
    {
        $count    = 0;
        $cache_id = sprintf('MyIssueCount_%s', md5($username));
        $cache    = Zend_Registry::get('cache');
        
        if (($count = $cache->load($cache_id)) === false)
        {
            //Load the user
            $u = new Bugify_Users();
            $user = $u->fetch($username);
            
            //Load the issue count for this user
            $i = new Bugify_Issues();
            $count = $i->fetchIssueCountForUser($user);
            
            //Save the count to cache
            $cache->save($cache_id, $count, array('IssueCount'));
        }
        
        return $count;
    }
}
