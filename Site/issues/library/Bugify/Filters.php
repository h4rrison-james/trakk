<?php

class Bugify_Filters
{
    public function __construct()
    {}
    
    public function fetchAll()
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('filters');
        
        $result  = $db->fetchAll($s);
        $filters = array();
        
        if (is_array($result) && count($result) > 0)
        {
            foreach ($result as $key => $val)
            {
                //Load into object
                $f = new Bugify_Filter();
                $f->setFilterId($val['id'])
                  ->setUserId($val['user_id'])
                  ->setName($val['name'])
                  ->setJsonFilter($val['filter']);
                
                $filters[] = $f;
            }
        }
        
        return $filters;
    }
    
    public function fetchAllForUser(Bugify_User $user)
    {
        if (!$user instanceof Bugify_User)
        {
            throw new Bugify_Exception('The object must be an instance of Bugify_User.');
        }
        
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('filters')
          ->where('user_id = ?', $user->getUserId());
        
        $result  = $db->fetchAll($s);
        $filters = array();
        
        if (is_array($result) && count($result) > 0)
        {
            foreach ($result as $key => $val)
            {
                //Load into object
                $f = new Bugify_Filter();
                $f->setFilterId($val['id'])
                  ->setUserId($val['user_id'])
                  ->setName($val['name'])
                  ->setJsonFilter($val['filter']);
                
                $filters[] = $f;
            }
        }
        
        return $filters;
    }
    
    public function fetch($filter_id)
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('filters')
          ->where('id = ?', $filter_id)
          ->limit(1);
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0)
        {
            $result = current($result);
            
            //Load into object
            $f = new Bugify_Filter();
            $f->setFilterId($result['id'])
              ->setUserId($result['user_id'])
              ->setName($result['name'])
              ->setJsonFilter($result['filter']);
            
            return $f;
        }
        else
        {
            throw new Bugify_Exception('The specified filter does not exist.', 404);
        }
    }
    
    public function save(Bugify_Filter $filter)
    {
        if (!$filter instanceof Bugify_Filter)
        {
            throw new Bugify_Exception('The object must be an instance of Bugify_Filter.');
        }
        
        if ($filter->getFilterId() > 0)
        {
            //Update the database
            $data = array(
               'name'   => $filter->getName(),
               'filter' => $filter->getJsonFilter(),
            );
            
            $db = Bugify_Db::get();
            
            $where   = array();
            $where[] = $db->quoteInto('id = ?', $filter->getFilterId());
            
            $db->update('filters', $data, $where);
        }
        else
        {
            //Insert as new filter
            $data = array(
               'name'    => $filter->getName(),
               'user_id' => $filter->getUserId(),
               'filter'  => $filter->getJsonFilter(),
            );
            
            $db = Bugify_Db::get();
            
            $db->insert('filters', $data);
            
            return $db->lastInsertId();
        }
    }
    
    public function remove(Bugify_Filter $filter)
    {
        if (!$filter instanceof Bugify_Filter)
        {
            throw new Bugify_Exception('The object must be an instance of Bugify_Filter.');
        }
        
        if ($filter->getFilterId() > 0)
        {
            //Delete the filter from the database
            $db = Bugify_Db::get();
            
            $where   = array();
            $where[] = $db->quoteInto('id = ?', $filter->getFilterId());
            
            $db->delete('filters', $where);
        }
    }
}
