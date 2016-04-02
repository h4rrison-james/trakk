<?php

class Zend_View_Helper_MyFilterCount extends Zend_View_Helper_Abstract
{
    /**
     * This shows the number of saved filters for the specified user.
     */
    public function MyFilterCount($username)
    {
        $count    = 0;
        $cache_id = sprintf('MyFilterCount_%s', md5($username));
        $cache    = Zend_Registry::get('cache');
        
        if (($count = $cache->load($cache_id)) === false)
        {
            //Load the user
            $u = new Bugify_Users();
            $user = $u->fetch($username);
            
            //Load the number of saved filters for this user
            $f = new Bugify_Filters();
            $result = $f->fetchAllForUser($user);
            $count  = count($result);
            
            //Save the count to cache
            $cache->save($cache_id, $count, array('FilterCount'));
        }
        
        return $count;
    }
}
