<?php

class Bugify_Issue_Comment
{
    private $_id       = 0;
    private $_issue_id = 0;
    private $_user_id  = 0;
    private $_created  = 0;
    private $_updated  = 0;
    private $_comment  = '';
    private $_state    = self::STATE_ACTIVE;
    
    const STATE_INACTIVE = 0;
    const STATE_ACTIVE   = 1;
    
    public function __construct()
    {}
    
    public function getCommentId()
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
    
    public function getCreated()
    {
        return Bugify_Date::getLocalTime($this->_created);
    }
    
    public function getUpdated()
    {
        return Bugify_Date::getLocalTime($this->_updated);
    }
    
    public function getComment()
    {
        return $this->_comment;
    }
    
    public function getState()
    {
        return $this->_state;
    }
    
    public function setCommentId($val)
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
    
    public function setCreated($val)
    {
        $this->_created = $val;
        
        return $this;
    }
    
    public function setUpdated($val)
    {
        $this->_updated = $val;
        
        return $this;
    }
    
    public function setComment($val)
    {
        $this->_comment = trim($val);
        
        return $this;
    }
    
    public function setState($val)
    {
        $valid_states = array(
           self::STATE_INACTIVE,
           self::STATE_ACTIVE,
        );
        
        if (in_array($val, $valid_states))
        {
            $this->_state = $val;
        }
        else
        {
            throw new Bugify_Exception('Invalid state.');
        }
        
        return $this;
    }
    
    public function toArray()
    {
        $data = array(
           'id'       => $this->getCommentId(),
           'issue_id' => $this->getIssueId(),
           'user_id'  => $this->getUserId(),
           'created'  => $this->getCreated(),
           'updated'  => $this->getUpdated(),
           'comment'  => $this->getComment(),
           'state'    => $this->getState(),
        );
        
        return $data;
    }
}
