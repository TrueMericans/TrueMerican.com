<?php

class widgets
{
   public function anchor($list)
   {
      if (empty($list['text']))
      {
         die('no text on anchor');
      }
      $text = $list['text'];
      unset($list['text']);
      $attribs = '';
      foreach ($list as $attribute=>$attributeVal)
      {
         $attribs .= ' ' . $attribute . '="' . $attributeVal . '" ';
      }
      return "<a $attribs>$text</a>";
   }
}

?>