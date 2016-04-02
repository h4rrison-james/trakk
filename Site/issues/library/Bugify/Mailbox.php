<?php

class Bugify_Mailbox
{
    private $_id           = 0;
    private $_created      = 0;
    private $_updated      = 0;
    private $_last_checked = 0;
    private $_name         = '';
    private $_host         = '';
    private $_port         = 0;
    private $_username     = '';
    private $_password     = '';
    private $_project_id   = 0;
    private $_category_id  = 0;
    private $_type         = self::TYPE_IMAP;
    private $_encryption   = self::ENCRYPTION_NONE;
    private $_state        = self::STATE_ACTIVE;
    
    //Mailbox types
    const TYPE_IMAP = 0;
    const TYPE_POP3 = 1;
    
    //Encryption types
    const ENCRYPTION_NONE = 0;
    const ENCRYPTION_SSL  = 1;
    const ENCRYPTION_TLS  = 2;
    
    //Mailbox states
    const STATE_INACTIVE = 0;
    const STATE_ACTIVE   = 1;
    
    public function __construct()
    {}
    
    public function getMailboxId()
    {
        return $this->_id;
    }
    
    public function getCreated()
    {
        return Bugify_Date::getLocalTime($this->_created);
    }
    
    public function getUpdated()
    {
        return Bugify_Date::getLocalTime($this->_updated);
    }
    
    public function getLastChecked()
    {
        return Bugify_Date::getLocalTime($this->_last_checked);
    }
    
    public function getName()
    {
        return $this->_name;
    }
    
    public function getHost()
    {
        return $this->_host;
    }
    
    public function getPort()
    {
        return $this->_port;
    }
    
    public function getUsername()
    {
        return $this->_username;
    }
    
    public function getPassword()
    {
        return $this->_password;
    }
    
    public function getProjectId()
    {
        return $this->_project_id;
    }
    
    public function getCategoryId()
    {
        return $this->_category_id;
    }
    
    public function getType()
    {
        return $this->_type;
    }
    
    public function getEncryption()
    {
        return $this->_encryption;
    }
    
    public function getState()
    {
        return $this->_state;
    }
    
    public function setMailboxId($val)
    {
        $this->_id = $val;
        
        return $this;
    }
    
    public function setCreated($val)
    {
        $this->_created = $val;
        
        return $this;
    }
    
    public function setUpdated($val)
    {
        $this->_updated = $val;
        
        return $this;
    }
    
    public function setLastChecked($val)
    {
        $this->_last_checked = (int)$val;
        
        return $this;
    }
    
    public function setName($val)
    {
        if (strlen($val) > 0)
        {
            $this->_name = $val;
        }
        else
        {
            throw new Bugify_Exception('Please specify a mailbox name.');
        }
        
        return $this;
    }
    
    public function setHost($val)
    {
        if (strlen($val) > 0)
        {
            $this->_host = $val;
        }
        else
        {
            throw new Bugify_Exception('Please specify a mailbox host.');
        }
        
        return $this;
    }
    
    public function setPort($val)
    {
        $this->_port = (int)$val;
        
        return $this;
    }
    
    public function setUsername($val)
    {
        if (strlen($val) > 0)
        {
            $this->_username = $val;
        }
        else
        {
            throw new Bugify_Exception('Please specify a mailbox username.');
        }
        
        return $this;
    }
    
    public function setPassword($val)
    {
        if (strlen($val) > 0)
        {
            $this->_password = $val;
        }
        else
        {
            throw new Bugify_Exception('Please specify a mailbox password.');
        }
        
        return $this;
    }
    
    public function setProjectId($val)
    {
        $this->_project_id = (int)$val;
        
        return $this;
    }
    
    public function setCategoryId($val)
    {
        $this->_category_id = (int)$val;
        
        return $this;
    }
    
    public function setType($val)
    {
        $valid_types = array(
           self::TYPE_IMAP,
           self::TYPE_POP3,
        );
        
        if (in_array($val, $valid_types))
        {
            $this->_type = $val;
        }
        else
        {
            throw new Bugify_Exception('Invalid type.');
        }
        
        return $this;
    }
    
    public function setEncryption($val)
    {
        $valid_encryption = array(
           self::ENCRYPTION_NONE,
           self::ENCRYPTION_SSL,
           self::ENCRYPTION_TLS,
        );
        
        if (in_array($val, $valid_encryption))
        {
            $this->_encryption = $val;
        }
        else
        {
            throw new Bugify_Exception('Invalid encryption type.');
        }
        
        return $this;
    }
    
    public function setState($val)
    {
        $valid_states = array(
           self::STATE_INACTIVE,
           self::STATE_ACTIVE,
        );
        
        if (in_array($val, $valid_states))
        {
            $this->_state = $val;
        }
        else
        {
            throw new Bugify_Exception('Invalid state.');
        }
        
        return $this;
    }
    
    /**
     * Try and automatically determine the connection details
     * for this account (ie, port, ssl).
     * Try the most secure settings first, then fall back on
     * the less secure settings.
     */
    public function determineConnection()
    {
        switch ($this->getType())
        {
            case self::TYPE_IMAP:
                $order = array(
                   array(
                      'port'       => 993,
                      'encryption' => self::ENCRYPTION_SSL,
                   ),
                   array(
                      'port'       => 143,
                      'encryption' => self::ENCRYPTION_TLS,
                   ),
                   array(
                      'port'       => 143,
                      'encryption' => self::ENCRYPTION_NONE,
                   ),
                );
                break;
            case self::TYPE_POP3:
                $order = array(
                   array(
                      'port'       => 995,
                      'encryption' => self::ENCRYPTION_SSL,
                   ),
                   array(
                      'port'       => 110,
                      'encryption' => self::ENCRYPTION_TLS,
                   ),
                   array(
                      'port'       => 110,
                      'encryption' => self::ENCRYPTION_NONE,
                   ),
                );
                break;
            default:
                throw new Bugify_Exception('Unknown mailbox type.');
        }
        
        foreach ($order as $key => $val)
        {
            //Attempt a connection using these details
            $settings = array(
               'host'     => $this->getHost(),
               'port'     => $val['port'],
               'user'     => $this->getUsername(),
               'password' => $this->getPassword(),
            );
            
            if ($val['encryption'] == self::ENCRYPTION_SSL)
            {
                $settings['ssl'] = 'SSL';
            }
            elseif ($val['encryption'] == self::ENCRYPTION_TLS)
            {
                $settings['ssl'] = 'TLS';
            }
            
            try
            {
                switch ($this->getType())
                {
                    case self::TYPE_IMAP:
                        $mail = new Zend_Mail_Storage_Imap($settings);
                        break;
                    case self::TYPE_POP3:
                        $mail = new Zend_Mail_Storage_Pop3($settings);
                        break;
                    default:
                        throw new Bugify_Exception('Unknown mailbox type.');
                }
                
                $messages = $mail->countMessages();
                
                echo '<pre>';
                print_r($messages);
                echo '</pre>';
                
                //This is the correct connection type, update the object
                $this->setPort($val['port'])
                     ->setEncryption($val['encryption']);
                
                
                return true;
            }
            catch (Exception $e)
            {
                echo '<pre>';
                print_r($e);
                echo '</pre>';
            }
        }
        
        return false;
    }
    
    /**
     * Check for new messages and add them as issues.
     */
    public function fetchNewMessages()
    {
        //Keep track of the number of new messages fetched
        $count = 0;
        
        $settings = array(
           'host'     => $this->getHost(),
           'port'     => $this->getPort(),
           'user'     => $this->getUsername(),
           'password' => $this->getPassword(),
        );
        
        if ($this->getEncryption() == self::ENCRYPTION_SSL)
        {
            $settings['ssl'] = 'SSL';
        }
        elseif ($this->getEncryption() == self::ENCRYPTION_TLS)
        {
            $settings['ssl'] = 'TLS';
        }
        
        switch ($this->getType())
        {
            case self::TYPE_IMAP:
                $mail = new Zend_Mail_Storage_Imap($settings);
                break;
            case self::TYPE_POP3:
                $mail = new Zend_Mail_Storage_Pop3($settings);
                break;
            default:
                throw new Bugify_Exception('Unknown mailbox type.');
        }
        
        //Check number
        $message_count = $mail->countMessages();
        
        if ($message_count > 0)
        {
            //Load all users
            $u = new Bugify_Users();
            $users = $u->fetchAll();
            
            foreach ($mail as $message_id => $message)
            {
                if ($this->getType() == self::TYPE_IMAP)
                {
                    //Make sure this is an unread message
                    if ($message->hasFlag(Zend_Mail_Storage::FLAG_SEEN))
                    {
                        //This message is not new, skip it
                        continue;
                    }
                }
                
                //Prepare the data
                $subject       = $message->getHeader('subject');
                $priority      = '';
                $content_plain = '';
                $content_html  = '';
                $attachments   = array();
                
                //Get the mail headers
                $headers = $message->getHeaders();
                
                //Figure out which user the email is from
                $email = $this->_splitFromAddress($headers['from']);
                
                //Make sure we have a user with this email address
                foreach ($users as $user)
                {
                    if ($user->getEmail() == $email)
                    {
                        //This is the user we are looking for
                        break;
                    }
                }
                
                //Check for multi-part message
                if ($message->isMultipart())
                {
                    $number_of_parts = $message->countParts();
                    
                    if ($number_of_parts > 0)
                    {
                        for ($i = 1; $i <= $number_of_parts; $i++)
                        {
                            $part = $message->getPart($i);
                            $type = $part->contentType;
                            
                            if (strpos($type, ';') !== false)
                            {
                                $type = trim(substr($type, 0, strpos($type, ';')));
                            }
                            
                            if ($type == 'text/plain')
                            {
                                //Add this to the plain content
                                $content_plain .= $part->getContent();
                            }
                            elseif ($type == 'text/html')
                            {
                                //Add this to the html content
                                $content_html .= $part->getContent();
                            }
                            else
                            {
                                //Get the headers so we can try and work out the attachment filename
                                $part_headers = $part->getHeaders();
                                $filename     = 'unknown.txt';
                                
                                if (isset($part_headers['content-disposition']))
                                {
                                    $filename = Zend_Mime_Decode::splitHeaderField($part_headers['content-disposition'], 'filename');
                                }
                                
                                //Work out the encoding
                                $encoding = (isset($part_headers['content-transfer-encoding'])) ? $part_headers['content-transfer-encoding'] : 'base64';
                                
                                //Add this part as an attachment
                                $attachments[] = array(
                                   'filename' => $filename,
                                   'type'     => $type,
                                   'content'  => $part->getContent(),
                                   'encoding' => $encoding,
                                );
                            }
                        }
                    }
                }
                else
                {
                    $content_plain = $message->getContent();
                }
                
                //Check for priority
                if (isset($headers['x-priority']))
                {
                    //Standard (Mac Mail) priority
                    $prio = (int)$headers['x-priority'];
                }
                elseif (isset($headers['x-msmail-priority']))
                {
                    //Outlook priority
                    $prio = (int)$headers['x-msmail-priority'];
                }
                else
                {
                    //Normal priority
                    $prio = 3;
                }
                
                if ($prio > 3)
                {
                    $priority = Bugify_Issue::PRIORITY_LOW;
                }
                elseif ($prio < 3)
                {
                    $priority = Bugify_Issue::PRIORITY_HIGH;
                }
                else
                {
                    $priority = Bugify_Issue::PRIORITY_NORMAL;
                }
                
                
                //Make sure we have a subject
                $subject = (strlen($subject) > 0) ? $subject : '[No subject]';
                
                //Decide which content to use
                if (strlen($content_plain) > 0)
                {
                    $content = $content_plain;
                }
                elseif (strlen($content_html) > 0)
                {
                    $content = $content_html;
                }
                else
                {
                    $content = '';
                }
                
                /**
                 * For the sake of Markdown, we want single line-breaks to become <br />
                 * In Markdown, this is done with 2 spaces before the newline character.
                 */
                $search  = array("\r\n", "\r", "\n");
                $content = str_replace($search, "  \n", $content);
                $content = str_replace("    \n", "  \n", $content);
                
                //Create a new issue
                $i = new Bugify_Issue();
                $i->setProjectId($this->getProjectId())
                  ->setCategoryId($this->getCategoryId())
                  ->setCreatorId($user->getUserId())
                  ->setSubject($subject)
                  ->setDescription($content)
                  ->setPriority($priority)
                  ->setState(Bugify_Issue::STATE_OPEN);
                
                $issues = new Bugify_Issues();
                $id = $issues->save($i);
                
                if (count($attachments) > 0)
                {
                    //Reload the issue
                    $i = $issues->fetch($id);
                    $config = Zend_Registry::get('config');
                    
                    foreach ($attachments as $key => $val)
                    {
                        //Work out the folder to store the file in
                        $folder    = $i->getIssueId();
                        $filename  = md5($folder.$val['filename'].time());
                        $full_path = $config->base_path.$config->storage->attachments.'/'.$folder;
                        $file_path = $full_path.'/'.$filename;
                        
                        if (!is_dir($full_path))
                        {
                            mkdir($full_path, 0755, true);
                        }
                        
                        //Decode the content
                        if ($val['encoding'] == 'base64')
                        {
                            $content = base64_decode($val['content']);
                        }
                        else
                        {
                            $content = $val['content'];
                        }
                        
                        //Save the file
                        if (file_put_contents($file_path, $content))
                        {
                            //Work out the filesize
                            $size = filesize($file_path);
                            
                            //Create a new attachment object
                            $a = new Bugify_Issue_Attachment();
                            $a->setName($val['filename'])
                              ->setUserId($user->getUserId())
                              ->setFilename($filename)
                              ->setFilesize($size)
                              ->setState(Bugify_Issue_Attachment::STATE_ACTIVE);
                            
                            //Save the attachment details
                            $i->saveAttachment($a);
                        }
                        else
                        {
                            throw new Ui_Exception(sprintf('Unable to store the uploaded file "%s".', $val['name']));
                        }
                        
                    }
                }
                
                $count++;
            }
        }
        
        return $count;
    }
    
    private function _splitFromAddress($from)
    {
        //eg, Joe Blogs <me@example.com>
        $address = substr($from, strpos($from, '<')+1);
        $address = substr($address, 0, strpos($address, '>'));
        
        return $address;
    }
    
    public function toArray()
    {
        $data = array(
           'id'           => $this->getMailboxId(),
           'created'      => $this->getCreated(),
           'updated'      => $this->getUpdated(),
           'last_checked' => $this->getLastChecked(),
           'name'         => $this->getName(),
           'host'         => $this->getHost(),
           'port'         => $this->getPort(),
           'username'     => $this->getUsername(),
           'project_id'   => $this->getProjectId(),
           'category_id'  => $this->getCategoryId(),
           'type'         => $this->getType(),
           'encryption'   => $this->getEncryption(),
           'state'        => $this->getState(),
        );
        
        return $data;
    }
}
