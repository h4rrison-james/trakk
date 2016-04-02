<?php

class Bugify_Queue_Job {
    private $_id       = 0;
    private $_created  = 0;
    private $_updated  = 0;
    private $_started  = 0;
    private $_finished = 0;
    private $_method   = '';
    private $_params   = '';
    private $_message  = '';
    private $_state    = self::STATE_PENDING;
    
    const STATE_PENDING  = 0;
    const STATE_RUNNING  = 1;
    const STATE_COMPLETE = 2;
    const STATE_FAILED   = 3;
    
    public function __construct() {}
    
    private function _serialize($data) {
        if (!is_array($data)) {
            $data = array($data);
        }
        
        return json_encode($data);
    }
    
    private function _unserialize($string) {
        return json_decode($string, true);
    }
    
    public function getJobId() {
        return $this->_id;
    }
    
    public function getCreated() {
        return Bugify_Date::getLocalTime($this->_created);
    }
    
    public function getUpdated() {
        return Bugify_Date::getLocalTime($this->_updated);
    }
    
    public function getStarted() {
        return Bugify_Date::getLocalTime($this->_started);
    }
    
    public function getFinished() {
        return Bugify_Date::getLocalTime($this->_finished);
    }
    
    public function getMethod() {
        return $this->_method;
    }
    
    public function getParams() {
        return $this->_unserialize($this->_params);
    }
    
    public function getParamsAsString() {
        return $this->_params;
    }
    
    public function getMessage() {
        return $this->_message;
    }
    
    public function getState() {
        return $this->_state;
    }
    
    public function setJobId($val) {
        $this->_id = (int)$val;
        
        return $this;
    }
    
    public function setCreated($val) {
        $this->_created = (int)$val;
        
        return $this;
    }
    
    public function setUpdated($val) {
        $this->_updated = (int)$val;
        
        return $this;
    }
    
    public function setStarted($val) {
        $this->_started = (int)$val;
        
        return $this;
    }
    
    public function setFinished($val) {
        $this->_finished = (int)$val;
        
        return $this;
    }
    
    public function setMethod($val) {
        $this->_method = $val;
        
        return $this;
    }
    
    public function setParams($val) {
        $this->_params = $this->_serialize($val);
        
        return $this;
    }
    
    public function setRawParams($val) {
        $this->_params = $val;
        
        return $this;
    }
    
    public function setMessage($val) {
        $this->_message = $val;
        
        return $this;
    }
    
    public function setState($val) {
        $valid_states = array(
           self::STATE_PENDING,
           self::STATE_RUNNING,
           self::STATE_COMPLETE,
           self::STATE_FAILED,
        );
        
        if (in_array($val, $valid_states)) {
            $this->_state = $val;
        } else {
            throw new Bugify_Exception('Invalid state.');
        }
        
        return $this;
    }
}
