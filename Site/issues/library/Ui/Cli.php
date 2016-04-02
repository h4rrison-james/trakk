<?php

class Ui_Cli {
    private $_expected_username = '';
    
    public function setExpectedUser($user) {
        $this->_expected_username = $user;
        
        return $this;
    }
    
    public function dropPrivileges() {
        //Make sure the user that is running this script is correct
        $user     = posix_getpwuid(posix_getuid());
        $username = (isset($user['name'])) ? $user['name'] : '';
        
        if ($this->_expected_username != $username) {
            if (strlen($this->_expected_username) > 0) {
                //Attempt to drop down into the expected user
                $user = posix_getpwnam($this->_expected_username);
                
                if (!isset($user['uid'])) {
                    throw new Bugify_Exception(sprintf('This user "%s" cannot be found.', $this->_expected_username));
                }
                
                if (posix_setgid($user['gid']) === false) {
                    //Cannot drop into the expected users' group
                    throw new Bugify_Exception(sprintf('This tool must be run as "%s".', $this->_expected_username));
                }
                
                if (posix_setuid($user['uid']) === false) {
                    //Cannot drop into the expected user
                    throw new Bugify_Exception(sprintf('This tool must be run as "%s".', $this->_expected_username));
                }
            } else {
                throw new Bugify_Exception('Please specify the expected username.');
            }
        }
        
        return true;
    }
}
