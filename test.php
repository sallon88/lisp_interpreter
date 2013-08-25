<?php
require './interpreter.php';
$GE = global_environment();

$string = '
	(define a 3)
	(define (twice a) (* a a))
	(twice a)
	(if #f (twice a) 2)
	(quote 3)
	(cond ((> 1 2) 1)
			((> 1 0) 2)
			(else 3))
	(define (addn n) (lambda(x) (+ x n)))
	((addn 3) 4)

	(define (cons x y)
		(lambda (msg)
			(if (eq? msg \'car) x y)))
	(define (car pair) (pair \'car))
	(define (cdr pair) (pair \'cdr))
	(car (cons 1 2))
	(let ((a 1) (b 2)) (+ a b)
	';

$expressions = parser(analyzer($string));

foreach($expressions as $expression)
{
	$a =  evaluate($expression, $GE);
	echo $a . "\n";
}
