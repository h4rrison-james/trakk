<?php

class IndexController extends Ui_Controller_Action {
    public function init()
    {}
    
    public function indexAction() {
        try {
            $limit = 5;
            
            $totals = array(
                'open'       => 0,
                'closed'     => 0,
                'assigned'   => 0,
                'unassigned' => 0,
            );
            
            $assigned = array(
                'limit'  => 0,
                'total'  => 0,
                'issues' => array(),
            );
            
            $following = array(
                'limit'  => 0,
                'total'  => 0,
                'issues' => array(),
            );
            
            $history = array(
                'limit'   => 0,
                'total'   => 0,
                'history' => array(),
            );
            
            //Work out basic issue stats
            $i = new Bugify_Issues();
            
            $totals['open']       = $i->fetchIssueCountWithStates($i->getOpenStates());
            $totals['closed']     = $i->fetchIssueCountWithStates($i->getClosedStates());
            $totals['assigned']   = $i->fetchIssueCountAssigned();
            $totals['unassigned'] = ($totals['open']-$totals['assigned']);
            
            //Prepare the defaults
            $i->setPaginationLimit($limit);
            
            /**
             * Load the open issues assigned to this user.
             */
            $filter = $i->filter();
            $filter->setAssigneeIds(array($this->user->getUserId()));
            $result = $i->fetchAll($filter);
            
            //Process the issues (ie, attach extra info)
            $h = new Bugify_Helpers_Issues();
            $assigned['issues'] = $h->processIssues($result);
            
            //Get the pagination info
            $assigned['limit'] = $i->getPaginationLimit();
            $assigned['total'] = $i->getTotal();
            
            
            /**
             * Load the issues this user is watching.
             */
            $follows   = $i->fetchFollowsByUser($this->user);
            $issue_ids = array();
            
            if (count($follows) > 0) {
                foreach ($follows as $follower) {
                    $issue_ids[] = $follower->getIssueId();
                }
                
                //Set the filter options
                $filter = $i->filter();
                $filter->setStates($i->getAllStates())
                       ->setIssueIds($issue_ids);
                $result = $i->fetchAll($filter);
                $issues = array();
                
                //Process the issues (ie, attach extra info)
                $h = new Bugify_Helpers_Issues();
                $following['issues'] = $h->processIssues($result);
                
                //Get the pagination info
                $following['limit'] = $i->getPaginationLimit();
                $following['total'] = $i->getTotal();
            }
            
            
            /**
             * Load the recent history.
             */
            $result = $i->fetchHistory('-1 week');
            
            if (is_array($result) && count($result) > 0) {
                //Process the history (ie, attach extra info)
                $h = new Bugify_Helpers_History();
                $history['history'] = $h->attachFullInfo($result, true);
                
                //Get the pagination info
                $history['limit'] = $i->getPaginationLimit();
                $history['total'] = $i->getTotal();
            }
            
        } catch (Exception $e) {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->totals    = $totals;
        $this->view->assigned  = $assigned;
        $this->view->following = $following;
        $this->view->history   = $history;
    }
    
    public function jsGetIssuesInProgressAction() {
        try {
            $html = '';
            
            //Load the issue details
            $i = new Bugify_Issues();
            
            //Load the issues assigned to this user and marked as "in progress"
            $filter = $i->filter();
            $filter->setAssigneeIds(array($this->user->getUserId()))
                   ->setStates(array(Bugify_Issue::STATE_IN_PROGRESS));
            
            $result = $i->fetchAll($filter);
            $issues = array();
            
            if (is_array($result) && count($result) > 0) {
                $percent = 0;
                
                foreach ($result as $issue) {
                    $issues[] = $issue->toArray();
                    
                    //Keep track of the total percentage
                    $percent += $issue->getPercentage();
                }
                
                //Work out the percent complete
                $percent = ($percent / count($issues));
                
                $html .= $this->view->BarGraph($percent);
                
                //Convert issues to html table
                $html .= $this->view->ListIssuesSimple($issues);
            }
            
            $data = array(
               'status' => true,
               'issues' => $html,
            );
        } catch (Exception $e) {
            $data = array(
               'error'  => $e->getMessage(),
               'status' => false,
            );
        }
        
        $this->_helper->json($data);
    }
    
    public function jsGetMilestonesAction() {
        try {
            $html = '';
            
            //Load the list of milestones
            $m = new Bugify_Milestones();
            $result = $m->fetchAll();
            
            if (is_array($result) && count($result) > 0) {
                //Process the milestones (attach issue counts etc)
                $h = new Bugify_Helpers_Issues();
                $milestones = $h->addIssueCountsForMilestones($result);
                
                $i = new Bugify_Issues();
                
                if (is_array($milestones) && count($milestones) > 0) {
                    foreach ($milestones as $key => $val) {
                        //Get the number of closed issues for this milestone
                        $filter = $i->filter();
                        $filter->setMilestoneIds(array($val['id']));
                        $filter->setStates($i->getClosedStates());
                        $milestones[$key]['closed_count'] = $i->fetchTotal($filter);
                    }
                    
                    //Convert milestones to html table
                    $html = $this->view->ListMilestonesSimple($milestones);
                }
            }
            
            $data = array(
               'status'     => true,
               'milestones' => $html,
            );
        } catch (Exception $e) {
            $data = array(
               'error'  => $e->getMessage(),
               'status' => false,
            );
        }
        
        $this->_helper->json($data);
    }
}
