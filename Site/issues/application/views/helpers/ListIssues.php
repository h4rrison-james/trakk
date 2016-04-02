<?php

class Zend_View_Helper_ListIssues extends Zend_View_Helper_Abstract
{
    public function ListIssues($issues, $show_assignee=false, $show_project=true)
    {
        $html = '';
        
        if (is_array($issues) && count($issues) > 0)
        {
            $html .= '<table class="issue-list">';
            
            foreach ($issues as $key => $val)
            {
                switch ($val['state'])
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
                
                switch ($val['priority'])
                {
                    case Bugify_Issue::PRIORITY_LOW:
                        $priority_css = 'low';
                        break;
                    case Bugify_Issue::PRIORITY_NORMAL:
                        $priority_css = 'normal';
                        break;
                    case Bugify_Issue::PRIORITY_HIGH:
                        $priority_css = 'high';
                        break;
                    case Bugify_Issue::PRIORITY_CRITICAL:
                        $priority_css = 'critical';
                        break;
                }
                
                //Issue id
                $html .= '<td class="column-issue-id">';
                $html .= sprintf('<div class="issue-id"><span>#</span>%s</div>', $val['id']);
                $html .= '</td>';
                
                /**
                 * Work out the project and category names so we know
                 * the total string length of the subject+project+category
                 * and can then work out how short to cut the subject etc.
                 */
                $project_name  = ($show_project === true && isset($val['project'])) ? $val['project']['name'] : '';
                $category_name = ($val['category_id'] > 0) ? $this->view->CategoryName($val['category_id']) : '';
                
                $extra_chars = strlen($project_name)+strlen($category_name);
                
                /**
                 * We want a maximum of around 60 chars for the subject if
                 * we are showing the assignee column, otherwise 80 chars.
                 */
                $total_length   = ($show_assignee === true) ? 60 : 80;
                $subject_length = ($total_length-$extra_chars);
                
                //Issue subject etc
                $html .= '<td>';
                $html .= '<div class="issue-name">';
                $html .= sprintf('<a href="/issues/%s" class="subject" title="%s">%s</a>', $val['id'], $val['subject'], $this->view->ShortenString($val['subject'], $subject_length));
                
                if ($show_project === true && isset($val['project']))
                {
                    $html .= sprintf(' <span class="separator">&rsaquo;</span> <a href="/projects/%s" class="project-name">%s</a>', $val['project']['slug'], $val['project']['name']);
                }
                
                if ($val['category_id'] > 0)
                {
                    $html .= ' <span class="separator">&rsaquo;</span> '.$this->view->CategoryName($val['category_id']);
                }
                
                if ($val['percentage'] > 0) {
                    $html .= sprintf('<span class="percentage">%s%% complete</span>', $val['percentage']);
                }
                
                $html .= '</div>';
                
                //Meta info (dates etc)
                $html .= '<div class="meta">';
                $html .= '<span class="dates">';
                $html .= sprintf('Added <a title="%s" rel="tipsydown">%s</a> by <a href="/users/%s/issues">%s</a>.', $val['created'], $this->view->RelativeDate($val['created']), $val['creator']['username'], $val['creator']['name']);
                
                if ($val['updated'] != $val['created'])
                {
                    $html .= sprintf('  Last updated <a title="%s" rel="tipsydown">%s</a>.', $val['updated'], $this->view->RelativeDate($val['updated']));
                }
                
                $html .= '</span>';
                
                $html .= '</div>';
                $html .= '</td>';
                
                //Category/state column
                $html .= '<td class="column-category">';
                $html .= '<div class="priority priority-'.$priority_css.'">'.$this->view->PriorityName($val['priority']).'</div>';
                $html .= $this->view->StateName($val['state']);
                $html .= '</td>';
                
                //Assignee column
                if ($show_assignee === true)
                {
                    //Assignee name
                    $html .= '<td class="column-assignee">';
                    
                    if (isset($val['assignee']))
                    {
                        $html .= sprintf('<span class="assignee">Assigned to <a href="/users/%s/issues">%s</a></span>', $val['assignee']['username'], $val['assignee']['name']);
                    }
                    else
                    {
                        $html .= '<span class="assignee">Unassigned</span>';
                    }
                    
                    $html .= '</td>';
                    
                    //Assignee gravatar
                    if (isset($val['assignee']))
                    {
                        $name  = $val['assignee']['name'];
                        $email = $val['assignee']['email'];
                    }
                    else
                    {
                        $name  = '';
                        $email = '';
                    }
                    
                    $html .= '<td class="column-gravatar">';
                    $html .= $this->view->Gravatar($name, $email);
                    $html .= '</td>';
                }
                
                $html .= '</tr>';
            }
            
            $html .= '</table>';
        } else {
            $html .= '<p class="no-items-message">No Issues Found</p>';
        }
        
        return $html;
    }
}
