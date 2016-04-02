<?php

class Zend_View_Helper_MyFollowCount extends Zend_View_Helper_Abstract
{
    /**
     * This shows the number of issues the specifiec user is following.
     */
    public function MyFollowCount($username)
    {
        $count    = 0;
        $cache_id = sprintf('MyFollowCount_%s', md5($username));
        $cache    = Zend_Registry::get('cache');
        
        if (($count = $cache->load($cache_id)) === false)
        {
            //Load the user
            $u = new Bugify_Users();
            $user = $u->fetch($username);
            
            //Load the follow count for this user
            $i = new Bugify_Issues();
            $count = $i->fetchFollowCountForUser($user);
            
            //Save the count to cache
            $cache->save($cache_id, $count, array('FollowCount'));
        }
        
        return $count;
    }
}
