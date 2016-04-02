<?php

class Zend_View_Helper_Pagination extends Zend_View_Helper_Abstract
{
    public function Pagination($uri, $page, $limit, $total)
    {
        $html = '';
        
        //Work out how many pages there are in total
        $pages = ($total > 0) ? ceil($total / $limit) : 1;
        
        if ($pages > 1)
        {
            $html .= '<div class="pagination">';
            
            //Just in case there are hundreds of pages
            $block_page_limit = 10;
            
            //Work out the previous page
            if ($page > 1)
            {
                //Display the Previous page link
                $html .= '<a href="'.sprintf($uri, ($page-1)).'" class="previous">&larr; Previous</a>';
            }
            
            //Display the pagination
            for ($i=1; $i<=$pages; $i++)
            {
                if ($page == $i)
                {
                    $html .= '<span class="current">'.$i.'</span>';
                }
                else
                {
                    $html .= '<a href="'.sprintf($uri, $i).'" title="Go to page '.$i.'">'.$i.'</a>';
                }
                
                //The following code needs reviewing (this whole thing was copied from some code i wrote years ago)
                if ($pages > $block_page_limit)
                {
                    //Set variables
                    $goto_middle = false;
                    $goto_end = false;
                    $first_end_page = 2;
                    $middle_begin_page = ($page - 2);
                    $middle_end_page = ($page + 1);
                    $end_begin_page = ($pages - 2);
                    
                    if ($page == ($first_end_page+2))
                    {
                        $first_end_page--;
                        $middle_begin_page--;
                    }
                    
                    if ($i > $first_end_page)
                    {
                        if ($i <= $middle_begin_page)
                        {
                            $goto_middle = true;
                        }
                        elseif ($i >= $middle_end_page && $i < $end_begin_page)
                        {
                            $goto_end = true;
                        }
                    }
                
                    if ($goto_middle)
                    {
                        //Jump ahead to the current page (minus 1)
                        $i = $middle_begin_page;
                        
                        //Display some dots to indicate that there are more pages in between
                        $html .= '...';
                    }
                    
                    if ($goto_end)
                    {
                        //Jump ahead to the last few pages
                        $i = $end_begin_page;
                        
                        //Display some dots to indicate that there are more pages in between
                        $html .= '...';
                    }
                }
            }
            
            //Calculate the next page
            if (($page+1) <= $pages)
            {
                //Display the Next page link
                $html .= '<a href="'.sprintf($uri, ($page+1)).'" class="next">Next &rarr;</a>';
            }
            
            $html .= '</div>';
        }
        
        return $html;
    }
}
