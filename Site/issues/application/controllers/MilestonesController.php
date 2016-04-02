<?php

class MilestonesController extends Ui_Controller_Action
{
    public function init()
    {}
    
    public function indexAction()
    {
        try
        {
            $milestones = array();
            
            if ($this->getRequest()->isPost())
            {
                $milestone = $this->_getParam('milestone');
                
                //Create a new milestone
                $new = new Bugify_Milestone();
                $new->setName($milestone['name'])
                    ->setDescription($milestone['description'])
                    ->setState(Bugify_Milestone::STATE_ACTIVE);
                
                if (isset($milestone['due']) && strlen($milestone['due']) > 0)
                {
                    $new->setDueDate(strtotime($milestone['due']));
                }
                
                //Save the milestone
                $m = new Bugify_Milestones();
                $m->save($new);
                
                //Clear milestones cache
                $this->cache->removeWithTags('Milestones');
                
                Ui_Messages::Add('ok', 'New milestone has been saved.');
                
                $this->_redirect('/milestones');
            }
            
            //Load the list of milestones
            $m = new Bugify_Milestones();
            $result = $m->fetchAll();
            
            //Process the milestones (attach issue counts etc)
            $h = new Bugify_Helpers_Issues();
            $milestones = $h->addIssueCountsForMilestones($result);
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->milestones = $milestones;
    }
    
    public function overviewAction()
    {
        try
        {
            $milestone   = array();
            $issues      = array();
            $page        = 1;
            $limit       = 0;
            $total       = 0;
            $closedCount = 0;
            
            $milestone_id = $this->_getParam('milestone_id');
            
            //Load the milestone details
            $m = new Bugify_Milestones();
            $result = $m->fetch($milestone_id);
            
            if ($this->getRequest()->isPost())
            {
                $milestone = $this->_getParam('milestone');
                
                //Update the milestone
                $result->setName($milestone['name'])
                       ->setDescription($milestone['description']);
                
                if (isset($milestone['due']) && strlen($milestone['due']) > 0) {
                    $result->setDueDate(strtotime($milestone['due']));
                } else {
                    $result->setDueDate(0);
                }
                
                //Save the milestone
                $m->save($result);
                
                //Clear milestones cache
                $this->cache->removeWithTags('Milestones');
                
                Ui_Messages::Add('ok', 'Changes have been saved.');
                
                $this->_redirect(sprintf('/milestones/%s', $result->getMilestoneId()));
            }
            
            //Convert object to array
            $milestone = $result->toArray();
            
            //Load all open issues for this milestone
            $i = new Bugify_Issues();
            $i->setPaginationPage((int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1);
            
            //First, get the total number of closed issues
            $filter = $i->filter();
            $filter->setMilestoneIds(array($result->getMilestoneId()));
            $filter->setStates($i->getClosedStates());
            $closedCount = $i->fetchTotal($filter);
            
            //Set the filter options
            $filter = $i->filter();
            $filter->setMilestoneIds(array($result->getMilestoneId()));
            $filter->setStates($i->getOpenStates());
            $result = $i->fetchAll($filter);
            
            //Process the issues (ie, attach extra info)
            $h = new Bugify_Helpers_Issues();
            $issues = $h->processIssues($result);
            
            //Get the pagination info
            $page  = $i->getPaginationPage();
            $limit = $i->getPaginationLimit();
            $total = $i->getTotal();
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
            
            $this->_redirect('/milestones');
        }
        
        $this->view->milestone   = $milestone;
        $this->view->issues      = $issues;
        $this->view->page        = $page;
        $this->view->limit       = $limit;
        $this->view->total       = $total;
        $this->view->closedCount = $closedCount;
        $this->view->openCount   = $total;
    }
}
