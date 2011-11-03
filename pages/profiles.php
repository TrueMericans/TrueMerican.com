<?php

class profiles extends page
{
   public $template;
   public $title               = "TRUEMERICAN.COM - Built by 'Mericans For 'Mericans";
   public $section_description = "'Merican Profiles";
   public $page_head           = "";
   public $section_menus;
   public $use_side_bar        = true;
   public $side_bar;
   public $content;
   public $footer;

   function __construct()
   {
      $DATABASE = parent::__construct();
	  
	  $DATABASE->Select("SELECT * FROM profiles");
  
      $this->content        = <<<CONTENT
         <h2>'Merican Profiles...</h2>
         <h3>User Data</h3>
		 <ul>
		    <!--MERICAN_DATA-->
		 </ul>
		 <br/>
		 <br/>
		 <br/>
		 <br/>
		 <br/>
CONTENT;

      $replacements = array('<!--MERICAN_DATA-->'=>'<li>User #'.implode('</li><li>User #',$DATABASE->results['USER_ID']).'</li>');
      $this->content = parent::fill($replacements,$this->content);
   }

   function render ()
   {
      //or write your own version of render instead of calling parent
      parent::render();
   }
}

?>