<?php

class Bugify_Project
{
    private $_id      = 0;
    private $_created = 0;
    private $_updated = 0;
    private $_name    = '';
    private $_slug    = '';
    private $_state   = self::STATE_ACTIVE;
    
    private $_categories = array();
    
    const STATE_ARCHIVED = 0;
    const STATE_ACTIVE   = 1;
    
    public function __construct()
    {}
    
    public function getProjectId()
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
    
    public function getName()
    {
        return $this->_name;
    }
    
    public function getSlug()
    {
        return $this->_slug;
    }
    
    public function getState()
    {
        return $this->_state;
    }
    
    public function getCategories()
    {
        if (count($this->_categories) == 0)
        {
            $this->_fetchCategories();
        }
        
        return $this->_categories;
    }
    
    private function _sortCategories($a, $b)
    {
        return strnatcasecmp($a['name'], $b['name']);
    }
    
    private function _fetchCategories()
    {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('categories')
          ->where('project_id = ?', $this->getProjectId())
          ->where('state = ?', Bugify_Project_Category::STATE_ACTIVE);
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0)
        {
            //Clear out existing categories
            $this->_categories = array();
            
            //Sort the categories alphabetically
            usort($result, array($this, '_sortCategories'));
            
            foreach ($result as $key => $val)
            {
                //Load into object
                $c = new Bugify_Project_Category();
                $c->setCategoryId($val['id'])
                  ->setProjectId($val['project_id'])
                  ->setCreated($val['created'])
                  ->setUpdated($val['updated'])
                  ->setName($val['name'])
                  ->setState($val['state']);
                
                $this->_categories[] = $c;
            }
        }
    }
    
    public function setProjectId($val)
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
    
    public function setName($val)
    {
        $this->_name = $val;
        
        if (strlen($this->getSlug()) == 0)
        {
            $this->setSlug($this->_generateSlug($val));
        }
        
        return $this;
    }
    
    /**
     * Generate a unique URL slug based on the provided string.
     */
    private function _generateSlug($string)
    {
        /**
         * Some slugs shouldnt be used because they
         * may intefere with the URL structure.
         */
        $blacklist = array(
           'new',
        );
        
        //Get rid of non-word characters and whitespace
        $slug = preg_replace('/[^\w\s]/i', '', strtolower($string));
        $slug = preg_replace('/\s+/', '-', $slug);
        
        //Make sure the slug is long enough
        if (strlen($slug) < 3)
        {
            //Padd out the slug
            $slug .= '-project';
        }
        
        //Make sure the slug is not in the blacklist
        foreach ($blacklist as $invalid)
        {
            if ($slug == $invalid)
            {
                $slug .= '-project';
            }
        }
        
        //Make sure the slug is unique
        $db = Bugify_Db::get();
        
        $unique = false;
        
        do
        {
            $s = $db->select();
            $s->from('projects', array('slug'))
              ->where('slug = ?', $slug)
              ->limit(1);
            
            $result = $db->fetchAll($s);
            
            if (!is_array($result) || count($result) == 0)
            {
                $unique = true;
            }
            else
            {
                //This slug already exists, append a number
                if (is_numeric(substr($slug, -1)))
                {
                    //Increment the last digit
                    $digit = substr($slug, -1)+1;
                    $slug  = substr($slug, 0, -1).$digit;
                }
                else
                {
                    $slug .= '-2';
                }
            }
        }
        while ($unique === false);
        
        return $slug;
    }
    
    public function setSlug($val)
    {
        $this->_slug = $val;
        
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
    
    public function saveCategory(Bugify_Project_Category $category)
    {
        if (!$category instanceof Bugify_Project_Category)
        {
            throw new Bugify_Exception('The object must be an instance of Bugify_Project_Category.');
        }
        
        if ($category->getCategoryId() > 0)
        {
            //Update the database
            $data = array(
               'updated' => time(),
               'name'    => $category->getName(),
               'state'   => $category->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $where = array();
            $where[] = $db->quoteInto('id = ?', $category->getCategoryId());
            
            $db->update('categories', $data, $where);
        }
        else
        {
            //Insert as new category
            $data = array(
               'created'    => time(),
               'updated'    => time(),
               'project_id' => $this->getProjectId(),
               'name'       => $category->getName(),
               'state'      => $category->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $db->insert('categories', $data);
        }
    }
    
    public function toArray()
    {
        $data = array(
           'id'         => $this->getProjectId(),
           'created'    => $this->getCreated(),
           'updated'    => $this->getUpdated(),
           'name'       => (strlen($this->getName()) > 0) ? $this->getName() : '[No Name]',
           'slug'       => $this->getSlug(),
           'categories' => array(),
           'state'      => $this->getState(),
        );
        
        if (is_array($this->_categories) && count($this->_categories) > 0)
        {
            foreach ($this->_categories as $category)
            {
                $data['categories'][] = $category->toArray();
            }
        }
        
        return $data;
    }
}
