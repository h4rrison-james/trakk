<?php

class Zend_View_Helper_Users extends Zend_View_Helper_Abstract
{
    public function Users()
    {
        $cache_id = 'Users';
        $cache    = Zend_Registry::get('cache');
        $users    = array();
        
        if (($users = $cache->load($cache_id)) === false)
        {
            //Load the users
            $u = new Bugify_Users();
            $result = $u->fetchAll();
            
            foreach ($result as $user)
            {
                $users[] = $user->toArray();
            }
            
            //Save the users to cache
            $cache->save($cache_id, $users, array('Users'));
        }
        
        return $users;
    }
}
