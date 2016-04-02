<?php

class Bugify_Issue
{
    private $_id              = 0;
    private $_project_id      = 0;
    private $_category_id     = 0;
    private $_milestone_id    = 0;
    private $_creator_id      = 0;
    private $_assignee_id     = 0;
    private $_created         = 0;
    private $_updated         = 0;
    private $_resolved        = 0;
    private $_subject         = '';
    private $_description     = '';
    private $_relatedIssueIds = '';
    private $_priority        = self::PRIORITY_NORMAL;
    private $_percentage      = 0;
    private $_state           = self::STATE_OPEN;
    
    //Attachments, comments, followers and history
    private $_comments    = array();
    private $_attachments = array();
    private $_followers   = array();
    private $_history     = array();
    
    const PRIORITY_LOW    = 0;
    const PRIORITY_NORMAL = 1;
    const PRIORITY_HIGH   = 2;
    
    const STATE_OPEN        = 0;
    const STATE_IN_PROGRESS = 1;
    const STATE_RESOLVED    = 2;
    const STATE_CLOSED      = 3;
    const STATE_REOPENED    = 4;
    
    public function __construct()
    {}
    
    private function _serialize($array) {
        if (is_null($array)) {
            $array = array();
        }
        
        if (!is_array($array)) {
            throw new Exception('Can only serialize an array.');
        }
        
        return json_encode($array);
    }
    
    private function _deserialize($string) {
        if (is_string($string) && strlen($string) > 0) {
            return json_decode($string, true);
        } elseif (is_null($string)) {
            return array();
        }
    }
    
    public function getIssueId() {
        return $this->_id;
    }
    
    public function getProjectId() {
        return $this->_project_id;
    }
    
    public function getCategoryId() {
        return $this->_category_id;
    }
    
    public function getMilestoneId() {
        return $this->_milestone_id;
    }
    
    public function getCreatorId() {
        return $this->_creator_id;
    }
    
    public function getAssigneeId() {
        return $this->_assignee_id;
    }
    
    public function getCreated() {
        return Bugify_Date::getLocalTime($this->_created);
    }
    
    public function getUpdated() {
        return Bugify_Date::getLocalTime($this->_updated);
    }
    
    public function getResolved() {
        return Bugify_Date::getLocalTime($this->_resolved);
    }
    
    public function getSubject() {
        return $this->_subject;
    }
    
    public function getDescription() {
        return $this->_description;
    }
    
    public function getRelatedIssueIds($deserialize=true) {
        if ($deserialize === true) {
            $data = $this->_deserialize($this->_relatedIssueIds);
        } else {
            $data = $this->_relatedIssueIds;
        }
        
        return $data;
    }
    
    public function addRelatedIssueId($issueId) {
        $relatedIssueIds = $this->getRelatedIssueIds();
        
        //Add issue to the list of related issues
        $relatedIssueIds[] = $issueId;
        
        //Make sure the list is unique
        $relatedIssueIds = array_unique($relatedIssueIds);
        
        $this->_relatedIssueIds = $this->_serialize($relatedIssueIds);
        
        return $this;
    }
    
    public function removeRelatedIssueId($issueId) {
        $relatedIssueIds = $this->getRelatedIssueIds();
        
        //Remove issue from the list of related issues
        foreach ($relatedIssueIds as $key => $val) {
            if ($val == $issueId) {
                unset($relatedIssueIds[$key]);
            }
        }
        
        //Make sure the list is unique
        $relatedIssueIds = array_unique($relatedIssueIds);
        
        $this->_relatedIssueIds = $this->_serialize($relatedIssueIds);
        
        return $this;
    }
    
    public function getPriority() {
        return $this->_priority;
    }
    
    public function getPercentage() {
        return $this->_percentage;
    }
    
    public function getState() {
        return $this->_state;
    }
    
    public function getAttachments() {
        if (count($this->_attachments) == 0) {
            $this->_fetchAttachments();
        }
        
        return $this->_attachments;
    }
    
    public function getComments() {
        if (count($this->_comments) == 0) {
            $this->_fetchComments();
        }
        
        return $this->_comments;
    }
    
    public function getFollowers() {
        if (count($this->_followers) == 0) {
            $this->_fetchFollowers();
        }
        
        return $this->_followers;
    }
    
    public function getHistory() {
        if (count($this->_history) == 0) {
            $this->_fetchHistory();
        }
        
        return $this->_history;
    }
    
    private function _fetchAttachments() {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('attachments')
          ->where('issue_id = ?', $this->getIssueId());
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0) {
            //Clear out existing attachments
            $this->_attachments = array();
            
            foreach ($result as $key => $val) {
                //Load into object
                $i = new Bugify_Issue_Attachment();
                $i->setAttachmentId($val['id'])
                  ->setIssueId($val['issue_id'])
                  ->setUserId($val['user_id'])
                  ->setCreated($val['created'])
                  ->setUpdated($val['updated'])
                  ->setName($val['name'])
                  ->setFilename($val['filename'])
                  ->setFilesize($val['filesize'])
                  ->setState($val['state']);
                
                $this->_attachments[] = $i;
            }
        }
    }
    
    private function _fetchComments() {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('comments')
          ->where('issue_id = ?', $this->getIssueId());
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0) {
            //Clear out existing comments
            $this->_comments = array();
            
            foreach ($result as $key => $val) {
                //Load into object
                $i = new Bugify_Issue_Comment();
                $i->setCommentId($val['id'])
                  ->setIssueId($val['issue_id'])
                  ->setUserId($val['user_id'])
                  ->setCreated($val['created'])
                  ->setUpdated($val['updated'])
                  ->setComment($val['comment'])
                  ->setState($val['state']);
                
                $this->_comments[] = $i;
            }
        }
    }
    
    private function _fetchFollowers() {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('followers')
          ->where('issue_id = ?', $this->getIssueId());
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0) {
            //Clear out existing followers
            $this->_followers = array();
            
            foreach ($result as $key => $val) {
                //Load into object
                $f = new Bugify_Issue_Follower();
                $f->setFollowerId($val['id'])
                  ->setIssueId($val['issue_id'])
                  ->setUserId($val['user_id']);
                
                $this->_followers[] = $f;
            }
        }
    }
    
    private function _fetchHistory() {
        $db = Bugify_Db::get();
        
        $s = $db->select();
        $s->from('history')
          ->where('issue_id = ?', $this->getIssueId());
        
        $result = $db->fetchAll($s);
        
        if (is_array($result) && count($result) > 0) {
            //Clear out existing history
            $this->_history = array();
            
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
                
                foreach ($changes_result as $key => $val)
                {
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
                
                $this->_history[] = $h;
            }
        }
    }
    
    public function setIssueId($val) {
        $this->_id = (int)$val;
        
        return $this;
    }
    
    public function setProjectId($val) {
        $this->_project_id = (int)$val;
        
        return $this;
    }
    
    public function setCategoryId($val) {
        $this->_category_id = (int)$val;
        
        return $this;
    }
    
    public function setMilestoneId($val) {
        $this->_milestone_id = (int)$val;
        
        return $this;
    }
    
    public function setCreatorId($val) {
        $this->_creator_id = (int)$val;
        
        return $this;
    }
    
    public function setAssigneeId($val) {
        $this->_assignee_id = (int)$val;
        
        return $this;
    }
    
    public function setCreated($val) {
        $this->_created = ((int)$val > 0) ? (int)$val : strtotime($val);
        
        return $this;
    }
    
    public function setUpdated($val) {
        $this->_updated = ((int)$val > 0) ? (int)$val : strtotime($val);
        
        return $this;
    }
    
    public function setResolved($val) {
        $this->_resolved = ((int)$val > 0) ? (int)$val : strtotime($val);
        
        return $this;
    }
    
    public function setSubject($val) {
        if (strlen($val) > 0) {
            $this->_subject = $val;
        } else {
            throw new Bugify_Exception('Please specify a subject.');
        }
        
        return $this;
    }
    
    public function setDescription($val) {
        $this->_description = $val;
        
        return $this;
    }
    
    public function setRawRelatedIssueIds($val) {
        $this->_relatedIssueIds = $val;
        
        return $this;
    }
    
    public function setSlug($val) {
        $this->_slug = $val;
        
        return $this;
    }
    
    public function setPriority($val) {
        $valid_priorities = array(
           self::PRIORITY_LOW,
           self::PRIORITY_NORMAL,
           self::PRIORITY_HIGH,
        );
        
        if (in_array($val, $valid_priorities)) {
            $this->_priority = $val;
        } else {
            throw new Bugify_Exception('Invalid priority.');
        }
        
        return $this;
    }
    
    public function setPercentage($val) {
        if (in_array($val, range(0, 100, 10))) {
            $this->_percentage = (int)$val;
        } else {
            throw new Bugify_Exception('Invalid percentage.');
        }
        
        return $this;
    }
    
    public function setState($val) {
        $i = new Bugify_Issues();
        
        if (in_array($val, $i->getAllStates())) {
            $this->_state = $val;
        } else {
            throw new Bugify_Exception('Invalid state.');
        }
        
        if (in_array($val, $i->getClosedStates())) {
            //Set the percent complete to 100
            $this->setPercentage(100);
            
            //Check if the resolved date should be set
            if (strtotime($this->getResolved()) == 0) {
                $this->setResolved(time());
            }
        } elseif (in_array($val, $i->getOpenStates())) {
            //Check if the resolved date should be reset
            if (strtotime($this->getResolved()) > 0) {
                $this->setResolved(0);
            }
        }
        
        return $this;
    }
    
    public function saveAttachment(Bugify_Issue_Attachment $attachment) {
        if (!$attachment instanceof Bugify_Issue_Attachment) {
            throw new Bugify_Exception('The object must be an instance of Bugify_Issue_Attachment.');
        }
        
        if ($attachment->getAttachmentId() > 0) {
            //Get a copy of the original attachment so we can check the changes
            $this->_fetchAttachments();
            
            //Get the attachment array
            $attachments = $this->getAttachments();
            
            foreach ($attachments as $a) {
                if ($a->getAttachmentId() == $attachment->getAttachmentId()) {
                    $original = $a;
                    break;
                }
            }
            
            //Update the database
            $data = array(
               'updated'  => time(),
               'name'     => $attachment->getName(),
               'filename' => $attachment->getFilename(),
               'filesize' => $attachment->getFilesize(),
               'state'    => $attachment->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $where   = array();
            $where[] = $db->quoteInto('id = ?', $attachment->getAttachmentId());
            
            $db->update('attachments', $data, $where);
            
            if (!isset($original)) {
                throw new Bugify_Exception('Could not find original attachment.');
            }
            
            if ($original->getName() != $attachment->getName()) {
                //Create a new history object
                $h = new Bugify_Issue_History();
                $h->setIssueId($this->getIssueId())
                  ->setUserId($attachment->getUserId());
                
                $c = new Bugify_Issue_History_Change();
                $c->setType(Bugify_Issue_History_Change::TYPE_ATTACHMENT)
                  ->setOriginal($original->getName())
                  ->setNew($attachment->getName());
                
                $h->addChange($c);
                
                //Save the history item
                $this->saveHistory($h);
            }
        } else {
            //Insert as new attachment
            $data = array(
               'created'  => time(),
               'updated'  => time(),
               'issue_id' => $this->getIssueId(),
               'user_id'  => $attachment->getUserId(),
               'name'     => $attachment->getName(),
               'filename' => $attachment->getFilename(),
               'filesize' => $attachment->getFilesize(),
               'state'    => $attachment->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $db->insert('attachments', $data);
            
            $attachment_id = $db->lastInsertId();
            
            //Create a new history object
            $h = new Bugify_Issue_History();
            $h->setIssueId($this->getIssueId())
              ->setUserId($attachment->getUserId());
            
            $c = new Bugify_Issue_History_Change();
            $c->setType(Bugify_Issue_History_Change::TYPE_ATTACHMENT)
              ->setNew($attachment->getName());
            
            $h->addChange($c);
            
            //Save the history item
            $this->saveHistory($h);
            
            return $attachment_id;
        }
    }
    
    public function saveComment(Bugify_Issue_Comment $comment) {
        if (!$comment instanceof Bugify_Issue_Comment) {
            throw new Bugify_Exception('The object must be an instance of Bugify_Issue_Comment.');
        }
        
        if ($comment->getCommentId() > 0) {
            //Get a copy of the original comment so we can check the changes
            $this->_fetchComments();
            
            //Get the comments array
            $comments = $this->getComments();
            
            foreach ($comments as $c) {
                if ($c->getCommentId() == $comment->getCommentId()) {
                    $original = $c;
                    break;
                }
            }
            
            //Update the database
            $data = array(
               'updated'  => time(),
               'comment'  => $comment->getComment(),
               'state'    => $comment->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $where   = array();
            $where[] = $db->quoteInto('id = ?', $comment->getCommentId());
            
            $db->update('comments', $data, $where);
            
            if (!isset($original)) {
                throw new Bugify_Exception('Could not find original comment.');
            }
            
            if ($original->getComment() != $comment->getComment()) {
                //Create a new history object
                $h = new Bugify_Issue_History();
                $h->setIssueId($this->getIssueId())
                  ->setUserId($comment->getUserId());
                
                $c = new Bugify_Issue_History_Change();
                $c->setType(Bugify_Issue_History_Change::TYPE_COMMENT)
                  ->setOriginal($original->getComment())
                  ->setNew($comment->getComment());
                
                $h->addChange($c);
                
                //Save the history item
                $h->setHistoryId($this->saveHistory($h));
                
                //Update the issue relationships
                $this->_queueUpdateRelationships();
                
                //Notify users of the update
                $this->_queueNotifyUsers($h);
            }
        } else {
            //Insert as new comment
            $data = array(
               'created'  => time(),
               'updated'  => time(),
               'issue_id' => $this->getIssueId(),
               'user_id'  => $comment->getUserId(),
               'comment'  => $comment->getComment(),
               'state'    => $comment->getState(),
            );
            
            $db = Bugify_Db::get();
            
            $db->insert('comments', $data);
            
            $comment_id = $db->lastInsertId();
            
            //Create a new history object
            $h = new Bugify_Issue_History();
            $h->setIssueId($this->getIssueId())
              ->setUserId($comment->getUserId());
            
            $c = new Bugify_Issue_History_Change();
            $c->setType(Bugify_Issue_History_Change::TYPE_COMMENT)
              ->setNew($comment->getComment());
            
            $h->addChange($c);
            
            //Save the history item
            $h->setHistoryId($this->saveHistory($h));
            
            //Update the issue relationships
            $this->_queueUpdateRelationships();
            
            //Notify users of the update
            $this->_queueNotifyUsers($h);
            
            return $comment_id;
        }
    }
    
    private function _queueNotifyUsers(Bugify_Issue_History $history) {
        if (!$history instanceof Bugify_Issue_History) {
            throw new Bugify_Exception('The object must be an instance of Bugify_Issue_History.');
        }
        
        //Add a job to notify the interested users
        $q = new Bugify_Queue();
        
        $job = new Bugify_Queue_Job();
        $job->setMethod('notifyUsers')
            ->setParams(array('issueId' => $this->getIssueId(), 'historyId' => $history->getHistoryId()));
        
        $q->save($job);
        
        //Start the queue
        $q->start();
    }
    
    private function _queueUpdateRelationships() {
        /**
         * Add a job to parse the issue details and create relationships if it finds
         * links to other issues (e.g., #123 would link to issue 123)
         */
        $q = new Bugify_Queue();
        
        $job = new Bugify_Queue_Job();
        $job->setMethod('updateIssueRelationships')
            ->setParams(array('issueId' => $this->getIssueId()));
        
        $q->save($job);
        
        //Start the queue
        $q->start();
    }
    
    public function saveFollower(Bugify_Issue_Follower $follower) {
        if (!$follower instanceof Bugify_Issue_Follower) {
            throw new Bugify_Exception('The object must be an instance of Bugify_Issue_Follower.');
        }
        
        if ($follower->getFollowerId() > 0) {
            //Update the database
            $data = array(
               'user_id' => $follower->getUserId(),
            );
            
            $db = Bugify_Db::get();
            
            $where   = array();
            $where[] = $db->quoteInto('id = ?', $follower->getFollowerId());
            
            $db->update('followers', $data, $where);
        } else {
            //Insert as new follower
            $data = array(
               'issue_id' => $this->getIssueId(),
               'user_id'  => $follower->getUserId(),
            );
            
            $db = Bugify_Db::get();
            
            $db->insert('followers', $data);
            
            return $db->lastInsertId();
        }
    }
    
    public function removeFollower(Bugify_Issue_Follower $follower) {
        if (!$follower instanceof Bugify_Issue_Follower) {
            throw new Bugify_Exception('The object must be an instance of Bugify_Issue_Follower.');
        }
        
        if ($follower->getFollowerId() > 0) {
            //Remove from the database
            $db = Bugify_Db::get();
            
            $db->delete('followers', $db->quoteInto('id = ?', $follower->getFollowerId()));
            
            //Reload the list of followers for this issue
            $this->getFollowers();
        } else {
            throw new Bugify_Exception('Invalid follower.');
        }
    }
    
    public function saveHistory(Bugify_Issue_History $history) {
        if (!$history instanceof Bugify_Issue_History) {
            throw new Bugify_Exception('The object must be an instance of Bugify_Issue_History.');
        }
        
        if ($history->getHistoryId() > 0) {
            //We do not currently support updating history
        } else {
            //Get the changes
            $changes = $history->getChanges();
            
            if (count($changes) > 0) {
                //Insert as new history item
                $data = array(
                   'created'  => time(),
                   'issue_id' => $this->getIssueId(),
                   'user_id'  => $history->getUserId(),
                );
                
                $db = Bugify_Db::get();
                
                $db->insert('history', $data);
                
                $history_id = $db->lastInsertId();
                
                //Save the changes
                foreach ($changes as $change) {
                    $data = array(
                       'history_id' => $history_id,
                       'type'       => $change->getType(),
                       'original'   => $change->getOriginal(),
                       'new'        => $change->getNew(),
                    );
                    
                    $db->insert('history_changes', $data);
                }
                
                return $history_id;
            } else {
                throw new Bugify_Exception('Cannot save history because there are no changes.');
            }
        }
    }
    
    public function updateRelatedIssues() {
        /**
         * Find the related issues based on the text in the subject, description,
         * and comments.  Then keep a list of issue id's.
         */
        $relatedIssues   = $this->_findRelatedIssues();
        $relatedIssueIds = $this->getRelatedIssueIds();
        
        if (is_array($relatedIssues) && count($relatedIssues) > 0) {
            foreach ($relatedIssues as $issue) {
                $relatedIssueIds[] = $issue->getIssueId();
            }
        }
        
        //Update the json string of related issue id's
        $this->_relatedIssueIds = $this->_serialize($relatedIssueIds);
    }
    
    private function _findRelatedIssues() {
        //Find links to other issues in the subject, description and comments
        //Prepare the list of possibly related issue id's
        $possiblyRelatedIssueIds = array();
        
        //Prepare list of related issues
        $relatedIssues = array();
        
        //Prepare the regex for basic links (e.g., #123)
        $regex   = '"\#(\d+)"';
        $matches = array();
        
        //Check in the subject
        $result = preg_match_all($regex, $this->getSubject(), $matches);
        
        if ($result > 0 && count($matches) > 0) {
            if (isset($matches[1]) && is_array($matches[1]) && count($matches[1]) > 0) {
                foreach ($matches[1] as $key => $val) {
                    $possiblyRelatedIssueIds[] = $val;
                }
            }
        }
        
        //Check in the description
        $result = preg_match_all($regex, $this->getDescription(), $matches);
        
        if ($result > 0 && count($matches) > 0) {
            if (isset($matches[1]) && is_array($matches[1]) && count($matches[1]) > 0) {
                foreach ($matches[1] as $key => $val) {
                    $possiblyRelatedIssueIds[] = $val;
                }
            }
        }
        
        //Load all the comments for this issue
        $comments = $this->getComments();
        
        if (is_array($comments) && count($comments) > 0) {
            foreach ($comments as $comment) {
                //Check in the comment
                $result = preg_match_all($regex, $comment->getComment(), $matches);
                
                if ($result > 0 && count($matches) > 0) {
                    if (isset($matches[1]) && is_array($matches[1]) && count($matches[1]) > 0) {
                        foreach ($matches[1] as $key => $val) {
                            $possiblyRelatedIssueIds[] = $val;
                        }
                    }
                }
            }
        }
        
        //Make sure this issue isnt related to itself
        if (count($possiblyRelatedIssueIds) > 0) {
            $issueId = $this->getIssueId();
            
            foreach ($possiblyRelatedIssueIds as $key => $val) {
                if ($val == $issueId) {
                    unset($possiblyRelatedIssueIds[$key]);
                }
            }
        }
        
        if (count($possiblyRelatedIssueIds) > 0) {
            //Now that we have a list of possibly related issues, make sure they are valid issues
            $i = new Bugify_Issues();
            
            $filter = $i->filter();
            $filter->setIssueIds($possiblyRelatedIssueIds)
                   ->setStates($i->getAllStates());
            
            //Now load all the issues that have these id's
            $relatedIssues = $i->fetchAll($filter);
        }
        
        return $relatedIssues;
    }
    
    public function toArray() {
        $data = array(
           'id'                => $this->getIssueId(),
           'project_id'        => $this->getProjectId(),
           'category_id'       => $this->getCategoryId(),
           'milestone_id'      => $this->getMilestoneId(),
           'creator_id'        => $this->getCreatorId(),
           'assignee_id'       => $this->getAssigneeId(),
           'created'           => $this->getCreated(),
           'updated'           => $this->getUpdated(),
           'resolved'          => $this->getResolved(),
           'subject'           => $this->getSubject(),
           'description'       => $this->getDescription(),
           'related_issue_ids' => $this->getRelatedIssueIds(),
           'priority'          => $this->getPriority(),
           'percentage'        => $this->getPercentage(),
           'comments'          => array(),
           'attachments'       => array(),
           'followers'         => array(),
           'history'           => array(),
           'state'             => $this->getState(),
        );
        
        if (is_array($this->_comments) && count($this->_comments) > 0) {
            foreach ($this->_comments as $comment) {
                $data['comments'][] = $comment->toArray();
            }
        }
        
        if (is_array($this->_attachments) && count($this->_attachments) > 0) {
            foreach ($this->_attachments as $attachment) {
                $data['attachments'][] = $attachment->toArray();
            }
        }
        
        if (is_array($this->_followers) && count($this->_followers) > 0) {
            foreach ($this->_followers as $follower) {
                $data['followers'][] = $follower->toArray();
            }
        }
        
        if (is_array($this->_history) && count($this->_history) > 0) {
            foreach ($this->_history as $history) {
                $data['history'][] = $history->toArray();
            }
        }
        
        return $data;
    }
}
