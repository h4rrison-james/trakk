<?php

class Bugify_Milestone
{
    private $_id          = 0;
    private $_created     = 0;
    private $_updated     = 0;
    private $_due         = 0;
    private $_name        = '';
    private $_description = '';
    private $_state       = self::STATE_ACTIVE;
    
    const STATE_INACTIVE = 0;
    const STATE_ACTIVE   = 1;
    
    public function __construct()
    {}
    
    public function getMilestoneId()
    {
        return $this->_id;
    }
    
    public function getCreated()
    {
        return Bugify_Date::getLocalTime($this->_created);
    }
    
    public function getUpdated()
    {
        return Bugify_Date::getLocalTime($this->_updated);
    }
    
    public function getDueDate()
    {
        return ($this->_due > 0) ? Bugify_Date::getLocalTime($this->_due) : '';
    }
    
    public function getName()
    {
        return $this->_name;
    }
    
    public function getDescription()
    {
        return $this->_description;
    }
    
    public function getState()
    {
        return $this->_state;
    }
    
    public function setMilestoneId($val)
    {
        $this->_id = $val;
        
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
    
    public function setDueDate($val)
    {
        $this->_due = (int)$val;
        
        return $this;
    }
    
    public function setName($val)
    {
        if (strlen($val) > 0)
        {
            $this->_name = $val;
        }
        else
        {
            throw new Bugify_Exception('Please specify a milestone name.');
        }
        
        return $this;
    }
    
    public function setDescription($val)
    {
        if (strlen($val) > 0)
        {
            $this->_description = $val;
        }
        
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
           'id'          => $this->getMilestoneId(),
           'created'     => $this->getCreated(),
           'updated'     => $this->getUpdated(),
           'due'         => $this->getDueDate(),
           'name'        => $this->getName(),
           'description' => $this->getDescription(),
           'state'       => $this->getState(),
        );
        
        return $data;
    }
}
