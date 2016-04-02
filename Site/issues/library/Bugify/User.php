<?php

class Bugify_User {
    private $_user_id       = 0;
    private $_created       = 0;
    private $_updated       = 0;
    private $_firstname     = '';
    private $_lastname      = '';
    private $_email         = '';
    private $_username      = '';
    private $_password_hash = '';
    private $_api_key       = '';
    private $_notifications = '';
    private $_timezone      = '';
    private $_state         = self::STATE_ACTIVE;
    
    const STATE_ARCHIVED = 0;
    const STATE_ACTIVE   = 1;
    
    const NOTIFICATION_CREATOR   = 'creator';   //Issues I created
    const NOTIFICATION_ASSIGNEE  = 'assignee';  //Issues assigned to me
    const NOTIFICATION_FOLLOWING = 'following'; //Issues I'm following
    const NOTIFICATION_COMMENTED = 'commented'; //Issues I commented on
    const NOTIFICATION_MYCHANGE  = 'mychange';  //Change I made
    
    private $_validNotifications = array(
        self::NOTIFICATION_CREATOR,
        self::NOTIFICATION_ASSIGNEE,
        self::NOTIFICATION_FOLLOWING,
        self::NOTIFICATION_COMMENTED,
        self::NOTIFICATION_MYCHANGE,
    );
    
    public function __construct() {}
    
    private function _serialize($array) {
        if (!is_array($array)) {
            throw new Exception('Can only serialize an array.');
        }
        
        return json_encode($array);
    }
    
    private function _deserialize($string) {
        $array = array();
        
        if (is_string($string)) {
            $array = json_decode($string, true);
        } elseif (is_null($string)) {
            $array = array();
        }
        
        if (!is_array($array)) {
            $array = array();
        }
        
        return $array;
    }
    
    public function getUserId() {
        return $this->_user_id;
    }
    
    public function getCreated() {
        return Bugify_Date::getLocalTime($this->_created);
    }
    
    public function getUpdated() {
        return Bugify_Date::getLocalTime($this->_updated);
    }
    
    public function getName() {
        //Work out the users' name as a single string
        $name = (strlen($this->getFirstname()) > 0) ? trim(sprintf('%s %s', $this->getFirstname(), $this->getLastname())) : $this->getUsername();
        
        return $name;
    }
    
    public function getFirstname() {
        return $this->_firstname;
    }
    
    public function getLastname() {
        return $this->_lastname;
    }
    
    public function getEmail() {
        return $this->_email;
    }
    
    public function getUsername() {
        return $this->_username;
    }
    
    public function getPasswordHash() {
        return $this->_password_hash;
    }
    
    public function getApiKey() {
        return $this->_api_key;
    }
    
    public function getRawNotificationSettings() {
        return $this->_notifications;
    }
    
    public function getRequiresNotification($type) {
        //Check if this user wants this type of notification
        //Default all notifications enabled
        $requiresNotification = true;
        
        if (in_array($type, $this->_validNotifications)) {
            $notifications = $this->_deserialize($this->_notifications);
            
            if (array_key_exists($type, $notifications)) {
                $requiresNotification = (bool)$notifications[$type];
            }
        } else {
            throw new Bugify_Exception('Invalid notification type.');
        }
        
        return $requiresNotification;
    }
    
    public function getValidNotificationTypes() {
        return $this->_validNotifications;
    }
    
    public function getTimezone() {
        return $this->_timezone;
    }
    
    public function getState() {
        return $this->_state;
    }
    
    public function setUserId($val) {
        $this->_user_id = $val;
        
        return $this;
    }
    
    public function setCreated($val) {
        $this->_created = $val;
        
        return $this;
    }
    
    public function setUpdated($val) {
        $this->_updated = $val;
        
        return $this;
    }
    
    public function setFirstname($val)
    {
        $this->_firstname = $val;
        
        return $this;
    }
    
    public function setLastname($val) {
        $this->_lastname = $val;
        
        return $this;
    }
    
    public function setEmail($val) {
        //Make sure the email is valid
        $v = new Zend_Validate_EmailAddress();
        
        if ($v->isValid($val)) {
            $this->_email = $val;
        } else {
            throw new Bugify_Exception('The email address is not valid.');
        }
        
        return $this;
    }
    
    public function setUsername($val) {
        $this->_username = strtolower($val);
        
        return $this;
    }
    
    public function setPlainTextPassword($val) {
        if (strlen($this->_username) > 0) {
            //Hash the password
            $a    = new Bugify_Auth();
            $hash = $a->hashPassword($val);
            
            return $this->setPasswordHash($hash);
        } else {
            throw new Bugify_Exception('Cannot generate a password hash without the username.');
        }
    }
    
    public function setPasswordHash($val) {
        $this->_password_hash = $val;
        
        return $this;
    }
    
    public function setRawNotificationSettings($val) {
        $this->_notifications = $val;
        
        return $this;
    }
    
    public function setRequiresNotification($type, $state) {
        if (in_array($type, $this->_validNotifications)) {
            //Load the current notification settings
            $notifications = $this->_deserialize($this->_notifications);
            
            //Update the specified notification
            $notifications[$type] = (bool)$state;
            
            //Remember the notification settings
            $this->_notifications = $this->_serialize($notifications);
        } else {
            throw new Bugify_Exception('Invalid notification type.');
        }
        
        return $this;
    }
    
    public function setApiKey($val) {
        if (strlen($val) > 0) {
            $this->_api_key = $val;
        } else {
            //Generate a new key because this one is empty
            $this->_api_key = $this->generateApiKey();
        }
        
        return $this;
    }
    
    public function generateApiKey() {
        //Generate a random-ish api key
        if (function_exists('openssl_random_pseudo_bytes')) {
            $api_key = openssl_random_pseudo_bytes(16);
        } else {
            //Not as random as openssl, but a suitable alternative for older PHP installs
            $chars   = 'abcdefghijklmnopqrstuvwxyz!@#$%^&*()_+1234567890';
            $api_key = '';
            
            for ($p = 0; $p < 50; $p++) {
                $api_key .= ($p%2) ? $chars[mt_rand(19, 23)] : $chars[mt_rand(0, 18)];
            }
        }
        
        $api_key = base64_encode($api_key);
        
        return $api_key;
    }
    
    public function setTimezone($val) {
        if (strlen($val) > 0) {
            if (Bugify_Date::isValidTimezone($val)) {
                $this->_timezone = $val;
            } else {
                throw new Bugify_Exception('Invalid timezone.');
            }
        } else {
            //Use the default timezone
            $this->_timezone = Bugify_Date::DEFAULT_TIMEZONE;
        }
        
        return $this;
    }
    
    public function setState($val) {
        $valid_states = array(
           self::STATE_ACTIVE,
           self::STATE_ARCHIVED,
        );
        
        if (in_array($val, $valid_states)) {
            $this->_state = $val;
        } else {
            throw new Bugify_Exception('Invalid state.');
        }
        
        return $this;
    }
    
    public function toArray() {
        /**
         * Note: we don't include the api key in this array for security/privacy reasons.
         */
        $data = array(
           'id'            => $this->getUserId(),
           'created'       => $this->getCreated(),
           'updated'       => $this->getUpdated(),
           'firstname'     => $this->getFirstname(),
           'lastname'      => $this->getLastname(),
           'name'          => $this->getName(),
           'email'         => $this->getEmail(),
           'username'      => $this->getUsername(),
           'notifications' => array(),
           'timezone'      => $this->getTimezone(),
           'state'         => $this->getState(),
        );
        
        //Add the notification settings
        foreach ($this->_validNotifications as $key => $val) {
            $data['notifications'][$val] = $this->getRequiresNotification($val);
        }
        
        return $data;
    }
}
