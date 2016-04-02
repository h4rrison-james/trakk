<?php

class Bugify_Categories
{
    public function __construct()
    {}
    
    public function fetchAll()
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('categories');
          //->where('state = ?', Bugify_Project_Category::STATE_ACTIVE);
        
        $result     = $db->fetchAll($s);
        $categories = array();
        
        if (is_array($result) && count($result) > 0)
        {
            foreach ($result as $key => $val)
            {
                //Load into object
                $p = new Bugify_Project_Category();
                $p->setCategoryId($val['id'])
                  ->setProjectId($val['project_id'])
                  ->setCreated($val['created'])
                  ->setUpdated($val['updated'])
                  ->setName($val['name'])
                  ->setState($val['state']);
                
                $categories[] = $p;
            }
        }
        
        return $categories;
    }
    
    /**
     * Fetch the specified category from the database
     * 
     * @return Bugify_Category
     */
    public function fetch($id)
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('categories')
          ->where('id = ?', $id)
          ->limit(1);
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0)
        {
            $result = current($result);
            
            //Load into object
            $p = new Bugify_Project_Category();
            $p->setCategoryId($result['id'])
              ->setProjectId($result['project_id'])
              ->setCreated($result['created'])
              ->setUpdated($result['updated'])
              ->setName($result['name'])
              ->setState($result['state']);
            
            return $p;
        }
        else
        {
            throw new Bugify_Exception('The specified category does not exist.', 404);
        }
    }
}
