<?php

class Bugify_Auth
{
    public function __construct()
    {
    
    }
    
    public function hashPassword($plain_text_password)
    {
        /**
         * Load the bcrypt hasher with an iteration count of 10
         * and non-portable hashes.
         */
        $h    = new Bugify_Auth_PasswordHash(10, false);
        $hash = $h->HashPassword($plain_text_password);
        
        return $hash;
    }
    
    /**
     * The possible password is provided from the login system.
     * The correct hash was loaded from the db.
     */
    public function checkPassword($possible_plain_text_password, $correct_hash)
    {
        //Check the password
        $h      = new Bugify_Auth_PasswordHash(10, false);
        $result = $h->CheckPassword($possible_plain_text_password, $correct_hash);
        
        return $result;
    }
    
    public function auth($username, $password)
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('users')
          ->where('username = ?', $username)
          ->where('state IN (?)', array(Bugify_User::STATE_ACTIVE))
          ->limit(1);
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0)
        {
            $result = current($result);
            
            //Get the password hash
            if ($this->checkPassword($password, $result['password']) === true)
            {
                //Password was correct, load the user object
                $u = new Bugify_User();
                $u->setUserId($result['id'])
                  ->setCreated($result['created'])
                  ->setUpdated($result['updated'])
                  ->setUsername($result['username'])
                  //->setPlainTextPassword($password)
                  ->setApiKey($result['api_key'])
                  ->setFirstname($result['firstname'])
                  ->setLastname($result['lastname'])
                  ->setEmail($result['email'])
                  ->setTimezone($result['timezone'])
                  ->setState($result['state']);
                
                return $u;
            }
            else
            {
                throw new Bugify_Exception('The username or password is not correct.');
            }
        }
        else
        {
            throw new Bugify_Exception('The username or password is not correct.');
        }
        
        return false;
    }
}
