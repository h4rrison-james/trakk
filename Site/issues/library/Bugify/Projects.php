<?php

class Bugify_Projects
{
    public function __construct()
    {}
    
    private function _sortProjects($a, $b)
    {
        return strnatcasecmp($a['name'], $b['name']);
    }
    
    public function fetchCount() {
        /**
         * Work out how many active projects there are.
         */
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('projects', 'COUNT(id) AS count')
          ->where('state = ?', Bugify_Project::STATE_ACTIVE);
        
        $result = $db->fetchAll($s);
        $result = current($result);
        
        return (isset($result['count'])) ? $result['count'] : 0;
    }
    
    public function fetchAll()
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('projects')
          ->where('state = ?', Bugify_Project::STATE_ACTIVE);
        
        $result   = $db->fetchAll($s);
        $projects = array();
        
        if (is_array($result) && count($result) > 0)
        {
            //Sort the projects alphabetically
            usort($result, array($this, '_sortProjects'));
            
            foreach ($result as $key => $val)
            {
                //Load into object
                $p = new Bugify_Project();
                $p->setProjectId($val['id'])
                  ->setCreated($val['created'])
                  ->setUpdated($val['updated'])
                  ->setSlug($val['slug'])
                  ->setName($val['name'])
                  ->setState($val['state']);
                
                $projects[] = $p;
            }
        }
        
        return $projects;
    }
    
    /**
     * Fetch the specified project from the database
     * using either the project slug or id.
     * 
     * @return Bugify_Project
     */
    public function fetch($slug)
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('projects')
          ->where('state = ?', Bugify_Project::STATE_ACTIVE);
        
        if (is_int($slug))
        {
            $s->where('id = ?', $slug);
        }
        else
        {
            $s->where('slug = ?', $slug);
        }
        
        $s->limit(1);
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0)
        {
            $result = current($result);
            
            //Load into object
            $p = new Bugify_Project();
            $p->setProjectId($result['id'])
              ->setCreated($result['created'])
              ->setUpdated($result['updated'])
              ->setSlug($result['slug'])
              ->setName($result['name'])
              ->setState($result['state']);
            
            return $p;
        }
        else
        {
            throw new Bugify_Exception('The specified project does not exist.', 404);
        }
    }
    
    public function save(Bugify_Project $project)
    {
        if (!$project instanceof Bugify_Project)
        {
            throw new Bugify_Exception('The object must be an instance of Bugify_Project.');
        }
        
        if ($project->getProjectId() > 0)
        {
            //Update the database
            $data = array(
               'updated' => time(),
               'name'    => $project->getName(),
               'slug'    => $project->getSlug(),
               'state'   => $project->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $where   = array();
            $where[] = $db->quoteInto('id = ?', $project->getProjectId());
            
            $db->update('projects', $data, $where);
        }
        else
        {
            //We are adding a new project, make sure we are allowed
            if (Bugify_Limitations::getMaxProjects() <= $this->fetchCount()) {
                throw new Bugify_Exception('You have reached the maximum number of allowed projects.  Please consider upgrading your plan.');
            }
            
            //Insert as new project
            $data = array(
               'created' => time(),
               'updated' => time(),
               'name'    => $project->getName(),
               'slug'    => $project->getSlug(),
               'state'   => $project->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $db->insert('projects', $data);
            
            return $db->lastInsertId();
        }
    }
}
