<?php

class Bugify_Issue_History
{
    private $_id       = 0;
    private $_issue_id = 0;
    private $_user_id  = 0;
    private $_created  = 0;
    private $_changes  = array();
    
    public function __construct()
    {}
    
    public function getHistoryId()
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
    
    public function getChanges()
    {
        if (count($this->_changes) == 0)
        {
            $this->_fetchChanges();
        }
        
        return $this->_changes;
    }
    
    
    public function setHistoryId($val)
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
    
    public function addChange(Bugify_Issue_History_Change $change)
    {
        if (!$change instanceof Bugify_Issue_History_Change)
        {
            throw new Bugify_Exception('The object must be an instance of Bugify_Issue_History_Change.');
        }
        
        $this->_changes[] = $change;
        
        return $this;
    }
    
    private function _fetchChanges()
    {
        if ($this->getHistoryId() > 0)
        {
            $db = Bugify_Db::get();
            
            $s = $db->select();
            $s->from('history_changes')
              ->where('history_id = ?', $this->getHistoryId());
            
            $result = $db->fetchAll($s);
            
            if (is_array($result) && count($result) > 0)
            {
                //Clear out existing changes
                $this->_changes = array();
                
                foreach ($result as $key => $val)
                {
                    //Load into object
                    $c = new Bugify_Issue_History_Change();
                    $c->setChangeId($val['id'])
                      ->setHistoryId($val['history_id'])
                      ->setType($val['type'])
                      ->setOriginal($val['original'])
                      ->setNew($val['new']);
                    
                    $this->_changes[] = $c;
                }
            }
        }
    }
    
    public function toArray()
    {
        $data = array(
           'id'       => $this->getHistoryId(),
           'issue_id' => $this->getIssueId(),
           'user_id'  => $this->getUserId(),
           'created'  => $this->getCreated(),
           'changes'  => array(),
        );
        
        //Load the changes
        $changes = $this->getChanges();
        
        foreach ($changes as $change)
        {
            $data['changes'][] = $change->toArray();
        }
        
        return $data;
    }
}
