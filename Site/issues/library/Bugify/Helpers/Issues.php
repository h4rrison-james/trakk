<?php

class Bugify_Helpers_Issues
{
    /**
     * Single method for processing issue lists
     * for both "my issues" and "all issues", and also project issues etc.
     * 
     * @var $issues            array of Bugify_Issue objects.
     * @var $include_strings   useful for the API - includes strings for things like priority ids.
     */
    public function processIssues($issues)
    {
        $processed = array();
        
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
        
        /**
         * Load all milestones so we can "attach" milestone info
         * to the issues.
         */
        $m = new Bugify_Milestones();
        $result     = $m->fetchAll();
        $milestones = array();
        
        if (is_array($result) && count($result) > 0)
        {
            foreach ($result as $milestone)
            {
                $milestones[$milestone->getMilestoneId()] = $milestone->toArray();
            }
        }
        
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
        
        //Load priority names
        $i = new Bugify_Issues();
        $priority_names = $i->getPriorities();
        $state_names    = $i->getStates();
        
        //Process the issues and add the extra data
        foreach ($issues as $issue)
        {
            $i = $issue->toArray();
            
            if ($issue->getProjectId() > 0)
            {
                if (array_key_exists($issue->getProjectId(), $projects))
                {
                    $i['project'] = $projects[$issue->getProjectId()];
                }
            }
            
            if ($issue->getMilestoneId() > 0)
            {
                if (array_key_exists($issue->getMilestoneId(), $milestones))
                {
                    $i['milestone'] = $milestones[$issue->getMilestoneId()];
                }
            }
            
            if ($issue->getCreatorId() > 0)
            {
                if (array_key_exists($issue->getCreatorId(), $users))
                {
                    $i['creator'] = $users[$issue->getCreatorId()];
                }
            }
            
            if ($issue->getAssigneeId() > 0)
            {
                if (array_key_exists($issue->getAssigneeId(), $users))
                {
                    $i['assignee'] = $users[$issue->getAssigneeId()];
                }
            }
            
            if (isset($i['comments']) && is_array($i['comments']) && count($i['comments']) > 0)
            {
                foreach ($i['comments'] as $k => $v)
                {
                    if (array_key_exists($v['user_id'], $users))
                    {
                        $i['comments'][$k]['user'] = $users[$v['user_id']];
                    }
                }
            }
            
            if (isset($i['attachments']) && is_array($i['attachments']) && count($i['attachments']) > 0)
            {
                foreach ($i['attachments'] as $k => $v)
                {
                    if (array_key_exists($v['user_id'], $users))
                    {
                        $i['attachments'][$k]['user'] = $users[$v['user_id']];
                    }
                }
            }
            
            if (isset($i['followers']) && is_array($i['followers']) && count($i['followers']) > 0)
            {
                foreach ($i['followers'] as $k => $v)
                {
                    if (array_key_exists($v['user_id'], $users))
                    {
                        $i['followers'][$k]['user'] = $users[$v['user_id']];
                    }
                }
            }
            
            if (isset($i['history']) && is_array($i['history']) && count($i['history']) > 0)
            {
                foreach ($i['history'] as $k => $v)
                {
                    if (array_key_exists($v['user_id'], $users))
                    {
                        $i['history'][$k]['user'] = $users[$v['user_id']];
                    }
                }
            }
            
            //Add priority name
            $i['priority_name'] = $priority_names[$i['priority']];
            
            //Add state name
            $i['state_name'] = $state_names[$i['state']];
            
            ksort($i);
            
            $processed[] = $i;
        }
        
        return $processed;
    }
    
    public function addIssueCountsForProjects($projects)
    {
        $processed = array();
        
        $db = Bugify_Db::get();
        $i  = new Bugify_Issues();
        
        $s = $db->select();
        $s->from('issues', array('project_id AS project', 'COUNT(id) AS issue_count'))
          ->where('state IN (?)', $i->getOpenStates())
          ->group('project_id');
        
        $result = $db->fetchAll($s);
        $counts = array();
        
        if (is_array($result) && count($result) > 0)
        {
            foreach ($result as $key => $val)
            {
                $counts[$val['project']] = $val['issue_count'];
            }
        }
        
        foreach ($projects as $project)
        {
            $p = $project->toArray();
            
            $p['issue_count'] = (array_key_exists($project->getProjectId(), $counts)) ? $counts[$project->getProjectId()] : 0;
            
            $processed[] = $p;
        }
        
        return $processed;
    }
    
    public function addIssueCountsForAssignees($users)
    {
        $processed = array();
        
        $db = Bugify_Db::get();
        $i  = new Bugify_Issues();
        
        $s = $db->select();
        $s->from('issues', array('assignee_id AS assignee', 'COUNT(id) AS issue_count'))
          ->where('state IN (?)', $i->getOpenStates())
          ->group('assignee_id');
        
        $result = $db->fetchAll($s);
        $counts = array();
        
        if (is_array($result) && count($result) > 0)
        {
            foreach ($result as $key => $val)
            {
                $counts[$val['assignee']] = $val['issue_count'];
            }
        }
        
        foreach ($users as $user)
        {
            $u = $user->toArray();
            
            $u['issue_count'] = (array_key_exists($user->getUserId(), $counts)) ? $counts[$user->getUserId()] : 0;
            
            $processed[] = $u;
        }
        
        return $processed;
    }
    
    public function addIssueCountsForMilestones($milestones)
    {
        $processed = array();
        
        $db = Bugify_Db::get();
        $i  = new Bugify_Issues();
        
        $s = $db->select();
        $s->from('issues', array('milestone_id AS milestone', 'COUNT(id) AS issue_count'))
          ->where('state IN (?)', $i->getOpenStates())
          ->group('milestone_id');
        
        $result = $db->fetchAll($s);
        $counts = array();
        
        if (is_array($result) && count($result) > 0)
        {
            foreach ($result as $key => $val)
            {
                $counts[$val['milestone']] = $val['issue_count'];
            }
        }
        
        foreach ($milestones as $milestone)
        {
            $m = $milestone->toArray();
            
            $m['issue_count'] = (array_key_exists($milestone->getMilestoneId(), $counts)) ? $counts[$milestone->getMilestoneId()] : 0;
            
            $processed[] = $m;
        }
        
        return $processed;
    }
}
