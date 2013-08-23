<?php
require './interpreter.php';
$GE = global_environment();

$string = '
	(define a 3)
	(define twice (lambda (x) (* x  x)))
	(twice a)
	(if #f (twice a) 2)
	(quote 3)
	(cond ((> 1 2) 1)
			((> 1 0) 2)
			(else 3))
	(define addn (lambda (n) (lambda(x) (+ x n))))
	((addn 3) 3)
	';

$expressions = parser(analyzer($string));

foreach($expressions as $expression)
{
	$a =  evaluate($expression, $GE);
	echo $a . "\n";
}
