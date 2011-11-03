<?php
class page
{
   public $template;
   public $title               = "TRUEMERICAN.COM - Built by 'Mericans For 'Mericans";
   public $section_description = "TRUE 'MERICAN.COM";
   public $page_head           = "";
   public $section_menus;
   public $use_side_bar        = true;
   public $side_bar;
   public $content;
   public $footer;
   public $widgets;

   function __construct()
   {
      global $DATABASE;
      $this->widgets             = new widgets();
      $this->template            =  APPLICATION_PATH."templates/starznstripes/main.html";
      $this->copyright           = date("Y");

      $this->footer              =
      "
       <p>True 'Merican.com &copy; {$this->copyright} 'Merica<br />
       Design by - ".$this->widgets->anchor(array('href'=>'http://emasai.com','target'=>'_blank','text'=>'eMasai.com'))."
       </p>
      ";
      $this->section_menus       =
      '
         <div id="menu">
              <div id="menu_center">
                 <ul>
                  <li><a href="/">Home</a></li>
                  <li><a href="/profiles">Profiles</a></li>
                  <li><a href="#">Link</a></li>
                  <li><a href="#">Link</a></li>
                  <li><a href="#">Link</a></li>
                  <li><a href="#">Link</a></li>
                 </ul>
              </div>
            </div>
         </div>
      ';

      $this->side_bar       = <<<SIDEBAR
            <h2>Useful links</h2>
            <div class="wrapper">
               <div class="buttons">
                  <a href="http://www.opendesigns.org/preview/?template=773">Lily</a>
                  <a href="http://www.opendesigns.org/preview/?template=797">Pastel</a>
                  <a href="http://www.opendesigns.org/preview/?template=825">Ice Blue</a>
               </div>
            </div>
            <h3>Header</h3>
            <p>
          Nunc pede sem, egestas vel, auctor sagittis, imperdiet posuere, augue. Curabitur condimentum.<br /> <a href="#">This is a link</a>.
            </p>
             <p>
           Fusce mattis facilisis nunc. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Lorem ipsum dolor sit amet, consectetuer adipiscing elit.<br />  <a href="#">This is a link</a>.
            </p>
SIDEBAR;

      $this->content        = <<<CONTENT
         <h2>Built by 'Mericans For 'Mericans</h2>
         <p>
         STARZ-N-STRIPES is a free XHTML and CSS based website template, which validates with W3C. This template is released under the <a href="http://creativecommons.org/licenses/by-nc-sa/3.0/">Creative Commons 3 Licence</a>. You are free to use it for personal or commercial use.
         </p>

         <p>
         You must give credit fot the work that has been put into this template by leaving the &quot;design by&quot; link in the footer. All images used were created by me in Photoshop. If you would like to share with me what you have done with this template, email me through my website <a href="http://emasai.com">emasai.com</a>
         </p>
         <p>
         I hope you enjoy my work...
         </p>

         <h2>Header for blockquote</h2>
         <blockquote>
         <p>
         This is a blockquote.<br /> Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Fusce dignissim auctor libero. In eget mi.
         </p>
         </blockquote>
         <p>
         This template has been tested on a PC in the following browsers. Shown using an unordered list:
         </p>
         <ul>
         <li>IE6</li>
         <li>IE7</li>
         <li>Firefox 1.5 </li>
         <li>Opera 9</li>
         </ul>
         <p>
         And also on a MAC in the following browsers. Shown using an ordered list:
         </p>
         <ol>
            <li>Safari 2.0.4</li>
            <li>Firefox 2.0.0.4</li>
            <li>Opera 9</li>
            <li>and even IE5.2...</li>
         </ol>
CONTENT;
      return $DATABASE;
   }

   function render()
   {
      $replacements = array(
         '<!--PAGE_HEAD-->'            => $this->page_head ,
         '<!--TITLE-->'                => $this->title ,
         '<!--SECTION_DESCRIPTION-->'  => $this->section_description ,
         '<!--SECTION_MENUS-->'        => $this->section_menus ,
         '<!--SIDEBAR-->'              => $this->side_bar ,
         '<!--PAGE_CONTENT-->'         => $this->content ,
         '<!--FOOTER-->'               => $this->footer ,
      );
      echo $this->fill($replacements,file_get_contents($this->template));
   }
   
   function fill($replacements,$template)
   {
      return str_replace(array_keys($replacements),$replacements,$template);
   }
}
?>