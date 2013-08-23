<?php
/**
 * @param string $string
 * @return array $tokens
 */
function analyzer($string)
{
	$string = trim(str_replace(array('(', ')'), array(' ( ', ' ) '), $string));
	return preg_split('/\s+/', $string);
}

/**
 * @param array $tokens
 * @return array $expressions
 */
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

function evaluate($expression, $environment)
{
	$expression_types = array(
		// primitives
		'number', 'string', 'boolean', 'variable', 

		//specail forms
		//'if', 'define', 'quote', 'cond', 'lambda', 'begin',

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
		return call_user_func_array($procedure[1], $arguments);
	}
	elseif (is_compound_procedure($procedure))
	{
		$new_environment = extend_enviroment(procedure_environment($procedure), procedure_parameters($procedure), $arguments);
		return evaluate(procedure_body($procedure), $new_environment);
	}
	else
	{
		error('unknow procedure');
	}
}

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

function is_lisp_begin($expression)
{
	return tag_check($expression, 'begin');
}

function is_lisp_application($expression)
{
	return is_array($expression);
}

function is_primitive_procedure($procedure)
{
	return tag_check($procedure, 'primitive');
}

function is_compound_procedure($procedure)
{
	return tag_check($procedure, 'procedure');
}

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

function evaluate_variable($expression, $environment)
{
	if (isset($environment['list'][$expression]))
	{
		return $environment['list'][$expression];
	}
	elseif ($environment['parent'])
	{
		return evaluate_variable($expression, $environment['parent']);
	}
	else
	{
		error('undefined variable');
	}
}

function evaluate_if($expression, $environment)
{
	return evaluate($expression[1], $environment) ? evaluate($expression[2], $environment) : evaluate($expression[3], $environment);
}

function evaluate_define($expression, &$environment)
{
	$environment[$expression[1]] = evaluate($expression[2]);
	return null;
}

function evaluate_application($expression, $environment)
{
	$evaluate_callback = function($expression) use ($environment) {
		return evaluate($expression, $environment);
	};
	$expression = array_map($evaluate_callback, $expression);

	$operator = $expression[0];
	$operands = array_slice($expression, 1);
	return apply($operator, $operands);
}

function error($msg)
{
	trigger_error($msg, E_USER_ERROR);
}

function tag_check($expression, $tag)
{
	return is_array($expression) && $expression[0] === $tag;
}

function environment_extend($environment, $parameters, $arguments)
{
	if (count($parameters) !== count($arguments))
	{
		error('wrong argument numbers');
	}

	$list = array_combine($parameters, $arguments);
	return array('list' => $list, 'parent' => &$environment);
}
