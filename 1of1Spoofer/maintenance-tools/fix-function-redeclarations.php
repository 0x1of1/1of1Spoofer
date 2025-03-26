<?php

/**
 * 1of1Spoofer - Function Redeclaration Fix Tool
 * 
 * This script analyzes the codebase for function redeclarations and either:
 * 1. Reports functions that are declared in multiple files
 * 2. Automatically wraps function declarations with function_exists() checks
 * 
 * Usage: php fix-function-redeclarations.php [--fix]
 * --fix    Add function_exists() checks to prevent redeclarations
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Determine the target directory
$targetDir = isset($argv[1]) ? $argv[1] : dirname(__DIR__);
echo "Scanning directory: $targetDir\n";

// Pattern to match function declarations
$functionPattern = '/function\s+([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)\s*\(/';
$functionExistsWrapperPattern = '/if\s*\(\s*!\s*function_exists\s*\(\s*[\'"]([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)[\'"]?\s*\)\s*\)\s*{/';

// Store all found functions for later analysis
$foundFunctions = [];

// Recursive directory iterator
$directory = new RecursiveDirectoryIterator($targetDir);
$iterator = new RecursiveIteratorIterator($directory);
$phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

// First pass: Find all functions
echo "Pass 1: Finding all function declarations...\n";
foreach ($phpFiles as $file) {
    $filePath = $file[0];

    // Skip this script itself
    if (basename($filePath) === basename(__FILE__)) {
        continue;
    }

    $content = file_get_contents($filePath);

    // Find all function declarations
    if (preg_match_all($functionPattern, $content, $matches)) {
        foreach ($matches[1] as $function) {
            if (!isset($foundFunctions[$function])) {
                $foundFunctions[$function] = [];
            }
            $foundFunctions[$function][] = $filePath;
        }
    }
}

// Identify functions declared in multiple files
$duplicateFunctions = array_filter($foundFunctions, function ($files) {
    return count($files) > 1;
});

if (!empty($duplicateFunctions)) {
    echo "\nWARNING: Found functions declared in multiple files:\n";
    foreach ($duplicateFunctions as $function => $files) {
        echo "  - '$function' in:\n";
        foreach ($files as $file) {
            echo "      " . str_replace($targetDir . '/', '', $file) . "\n";
        }
    }
    echo "\n";
}

// Second pass: Add function_exists checks
echo "Pass 2: Adding function_exists() checks...\n";
$modifiedFiles = 0;

foreach ($phpFiles as $file) {
    $filePath = $file[0];

    // Skip this script itself
    if (basename($filePath) === basename(__FILE__)) {
        continue;
    }

    $content = file_get_contents($filePath);
    $originalContent = $content;

    // Check if file contains function declarations
    if (preg_match_all($functionPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        $offset = 0;

        foreach ($matches[0] as $index => $fullMatch) {
            $functionName = $matches[1][$index][0];
            $functionPos = $fullMatch[1] + $offset;

            // Check if this function is already wrapped with function_exists
            $beforeFunction = substr($content, 0, $functionPos);
            $lastOpenBrace = strrpos($beforeFunction, '{');
            $lastIfStatement = $lastOpenBrace !== false ? strrpos($beforeFunction, 'if', - ($functionPos - $lastOpenBrace)) : false;

            if ($lastIfStatement !== false && $lastOpenBrace !== false) {
                $ifBlock = substr($beforeFunction, $lastIfStatement, $functionPos - $lastIfStatement);
                if (preg_match('/if\s*\(\s*!\s*function_exists\s*\(\s*[\'"]' . preg_quote($functionName, '/') . '[\'"]?\s*\)\s*\)\s*{/', $ifBlock)) {
                    // Already wrapped, skip
                    continue;
                }
            }

            // Find where to insert the function_exists check
            // Look for the function signature and its preceding comment block
            $functionStart = $functionPos;
            $beforeFunction = substr($content, 0, $functionPos);

            // Look for the nearest preceding comment block
            $commentStart = strrpos($beforeFunction, '/**');
            if ($commentStart !== false) {
                $commentEnd = strpos($beforeFunction, '*/', $commentStart);
                if ($commentEnd !== false && $commentEnd + 2 >= $functionPos - 100) {
                    // Comment block belongs to this function
                    $functionStart = $commentStart;
                }
            }

            // Add the function_exists check
            $replacement = "if (!function_exists('$functionName')) {\n";
            $content = substr($content, 0, $functionStart) . $replacement . substr($content, $functionStart);

            // Find the end of the function to add closing brace
            $openBraces = 1;
            $pos = $functionPos + strlen($fullMatch[0]) + strlen($replacement);
            $length = strlen($content);

            while ($openBraces > 0 && $pos < $length) {
                if ($content[$pos] === '{') {
                    $openBraces++;
                } elseif ($content[$pos] === '}') {
                    $openBraces--;
                    if ($openBraces === 0) {
                        // End of the function found, add closing brace
                        $content = substr($content, 0, $pos + 1) . "\n}" . substr($content, $pos + 1);
                        $offset += strlen("\n}");
                        break;
                    }
                }
                $pos++;
            }

            // Update offset for subsequent replacements
            $offset += strlen($replacement);
        }

        // Save modified content if changes were made
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            echo "  Modified: " . str_replace($targetDir . '/', '', $filePath) . "\n";
            $modifiedFiles++;
        }
    }
}

echo "\nSummary:\n";
echo "  Found " . count($foundFunctions) . " unique function declarations\n";
echo "  Found " . count($duplicateFunctions) . " functions declared in multiple files\n";
echo "  Modified $modifiedFiles PHP files\n";

if (!empty($duplicateFunctions)) {
    echo "\nRECOMMENDATION: Review the functions declared in multiple files and remove duplicates.\n";
}

echo "\nDone.\n";
