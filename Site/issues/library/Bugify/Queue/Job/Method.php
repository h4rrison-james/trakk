<?php

class Bugify_Queue_Job_Method {
    
    public function __construct() {}
    
    public function run($method, $params=array()) {
        switch ($method) {
            case 'notifyUsers':
                //Notify users of a change
                $this->_notifyUsers($params);
                break;
            case 'sendEmail':
                //Send an email
                $this->_sendEmail($params);
                break;
            case 'updateIssueRelationships':
                //Update the issue relationships/links
                $this->_updateIssueRelationships($params);
                break;
            default:
                throw new Bugify_Exception(sprintf('The method "%s" is not supported.', $method));
        }
    }
    
    private function _notifyUsers($params) {
        if (isset($params['issueId'])) {
            $issueId = $params['issueId'];
        } else {
            throw new Bugify_Exception('Missing issueId.');
        }
        
        if (isset($params['historyId'])) {
            $historyId = $params['historyId'];
        } else {
            throw new Bugify_Exception('Missing historyId.');
        }
        
        //Load the issue
        $i = new Bugify_Issues();
        $issue = $i->fetch($issueId);
        
        //Load the history item
        foreach ($issue->getHistory() as $h) {
            if ($h->getHistoryId() == $historyId) {
                //This is the history item we want
                $history = $h;
                break;
            }
        }
        
        if (!isset($history)) {
            throw new Bugify_Exception('Could not find the history item.');
        }
        
        /**
         * Get a list of all users interested in this issue.
         * Could be: creator, assignee, follower, commenter.
         */
        $userIds          = array();
        $followerUserIds  = array();
        $commenterUserIds = array();
        
        //Get the issue creator
        $userIds[] = $issue->getCreatorId();
        
        //Get the assignee id
        if ($issue->getAssigneeId() > 0) {
            $userIds[] = $issue->getAssigneeId();
        }
        
        //Get the followers
        if (count($issue->getFollowers()) > 0) {
            foreach ($issue->getFollowers() as $follower) {
                $userIds[] = $follower->getUserId();
                
                //Keep a separate list of followers on this issue
                $followerUserIds[] = $follower->getUserId();
            }
        }
        
        //Get the commenters
        if (count($issue->getComments()) > 0) {
            foreach ($issue->getComments() as $comment) {
                $userIds[] = $comment->getUserId();
                
                //Keep a separate list of commenters on this issue
                $commenterUserIds[] = $comment->getUserId();
            }
        }
        
        //Get rid of any duplicates
        $userIds = array_unique($userIds);
        
        //Now load the user details
        $u = new Bugify_Users();
        $allUsers     = $u->fetchAll();
        $notifyUsers  = array();
        $notifyEmails = array();
        
        foreach ($allUsers as $user) {
            if ($history->getUserId() == $user->getUserId()) {
                //This is the user that made the change
                $historyUser = $user;
            } else {
                //We dont know who made this change
                //todo - what should we do?
            }
            
            if (in_array($user->getUserId(), $userIds)) {
                $requiresNotification = false;
                
                //Check if this user is the creator
                if ($issue->getCreatorId() == $user->getUserId()) {
                    //This user is the creator of this issue, check if they want this notification
                    if ($user->getRequiresNotification(Bugify_User::NOTIFICATION_CREATOR)) {
                        $requiresNotification = true;
                    }
                }
                
                //Check if the issue is assigned to this user
                if ($issue->getAssigneeId() == $user->getUserId()) {
                    //This issue is assigned to this user, check if they want this notification
                    if ($user->getRequiresNotification(Bugify_User::NOTIFICATION_ASSIGNEE)) {
                        $requiresNotification = true;
                    }
                }
                
                //Check if the user is following this issue
                if (in_array($user->getUserId(), $followerUserIds)) {
                    //This user is following this issue, check if they want this notification
                    if ($user->getRequiresNotification(Bugify_User::NOTIFICATION_FOLLOWING)) {
                        $requiresNotification = true;
                    }
                }
                
                //Check if the user commented on this issue
                if (in_array($user->getUserId(), $commenterUserIds)) {
                    //This user commented on this issue, check if they want this notification
                    if ($user->getRequiresNotification(Bugify_User::NOTIFICATION_COMMENTED)) {
                        $requiresNotification = true;
                    }
                }
                
                //Check if the user wants to receive this type of notification
                if ($history->getUserId() == $user->getUserId()) {
                    //This is the user that made the change, check if they want this notification
                    if ($user->getRequiresNotification(Bugify_User::NOTIFICATION_MYCHANGE)) {
                        $requiresNotification = true;
                    } else {
                        /**
                         * Even if another condition is met, the user doesn't want to get
                         * notifications for changes they make.
                         */
                        $requiresNotification = false;
                    }
                }
                
                if ($requiresNotification === true) {
                    /**
                     * Check that we don't already have this email as there is no point
                     * in sending the notification to the same address twice.
                     */
                    if (!in_array($user->getEmail(), $notifyEmails)) {
                        $notifyUsers[] = $user;
                    }
                    
                    //Keep track of which email addresses we are sending to
                    $notifyEmails[] = $user->getEmail();
                }
            }
        }
        
        if (count($notifyUsers) > 0) {
            //Prepare the job queue
            $q = new Bugify_Queue();
            
            //Prepare the project name
            $projectName = '';
            
            if ($issue->getProjectId() > 0) {
                //Load the project for this issue so we can get it's name
                $p = new Bugify_Projects();
                $project = $p->fetch((int)$issue->getProjectId());
                
                $projectName = $project->getName();
            }
            
            //Prepare the state name
            switch ($issue->getState())
            {
                case Bugify_Issue::STATE_OPEN:
                    $stateName = 'Open';
                    break;
                case Bugify_Issue::STATE_IN_PROGRESS:
                    $stateName = 'In Progress';
                    break;
                case Bugify_Issue::STATE_RESOLVED:
                    $stateName = 'Resolved';
                    break;
                case Bugify_Issue::STATE_CLOSED:
                    $stateName = 'Closed';
                    break;
                case Bugify_Issue::STATE_REOPENED:
                    $stateName = 'Re-opened';
                    break;
            }
            
            //Prepare the email subject
            if (strlen($projectName) > 0) {
                $subject = sprintf('[%s #%s] %s (%s)', $projectName, $issue->getIssueId(), $issue->getSubject(), $stateName);
            } else {
                $subject = sprintf('[#%s] %s (%s)', $issue->getIssueId(), $issue->getSubject(), $stateName);
            }
            
            //Work out the history date for this user (using their timezone)
            $date = new Zend_Date($history->getCreated(), Zend_Date::ISO_8601);
            
            //Prepare the email body
            if (isset($historyUser)) {
                //We know the user, so convert the date to their timezone
                $date->setTimezone($historyUser->getTimezone());
                
                $body = sprintf('%s updated this issue %s at %s', $historyUser->getName(), $date->toString('d MMM, YYYY'), $date->toString('h:ma'))."\n";
            } else {
                $body = sprintf('%s updated this issue %s at %s', 'Unknown User', $date->toString('d MMM, YYYY'), $date->toString('h:ma'))."\n";
            }
            
            //Add a link to the issue
            $body .= sprintf('%s/issues/%s', Bugify_Host::getHostname(true), $issue->getIssueId())."\n\n";
            
            //Add the changes
            $body .= 'Changes:'."\n";
            
            foreach ($history->getChanges() as $change) {
                //Get the change description and append it to the body
                $body .= $change->getDescription();
                
                if ($change->getType() == Bugify_Issue_History_Change::TYPE_NEW_ISSUE) {
                    //Include the full issue details
                    $body .= "\n\n";
                    $body .= $issue->getDescription();
                }
                
                $body .= "\n\n";
            }
            
            //Add a job to send each of these emails
            foreach ($notifyUsers as $user) {
                //Generate the ticket footer with link to change notification settings
                $footer = "--\n".sprintf('You may change your notification settings at %s/users/%s/edit', Bugify_Host::getHostname(true), $user->getUsername());
                
                //Create the job
                $job = new Bugify_Queue_Job();
                $job->setMethod('sendEmail')
                    ->setParams(array(
                        'toAddresses' => $user->getEmail(),
                        'subject'     => $subject,
                        'plainBody'   => $body.$footer,
                    ));
                
                $q->save($job);
            }
        }
    }
    
    private function _sendEmail($params) {
        //Make sure we have all the required params
        $toAddresses   = array();
        $ccAddresses   = array();
        $fromAddress   = '';
        $subject       = '';
        $plainBody     = '';
        $htmlBody      = '';
        
        if (is_array($params) && count($params) > 0) {
            foreach ($params as $key => $val) {
                switch ($key) {
                    case 'toAddresses':
                        if (is_array($val)) {
                            $toAddresses = $val;
                        } elseif (is_string($val)) {
                            $toAddresses = array($val);
                        }
                        break;
                    case 'ccAddresses':
                        if (is_array($val)) {
                            $ccAddresses = $val;
                        } elseif (is_string($val)) {
                            $ccAddresses = array($val);
                        }
                        break;
                    case 'fromAddresses':
                        $fromAddress = (string)$val;
                        break;
                    case 'subject':
                        $subject = (string)$val;
                        break;
                    case 'plainBody':
                        $plainBody = (string)$val;
                        break;
                    case 'htmlBody':
                        $htmlBody = (string)$val;
                        break;
                }
            }
            
            //Now make sure we have enough details to send an email
            if (count($toAddresses) == 0) {
                throw new Bugify_Exception('Please specify at least one "to" address.');
            }
            
            if (strlen($plainBody) == 0 && strlen($htmlBody) == 0) {
                throw new Bugify_Exception('Please specify at least one of "plain" or "html" body.');
            }
            
            //We have enough to send an email, so lets send it
            $m = new Bugify_Mail();
            
            //Check for a "from" address
            if (strlen($fromAddress) == 0) {
                //Use the default "from" address
                $mailer = $m->getMailer();
            } else {
                //Use the specified "from" address
                $mailer = $m->getMailer(false);
                $mailer->setFrom($fromAddress);
            }
            
            //Add the "to" addresses
            foreach ($toAddresses as $key => $val) {
                $mailer->addTo($val);
            }
            
            //Add the "cc" addresses
            foreach ($ccAddresses as $key => $val) {
                $mailer->addCc($val);
            }
            
            //Set the remaining parts
            $mailer->setSubject($subject);
            
            if (strlen($plainBody) > 0) {
                $mailer->setBodyText($plainBody);
            }
            
            if (strlen($htmlBody) > 0) {
                $mailer->setBodyHtml($htmlBody);
            }
            
            try {
                //Send the email
                $mailer->send();
            } catch (Exception $e) {
                /**
                 * Could not send the email - save the error to config so we
                 * can display it to the user when they log in next.
                 */
                //todo
                
                
                throw $e;
            }
        } else {
            throw new Bugify_Exception('Please supply a list of parameters.');
        }
    }
    
    private function _updateIssueRelationships($params) {
        //Make sure we have an issueId to deal with
        if (isset($params['issueId'])) {
            $issueId = $params['issueId'];
        } else {
            throw new Bugify_Exception('Please specify an issue id to process.');
        }
        
        //Load the issue
        $i = new Bugify_Issues();
        $issue = $i->fetch($issueId);
        
        //Find any links to other issues
        $issue->updateRelatedIssues();
        
        //Save the issue
        $i->save($issue);
        
        //Get the related issue id's
        $relatedIssueIds = $issue->getRelatedIssueIds();
        
        if (is_array($relatedIssueIds) && count($relatedIssueIds) > 0) {
            foreach ($relatedIssueIds as $key => $val) {
                //Load the related issue
                $related = $i->fetch($val);
                
                //Add a reciprocal link to the original issue
                $related->addRelatedIssueId($issue->getIssueId());
                
                //Save the related issue
                $i->save($related);
            }
        }
    }
}
