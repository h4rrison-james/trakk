<?php

class AssetsController extends Ui_Controller_Action
{
    public function init()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
    }
    
    public function gravatarAction()
    {
        try
        {
            $size  = $this->_getParam('size');
            $email = $this->_getParam('email');
            
            //Work out the path to the default image
            $default_path = ($size >= 32) ? '/images/icons/32/user-unknown.png' : '/images/icons/16/user-unknown.png';
            
            /**
             * Check if we have a cached copy of this gravatar.
             * We cache copies locally because its usually much faster
             * than fetching from the gravatar site.
             */
            $md5      = md5(strtolower(trim($email)));
            $cache_id = sprintf('Gravatar_%s_%s', $md5, md5($size));
            $base64   = '';
            $type     = 'png';
            
            if (($base64 = $this->cache->load($cache_id)) !== false)
            {
                //Loaded base64 data from cache, get the content-type
                $tags  = $this->cache->getTags($cache_id);
                $types = array(
                   'png',
                   'jpeg',
                   'gif',
                );
                
                foreach ($tags as $tag)
                {
                    if (in_array($tag, $types))
                    {
                        $type = $tag;
                        break;
                    }
                }
            }
            else
            {
                //Fetch the image
                $default = Bugify_Host::getHostname(true).$default_path;
                $url     = sprintf('http://www.gravatar.com/avatar/%s?d=%s&s=%s', $md5, urlencode($default), $size);
                
                $client = new Zend_Http_Client();
                $client->setUri($url);
                $client->setConfig(array('maxredirects' => 0, 'timeout' => 30));
                $client->setHeaders('User-Agent', $_SERVER['HTTP_USER_AGENT']);
                $client->setHeaders('Referer',    Bugify_Host::getHostname(true));
                
                $response = $client->request(Zend_Http_Client::GET);
                
                $content_type = $response->getHeader('Content-type');
                
                switch ($content_type)
                {
                    case 'image/jpeg':
                    case 'image/jpg':
                        $type = 'jpeg';
                        break;
                    case 'image/gif':
                        $type = 'gif';
                        break;
                    default:
                        $type = 'png';
                }
                
                if ($response->isSuccessful())
                {
                    $base64 = base64_encode($response->getRawBody());
                }
            }
            
            if (strlen($base64) == 0)
            {
                //Use the local default avatar
                $config = Zend_Registry::get('config');
                $path   = $config->public_path.$default_path;
                
                if (file_exists($path))
                {
                    $base64 = base64_encode(file_get_contents($path));
                    $type   = 'png';
                }
                else
                {
                    throw new Ui_Exception('Default avatar does not exist.');
                }
            }
            
            if (strlen($base64) > 0)
            {
                //Save the image to cache
                $lifetime = 43200;
                
                $this->cache->save($cache_id, $base64, array('Gravatar', $type), $lifetime);
            }
            
            //Now display the image
            $this->getResponse()->setHeader('Content-type', 'image/'.$type);
            $this->getResponse()->setHeader('Content-disposition', 'inline; filename="'.$md5.'.png"');
            $this->getResponse()->setBody(base64_decode($base64));
        }
        catch (Exception $e)
        {
            //Not entirely sure what the best action here is
            throw $e;
        }
    }
    
    public function attachmentAction()
    {
        try
        {
            $issue_id = $this->_getParam('issue_id');
            $filename = $this->_getParam('filename');
            
            //Load the issue details
            $i = new Bugify_Issues();
            $issue = $i->fetch($issue_id);
            
            //Load the attachments for this issue
            $attachments = $issue->getAttachments();
            $found       = false;
            
            if (is_array($attachments) && count($attachments) > 0)
            {
                foreach ($attachments as $attachment)
                {
                    if ($attachment->getFilename() == $filename)
                    {
                        //This is the attachment we're after
                        $found = true;
                        break;
                    }
                }
            }
            
            if ($found === true && $attachment instanceof Bugify_Issue_Attachment)
            {
                //Work out the full path to the file
                $config    = Zend_Registry::get('config');
                $full_path = $config->base_path.$config->storage->attachments.'/'.$issue_id.'/'.$attachment->getFilename();
                
                if (is_readable($full_path))
                {
                    //Load the file contents
                    $contents = file_get_contents($full_path);
                    
                    //Send it to the user
                    $this->getResponse()->setHeader('Content-type', 'application/octet-stream');
                    $this->getResponse()->setHeader('Content-length', $attachment->getFilesize());
                    $this->getResponse()->setHeader('Content-disposition', 'attachment; filename="'.$attachment->getName().'"');
                    
                    
                    $this->getResponse()->setBody($contents);
                }
                else
                {
                    throw new Ui_Exception(sprintf('The file "%s" cannot be found.', $attachment->getName()));
                }
            }
        }
        catch (Exception $e)
        {
            //Not entirely sure what the best action here is
            throw $e;
        }
    }
    
    public function chartAction() {
        /**
         * Get a Google Charts image.
         */
        try {
            $chart  = $this->_getParam('name');
            $width  = $this->_getParam('width');
            $height = $this->_getParam('height');
            $size   = sprintf('%sx%s', $width, $height);
            
            //Work out the cache details
            $md5      = md5(strtolower(trim($chart)));
            $cache_id = sprintf('Chart_%s_%s', $md5, md5($size));
            $base64   = '';
            
            //$this->cache->remove($cache_id);
            
            if (($base64 = $this->cache->load($cache_id)) === false) {
                //Work out the Google Charts URL
                $rand = md5(uniqid(rand(), true));
                $url  = sprintf('https://chart.googleapis.com/chart?chid=%s', $rand);
                
                if ($chart == 'overviewOpenClosed') {
                    //Overview of open vs closed issues
                    $chartType      = 'ls';
                    $legend         = 'Opened|Resolved';
                    $legendPosition = 'b';
                    $colours        = '9DB1B9,CF5A51';
                    $createdData    = array();
                    $resolvedData   = array();
                    $min = 0;
                    $max = 0;
                    
                    //Prepare the data arrays
                    for ($i=0; $i<30; $i++) {
                        $createdData[$i]  = 0;
                        $resolvedData[$i] = 0;
                    }
                    
                    //Load the issues created/closed in the last 30 days
                    $i = new Bugify_Issues();
                    
                    //Issues created in the last 30 days
                    $filter = $i->filter();
                    $filter->setCreatedFrom(date('c', strtotime('-30 days')));
                    $created = $i->fetchAll($filter);
                    
                    if (is_array($created) && count($created) > 0) {
                        foreach ($created as $issue) {
                            //Work out which day (of the last 30 days) this issue was created
                            $diff = (time() - strtotime($issue->getCreated())) / 86400;
                            $day  = ceil(30 - $diff);
                            
                            $createdData[$day] = (isset($createdData[$day])) ? $createdData[$day]+1 : 1;
                            
                            if ($createdData[$day] > $max) {
                                $max = $createdData[$day];
                            }
                        }
                    }
                    
                    //Issues resolved in the last 30 days
                    $filter = $i->filter();
                    $filter->setResolvedFrom(date('c', strtotime('-30 days')));
                    $resolved = $i->fetchAll($filter);
                    
                    if (is_array($resolved) && count($resolved) > 0) {
                        foreach ($resolved as $issue) {
                            //Work out which day (of the last 30 days) this issue was resolved
                            $diff = (time() - strtotime($issue->getCreated())) / 86400;
                            $day  = ceil(30 - $diff);
                            
                            $resolvedData[$day] = (isset($resolvedData[$day])) ? $resolvedData[$day]+1 : 1;
                            
                            if ($resolvedData[$day] > $max) {
                                $max = $resolvedData[$day];
                            }
                        }
                    }
                    
                    //Add the data to the charts string
                    $data = 't:';
                    
                    foreach ($createdData as $key => $val) {
                        $data .= $val.',';
                    }
                    
                    $data = substr($data, 0, -1);
                    $data .= '|';
                    
                    foreach ($resolvedData as $key => $val) {
                        $data .= $val.',';
                    }
                    
                    $data = substr($data, 0, -1);
                    
                    //Give a little buffer to the max
                    $max++;
                } else {
                    throw new Bugify_Exception('Invalid chart type.');
                }
                
                
                $client = new Zend_Http_Client();
                $client->setUri($url);
                $client->setConfig(array('maxredirects' => 0, 'timeout' => 30));
                $client->setHeaders('User-Agent', $_SERVER['HTTP_USER_AGENT']);
                $client->setHeaders('Referer',    Bugify_Host::getHostname(true));
                
                //Set the post params
                $client->setParameterPost('cht', $chartType); //Chart type
                $client->setParameterPost('chs', $size);      //Size
                $client->setParameterPost('chd', $data);      //Data
                $client->setParameterPost('chdl', $legend);   //Legend
                $client->setParameterPost('chdlp', $legendPosition);   //Legend position
                $client->setParameterPost('chds', $min.','.$max);   //Chart scale
                $client->setParameterPost('chco', $colours);   //Colours
                $client->setParameterPost('chf', 'bg,s,00000000');   //Transparent background
                
                $response = $client->request(Zend_Http_Client::POST);
                
                if ($response->isSuccessful()) {
                    $base64 = base64_encode($response->getRawBody());
                }
            }
            
            if (strlen($base64) > 0) {
                //Save the image to cache
                $lifetime = 43200;
                
                $this->cache->save($cache_id, $base64, array('Chart'), $lifetime);
            }
            
            //Now display the image
            $this->getResponse()->setHeader('Content-type', 'image/png');
            //$this->getResponse()->setHeader('Content-disposition', 'inline; filename="'.$md5.'.png"');
            $this->getResponse()->setBody(base64_decode($base64));
        }
        catch (Exception $e)
        {
            //Not entirely sure what the best action here is
            throw $e;
        }
    }
}
