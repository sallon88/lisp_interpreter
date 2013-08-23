<?php
function analyzer($string)
{
	$string = trim(str_replace(array('(', ')'), array(' ( ', ' ) '), $string));
	return preg_split('/\s+/', $string);
}

function parser(&$tokens)
{
	$expressions = array();
	while ($tokens)
	{
		$token = array_shift($tokens);

		if ($token === '(')
		{
			$expressions[] = parser($tokens);
		}
		elseif ($token === ')')
		{
			return $expressions;
		}
		else
		{
			$expressions[] = $token;
		}
	}

	return $expressions;
}

function evaluate($expression, &$environment)
{
	$expression_types = array(
		// primitives
		'number', 'string', 'boolean', 'variable', 

		//specail forms
		'if', 'define', 'quote', 'cond', 'lambda', 

		//application
		'application',
	);

	foreach ($expression_types as $expression_type)
	{
		$check_callback = "is_lisp_{$expression_type}";
		$evaluate_callback = "evaluate_{$expression_type}";
		if ($check_callback($expression))
		{
			return $evaluate_callback($expression, $environment);
		}
	}

	exit('undefined expression type');
}

function apply($procedure, $arguments)
{
	if (is_primitive_procedure($procedure))
	{
		return call_user_func_array($procedure, $arguments);
	}
	elseif (is_compound_procedure($procedure))
	{
		$new_environment = extend_environment($procedure['environment'], $procedure['parameters'], $arguments);
		return evaluate_sequence($procedure['body'], $new_environment);
	}
	else
	{
		exit('unknow procedure');
	}
}

/* check expression type start */
function is_lisp_number($expression)
{
	return is_numeric($expression);
}

function is_lisp_string($expression)
{
	return is_string($expression) && preg_match('/^".*"$/', $expression);
}

function is_lisp_boolean($expression)
{
	return is_string($expression) && ($expression === '#f' || $expression === '#t');
}

function is_lisp_variable($expression)
{
	return is_string($expression);
}

function is_lisp_if($expression)
{
	return tag_check($expression, 'if');
}

function is_lisp_define($expression)
{
	return tag_check($expression, 'define');
}

function is_lisp_cond($expression)
{
	return tag_check($expression, 'cond');
}

function is_lisp_lambda($expression)
{
	return tag_check($expression, 'lambda');
}

function is_lisp_quote($expression)
{
	return tag_check($expression, 'quote');
}

function is_lisp_application($expression)
{
	return is_array($expression);
}
/* check expression type end */

/* evaluate expression start*/
function evaluate_number($expression)
{
	return $expression;
}

function evaluate_string($expression)
{
	return substr($expression, 1, -1);
}

function evaluate_boolean($expression)
{
	return $expression === '#f' ? false : true;
}

function evaluate_variable($expression, &$environment)
{
	if (isset($environment['frame'][$expression]))
	{
		return $environment['frame'][$expression];
	}
	elseif ($environment['parent'])
	{
		return evaluate_variable($expression, $environment['parent']);
	}
	else
	{
		exit('undefined variable');
	}
}

function evaluate_define($expression, &$environment)
{
	$environment['frame'][$expression[1]] = evaluate($expression[2], $environment);
	return null;
}

function evaluate_lambda($expression, &$environment)
{
	return array(
		'type' => 'procedure',
		'parameters' => $expression[1],
		'body' => array_slice($expression, 2),
		'environment' => &$environment,
	);
}

function evaluate_if($expression, &$environment)
{
	return evaluate($expression[1], $environment) ? evaluate($expression[2], $environment) : evaluate($expression[3], $environment);
}

function evaluate_cond($expression, &$environment)
{
	$cond_clauses = array_slice($expression, 1);
	foreach($cond_clauses as $clause)
	{
		if ($clause[0] === 'else')
		{
			return evaluate($clause[1], $environment);
		}

		if (evaluate($clause[0], $environment))
		{
			return evaluate($clause[1], $environment);
		}
	}
}

function evaluate_quote($expression)
{
	return $expression[1];
}

function evaluate_application($expression, &$environment)
{
	$evaluate_callback = function($expression) use (&$environment) {
		return evaluate($expression, $environment);
	};
	$expression = array_map($evaluate_callback, $expression);

	$operator = $expression[0];
	$operands = array_slice($expression, 1);
	return apply($operator, $operands);
}

/* evaluate expression end */

function is_primitive_procedure($procedure)
{
	return is_callable($procedure);
}

function is_compound_procedure($procedure)
{
	return is_array($procedure) && $procedure['type'] === 'procedure';
}

function evaluate_sequence($expressions, &$environment)
{
	foreach($expressions as $expression)
	{
		$return = evaluate($expression, $environment);
	}

	return $return;
}

function tag_check($expression, $tag)
{
	return is_array($expression) && $expression[0] === $tag;
}

function extend_environment(&$environment, $parameters, $arguments)
{
	if (count($parameters) !== count($arguments))
	{
		exit('wrong argument numbers');
	}

	$frame = array_combine($parameters, $arguments);
	return array(
		'frame' => $frame,
		'parent' => &$environment,
	);
}

function global_environment()
{
	return array(
		'parent' => null,
		'frame' => array(
			'+' => function(){return array_sum(func_get_args());},
			'-' => function($x, $y){return $x - $y;},
			'*' => function(){return array_product(func_get_args());},
			'/' => function($x, $y){return $x / $y;},
			'>' => function($x, $y){return $x > $y;},
			'<' => function($x, $y){return $x < $y;},
		),
	);
}

function lisp_eval($strings)
{
	$GE = global_environment();
	return evaluate_sequence(parser(analyzer($strings)), $GE);
}
