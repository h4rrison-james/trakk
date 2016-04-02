<?php

class Bugify_Filter
{
    private $_id            = 0;
    private $_user_id       = 0;
    private $_name          = '';
    private $_search        = '';
    private $_created_from  = ''; //ISO8601 format
    private $_created_to    = ''; //ISO8601 format
    private $_resolved_from = ''; //ISO8601 format
    private $_resolved_to   = ''; //ISO8601 format
    private $_assignee_ids  = array();
    private $_project_ids   = array();
    private $_category_ids  = array();
    private $_milestone_ids = array();
    private $_issue_ids     = array();
    private $_priorities    = array();
    private $_states        = array();
    
    public function __construct()
    {
        //Set the default states array (all open issues)
        $i = new Bugify_Issues();
        $this->setStates($i->getOpenStates());
        unset($i);
    }
    
    public function getFilterId()
    {
        return $this->_id;
    }
    
    public function getUserId()
    {
        return $this->_user_id;
    }
    
    public function getName()
    {
        return $this->_name;
    }
    
    public function getSearch()
    {
        return $this->_search;
    }
    
    public function getCreatedFrom()
    {
        return $this->_created_from;
    }
    
    public function getCreatedTo()
    {
        return $this->_created_to;
    }
    
    public function getResolvedFrom()
    {
        return $this->_resolved_from;
    }
    
    public function getResolvedTo()
    {
        return $this->_resolved_to;
    }
    
    public function getAssigneeIds()
    {
        return $this->_assignee_ids;
    }
    
    public function getProjectIds()
    {
        return $this->_project_ids;
    }
    
    public function getCategoryIds()
    {
        return $this->_category_ids;
    }
    
    public function getMilestoneIds()
    {
        return $this->_milestone_ids;
    }
    
    public function getIssueIds()
    {
        return $this->_issue_ids;
    }
    
    public function getPriorities()
    {
        return $this->_priorities;
    }
    
    public function getStates()
    {
        return $this->_states;
    }
    
    /**
     * This is used for persistence to the db.
     * The filter is stored as a json string.
     * This method converts the filter options to
     * a json string.
     */
    public function getJsonFilter()
    {
        /**
         * Only get the data that has actually been set.
         * There is no point in storing the filters that
         * are empty.
         * NOTE: We dont current persist the issue_ids filter
         * because they are usually filled in by the results
         * of the "search" string (using Lucene).
         * This will probably need to be changed in the future to
         * allow a param to specify whether or not the issue_ids
         * should be persisted.  Or maybe it should decide
         * automatically based on whether or not the "search" string
         * is being used (although its conceivable that we might
         * want a saved filter that has both a search string and a
         * specific set of issue_ids.
         */
        $all_data = $this->toArray(false);
        $data     = array();
        
        foreach ($all_data as $key => $val)
        {
            if ($key != 'issue_ids')
            {
                if (is_array($val) && count($val) > 0)
                {
                    $data[$key] = $val;
                }
                elseif ($key == 'search' && strlen($val) > 0)
                {
                    $data[$key] = $val;
                }
            }
        }
        
        return json_encode($data);
    }
    
    
    public function setFilterId($val)
    {
        $this->_id = $val;
        
        return $this;
    }
    
    public function setUserId($val)
    {
        $this->_user_id = (int)$val;
        
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
            throw new Bugify_Exception('Please specify a filter name.');
        }
        
        return $this;
    }
    
    public function setSearch($val)
    {
        $this->_search = $val;
        
        return $this;
    }
    
    public function setCreatedFrom($val)
    {
        //Expected format: ISO8601
        $this->_created_from = $val;
        
        return $this;
    }
    
    public function setCreatedTo($val)
    {
        //Expected format: ISO8601
        $this->_created_to = $val;
        
        return $this;
    }
    
    public function setResolvedFrom($val)
    {
        //Expected format: ISO8601
        $this->_resolved_from = $val;
        
        return $this;
    }
    
    public function setResolvedTo($val)
    {
        //Expected format: ISO8601
        $this->_resolved_to = $val;
        
        return $this;
    }
    
    public function setAssigneeIds($val)
    {
        if (is_array($val))
        {
            $this->_assignee_ids = $val;
        }
        else
        {
            throw new Bugify_Exception('Please specify an array of assignee ids.');
        }
        
        return $this;
    }
    
    public function setProjectIds($val)
    {
        if (is_array($val))
        {
            $this->_project_ids = $val;
        }
        else
        {
            throw new Bugify_Exception('Please specify an array of project ids.');
        }
        
        return $this;
    }
    
    public function setCategoryIds($val)
    {
        if (is_array($val))
        {
            $this->_category_ids = $val;
        }
        else
        {
            throw new Bugify_Exception('Please specify an array of category ids.');
        }
        
        return $this;
    }
    
    public function setMilestoneIds($val)
    {
        if (is_array($val))
        {
            $this->_milestone_ids = $val;
        }
        else
        {
            throw new Bugify_Exception('Please specify an array of milestone ids.');
        }
        
        return $this;
    }
    
    public function setIssueIds($val)
    {
        if (is_array($val))
        {
            $this->_issue_ids = $val;
        }
        else
        {
            throw new Bugify_Exception('Please specify an array of issue ids.');
        }
        
        return $this;
    }
    
    public function setPriorities($val)
    {
        if (is_array($val))
        {
            $this->_priorities = $val;
        }
        else
        {
            throw new Bugify_Exception('Please specify an array of priorities.');
        }
        
        return $this;
    }
    
    public function setStates($val)
    {
        if (is_array($val))
        {
            $this->_states = $val;
        }
        else
        {
            throw new Bugify_Exception('Please specify an array of states.');
        }
        
        return $this;
    }
    
    /**
     * This is used for persistence to the db.
     * The filter is stored as a json string.
     * This method fills in the filter options.
     */
    public function setJsonFilter($json)
    {
        $data = json_decode($json, true);
        
        if (is_array($data) && count($data) > 0)
        {
            foreach ($data as $key => $val)
            {
                switch ($key)
                {
                    case 'search':
                        $this->setSearch((string)$val);
                        break;
                    case 'assignee_ids':
                        $this->setAssigneeIds($val);
                        break;
                    case 'project_ids':
                        $this->setProjectIds($val);
                        break;
                    case 'category_ids':
                        $this->setCategoryIds($val);
                        break;
                    case 'milestone_ids':
                        $this->setMilestoneIds($val);
                        break;
                    case 'issue_ids':
                        $this->setIssueIds($val);
                        break;
                    case 'priorities':
                        $this->setPriorities($val);
                        break;
                    case 'states':
                        $this->setStates($val);
                        break;
                }
            }
        }
    }
    
    public function toArray($all_data=true)
    {
        $data = array(
           'search'        => $this->getSearch(),
           'created_from'  => $this->getCreatedFrom(),
           'created_to'    => $this->getCreatedTo(),
           'resolved_from' => $this->getResolvedFrom(),
           'resolved_to'   => $this->getResolvedTo(),
           'assignee_ids'  => $this->getAssigneeIds(),
           'project_ids'   => $this->getProjectIds(),
           'category_ids'  => $this->getCategoryIds(),
           'milestone_ids' => $this->getMilestoneIds(),
           'issue_ids'     => $this->getIssueIds(),
           'priorities'    => $this->getPriorities(),
           'states'        => $this->getStates(),
        );
        
        if ($all_data === true)
        {
            $data['id']      = $this->getFilterId();
            $data['user_id'] = $this->getUserId();
            $data['name']    = $this->getName();
        }
        
        return $data;
    }
}
