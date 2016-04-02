<?php

class SearchController extends Ui_Controller_Action
{
    public function init()
    {}
    
    public function indexAction()
    {
        try
        {
            $search_string = $this->_getParam('q');
            $issues        = array();
            $page          = 1;
            $limit         = 0;
            $total         = 0;
            
            if (strlen($search_string) > 0)
            {
                //Open the search index
                $this->search->setPaginationPage((int)$this->_getParam('page') > 0 ? $this->_getParam('page') : 1);
                
                $results = $this->search->searchIndex($search_string);
                
                if (is_array($results) && count($results) > 0)
                {
                    $issue_ids = array();
                    
                    foreach ($results as $key => $val)
                    {
                        $issue_ids[] = $val['issue_id'];
                    }
                    
                    //Now load these issues to get the full details
                    $i = new Bugify_Issues();
                    
                    $filter = $i->filter();
                    $filter->setIssueIds($issue_ids);
                    $filter->setStates(array(
                       Bugify_Issue::STATE_OPEN,
                       Bugify_Issue::STATE_IN_PROGRESS,
                       Bugify_Issue::STATE_RESOLVED,
                       Bugify_Issue::STATE_CLOSED,
                       Bugify_Issue::STATE_REOPENED,
                    ));
                    
                    $result = $i->fetchAll($filter);
                    
                    //Process the issues (ie, attach extra info)
                    $h = new Bugify_Helpers_Issues();
                    $issues = $h->processIssues($result);
                    
                    //todo - need to order these issues by the scoring that lucene gave us
                    
                    //Get the pagination info
                    $page  = $this->search->getPaginationPage();
                    $limit = $this->search->getPaginationLimit();
                    $total = $this->search->getTotal();
                }
            }
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->q      = $search_string;
        $this->view->issues = $issues;
        $this->view->page   = $page;
        $this->view->limit  = $limit;
        $this->view->total  = $total;
    }
    
    public function settingsAction()
    {
        try
        {
            $stats = array(
               'docs'         => 0,
               'size_on_disk' => 0,
            );
            
            $stats['docs']         = $this->search->getNumDocs();
            $stats['size_on_disk'] = $this->search->getSizeOnDisk();
            
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->stats = $stats;
    }
    
    public function reIndexAction()
    {
        try
        {
            //This might take a while
            set_time_limit(0);
            
            //Begin a re-index process by deleting all existing documents
            $this->search->deleteAll();
            
            //We use pagination to go through all the issues
            $page = 1;
            
            do
            {
                //Now load all issues (including resolved/closed)
                $i = new Bugify_Issues();
                $i->setPaginationPage($page);
                
                $filter = $i->filter();
                $filter->setStates(array(
                   Bugify_Issue::STATE_OPEN,
                   Bugify_Issue::STATE_IN_PROGRESS,
                   Bugify_Issue::STATE_RESOLVED,
                   Bugify_Issue::STATE_CLOSED,
                   Bugify_Issue::STATE_REOPENED,
                ));
                
                $result = $i->fetchAll($filter);
                
                if (count($result) > 0)
                {
                    foreach ($result as $issue)
                    {
                        //Add this issue to the index
                        $this->search->addIssueDocument($issue);
                    }
                }
                
                $page++;
            }
            while ($page <= $i->getTotalPages());
            
            //Optimise the index
            $this->search->optimise();
            
            Ui_Messages::Add('ok', 'Issues have been re-indexed.');
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->_redirect('/search/settings');
    }
}
