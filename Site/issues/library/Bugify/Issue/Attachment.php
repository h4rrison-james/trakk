<?php

class Bugify_Issue_Attachment
{
    private $_id       = 0;
    private $_issue_id = 0;
    private $_user_id  = 0;
    private $_created  = 0;
    private $_updated  = 0;
    private $_name     = '';
    private $_filename = '';
    private $_filesize = 0;
    private $_state    = self::STATE_ACTIVE;
    
    const STATE_INACTIVE = 0;
    const STATE_ACTIVE   = 1;
    
    public function __construct()
    {}
    
    public function getAttachmentId()
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
        return $this->_created;
    }
    
    public function getUpdated()
    {
        return $this->_updated;
    }
    
    public function getName()
    {
        return $this->_name;
    }
    
    public function getFilename()
    {
        return $this->_filename;
    }
    
    public function getFilesize()
    {
        return $this->_filesize;
    }
    
    public function getState()
    {
        return $this->_state;
    }
    
    public function setAttachmentId($val)
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
    
    public function setName($val)
    {
        $this->_name = $val;
        
        return $this;
    }
    
    public function setFilename($val)
    {
        $this->_filename = $val;
        
        return $this;
    }
    
    public function setFilesize($val)
    {
        $this->_filesize = $val;
        
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
           'id'       => $this->getAttachmentId(),
           'issue_id' => $this->getIssueId(),
           'user_id'  => $this->getUserId(),
           'created'  => $this->getCreated(),
           'updated'  => $this->getUpdated(),
           'name'     => $this->getName(),
           'filename' => $this->getFilename(),
           'filesize' => $this->getFilesize(),
           'state'    => $this->getState(),
        );
        
        return $data;
    }
}
