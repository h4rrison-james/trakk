<?php

class Bugify_Helpers_History
{
    public function attachFullInfo($history_items, $add_issue_data=false)
    {
        $processed = array();
        
        /**
         * Load all the users so we can "attach" user info
         * to the issues.
         */
        $u = new Bugify_Users();
        $result = $u->fetchAll();
        $users  = array();
        
        if (is_array($result) && count($result) > 0)
        {
            foreach ($result as $user)
            {
                $users[$user->getUserId()] = $user->toArray();
            }
        }
        
        if ($add_issue_data === true)
        {
            /**
             * Get all the issue id's from these history items
             * so we can load the issue details for them.
             */
            $issue_ids = array();
            
            foreach ($history_items as $history)
            {
                if (!in_array($history->getIssueId(), $issue_ids))
                {
                    $issue_ids[] = $history->getIssueId();
                }
            }
            
            //Now fetch all these issues
            $i = new Bugify_Issues();
            
            $filter = $i->filter();
            $filter->setIssueIds($issue_ids);
            $filter->setStates($i->getAllStates());
            $result = $i->fetchAll($filter);
            
            $issues = array();
            
            foreach ($result as $issue)
            {
                $issues[$issue->getIssueId()] = $issue->toArray();
            }
            
            /**
             * Load all projects so we can "attach" project info
             * to the issues.
             */
            $p = new Bugify_Projects();
            $result   = $p->fetchAll();
            $projects = array();
            
            if (is_array($result) && count($result) > 0)
            {
                foreach ($result as $project)
                {
                    $projects[$project->getProjectId()] = $project->toArray();
                }
            }
        }
        
        foreach ($history_items as $history)
        {
             $h = $history->toArray();
             
             if ($history->getUserId() > 0)
             {
                 if (array_key_exists($history->getUserId(), $users))
                 {
                     $h['user'] = $users[$history->getUserId()];
                 }
             }
             
             if ($add_issue_data === true)
             {
                 if (array_key_exists($history->getIssueId(), $issues))
                 {
                     $h['issue'] = $issues[$history->getIssueId()];
                     
                     if ($h['issue']['project_id'] > 0)
                     {
                         if (array_key_exists($h['issue']['project_id'], $projects))
                         {
                             $h['issue']['project'] = $projects[$h['issue']['project_id']];
                         }
                    }
                 }
             }
             
             $processed[] = $h;
        }
        
        return $processed;
    }
}
