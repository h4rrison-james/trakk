<?php

class MailboxesController extends Ui_Controller_Action
{
    public function init()
    {
        throw new Bugify_Exception('Mailboxes feature is not available.', 404);
    }
    
    public function indexAction()
    {
        try
        {
            $mailboxes = array();
            
            //Load all the mailboxes
            $m = new Bugify_Mailboxes();
            $result = $m->fetchAll();
            
            foreach ($result as $mailbox)
            {
                $mailboxes[] = $mailbox->toArray();
            }
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->mailboxes = $mailboxes;
    }
    
    public function newAction()
    {
        try
        {
            if ($this->getRequest()->isPost())
            {
                $mailbox = $this->_getParam('mailbox');
                
                //Create a new mailbox
                $new = new Bugify_Mailbox();
                $new->setName($mailbox['name'])
                    ->setHost($mailbox['host'])
                    ->setUsername($mailbox['username'])
                    ->setPassword($mailbox['password'])
                    ->setState(Bugify_Mailbox::STATE_INACTIVE);
                
                //Save the mailbox
                $m  = new Bugify_Mailboxes();
                $id = $m->save($new);
                
                //Now, load the mailbox and determine the connection details
                $mailbox = $m->fetch($id);
                
                if ($mailbox->determineConnection() === true)
                {
                    /**
                     * Found the correct connection settings.
                     * Mark the mailbox as active, and save it.
                     */
                    $mailbox->setState(Bugify_Mailbox::STATE_ACTIVE);
                    
                    $m->save($mailbox);
                }
                else
                {
                    /**
                     * Could not determine the correct connection settings.
                     * Could be bad username/password, or custom port.
                     */
                    throw new Ui_Exception('Could not determine the correct connection settings.  Please check your credentials, and extra settings.');
                }
                
                
                echo '<pre>';
                print_r($mailbox);
                echo '</pre>';
            }
        }
        catch (Exception $e)
        {
            Ui_Messages::Add('error', $e->getMessage());
        }
        
        $this->view->projects   = array();
        $this->view->categories = array();
    }
    
    public function jsCheckForMessagesAction()
    {
        try
        {
            $mailbox_id = (int)$this->_getParam('mailbox_id');
            
            //Load the mailbox details
            $m = new Bugify_Mailboxes();
            $mailbox = $m->fetch($mailbox_id);
            $count   = 0;
            
            if ($mailbox->getState() == Bugify_Mailbox::STATE_ACTIVE)
            {
                //Fetch new messages
                $count = $mailbox->fetchNewMessages();
            }
            else
            {
                throw new Ui_Exception('Mailbox is disabled.');
            }
            
            $data = array(
               'status' => true,
               'count'  => $count,
            );
        }
        catch (Exception $e)
        {
            $data = array(
               'error'  => $e->getMessage(),
               'status' => false,
            );
        }
        
        $this->_helper->json($data);
    }
}
