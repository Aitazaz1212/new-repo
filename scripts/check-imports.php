<?php

/**
 * PHP Import and Class Dependency Checker
 *
 * This script analyzes PHP files for:
 * - Missing class imports
 * - Undefined class usage
 * - Proper namespace declarations
 * - Unused variables
 * - Unused imports
 */

if (empty($argv[1])) {
    echo "No files to check.\n";
    exit(0);
}

$files = explode(' ', $argv[1]);
$hasErrors = false;

foreach ($files as $file) {
    if (!file_exists($file)) {
        continue;
    }

    echo "Checking file: $file\n";

    // Get file contents
    $content = file_get_contents($file);

    // Parse the file
    $tokens = token_get_all($content);

    $namespace = '';
    $classes = [];
    $imports = [];
    $usedClasses = [];
    $variables = [];
    $usedVariables = [];
    $declaredImports = [];
    $usedImports = [];
    $inFunction = false;
    $currentFunction = '';
    $functionVariables = [];
    $functionUsedVariables = [];

    for ($i = 0; $i < count($tokens); $i++) {
        $token = $tokens[$i];
        if (!is_array($token)) {
            continue;
        }

        // Track function scope
        if ($token[0] === T_FUNCTION) {
            $inFunction = true;
            // Get function name
            while ($i < count($tokens)) {
                $i++;
                if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                    $currentFunction = $tokens[$i][1];
                    break;
                }
            }
            $functionVariables[$currentFunction] = [];
            $functionUsedVariables[$currentFunction] = [];
        }

        // Track curly braces to determine function scope
        if ($token === '{') {
            $braceLevel++;
        } elseif ($token === '}') {
            $braceLevel--;
            if ($braceLevel === 0 && $inFunction) {
                $inFunction = false;
                $currentFunction = '';
            }
        }

        // Get namespace
        if ($token[0] === T_NAMESPACE) {
            $namespace = getNamespace($tokens, $i);
        }

        // Get imports
        if ($token[0] === T_USE) {
            $import = getImport($tokens, $i);
            if ($import) {
                $imports[] = $import;
                $declaredImports[] = $import;
            }
        }

        // Track variable declarations
        if ($token[0] === T_VARIABLE) {
            $varName = substr($token[1], 1); // Remove $ from variable name
            if ($inFunction) {
                $functionVariables[$currentFunction][$varName] = true;
            } else {
                $variables[$varName] = true;
            }
        }

        // Track variable usage
        if ($token[0] === T_VARIABLE) {
            $varName = substr($token[1], 1);
            if ($inFunction) {
                $functionUsedVariables[$currentFunction][$varName] = true;
            } else {
                $usedVariables[$varName] = true;
            }
        }

        // Get class usage
        if ($token[0] === T_STRING) {
            $class = $token[1];
            if (isClassName($class) && !isPhpToken($class)) {
                $usedClasses[] = $class;
                if (in_array($class, $declaredImports)) {
                    $usedImports[] = $class;
                }
            }
        }
    }

    // Check for missing imports
    $missingImports = [];
    foreach (array_unique($usedClasses) as $class) {
        if (!in_array($class, $imports) && !isBuiltInClass($class)) {
            $missingImports[] = $class;
        }
    }

    // Check for unused imports
    $unusedImports = array_diff($declaredImports, $usedImports);

    // Check for unused variables
    $unusedVariables = array_diff_key($variables, $usedVariables);

    // Check for unused function variables
    $unusedFunctionVariables = [];
    foreach ($functionVariables as $function => $vars) {
        $used = isset($functionUsedVariables[$function]) ? $functionUsedVariables[$function] : [];
        $unused = array_diff_key($vars, $used);
        if (!empty($unused)) {
            $unusedFunctionVariables[$function] = array_keys($unused);
        }
    }

    // Report issues
    if (!empty($missingImports) || !empty($unusedImports) || !empty($unusedVariables) || !empty($unusedFunctionVariables)) {
        $hasErrors = true;

        if (!empty($missingImports)) {
            echo "\nMissing imports in $file:\n";
            foreach ($missingImports as $class) {
                echo "- $class\n";
            }
        }

        if (!empty($unusedImports)) {
            echo "\nUnused imports in $file:\n";
            foreach ($unusedImports as $import) {
                echo "- $import\n";
            }
        }

        if (!empty($unusedVariables)) {
            echo "\nUnused variables in global scope in $file:\n";
            foreach (array_keys($unusedVariables) as $var) {
                echo "- \$$var\n";
            }
        }

        if (!empty($unusedFunctionVariables)) {
            echo "\nUnused variables in functions in $file:\n";
            foreach ($unusedFunctionVariables as $function => $vars) {
                echo "In function $function:\n";
                foreach ($vars as $var) {
                    echo "- \$$var\n";
                }
            }
        }
    }
}

exit($hasErrors ? 1 : 0);

function getNamespace($tokens, $start)
{
    $namespace = '';
    $count = count($tokens);
    for ($i = $start + 1; $i < $count; $i++) {
        if ($tokens[$i][0] === T_STRING || $tokens[$i][0] === T_NS_SEPARATOR) {
            $namespace .= $tokens[$i][1];
        }
        if ($tokens[$i] === ';') {
            break;
        }
    }
    return $namespace;
}

function getImport($tokens, $start)
{
    $import = '';
    $count = count($tokens);
    for ($i = $start + 1; $i < $count; $i++) {
        if ($tokens[$i][0] === T_STRING || $tokens[$i][0] === T_NS_SEPARATOR) {
            $import .= $tokens[$i][1];
        }
        if ($tokens[$i] === ';') {
            break;
        }
    }
    return $import;
}

function isClassName($string)
{
    return $string && $string[0] === strtoupper($string[0]);
}

function isPhpToken($string)
{
    // Check if the string starts with 'T_'
    return strpos($string, 'T_') === 0;
}

function isBuiltInClass($class)
{
    $builtInClasses = [
        // PHP Core Classes
        'stdClass',
        'Exception',
        'ErrorException',
        'Error',
        'ParseError',
        'TypeError',
        'ArgumentCountError',
        'ArithmeticError',
        'DivisionByZeroError',
        'DateTime',
        'DateTimeImmutable',
        'DateTimeZone',
        'DateInterval',
        'DatePeriod',

        // PHP Token Constants (commonly used as class-like constants)
        'T_NAMESPACE',
        'T_USE',
        'T_STRING',
        'T_NS_SEPARATOR',
        'T_CLASS',
        'T_INTERFACE',
        'T_TRAIT',
        'T_ABSTRACT',
        'T_FINAL',
        'T_PUBLIC',
        'T_PROTECTED',
        'T_PRIVATE',
        'T_STATIC',
        'T_FUNCTION',
        'T_VAR',
        'T_CONST',
    ];

    return in_array($class, $builtInClasses) || isPhpToken($class);
}
