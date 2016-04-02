<?php

class Bugify_Issue_Follower
{
    private $_id       = 0;
    private $_issue_id = 0;
    private $_user_id  = 0;
    
    public function __construct()
    {}
    
    public function getFollowerId()
    {
        return $this->_id;
    }
    
    public function getIssueId()
    {
        return $this->_issue_id;
    }
    
    public function getUserId()
    {
        return $this->_user_id;
    }
    
    public function setFollowerId($val)
    {
        $this->_id = $val;
        
        return $this;
    }
    
    public function setIssueId($val)
    {
        $this->_issue_id = $val;
        
        return $this;
    }
    
    public function setUserId($val)
    {
        $this->_user_id = $val;
        
        return $this;
    }
    
    public function toArray()
    {
        $data = array(
           'id'       => $this->getFollowerId(),
           'issue_id' => $this->getIssueId(),
           'user_id'  => $this->getUserId(),
        );
        
        return $data;
    }
}
