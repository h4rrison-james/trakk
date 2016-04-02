<?php

class Bugify_Milestones
{
    public function __construct()
    {}
    
    public function fetchAll()
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('milestones')
          ->where('state = ?', Bugify_Milestone::STATE_ACTIVE);
        
        $result     = $db->fetchAll($s);
        $milestones = array();
        
        if (is_array($result) && count($result) > 0)
        {
            foreach ($result as $key => $val)
            {
                //Load into object
                $m = new Bugify_Milestone();
                $m->setMilestoneId($val['id'])
                  ->setCreated($val['created'])
                  ->setUpdated($val['updated'])
                  ->setDueDate($val['due'])
                  ->setName($val['name'])
                  ->setDescription($val['description'])
                  ->setState($val['state']);
                
                $milestones[] = $m;
            }
        }
        
        return $milestones;
    }
    
    /**
     * Fetch the specified milestone from the database
     * 
     * @return Bugify_Milestone
     */
    public function fetch($milestone_id)
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('milestones')
          ->where('id = ?', $milestone_id)
          ->limit(1);
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0)
        {
            $result = current($result);
            
            //Load into object
            $m = new Bugify_Milestone();
            $m->setMilestoneId($result['id'])
              ->setCreated($result['created'])
              ->setUpdated($result['updated'])
              ->setDueDate($result['due'])
              ->setName($result['name'])
              ->setDescription($result['description'])
              ->setState($result['state']);
            
            return $m;
        }
        else
        {
            throw new Bugify_Exception('The specified milestone does not exist.', 404);
        }
    }
    
    public function save(Bugify_Milestone $milestone)
    {
        if (!$milestone instanceof Bugify_Milestone)
        {
            throw new Bugify_Exception('The object must be an instance of Bugify_Milestone.');
        }
        
        if ($milestone->getMilestoneId() > 0)
        {
            //Update the database
            $data = array(
               'updated'     => time(),
               'due'         => Bugify_Date::getTimestamp($milestone->getDueDate()),
               'name'        => $milestone->getName(),
               'description' => $milestone->getDescription(),
               'state'       => $milestone->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $where   = array();
            $where[] = $db->quoteInto('id = ?', $milestone->getMilestoneId());
            
            $db->update('milestones', $data, $where);
        }
        else
        {
            //Insert as new milestone
            $data = array(
               'created'     => time(),
               'updated'     => time(),
               'due'         => Bugify_Date::getTimestamp($milestone->getDueDate()),
               'name'        => $milestone->getName(),
               'description' => $milestone->getDescription(),
               'state'       => $milestone->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $db->insert('milestones', $data);
            
            return $db->lastInsertId();
        }
    }
}
