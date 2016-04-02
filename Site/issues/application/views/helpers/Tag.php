<?php
class Zend_View_Helper_Tag extends Zend_View_Helper_Abstract
{
    public function Tag($colour, $text, $hint='')
    {
        return sprintf('<span class="tag tag-%s" title="%s">%s</span>', $colour, $hint, $text);
    }
}
