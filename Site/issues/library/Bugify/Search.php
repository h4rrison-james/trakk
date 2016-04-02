<?php

class Bugify_Search
{
    //Sourced from: http://armandbrahaj.blog.al/2009/04/14/list-of-english-stop-words/
    private $_stopWords = array('a', 'about', 'above', 'above', 'across', 'after', 'afterwards', 'again', 'against', 'all', 'almost', 'alone', 'along', 'already', 'also', 'although', 'always', 'am', 'among', 'amongst', 'amoungst', 'amount',  'an', 'and', 'another', 'any', 'anyhow', 'anyone', 'anything', 'anyway', 'anywhere', 'are', 'around', 'as',  'at', 'back', 'be', 'became', 'because', 'become', 'becomes', 'becoming', 'been', 'before', 'beforehand', 'behind', 'being', 'below', 'beside', 'besides', 'between', 'beyond', 'bill', 'both', 'bottom', 'but', 'by', 'call', 'can', 'cannot', 'cant', 'co', 'con', 'could', 'couldnt', 'cry', 'de', 'describe', 'detail', 'do', 'done', 'down', 'due', 'during', 'each', 'eg', 'eight', 'either', 'eleven', 'else', 'elsewhere', 'empty', 'enough', 'etc', 'even', 'ever', 'every', 'everyone', 'everything', 'everywhere', 'except', 'few', 'fifteen', 'fify', 'fill', 'find', 'fire', 'first', 'five', 'for', 'former', 'formerly', 'forty', 'found', 'four', 'from', 'front', 'full', 'further', 'get', 'give', 'go', 'had', 'has', 'hasnt', 'have', 'he', 'hence', 'her', 'here', 'hereafter', 'hereby', 'herein', 'hereupon', 'hers', 'herself', 'him', 'himself', 'his', 'how', 'however', 'hundred', 'ie', 'if', 'in', 'inc', 'indeed', 'interest', 'into', 'is', 'it', 'its', 'itself', 'keep', 'last', 'latter', 'latterly', 'least', 'less', 'ltd', 'made', 'many', 'may', 'me', 'meanwhile', 'might', 'mill', 'mine', 'more', 'moreover', 'most', 'mostly', 'move', 'much', 'must', 'my', 'myself', 'name', 'namely', 'neither', 'never', 'nevertheless', 'next', 'nine', 'no', 'nobody', 'none', 'noone', 'nor', 'not', 'nothing', 'now', 'nowhere', 'of', 'off', 'often', 'on', 'once', 'one', 'only', 'onto', 'or', 'other', 'others', 'otherwise', 'our', 'ours', 'ourselves', 'out', 'over', 'own', 'part', 'per', 'perhaps', 'please', 'put', 'rather', 're', 'same', 'see', 'seem', 'seemed', 'seeming', 'seems', 'serious', 'several', 'she', 'should', 'show', 'side', 'since', 'sincere', 'six', 'sixty', 'so', 'some', 'somehow', 'someone', 'something', 'sometime', 'sometimes', 'somewhere', 'still', 'such', 'system', 'take', 'ten', 'than', 'that', 'the', 'their', 'them', 'themselves', 'then', 'thence', 'there', 'thereafter', 'thereby', 'therefore', 'therein', 'thereupon', 'these', 'they', 'thickv', 'thin', 'third', 'this', 'those', 'though', 'three', 'through', 'throughout', 'thru', 'thus', 'to', 'together', 'too', 'top', 'toward', 'towards', 'twelve', 'twenty', 'two', 'un', 'under', 'until', 'up', 'upon', 'us', 'very', 'via', 'was', 'we', 'well', 'were', 'what', 'whatever', 'when', 'whence', 'whenever', 'where', 'whereafter', 'whereas', 'whereby', 'wherein', 'whereupon', 'wherever', 'whether', 'which', 'while', 'whither', 'who', 'whoever', 'whole', 'whom', 'whose', 'why', 'will', 'with', 'within', 'without', 'would', 'yet', 'you', 'your', 'yours', 'yourself', 'yourselves', 'the', 'want', 'wants', 'control', 'panel');
    
    //Pagination
    private $_pagination_limit = 15;
    private $_pagination_page  = 1;
    private $_pagination_total = 0;
    
    public function __construct($path) {
        $this->_path = $path;
    }
    
    private function _getIndex() {
        if (!isset($this->_index)) {
            try {
                //Check that the index path exists
                $this->_index = Zend_Search_Lucene::open($this->_path);
            } catch (Zend_Search_Lucene_Exception $e) {
                $this->_createIndex($this->_path);
            }
        }
        
        return $this->_index;
    }
    
    private function _getPath() {
        return $this->_path;
    }
    
    private function _createIndex() {
        Zend_Search_Lucene_Storage_Directory_Filesystem::setDefaultFilePermissions(0666);
        $search = Zend_Search_Lucene::create($this->_getPath());
        
        $this->_index = $search;
        
        //Add a short-words filter to the search index
        $shortWordsFilter = new Zend_Search_Lucene_Analysis_TokenFilter_ShortWords();
        
        //Add a stop-words filter
        $stopWordsFilter = new Zend_Search_Lucene_Analysis_TokenFilter_StopWords($this->_stopWords);
        
        $analyzer = new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive();
        $analyzer->addFilter($shortWordsFilter);
        $analyzer->addFilter($stopWordsFilter);
        
        Zend_Search_Lucene_Analysis_Analyzer::setDefault($analyzer);
    }
    
    public function getNumDocs()
    {
        return $this->_getIndex()->numDocs();
    }
    
    public function getSizeOnDisk()
    {
        $size = 0;
        
        if (is_dir($this->_path))
        {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->_path)) as $file)
            {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }
    
    public function addIssueDocument(Bugify_Issue $issue)
    {
        if (!$issue instanceof Bugify_Issue)
        {
            throw new Bugify_Exception('The object must be an instance of Bugify_Issue.');
        }
        
        //Now add the issue to the search index
        $doc = new Zend_Search_Lucene_Document();
        
        $doc->addField(Zend_Search_Lucene_Field::keyword('issueid', $issue->getIssueId(), 'utf-8'));
        $doc->addField(Zend_Search_Lucene_Field::unStored('subject', $issue->getSubject()));
        $doc->addField(Zend_Search_Lucene_Field::unStored('description', $issue->getDescription(), 'utf-8'));
        
        //Check for comments
        $comments = $issue->getComments();
        
        foreach ($comments as $comment)
        {
            $doc->addField(Zend_Search_Lucene_Field::unStored('comment'.$comment->getCommentId(), $comment->getComment(), 'utf-8'));
        }
        
        //Check for attachments
        $attachments = $issue->getAttachments();
        
        foreach ($attachments as $attachment)
        {
            $doc->addField(Zend_Search_Lucene_Field::unStored('attachment'.$attachment->getAttachmentId(), $attachment->getName(), 'utf-8'));
        }
        
        //Add the document to the search index
        $this->_getIndex()->addDocument($doc);
        $this->_getIndex()->commit();
    }
    
    public function updateIssueDocument(Bugify_Issue $issue)
    {
        if (!$issue instanceof Bugify_Issue)
        {
            throw new Bugify_Exception('The object must be an instance of Bugify_Issue.');
        }
        
        $result = $this->_getIndex()->find('issueid:'.$issue->getIssueId());
        $found  = false;
        
        foreach ($result as $doc)
        {
            if ($doc->issueid == $issue->getIssueId())
            {
                //Delete the document
                $this->_getIndex()->delete($doc->id);
                
                //Now re-add it
                $this->addIssueDocument($issue);
                
                $found = true;
                
                break;
            }
        }
        
        if ($found === false)
        {
            //Add this issue to index because it doesnt seem to be there
            $this->addIssueDocument($issue);
        }
        
        $this->optimise();
    }
    
    public function deleteAll()
    {
        $count = $this->_getIndex()->count();
        
        for ($i = 0; $i < $count; $i++)
        {
            $this->_getIndex()->delete($i);
        }
        
        $this->optimise();
    }
    
    public function optimise()
    {
        $this->_getIndex()->optimize();
    }
    
    public function getPaginationLimit()
    {
        return $this->_pagination_limit;
    }
    
    public function getPaginationPage()
    {
        return $this->_pagination_page;
    }
    
    public function getTotal()
    {
        return $this->_pagination_total;
    }
    
    public function getTotalPages()
    {
        return ($this->_pagination_total > 0) ? ceil($this->_pagination_total / $this->_pagination_limit) : 1;
    }
    
    public function setPaginationLimit($val)
    {
        $this->_pagination_limit = (int)$val;
        
        return $this;
    }
    
    public function setPaginationPage($val)
    {
        $this->_pagination_page = (int)$val;
        
        return $this;
    }
    
    public function searchIndex($string='')
    {
        $result = $this->_getIndex()->find($string);
        $issues = array();
        
        if (is_array($result) && count($result) > 0)
        {
            /**
             * Work out where to start the results.
             * Zend_Search_Lucene doesnt support proper pagination,
             * but supposedly it is very cheap to get results, it is
             * only expensive to work out the score - it doesnt
             * do much until you start loading details about the document.
             */
            $start = (($this->_pagination_page * $this->_pagination_limit) - $this->_pagination_limit);
            $count = 0;
            $i     = 0;
            
            foreach ($result as $hit)
            {
                if ($i >= $start && $count < $this->_pagination_limit)
                {
                    $issue = array(
                       'score'    => $hit->score,
                       'issue_id' => $hit->issueid,
                    );
                    
                    $issues[] = $issue;
                    
                    $count++;
                }
                elseif ($count > $this->_pagination_limit)
                {
                    break;
                }
                
                $i++;
            }
            
            //Get the total number of results for pagination
            $this->_pagination_total = count($result);
        }
        
        return $issues;
    }
}
