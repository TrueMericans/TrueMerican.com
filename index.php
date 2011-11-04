<?php

require 'includes/core.php';

//From mod_rewrite rule see .htaccess for regex which will pass page=xxx to the folder name of the request
//
if (!empty($_REQUEST['page']))
{
	$className = $_REQUEST['page'];
}
else
{
	$className = 'index';
}

//dynamically instantiate the page object
$page = new $className();

if ($page->use_side_bar)
{
	$page->side_bar = "
		<div id=\"contentright\">
		$page->side_bar
		</div>
	";
}

$page->render();

?>
