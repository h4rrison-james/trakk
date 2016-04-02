<?php

class Bugify_Issue_History_Change {
    private $_id          = 0;
    private $_history_id  = 0;
    private $_type        = 0;
    private $_original    = '';
    private $_new         = '';
    private $_description = ''; //Not stored in Db
    
    /**
     * Helper arrays.
     * These are used to store temporary data that can help speed
     * up the getDescription method.
     */
    private $_helperProjects   = array();
    private $_helperCategories = array();
    private $_helperUsers      = array();
    private $_helperMilestones = array();
    
    //Change types
    const TYPE_SUBJECT       = 0;
    const TYPE_DESCRIPTION   = 1;
    const TYPE_PRIORITY      = 2;
    const TYPE_PROJECT       = 3;
    const TYPE_CATEGORY      = 4;
    const TYPE_STATE         = 5;
    const TYPE_ASSIGNEE      = 6;
    const TYPE_MILESTONE     = 7;
    const TYPE_DATE_RESOLVED = 8;
    const TYPE_COMMENT       = 9;
    const TYPE_ATTACHMENT    = 10;
    const TYPE_EMAIL_IMPORT  = 11;  //Issue was imported from an email
    const TYPE_NEW_ISSUE     = 12;
    const TYPE_PERCENTAGE    = 13;
    
    public function __construct() {}
    
    public function getTypes() {
        $types = array(
           self::TYPE_SUBJECT,
           self::TYPE_DESCRIPTION,
           self::TYPE_PRIORITY,
           self::TYPE_PROJECT,
           self::TYPE_CATEGORY,
           self::TYPE_STATE,
           self::TYPE_ASSIGNEE,
           self::TYPE_MILESTONE,
           self::TYPE_DATE_RESOLVED,
           self::TYPE_COMMENT,
           self::TYPE_ATTACHMENT,
           self::TYPE_EMAIL_IMPORT,
           self::TYPE_NEW_ISSUE,
           self::TYPE_PERCENTAGE,
        );
        
        return $types;
    }
    
    public function getChangeId() {
        return $this->_id;
    }
    
    public function getHistoryId() {
        return $this->_history_id;
    }
    
    public function getType() {
        return $this->_type;
    }
    
    public function getOriginal() {
        return $this->_original;
    }
    
    public function getNew() {
        return $this->_new;
    }
    
    public function getDescription() {
        /**
         * This method works out a string representation of the change.
         * It is not stored in the db in this format.
         */
        if (strlen($this->_description) == 0) {
            //Generate the description
            $description = '';
            
            switch ($this->getType()) {
                case self::TYPE_SUBJECT:
                    $description .= sprintf('Changed subject from "%s" to "%s"', $this->getOriginal(), $this->getNew());
                    break;
                case self::TYPE_DESCRIPTION:
                    $description .= sprintf('Changed description from "%s" to "%s"', $this->getOriginal(), $this->getNew());
                    break;
                case self::TYPE_PRIORITY:
                    //Get priority names
                    $orig_priority = $this->_getPriorityName($this->getOriginal());
                    $new_priority  = $this->_getPriorityName($this->getNew());
                    
                    $description .= sprintf('Changed priority from "%s" to "%s"', $orig_priority, $new_priority);
                    break;
                case self::TYPE_PERCENTAGE:
                    $description .= sprintf('Percent complete changed from "%s" to "%s"', $this->getOriginal(), $this->getNew());
                    break;
                case self::TYPE_PROJECT:
                    //Get project names
                    $orig_project = $this->_getProjectName($this->getOriginal());
                    $new_project  = $this->_getProjectName($this->getNew());
                    
                    $description .= sprintf('Changed project from "%s" to "%s"', $orig_project, $new_project);
                    break;
                case self::TYPE_CATEGORY:
                    //Get category names
                    $orig_category = $this->_getCategoryName($this->getOriginal());
                    $new_category  = $this->_getCategoryName($this->getNew());
                    
                    $description .= sprintf('Changed category from "%s" to "%s"', $orig_category, $new_category);
                    break;
                case self::TYPE_STATE:
                    //Get state names
                    $orig_state = $this->_getStateName($this->getOriginal());
                    $new_state  = $this->_getStateName($this->getNew());
                    
                    $description .= sprintf('Changed status from "%s" to "%s"', $orig_state, $new_state);
                    break;
                case self::TYPE_ASSIGNEE:
                    //Get users name
                    $orig_user = $this->_getUsersName($this->getOriginal());
                    $new_user  = $this->_getUsersName($this->getNew());
                    
                    $description .= sprintf('Changed assignee from "%s" to "%s"', $orig_user, $new_user);
                    break;
                case self::TYPE_MILESTONE:
                    //Get milestone name
                    $orig_milestone = $this->_getMilestoneName($this->getOriginal());
                    $new_milestone  = $this->_getMilestoneName($this->getNew());
                    
                    $description .= sprintf('Changed milestone from "%s" to "%s"', $orig_milestone, $new_milestone);
                    break;
                case self::TYPE_DATE_RESOLVED:
                    if (strtotime($this->getNew()) > 0) {
                        //Marked as resolved
                        $description .= 'Issue marked as resolved';
                    } else {
                        //Unmarked as resolved
                        $description .= 'Issue marked as un-resolved';
                    }
                    
                    break;
                case self::TYPE_COMMENT:
                    if (strlen($this->getOriginal()) > 0) {
                        $description .= sprintf('Changed comment from: %s to: %s', $this->getOriginal(), $this->getNew());
                    } else {
                        $description .= sprintf('Added new comment: %s', $this->getNew());
                    }
                    break;
                case self::TYPE_ATTACHMENT:
                    if (strlen($this->getOriginal()) > 0) {
                        $description .= sprintf('Changed attachment name from "%s" to "%s"', $this->getOriginal(), $this->getNew());
                    } else {
                        $description .= sprintf('Added new attachment "%s"', $this->getNew());
                    }
                    break;
                case self::TYPE_EMAIL_IMPORT:
                    $description .= 'Issue imported from email';
                    break;
                case self::TYPE_NEW_ISSUE:
                    $description .= 'New issue added';
                    break;
                default:
                    $description .= 'Unknown change';
            }
            
            $this->_description = $description;
        }
        
        return $this->_description;
    }
    
    /**
     * The following _get methods are helpers for the getDescription method above.
     */
    
    private function _getPriorityName($priority) {
        $i = new Bugify_Issues();
        $priorities = $i->getPriorities();
        $name       = 'Unknown';
        
        if (array_key_exists($priority, $priorities)) {
            $name = $priorities[$priority];
        }
        
        return $name;
    }
    
    private function _getProjectName($project_id) {
        if (count($this->_helperProjects) == 0) {
            //Load the projects
            $p = new Bugify_Projects();
            $result = $p->fetchAll();
            
            foreach ($result as $project) {
                $this->_helperProjects[$project->getProjectId()] = $project->toArray();
            }
        }
        
        if ($project_id > 0) {
            $name = 'Unknown';
            
            if (array_key_exists($project_id, $this->_helperProjects)) {
                $name = $this->_helperProjects[$project_id]['name'];
            }
        } else {
            $name = 'None';
        }
        
        return $name;
    }
    
    private function _getCategoryName($category_id) {
        if (count($this->_helperCategories) == 0) {
            //Load the categories
            $c = new Bugify_Categories();
            $result = $c->fetchAll();
            
            foreach ($result as $category) {
                $this->_helperCategories[$category->getCategoryId()] = $category->toArray();
            }
        }
        
        if ($category_id > 0) {
            $name = 'Unknown';
            
            if (array_key_exists($category_id, $this->_helperCategories)) {
                $name = $this->_helperCategories[$category_id]['name'];
            }
        } else {
            $name = 'None';
        }
        
        return $name;
    }
    
    private function _getStateName($state) {
        $i = new Bugify_Issues();
        $states = $i->getStates();
        $name   = 'Unknown';
        
        if (array_key_exists($state, $states)) {
            $name = $states[$state];
        }
        
        return $name;
    }
    
    private function _getUsersName($user_id) {
        if (count($this->_helperUsers) == 0) {
            //Load the users
            $u = new Bugify_Users();
            $result = $u->fetchAll();
            
            foreach ($result as $user) {
                $this->_helperUsers[$user->getUserId()] = $user->toArray();
            }
        }
        
        if ($user_id > 0) {
            $name = 'Unknown';
            
            if (array_key_exists($user_id, $this->_helperUsers)) {
                $user = $this->_helperUsers[$user_id];
                $name = $user['name'];
            }
        } else {
            $name = 'Nobody';
        }
        
        return $name;
    }
    
    private function _getMilestoneName($milestone_id) {
        if (count($this->_helperMilestones) == 0) {
            //Load the milestones
            $m = new Bugify_Milestones();
            $result = $m->fetchAll();
            
            foreach ($result as $milestone) {
                $this->_helperMilestones[$milestone->getMilestoneId()] = $milestone->toArray();
            }
        }
        
        if ($milestone_id > 0) {
            $name = 'Unknown';
            
            if (array_key_exists($milestone_id, $this->_helperMilestones)) {
                $name = $this->_helperMilestones[$milestone_id]['name'];
            }
        } else {
            $name = 'None';
        }
        
        return $name;
    }
    
    
    public function setChangeId($val) {
        $this->_id = $val;
        
        return $this;
    }
    
    public function setHistoryId($val) {
        $this->_history_id = $val;
        
        return $this;
    }
    
    public function setType($val) {
        $valid_types = $this->getTypes();
        
        if (in_array($val, $valid_types)) {
            $this->_type = $val;
        } else {
            throw new Bugify_Exception('Invalid change type.');
        }
        
        return $this;
    }
    
    public function setOriginal($val) {
        $this->_original = $val;
        
        return $this;
    }
    
    public function setNew($val) {
        $this->_new = $val;
        
        return $this;
    }
    
    public function toArray() {
        $data = array(
           'id'          => $this->getChangeId(),
           'type'        => $this->getType(),
           'original'    => $this->getOriginal(),
           'new'         => $this->getNew(),
           'description' => $this->getDescription(),
        );
        
        return $data;
    }
}
