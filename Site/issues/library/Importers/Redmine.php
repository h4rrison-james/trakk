<?php

/**
 * Import data from Redmine.
 * @url http://www.redmine.org
 * 
 * Note: Does not support importing attachments because the Redmine Api
 * does not support listing/fetching attachments.
 */
class Importers_Redmine
{
	private $_url      = '';
	private $_username = '';
	private $_password = '';
	
	public function __construct()
	{
		
	}
	
	public function setUrl($val)
	{
		$this->_url = $val;
		
		return $this;
	}
	
	public function setUsername($val)
	{
		$this->_username = $val;
		
		return $this;
	}
	
	public function setPassword($val)
	{
		$this->_password = $val;
		
		return $this;
	}
	
	public function import()
	{
		set_time_limit(0);
		
		//Get issues
		$this->_getIssues();
		
	}
	
	private function _getCurlResource()
	{
		$c = new Bugify_Curl();
		
		//Check if we have a username and password
		if (strlen($this->_username) > 0 && strlen($this->_password) > 0)
		{
			$post = array(
			   'username' => $this->_username,
			   'password' => $this->_password,
			);
			
			$c->setHttpMethod('post')
			  ->setPostData($post);
		}
		
		return $c;
	}
	
	private function _parseJsonBody($body)
	{
		if (strlen($body) > 0)
		{
			//Convert json to array
			$data = json_decode($body, true);
			
			if ($data === false)
			{
				throw new Importers_Exception('Invalid json data.');
			}
			
			return $data;
		}
		else
		{
			throw new Importers_Exception('Empty response.');
		}
	}
	
	private function _getInternalUser($name)
	{
		/**
		 * Keep a list of all users.  Match on the users full name.
		 * If the user cannot be found, add it to the db (and our list
		 * here), then return the id.
		 */
		
		
		
	}
	
	private function _getUsers()
	{
		
	}
	
	private function _getProjects()
	{
		
	}
	
	private function _getIssues()
	{
		$limit  = 5;
		$offset = 0;
		$count  = 0;
		$total  = 100; //The total will be reset after the first fetch
		
		//Load cURL
		$c = $this->_getCurlResource();
		
		do
		{
			//Work out the URL
			$url = sprintf('%s/issues.json?limit=%s&offset=%s', $this->_url, $limit, $offset);
			
			$c->setUrl($url)
			  ->request();
			
			if ($c->isSuccess())
			{
				$data = $this->_parseJsonBody($c->getBody());
				
				/*
				echo '<pre>';
				print_r($data);
				echo '</pre>';
				*/
				
				//Get the total number of issues
				$total = $data['total_count'];
				
				foreach ($data['issues'] as $key => $val)
				{
					//Fetch the full data for this issue
					$url = sprintf('%s/issues/%s.json?include=journals', $this->_url, $val['id']);
					
					$c->setUrl($url)
					  ->request();
					
					if ($c->isSuccess())
					{
						$issue = $this->_parseJsonBody($c->getBody());
						
						echo '<pre>';
						print_r($issue);
						echo '</pre>';
					}
					
					$i = new Bugify_Issue();
					$i->setCreated(strtotime($val['created_on']))
					  ->setUpdated(strtotime($val['updated_on']))
					  ->setSubject($val['subject'])
					  ->setDescription($val['description']);
					
					echo '<pre>';
					print_r($i);
					echo '</pre>';
					
					$count++;
				}
				
				//Update the offset
				$offset += $limit;
				
				
				if ($count >= 5)
				{
					//temp
					break;
				}
			}
			else
			{
				echo '<pre>';
				print_r($c);
				echo '</pre>';
			}
		}
		while ($count < $total);
	}
}
