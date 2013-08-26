this is a minimal implementation of scheme (a lisp dialect) in php language.

special forms:

	define lambda if cond let quote set! 
	
primitive procedures:

	+ - * / > < >= <= eq?

usage:

	php repl.php

example:

	(define a 2)
	==> 
	a
	==> 2
	(define (twice x) (* x x))
	==> 
	(twice 2)
	==> 4
	(twice a)
	==> 4
	(define (quad x) (twice (twice x)))
	==> 
	(quad 2)
	==> 16
	(define (make-counter x) (lambda() (set! x (+ x 1)) x))
	==> 
	(define c1 (make-counter 1))
	==> 
	(c1)
	==> 2
	(c1)
	==> 3
	(c1)
	==> 4
	(c1)
	==> 5
