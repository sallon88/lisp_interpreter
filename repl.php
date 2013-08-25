<?php
require './scheme.php';
$GE = global_environment();
while(true)
{
	$string = fgets(STDIN);
	echo '==> ';
	echo lisp_eval(trim($string));
	echo "\n";
}
