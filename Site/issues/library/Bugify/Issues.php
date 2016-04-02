<?php

class Bugify_Issues {
    private $_pagination_limit = 15;
    private $_pagination_page  = 1;
    private $_pagination_total = 0;
    
    public function __construct() {}
    
    public function getPriorities() {
        $priorities = array(
           Bugify_Issue::PRIORITY_LOW    => 'Low',
           Bugify_Issue::PRIORITY_NORMAL => 'Normal',
           Bugify_Issue::PRIORITY_HIGH   => 'High',
        );
        
        return $priorities;
    }
    
    public function getStates() {
        $states = array(
           Bugify_Issue::STATE_OPEN        => 'Open',
           Bugify_Issue::STATE_IN_PROGRESS => 'In Progress',
           Bugify_Issue::STATE_RESOLVED    => 'Resolved',
           Bugify_Issue::STATE_CLOSED      => 'Closed',
           Bugify_Issue::STATE_REOPENED    => 'Reopened',
        );
        
        return $states;
    }
    
    public function getOpenStates() {
        $states = array(
           Bugify_Issue::STATE_OPEN,
           Bugify_Issue::STATE_IN_PROGRESS,
           Bugify_Issue::STATE_REOPENED,
        );
        
        return $states;
    }
    
    public function getClosedStates() {
        $states = array(
           Bugify_Issue::STATE_RESOLVED,
           Bugify_Issue::STATE_CLOSED,
        );
        
        return $states;
    }
    
    public function getAllStates() {
        $states = array(
           Bugify_Issue::STATE_OPEN,
           Bugify_Issue::STATE_IN_PROGRESS,
           Bugify_Issue::STATE_REOPENED,
           Bugify_Issue::STATE_RESOLVED,
           Bugify_Issue::STATE_CLOSED,
        );
        
        return $states;
    }
    
    public function getPaginationLimit() {
        return $this->_pagination_limit;
    }
    
    public function getPaginationPage() {
        return $this->_pagination_page;
    }
    
    public function getTotal() {
        return $this->_pagination_total;
    }
    
    public function getTotalPages() {
        return ($this->_pagination_total > 0) ? ceil($this->_pagination_total / $this->_pagination_limit) : 1;
    }
    
    public function setPaginationLimit($val) {
        $this->_pagination_limit = (int)$val;
        
        return $this;
    }
    
    public function setPaginationPage($val) {
        $this->_pagination_page = (int)$val;
        
        return $this;
    }
    
    public function filter() {
        return new Bugify_Filter();
    }
    
    private function _fetchAll(Bugify_Filter $filter=null, $onlyGetTotal=false) {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('issues')
          ->order('priority DESC');
        
        if ($filter !== null) {
            if ($filter instanceof Bugify_Filter) {
                if (count($filter->getAssigneeIds()) > 0) {
                    $s->where('assignee_id IN (?)', $filter->getAssigneeIds());
                }
                
                if (count($filter->getProjectIds()) > 0) {
                    $s->where('project_id IN (?)', $filter->getProjectIds());
                }
                
                if (count($filter->getCategoryIds()) > 0) {
                    $s->where('category_id IN (?)', $filter->getCategoryIds());
                }
                
                if (count($filter->getMilestoneIds()) > 0) {
                    $s->where('milestone_id IN (?)', $filter->getMilestoneIds());
                }
                
                if (count($filter->getIssueIds()) > 0) {
                    $s->where('id IN (?)', $filter->getIssueIds());
                }
                
                if (count($filter->getPriorities()) > 0) {
                    $s->where('priority IN (?)', $filter->getPriorities());
                }
                
                if (count($filter->getStates()) > 0) {
                    $s->where('state IN (?)', $filter->getStates());
                }
                
                if (strlen($filter->getCreatedFrom()) > 0) {
                    $s->where('created >= ?', strtotime($filter->getCreatedFrom()));
                }
                
                if (strlen($filter->getCreatedTo()) > 0) {
                    $s->where('created <= ?', strtotime($filter->getCreatedTo()));
                }
                
                if (strlen($filter->getResolvedFrom()) > 0) {
                    $s->where('resolved >= ?', strtotime($filter->getResolvedFrom()));
                }
                
                if (strlen($filter->getResolvedTo()) > 0) {
                    $s->where('resolved <= ?', strtotime($filter->getResolvedTo()));
                }
            } else {
                throw new Bugify_Exception('Filter must be an instance of Bugify_Filter');
            }
        } else {
            $s->where('state IN (?)', $this->getOpenStates());
        }
        
        //Use Zend Pagination to fetch the results
        $adapter = new Zend_Paginator_Adapter_DbSelect($s);
        
        $p = new Zend_Paginator($adapter);
        $p->setItemCountPerPage($this->getPaginationLimit());
        $p->setCurrentPageNumber($this->getPaginationPage());
        
        //Save the total
        $this->_pagination_total = $p->getTotalItemCount();
        
        //Prepare the issues array
        $issues = array();
        
        if ($onlyGetTotal === false) {
            //Fetch the data
            $result = (array)$p->getCurrentItems();
            
            if (is_array($result) && count($result) > 0) {
                foreach ($result as $key => $val) {
                    //Load into object
                    $i = new Bugify_Issue();
                    $i->setIssueId($val['id'])
                      ->setProjectId($val['project_id'])
                      ->setCategoryId($val['category_id'])
                      ->setMilestoneId($val['milestone_id'])
                      ->setCreatorId($val['creator_id'])
                      ->setAssigneeId($val['assignee_id'])
                      ->setCreated($val['created'])
                      ->setUpdated($val['updated'])
                      ->setResolved($val['resolved'])
                      ->setSubject($val['subject'])
                      ->setDescription($val['description'])
                      ->setRawRelatedIssueIds($val['related_issues'])
                      ->setPriority($val['priority'])
                      ->setPercentage($val['percentage'])
                      ->setState($val['state']);
                    
                    $issues[] = $i;
                }
            }
        }
        
        return $issues;
    }
    
    public function fetchAll(Bugify_Filter $filter=null) {
        return $this->_fetchAll($filter);
    }
    
    public function fetchTotal(Bugify_Filter $filter=null) {
        $this->_fetchAll($filter, true);
        
        return $this->getTotal();
    }
    
    /**
     * Fetch the specified issue from the database
     * 
     * @return Bugify_Issue
     */
    public function fetch($id) {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('issues')
          ->where('id = ?', $id)
          ->limit(1);
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0) {
            $result = current($result);
            
            //Load into object
            $m = new Bugify_Issue();
            $m->setIssueId($result['id'])
              ->setProjectId($result['project_id'])
              ->setCategoryId($result['category_id'])
              ->setMilestoneId($result['milestone_id'])
              ->setCreatorId($result['creator_id'])
              ->setAssigneeId($result['assignee_id'])
              ->setCreated($result['created'])
              ->setUpdated($result['updated'])
              ->setResolved($result['resolved'])
              ->setSubject($result['subject'])
              ->setDescription($result['description'])
              ->setRawRelatedIssueIds($result['related_issues'])
              ->setPriority($result['priority'])
              ->setPercentage($result['percentage'])
              ->setState($result['state']);
            
            return $m;
        } else {
            throw new Bugify_Exception('The specified issue does not exist.', 404);
        }
    }
    
    public function fetchIssueCount() {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('issues', array('COUNT(id)'))
          ->where('state IN (?)', $this->getOpenStates())
          ->limit(1);
        
        $result = $db->fetchAll($s);
        $count  = 0;
        
        if (is_array($result) && count($result) > 0) {
            $result = current($result);
            $count  = $result['COUNT(id)'];
        }
        
        return $count;
    }
    
    public function fetchIssueCountWithStates($states=array()) {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('issues', array('COUNT(id)'))
          ->where('state IN (?)', $states)
          ->limit(1);
        
        $result = $db->fetchAll($s);
        $count  = 0;
        
        if (is_array($result) && count($result) > 0) {
            $result = current($result);
            $count  = $result['COUNT(id)'];
        }
        
        return $count;
    }
    
    public function fetchIssueCountAssigned() {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('issues', array('COUNT(id)'))
          ->where('state IN (?)', $this->getOpenStates())
          ->where('assignee_id > ?', 0)
          ->limit(1);
        
        $result = $db->fetchAll($s);
        $count  = 0;
        
        if (is_array($result) && count($result) > 0) {
            $result = current($result);
            $count  = $result['COUNT(id)'];
        }
        
        return $count;
    }
    
    public function fetchIssueCountForUser(Bugify_User $user) {
        if (!$user instanceof Bugify_User) {
            throw new Bugify_Exception('The object must be an instance of Bugify_User.');
        }
        
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('issues', array('COUNT(id)'))
          ->where('state IN (?)', $this->getOpenStates())
          ->where('assignee_id = ?', $user->getUserId())
          ->limit(1);
        
        $result = $db->fetchAll($s);
        $count  = 0;
        
        if (is_array($result) && count($result) > 0) {
            $result = current($result);
            $count  = $result['COUNT(id)'];
        }
        
        return $count;
    }
    
    public function fetchFollowCountForUser(Bugify_User $user) {
        if (!$user instanceof Bugify_User) {
            throw new Bugify_Exception('The object must be an instance of Bugify_User.');
        }
        
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('followers', array('COUNT(id)'))
          ->where('user_id = ?', $user->getUserId())
          ->limit(1);
        
        $result = $db->fetchAll($s);
        $count  = 0;
        
        if (is_array($result) && count($result) > 0) {
            $result = current($result);
            $count  = $result['COUNT(id)'];
        }
        
        return $count;
    }
    
    public function fetchFollowsByUser(Bugify_User $user)
    {
        if (!$user instanceof Bugify_User)
        {
            throw new Bugify_Exception('The object must be an instance of Bugify_User.');
        }
        
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('followers')
          ->where('user_id = ?', $user->getUserId());
        
        $result  = $db->fetchAll($s);
        $follows = array();
        
        if (is_array($result) && count($result) > 0) {
            foreach ($result as $key => $val) {
                //Load into object
                $f = new Bugify_Issue_Follower();
                $f->setFollowerId($val['id'])
                  ->setIssueId($val['issue_id'])
                  ->setUserId($val['user_id']);
                
                $follows[] = $f;
            }
        }
        
        return $follows;
    }
    
    public function fetchHistory($since='-24 hours') {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('history')
          ->where('created >= ?', strtotime($since))
          ->order('id DESC');
        
        //Use Zend Pagination to fetch the results
        $adapter = new Zend_Paginator_Adapter_DbSelect($s);
        
        $p = new Zend_Paginator($adapter);
        $p->setItemCountPerPage($this->getPaginationLimit());
        $p->setCurrentPageNumber($this->getPaginationPage());
        
        $result = (array)$p->getCurrentItems();
        
        //Save the total
        $this->_pagination_total = $p->getTotalItemCount();
        
        $history = array();
        
        if (is_array($result) && count($result) > 0) {
            /**
             * We load history data a little differently.
             * First, get the history_id's, then use them to do
             * a single query to load the changes for those history items.
             */
            $history_ids = array();
            
            foreach ($result as $key => $val) {
                $history_ids[] = $val['id'];
            }
            
            //Now fetch all the changes for these history items
            $s = $db->select();
            $s->from('history_changes')
              ->where('history_id IN (?)', $history_ids);
            
            $changes_result = $db->fetchAll($s);
            
            if (is_array($changes_result) && count($changes_result) > 0) {
                /**
                 * Now, we rearrange the changes to group them by history_id.
                 * This is just to make it quicker to process the full history details
                 * in the next step.
                 */
                $changes = array();
                
                foreach ($changes_result as $key => $val) {
                    $changes[$val['history_id']][] = $val;
                }
                
                unset($changes_result);
            }
            
            /**
             * Now go through the history data again, but this time,
             * attach the changes.
             */
            foreach ($result as $key => $val) {
                //Load into object
                $h = new Bugify_Issue_History();
                $h->setHistoryId($val['id'])
                  ->setIssueId($val['issue_id'])
                  ->setUserId($val['user_id'])
                  ->setCreated($val['created']);
                
                //Now add the changes to this history item
                if (array_key_exists($val['id'], $changes)) {
                    foreach ($changes[$val['id']] as $k => $v) {
                        //Load into an object
                        $c = new Bugify_Issue_History_Change();
                        $c->setChangeId($v['id'])
                          ->setHistoryId($val['id'])
                          ->setType($v['type'])
                          ->setOriginal($v['original'])
                          ->setNew($v['new']);
                        
                        //Add this change to the history object
                        $h->addChange($c);
                    }
                }
                
                $history[] = $h;
            }
        }
        
        return $history;
    }
    
    private function _generateHistory(Bugify_Issue $issue, Bugify_User $user=null) {
        if (!$issue instanceof Bugify_Issue) {
            throw new Bugify_Exception('The object must be an instance of Bugify_Issue.');
        }
        
        if ($user !== null) {
            if (!$user instanceof Bugify_User) {
                throw new Bugify_Exception('The object must be an instance of Bugify_User.');
            }
            
            $user_id = $user->getUserId();
        } else {
            //We dont know which user made this change
            $user_id = 0;
        }
        
        //Load the original version from the db so we can find whats different
        $original = $this->fetch($issue->getIssueId());
        
        //Create a new history object (we may or may not save it)
        $h = new Bugify_Issue_History();
        $h->setIssueId($issue->getIssueId())
          ->setUserId($user_id);
        
        //Check subject
        if ($original->getSubject() != $issue->getSubject()) {
            $c = new Bugify_Issue_History_Change();
            $c->setType(Bugify_Issue_History_Change::TYPE_SUBJECT)
              ->setOriginal($original->getSubject())
              ->setNew($issue->getSubject());
            
            $h->addChange($c);
        }
        
        //Check description
        if ($original->getDescription() != $issue->getDescription()) {
            $c = new Bugify_Issue_History_Change();
            $c->setType(Bugify_Issue_History_Change::TYPE_DESCRIPTION)
              ->setOriginal($original->getDescription())
              ->setNew($issue->getDescription());
            
            $h->addChange($c);
        }
        
        //Check priority
        if ($original->getPriority() != $issue->getPriority())
        {
            $c = new Bugify_Issue_History_Change();
            $c->setType(Bugify_Issue_History_Change::TYPE_PRIORITY)
              ->setOriginal($original->getPriority())
              ->setNew($issue->getPriority());
            
            $h->addChange($c);
        }
        
        //Check percentage
        if ($original->getPercentage() != $issue->getPercentage())
        {
            $c = new Bugify_Issue_History_Change();
            $c->setType(Bugify_Issue_History_Change::TYPE_PERCENTAGE)
              ->setOriginal($original->getPercentage())
              ->setNew($issue->getPercentage());
            
            $h->addChange($c);
        }
        
        //Check project
        if ($original->getProjectId() != $issue->getProjectId()) {
            $c = new Bugify_Issue_History_Change();
            $c->setType(Bugify_Issue_History_Change::TYPE_PROJECT)
              ->setOriginal($original->getProjectId())
              ->setNew($issue->getProjectId());
            
            $h->addChange($c);
        }
        
        //Check category
        if ($original->getCategoryId() != $issue->getCategoryId()) {
            $c = new Bugify_Issue_History_Change();
            $c->setType(Bugify_Issue_History_Change::TYPE_CATEGORY)
              ->setOriginal($original->getCategoryId())
              ->setNew($issue->getCategoryId());
            
            $h->addChange($c);
        }
        
        //Check state
        if ($original->getState() != $issue->getState()) {
            $c = new Bugify_Issue_History_Change();
            $c->setType(Bugify_Issue_History_Change::TYPE_STATE)
              ->setOriginal($original->getState())
              ->setNew($issue->getState());
            
            $h->addChange($c);
        }
        
        //Check assignee
        if ($original->getAssigneeId() != $issue->getAssigneeId()) {
            $c = new Bugify_Issue_History_Change();
            $c->setType(Bugify_Issue_History_Change::TYPE_ASSIGNEE)
              ->setOriginal($original->getAssigneeId())
              ->setNew($issue->getAssigneeId());
            
            $h->addChange($c);
        }
        
        //Check milestone
        if ($original->getMilestoneId() != $issue->getMilestoneId())
        {
            $c = new Bugify_Issue_History_Change();
            $c->setType(Bugify_Issue_History_Change::TYPE_MILESTONE)
              ->setOriginal($original->getMilestoneId())
              ->setNew($issue->getMilestoneId());
            
            $h->addChange($c);
        }
        
        //Check resolved date
        if ($original->getResolved() != $issue->getResolved()) {
            $c = new Bugify_Issue_History_Change();
            $c->setType(Bugify_Issue_History_Change::TYPE_DATE_RESOLVED)
              ->setOriginal($original->getResolved())
              ->setNew($issue->getResolved());
            
            $h->addChange($c);
        }
        
        if (count($h->getChanges()) > 0) {
            //Save the history item
            $h->setHistoryId($issue->saveHistory($h));
        }
        
        return $h;
    }
    
    public function save(Bugify_Issue $issue, Bugify_User $user=null) {
        if (!$issue instanceof Bugify_Issue) {
            throw new Bugify_Exception('The object must be an instance of Bugify_Issue.');
        }
        
        if ($user !== null) {
            if (!$user instanceof Bugify_User) {
                throw new Bugify_Exception('The object must be an instance of Bugify_User.');
            }
        }
        
        if ($issue->getIssueId() > 0) {
            //Check what has changed so we can log the history
            $history = $this->_generateHistory($issue, $user);
            
            //Update the database
            $data = array(
               'updated'        => time(),
               'resolved'       => Bugify_Date::getTimestamp($issue->getResolved()),
               'project_id'     => $issue->getProjectId(),
               'category_id'    => $issue->getCategoryId(),
               'milestone_id'   => $issue->getMilestoneId(),
               'creator_id'     => $issue->getCreatorId(),
               'assignee_id'    => $issue->getAssigneeId(),
               'subject'        => $issue->getSubject(),
               'description'    => $issue->getDescription(),
               'related_issues' => $issue->getRelatedIssueIds(false),
               'priority'       => $issue->getPriority(),
               'percentage'     => $issue->getPercentage(),
               'state'          => $issue->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $where   = array();
            $where[] = $db->quoteInto('id = ?', $issue->getIssueId());
            
            $db->update('issues', $data, $where);
            
            if (count($history->getChanges()) > 0) {
                //Update issue relationships
                $this->_queueUpdateRelationships($issue);
                
                //Notify users of the update
                $this->_queueNotifyUsers($issue, $history);
            }
        } else {
            //Insert as new issue
            $data = array(
               'created'        => time(),
               'updated'        => time(),
               'resolved'       => Bugify_Date::getTimestamp($issue->getResolved()),
               'project_id'     => $issue->getProjectId(),
               'category_id'    => $issue->getCategoryId(),
               'milestone_id'   => $issue->getMilestoneId(),
               'creator_id'     => $issue->getCreatorId(),
               'assignee_id'    => $issue->getAssigneeId(),
               'subject'        => $issue->getSubject(),
               'description'    => $issue->getDescription(),
               'related_issues' => $issue->getRelatedIssueIds(false),
               'priority'       => $issue->getPriority(),
               'percentage'     => $issue->getPercentage(),
               'state'          => $issue->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $db->insert('issues', $data);
            
            $issue_id = $db->lastInsertId();
            
            //Create a history item for this new issue
            $h = new Bugify_Issue_History();
            $h->setIssueId($issue_id)
              ->setUserId($issue->getCreatorId());
            
            $c = new Bugify_Issue_History_Change();
            $c->setType(Bugify_Issue_History_Change::TYPE_NEW_ISSUE)
              ->setNew($issue_id);
            
            $h->addChange($c);
            
            //Save the history item
            $issue->setIssueId($issue_id);
            $h->setHistoryId($issue->saveHistory($h));
            
            //Update issue relationships
            $this->_queueUpdateRelationships($issue);
            
            //Notify users of the update
            $this->_queueNotifyUsers($issue, $h);
            
            return $issue_id;
        }
    }
    
    private function _queueNotifyUsers(Bugify_Issue $issue, Bugify_Issue_History $history) {
        if (!$issue instanceof Bugify_Issue) {
            throw new Bugify_Exception('The object must be an instance of Bugify_Issue.');
        }
        
        if (!$history instanceof Bugify_Issue_History) {
            throw new Bugify_Exception('The object must be an instance of Bugify_Issue_History.');
        }
        
        //Add a job to notify the interested users
        $q = new Bugify_Queue();
        
        $job = new Bugify_Queue_Job();
        $job->setMethod('notifyUsers')
            ->setParams(array('issueId' => $issue->getIssueId(), 'historyId' => $history->getHistoryId()));
        
        $q->save($job);
        
        //Start the queue
        $q->start();
    }
    
    private function _queueUpdateRelationships(Bugify_Issue $issue) {
        /**
         * Add a job to parse the issue details and create relationships if it finds
         * links to other issues (e.g., #123 would link to issue 123)
         */
        if (!$issue instanceof Bugify_Issue) {
            throw new Bugify_Exception('The object must be an instance of Bugify_Issue.');
        }
        
        $q = new Bugify_Queue();
        
        $job = new Bugify_Queue_Job();
        $job->setMethod('updateIssueRelationships')
            ->setParams(array('issueId' => $issue->getIssueId()));
        
        $q->save($job);
        
        //Start the queue
        $q->start();
    }
}
