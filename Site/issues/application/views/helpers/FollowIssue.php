<?php

class Zend_View_Helper_FollowIssue extends Zend_View_Helper_Abstract
{
    /**
     * Show the interface for allowing a user to follow/un-follow an issue.
     * Requires the js file "issue.js" to be loaded as well.
     */
    public function FollowIssue($issue_id, $followers, $user_id)
    {
        $html = '';
        
        $already_follows = false;
        
        if (is_array($followers) && count($followers) > 0)
        {
            //Check if this user is already following this issue
            foreach ($followers as $key => $val)
            {
                if ($val['user_id'] == $user_id)
                {
                    $already_follows = true;
                    break;
                }
            }
        }
        
        $html .= '<div class="follow">';
        
        if ($already_follows === true)
        {
            $html .= '<a href="#unfollow-issue" onclick="unFollowIssue(\''.$issue_id.'\'); return false;" class="unfollow-icon" title="Un-Follow Issue" rel="tipsyleft">&nbsp;</a>';
        }
        else
        {
            $html .= '<a href="#follow-issue" onclick="followIssue(\''.$issue_id.'\'); return false;" class="follow-icon" title="Follow Issue" rel="tipsyleft">&nbsp;</a>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
