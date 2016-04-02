<?php

class Bugify_Curl
{
    private $_url         = '';
    private $_headers     = array();
    private $_post_data   = '';
    private $_http_method = 'get';
    private $_timeout     = 15;
    private $_save_path   = '';
    
    private $_request_time     = 0;
    private $_response_code    = null;
    private $_curl_info        = array();
    private $_response_headers = array();
    private $_response_body    = '';
    
    private $_error_number  = 0;
    private $_error_message = '';
    
    private $_valid_http_methods = array(
       'get',
       'post',
       'put',
       'head',
       'delete',
    );
    
    public function __construct()
    {}
    
    private function _getCurlResource()
    {
        //Build the headers
        $headers      = $this->_getRequestHeaders();
        $curl_headers = array();
        
        if (is_array($headers) && count($headers) > 0)
        {
            foreach ($headers as $key => $val)
            {
                //Format the headers for cURL
                $curl_headers[] = $key.': '.$val;
            }
        }
        
        unset($headers);
        
        //Load cURL
        $c = curl_init();
        
        //Set the request method
        switch ($this->_http_method)
        {
            case 'delete':
                curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'head':
                curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'HEAD');
                break;
            case 'put':
                curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($c, CURLOPT_POSTFIELDS,    $this->_post_data);
                break;
            case 'post':
                curl_setopt($c, CURLOPT_POST,          true);
                curl_setopt($c, CURLOPT_POSTFIELDS,    $this->_post_data);
                break;
        }
        
        $config = Zend_Registry::get('config');
        
        //Set the cURL options
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, $this->_timeout);
        curl_setopt($c, CURLOPT_NOSIGNAL,       true);
        curl_setopt($c, CURLOPT_URL,            $this->_url);
        curl_setopt($c, CURLOPT_HTTPHEADER,     $curl_headers);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_HEADER,         true);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($c, CURLOPT_CAINFO,         $config->base_path.'/library/Bugify/Curl/ca-bundle.crt');
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, true);
        
        return $c;
    }
    
    private function _getRequestHeaders()
    {
        if (strlen($this->_save_path) == 0)
        {
            //Set the accept-encoding header
            if (function_exists('gzinflate'))
            {
                $this->_headers['Accept-encoding'] = 'gzip';
            }
        }
        
        //Set the user-agent header
        $this->_headers['User-agent'] = 'Bugify/'.Bugify_Version::VERSION;
        
        return $this->_headers;
    }
    
    private function _processResponse($curl_info, $response)
    {
        //Save the request time
        $this->_request_time = $curl_info['total_time'];
        
        //Get the http response code
        $this->_response_code = $curl_info['http_code'];
        
        if ($this->_response_code >= 400 && $this->_response_code < 500)
        {
            switch ($this->_response_code)
            {
                case 401:
                    $this->_error_message = 'Unauthorised.';
                    break;
                case 404:
                    $this->_error_message = 'Not found.';
                    break;
                default:
                    $this->_error_message = sprintf('Bad request (%s).', $this->_response_code);
            }
        }
        
        if ($this->_response_code >= 500 && $this->_response_code < 600)
        {
            switch ($this->_response_code)
            {
                case 500:
                    $this->_error_message = 'Internal server error.';
                    break;
                default:
                    $this->_error_message = sprintf('Server error (%s).', $this->_response_code);
            }
        }
        
        //Save the cURL info
        $this->_curl_info = $curl_info;
        
        //Extract the headers and body etc
        $parts   = preg_split('|(?:\r?\n){2}|m', $response, 2);
        $headers = (isset($parts[0])) ? $parts[0] : '';
        $body    = (isset($parts[1])) ? $parts[1] : '';
        
        //Check for status code 100 Continue
        if (strlen($headers) <= strlen('HTTP/1.1 100 Continue'))
        {
            $header = strtolower($headers);
            
            if (strpos($header, '100 continue') !== false)
            {
                //Ignore this part and parse the remaining headers
                $parts   = preg_split('|(?:\r?\n){2}|m', $response, 3);
                $headers = (isset($parts[1])) ? $parts[1] : '';
                $body    = (isset($parts[2])) ? $parts[2] : '';
            }
        }
        
        $this->_response_headers = $this->_parseResponseHeaders($headers);
        $this->_response_body    = $this->_parseResponseBody($body);
    }
    
    private function _parseResponseHeaders($header_string='')
    {
        $headers = array();
        
        if ($header_string != '')
        {
            $lines = explode("\r\n", $header_string);
            
            if (is_array($lines) && count($lines) > 0)
            {
                $empty_lines_count = 0;
                
                foreach ($lines as $line)
                {
                    $line = trim($line, "\r\n");
                    
                    if ($line != '' && strpos($line, ':') !== false)
                    {    
                        //Each line should be in this format:
                        //Key: Value
                        $parts = explode(':', $line, 2);
                        
                        if (is_array($parts) && count($parts) > 0)
                        {
                            if (isset($parts[1]))
                            {
                                if (trim($parts[1]) != '')
                                {
                                    $headers[trim($parts[0])] = trim($parts[1]);
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $headers;
    }
    
    private function _parseResponseBody($body='')
    {
        if (strlen($body) > 0)
        {
            //Check if the response body is gzip'd
            if (isset($this->_response_headers['Content-Encoding']))
            {
                $encoding = $this->_response_headers['Content-Encoding'];
                
                switch (strtolower($encoding))
                {
                    case 'gzip':
                        //Decode the response
                        if (function_exists('gzinflate'))
                        {
                            $body = gzinflate(substr($body, 10));
                        }
                        else
                        {
                            throw new Bugify_Exception('Unable to inflate the deflated response.');
                        }
                        break;
                }
            }
        }
        
        return $body;
    }
    
    public function setUrl($val)
    {
        $this->_url = $val;
        
        return $this;
    }
    
    public function setHeaders($val)
    {
        if (is_array($val))
        {
            $this->_headers = $val;
        }
        else
        {
            throw new Bugify_Exception('Please specify the headers as an array.');
        }
        
        return $this;
    }
    
    public function setHttpMethod($val)
    {
        if (in_array($val, $this->_valid_http_methods))
        {
            $this->_http_method = $val;
        }
        else
        {
            throw new Bugify_Exception(sprintf('The specified Http request method is not valid.  Valid methods are: %s', implode(', ', $this->_valid_http_methods)));
        }
        
        return $this;
    }
    
    public function setPostData($val)
    {
        $this->_post_data = $val;
        
        return $this;
    }
    
    public function saveToLocalPath($path)
    {
        $this->_save_path = $path;
        
        return $this;
    }
    
    public function request()
    {
        $c = $this->_getCurlResource();
        
        //Check if we have a local path for saving the output
        if (strlen($this->_save_path) > 0)
        {
            //Open a file handle to the path
            $fh = fopen($this->_save_path, 'w');
            
            curl_setopt($c, CURLOPT_FILE, $fh);
            //curl_setopt($c, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($c, CURLOPT_HEADER, false);
        }
        
        //Send the request
        $response = curl_exec($c);
        
        //Get the info about this request
        $info = curl_getinfo($c);
        
        //Check for error
        $this->_error_number  = curl_errno($c);
        $this->_error_message = curl_error($c);
        
        //Tidy up the connection
        curl_close($c);
        
        if (isset($fh))
        {
            //Close the file handle
            fclose($fh);
        }
        
        //Process the response
        $this->_processResponse($info, $response);
    }
    
    public function getResponseCode()
    {
        return $this->_response_code;
    }
    
    public function isSuccess()
    {
        if ($this->_error_number == 0 && $this->getResponseCode() >= 200 && $this->getResponseCode() < 300)
        {
            return true;
        }
        
        return false;
    }
    
    public function getErrorMessage()
    {
        return $this->_error_message;
    }
    
    public function getRequestTime()
    {
        return $this->_request_time;
    }
    
    public function getCurlInfo()
    {
        return $this->_curl_info;
    }
    
    public function getHeaders()
    {
        return $this->_response_headers;
    }
    
    public function getBody()
    {
        return $this->_response_body;
    }
}
