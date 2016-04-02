<?php

/**
 * Import data from Mantis.
 * @url http://www.mantisbt.org
 */
class Importers_Mantis
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
		
	}
	
	private function _getUsers()
	{
		
	}
	
	private function _getProjects()
	{
		
	}
	
	private function _getIssues()
	{
		
	}
}
