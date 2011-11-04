<?php   

require 'sql.php';

if (!isset($DATABASE) || ! is_object($DATABASE))
{
	try
	{
		$DATABASE = new sql();
		$DATABASE->Connect();
	}
	catch (Exception $e)
	{
		$valid = false;
	}
}
else
{
	$valid = true;
}

?>
