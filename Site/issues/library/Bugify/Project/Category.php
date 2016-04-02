<?php

class Bugify_Project_Category
{
    private $_id         = 0;
    private $_project_id = 0;
    private $_created    = 0;
    private $_updated    = 0;
    private $_name       = '';
    private $_state      = self::STATE_ACTIVE;
    
    const STATE_ARCHIVED = 0;
    const STATE_ACTIVE   = 1;
    
    public function __construct()
    {}
    
    public function getCategoryId()
    {
        return $this->_id;
    }
    
    public function getProjectId()
    {
        return $this->_project_id;
    }
    
    public function getCreated()
    {
        return Bugify_Date::getLocalTime($this->_created);
    }
    
    public function getUpdated()
    {
        return Bugify_Date::getLocalTime($this->_updated);
    }
    
    public function getName()
    {
        return $this->_name;
    }
    
    public function getState()
    {
        return $this->_state;
    }
    
    public function setCategoryId($val)
    {
        $this->_id = $val;
        
        return $this;
    }
    
    public function setProjectId($val)
    {
        $this->_project_id = $val;
        
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
    
    public function setState($val)
    {
        $valid_states = array(
           self::STATE_ACTIVE,
           self::STATE_ARCHIVED,
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
           'id'         => $this->getCategoryId(),
           'project_id' => $this->getProjectId(),
           'created'    => $this->getCreated(),
           'updated'    => $this->getUpdated(),
           'name'       => (strlen($this->getName()) > 0) ? $this->getName() : '[No Name]',
        );
        
        return $data;
    }
}
