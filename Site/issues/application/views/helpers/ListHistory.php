<?php

class Zend_View_Helper_ListHistory extends Zend_View_Helper_Abstract
{
    private $_projects   = array();
    private $_categories = array();
    private $_milestones = array();
    private $_users      = array();
    
    public function ListHistory($history=array(), $show_issue=false)
    {
        $html = '';
        
        if (is_array($history) && count($history) > 0)
        {
            $html .= '<table class="history-list">';
            
            foreach ($history as $key => $val)
            {
                $state = (isset($val['issue']['state'])) ? $val['issue']['state'] : Bugify_Issue::STATE_OPEN;
                
                switch ($state)
                {
                    case Bugify_Issue::STATE_OPEN:
                        $state_css = 'open';
                        break;
                    case Bugify_Issue::STATE_IN_PROGRESS:
                        $state_css = 'progress';
                        break;
                    case Bugify_Issue::STATE_RESOLVED:
                        $state_css = 'resolved';
                        break;
                    case Bugify_Issue::STATE_CLOSED:
                        $state_css = 'closed';
                        break;
                    case Bugify_Issue::STATE_REOPENED:
                        $state_css = 'reopened';
                        break;
                }
                
                $html .= '<tr class="'.$state_css.'">';
                
                //Issue id
                if ($show_issue === true)
                {
                    $html .= '<td class="column-issue-id">';
                    $html .= sprintf('<div class="issue-id"><span>#</span>%s</div>', $val['issue_id']);
                    $html .= '</td>';
                }
                
                //User gravatar
                if (isset($val['user']))
                {
                    $name  = $val['user']['name'];
                    $email = $val['user']['email'];
                }
                else
                {
                    $name  = '';
                    $email = '';
                }
                
                $html .= '<td class="column-gravatar">';
                $html .= $this->view->Gravatar($name, $email);
                $html .= '</td>';
                
                //Changes etc column
                $html .= '<td class="column-changes">';
                
                //User and date
                $html .= '<div class="meta right">';
                $html .= sprintf('<a class="date" title="%s" rel="tipsydown">%s</a>', $val['created'], $this->view->RelativeDate($val['created']));
                
                if (isset($val['user']))
                {
                    $html .= sprintf(' by <a class="meta user" href="/users/%s/issues">%s</a>', $val['user']['username'], $val['user']['name']);
                }
                
                $html .= '</div>';
                
                
                //Issue details
                if (isset($val['issue']))
                {
                    $html .= '<div class="issue-name">';
                    $html .= sprintf('<a href="/issues/%s" class="subject" title="%s">%s</a>', $val['issue']['id'], $val['issue']['subject'], $this->view->ShortenString($val['issue']['subject'], 90));
                    
                    if (isset($val['issue']['project']))
                    {
                        $html .= sprintf(' <span class="separator">&rsaquo;</span> <a href="/projects/%s" class="project-name">%s</a>', $val['issue']['project']['slug'], $val['issue']['project']['name']);
                    }
                    
                    if ($val['issue']['category_id'] > 0)
                    {
                        $html .= ' <span class="separator">&rsaquo;</span> '.$this->view->CategoryName($val['issue']['category_id']);
                    }
                    
                    $html .= '</div>';
                }
                
                //List of changes
                $html .= (count($val['changes']) > 1) ? '<ul>' : '';
                
                foreach ($val['changes'] as $k => $v)
                {
                    $html .= (count($val['changes']) > 1) ? '<li>' : '<p>';
                    //$html .= $this->_changeToString($v['type'], $v['original'], $v['new']);
                    $html .= $v['description'];
                    $html .= (count($val['changes']) > 1) ? '</li>' : '</p>';
                }
                
                $html .= (count($val['changes']) > 1) ? '</ul>' : '';
                $html .= '</td>';
                
                $html .= '</tr>';
            }
            
            $html .= '</table>';
        } else {
            $html .= '<p class="no-items-message">No Recent History Found</p>';
        }
        
        return $html;
    }
    
    /**
     * I have commented out the following code because i added a "getDescription" method
     * in the Bugify_History_Change object.  I did this so i can show a text description
     * of what the change was.  So the "getDescription" method does essentially the same as
     * the following "_changeToString" method, but it doesn't use any html, so we lose the
     * ability to put links in the description.
     */
    
    /*
    private function _changeToString($type, $original, $new)
    {
        $html = '';
        
        switch ($type)
        {
            case Bugify_Issue_History_Change::TYPE_SUBJECT:
                $html .= sprintf('Changed subject from "%s" to "%s"', $original, $new);
                break;
            case Bugify_Issue_History_Change::TYPE_DESCRIPTION:
                $html .= sprintf('Changed description from "<span title="%s">%s</span>" to "<span title="%s">%s</span>"', $original, $this->view->ShortenString($original), $new, $this->view->ShortenString($new));
                break;
            case Bugify_Issue_History_Change::TYPE_PRIORITY:
                //Get priority names
                $orig_priority = $this->_getPriorityName($original);
                $new_priority  = $this->_getPriorityName($new);
                
                $html .= sprintf('Changed priority from "%s" to "%s"', $orig_priority, $new_priority);
                break;
            case Bugify_Issue_History_Change::TYPE_PROJECT:
                //Get project names
                $orig_project = $this->_getProjectName($original);
                $new_project  = $this->_getProjectName($new);
                
                $html .= sprintf('Changed project from "%s" to "%s"', $orig_project, $new_project);
                break;
            case Bugify_Issue_History_Change::TYPE_CATEGORY:
                //Get category names
                $orig_category = $this->_getCategoryName($original);
                $new_category  = $this->_getCategoryName($new);
                
                $html .= sprintf('Changed category from "%s" to "%s"', $orig_category, $new_category);
                break;
            case Bugify_Issue_History_Change::TYPE_STATE:
                //Get state names
                $orig_state = $this->_getStateName($original);
                $new_state  = $this->_getStateName($new);
                
                $html .= sprintf('Changed status from "%s" to "%s"', $orig_state, $new_state);
                break;
            case Bugify_Issue_History_Change::TYPE_ASSIGNEE:
                //Get users name
                $orig_user = $this->_getUsersName($original);
                $new_user  = $this->_getUsersName($new);
                
                $html .= sprintf('Changed assignee from "%s" to "%s"', $orig_user, $new_user);
                break;
            case Bugify_Issue_History_Change::TYPE_MILESTONE:
                //Get milestone name
                $orig_milestone = $this->_getMilestoneName($original);
                $new_milestone  = $this->_getMilestoneName($new);
                
                $html .= sprintf('Changed milestone from "%s" to "%s"', $orig_milestone, $new_milestone);
                break;
            case Bugify_Issue_History_Change::TYPE_DATE_RESOLVED:
                $html .= 'Issue marked as resolved';
                break;
            case Bugify_Issue_History_Change::TYPE_COMMENT:
                
                $orig_comment = $this->view->Markdown($this->view->Linkify($original));
                $new_comment  = $this->view->Markdown($this->view->Linkify($new));
                
                if (strlen($original) > 0)
                {
                    $html .= sprintf('Changed comment from: %s to: %s', $orig_comment, $new_comment);
                }
                else
                {
                    $html .= sprintf('Added new comment: %s', $new_comment);
                }
                break;
            case Bugify_Issue_History_Change::TYPE_ATTACHMENT:
                if (strlen($original) > 0)
                {
                    $html .= sprintf('Changed attachment name from "%s" to "%s"', $original, $new);
                }
                else
                {
                    $html .= sprintf('Added new attachment "%s"', $new);
                }
                break;
            case Bugify_Issue_History_Change::TYPE_EMAIL_IMPORT:
                $html .= 'Issue imported from email';
                break;
            case Bugify_Issue_History_Change::TYPE_NEW_ISSUE:
                $html .= 'New issue added';
                break;
            default:
                $html .= 'Unknown change';
        }
        
        return $html;
    }
    */
    
    /*
    private function _getPriorityName($priority)
    {
        $i = new Bugify_Issues();
        $priorities = $i->getPriorities();
        $name       = 'Unknown';
        
        if (array_key_exists($priority, $priorities))
        {
            $name = $priorities[$priority];
        }
        
        return $name;
    }
    
    private function _getProjectName($project_id)
    {
        if (count($this->_projects) == 0)
        {
            //Load the projects
            $p = new Bugify_Projects();
            $result = $p->fetchAll();
            
            foreach ($result as $project)
            {
                $this->_projects[$project->getProjectId()] = $project->toArray();
            }
        }
        
        if ($project_id > 0)
        {
            $name = 'Unknown';
            
            if (array_key_exists($project_id, $this->_projects))
            {
                $name = sprintf('<a href="/projects/%s">%s</a>', $project_id, $this->_projects[$project_id]['name']);
            }
        }
        else
        {
            $name = 'None';
        }
        
        return $name;
    }
    
    private function _getCategoryName($category_id)
    {
        if (count($this->_categories) == 0)
        {
            //Load the categories
            $c = new Bugify_Categories();
            $result = $c->fetchAll();
            
            foreach ($result as $category)
            {
                $this->_categories[$category->getCategoryId()] = $category->toArray();
            }
        }
        
        if ($category_id > 0)
        {
            $name = 'Unknown';
            
            if (array_key_exists($category_id, $this->_categories))
            {
                $name = $this->_categories[$category_id]['name'];
            }
        }
        else
        {
            $name = 'None';
        }
        
        return $name;
    }
    
    private function _getStateName($state)
    {
        $i = new Bugify_Issues();
        $states = $i->getStates();
        $name   = 'Unknown';
        
        if (array_key_exists($state, $states))
        {
            $name = $states[$state];
        }
        
        return $name;
    }
    
    private function _getUsersName($user_id)
    {
        if (count($this->_users) == 0)
        {
            //Load the users
            $u = new Bugify_Users();
            $result = $u->fetchAll();
            
            foreach ($result as $user)
            {
                $this->_users[$user->getUserId()] = $user->toArray();
            }
        }
        
        if ($user_id > 0)
        {
            $name = 'Unknown';
            
            if (array_key_exists($user_id, $this->_users))
            {
                $user = $this->_users[$user_id];
                $name = sprintf('<a href="/users/%s/issues">%s</a>', $user['username'], $user['name']);
            }
        }
        else
        {
            $name = 'Nobody';
        }
        
        return $name;
    }
    
    private function _getMilestoneName($milestone_id)
    {
        if (count($this->_milestones) == 0)
        {
            //Load the milestones
            $m = new Bugify_Milestones();
            $result = $m->fetchAll();
            
            foreach ($result as $milestone)
            {
                $this->_milestones[$milestone->getMilestoneId()] = $milestone->toArray();
            }
        }
        
        if ($milestone_id > 0)
        {
            $name = 'Unknown';
            
            if (array_key_exists($milestone_id, $this->_milestones))
            {
                $name = sprintf('<a href="/milestones/%s">%s</a>', $milestone_id, $this->_milestones[$milestone_id]['name']);
            }
        }
        else
        {
            $name = 'None';
        }
        
        return $name;
    }
    */
}
