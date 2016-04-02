<?php

class Bugify_Users
{
    public function __construct()
    {}
    
    private function _sortUsers($a, $b)
    {
        return strnatcasecmp($a['firstname'].$a['lastname'], $b['firstname'].$b['lastname']);
    }
    
    public function fetchCount() {
        /**
         * Work out how many active users there are.
         * This gets called every time the auth page is loaded, so it needs
         * to be quick and cheap.
         */
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('users', 'COUNT(id) AS count')
          ->where('state = ?', Bugify_User::STATE_ACTIVE);
        
        $result = $db->fetchAll($s);
        $result = current($result);
        
        return (isset($result['count'])) ? $result['count'] : 0;
    }
    
    public function fetchAll()
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('users')
          ->where('state = ?', Bugify_User::STATE_ACTIVE);
        
        $result = $db->fetchAll($s);
        $users  = array();
        
        if (is_array($result) && count($result) > 0)
        {
            //Sort the projects alphabetically
            usort($result, array($this, '_sortUsers'));
            
            foreach ($result as $key => $val)
            {
                //Load into object
                $u = new Bugify_User();
                $u->setUserId($val['id'])
                  ->setUsername($val['username'])
                  ->setPasswordHash($val['password'])
                  ->setApiKey($val['api_key'])
                  ->setCreated($val['created'])
                  ->setUpdated($val['updated'])
                  ->setFirstname($val['firstname'])
                  ->setLastname($val['lastname'])
                  ->setEmail($val['email'])
                  ->setRawNotificationSettings($val['notifications'])
                  ->setTimezone($val['timezone'])
                  ->setState($val['state']);
                
                $users[] = $u;
            }
        }
        
        return $users;
    }
    
    /**
     * Fetch the specified user from the database
     * 
     * @return Bugify_User
     */
    public function fetch($username)
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('users')
          ->where('username = ?', $username)
          ->limit(1);
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0)
        {
            $result = current($result);
            
            //Load into object
            $u = new Bugify_User();
            $u->setUserId($result['id'])
              ->setUsername($result['username'])
              ->setPasswordHash($result['password'])
              ->setApiKey($result['api_key'])
              ->setCreated($result['created'])
              ->setUpdated($result['updated'])
              ->setFirstname($result['firstname'])
              ->setLastname($result['lastname'])
              ->setEmail($result['email'])
              ->setRawNotificationSettings($result['notifications'])
              ->setTimezone($result['timezone'])
              ->setState($result['state']);
            
            return $u;
        }
        else
        {
            throw new Bugify_Exception('The specified user does not exist.', 404);
        }
    }
    
    /**
     * Fetch the specified user from the database based on their Api key.
     * 
     * @return Bugify_User
     */
    public function fetchApiUser($api_key)
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('users')
          ->where('api_key = ?', $api_key)
          ->where('state = ?', Bugify_User::STATE_ACTIVE)
          ->limit(1);
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0)
        {
            $result = current($result);
            
            //Load into object
            $u = new Bugify_User();
            $u->setUserId($result['id'])
              ->setUsername($result['username'])
              ->setPasswordHash($result['password'])
              ->setApiKey($result['api_key'])
              ->setCreated($result['created'])
              ->setUpdated($result['updated'])
              ->setFirstname($result['firstname'])
              ->setLastname($result['lastname'])
              ->setEmail($result['email'])
              ->setRawNotificationSettings($result['notifications'])
              ->setTimezone($result['timezone'])
              ->setState($result['state']);
            
            return $u;
        }
        else
        {
            throw new Bugify_Exception('The specified Api key is not valid.', 401);
        }
    }
    
    public function save(Bugify_User $user)
    {
        if (!$user instanceof Bugify_User)
        {
            throw new Bugify_Exception('The object must be an instance of Bugify_User.');
        }
        
        if ($user->getUserId() > 0)
        {
            //Make sure the username is unique
            if (!$this->isUniqueUsername($user->getUsername(), $user->getUserId()))
            {
                throw new Bugify_Exception('The specified username has already been taken.  Please try a different username.');
            }
            
            //Update the database
            $data = array(
               'updated'       => time(),
               'firstname'     => $user->getFirstname(),
               'lastname'      => $user->getLastname(),
               'email'         => $user->getEmail(),
               'username'      => $user->getUsername(),
               'api_key'       => $user->getApiKey(),
               'notifications' => $user->getRawNotificationSettings(),
               'timezone'      => $user->getTimezone(),
               'state'         => $user->getState(),
            );
            
            /**
             * Only set a new password if the password hash is not empty.
             * We dont store the hashed (or plain) passwords in the object
             * unless we are changing the password.
             */
            $password = $user->getPasswordHash();
            
            if (strlen($password) > 0)
            {
                $data['password'] = $password;
            }
            
            $db = Bugify_Db::get();
            
            $where = array();
            $where[] = $db->quoteInto('id = ?', $user->getUserId());
            
            $db->update('users', $data, $where);
        }
        else
        {
            //We are adding a new user, make sure we are allowed
            if (Bugify_Limitations::getMaxUsers() <= $this->fetchCount()) {
                throw new Bugify_Exception('You have reached the maximum number of allowed users.  Please consider upgrading your plan.');
            }
            
            //Make sure the username is unique
            if (!$this->isUniqueUsername($user->getUsername()))
            {
                throw new Bugify_Exception('The specified username has already been taken.  Please try a different username.');
            }
            
            //Make sure the Api key has been set
            if (strlen($user->getApiKey()) == 0)
            {
                $user->setApiKey($user->generateApiKey());
            }
            
            //Insert as new user
            $data = array(
               'created'       => time(),
               'updated'       => time(),
               'firstname'     => $user->getFirstname(),
               'lastname'      => $user->getLastname(),
               'email'         => $user->getEmail(),
               'username'      => $user->getUsername(),
               'password'      => $user->getPasswordHash(),
               'api_key'       => $user->getApiKey(),
               'notifications' => $user->getRawNotificationSettings(),
               'timezone'      => $user->getTimezone(),
               'state'         => $user->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $db->insert('users', $data);
        }
    }
    
    public function isUniqueUsername($username, $user_id='')
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('users', array('id'))
          ->where('username = ?', $username);
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0)
        {
            $result = current($result);
            
            if ($result['id'] == $user_id)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        
        return true;
    }
}
