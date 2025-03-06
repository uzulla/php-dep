#!/bin/bash

# This script demonstrates how to use the PHP Dependency Analyzer CLI tool

# Ensure we're in the examples directory
cd "$(dirname "$0")"

# Path to the PHP Dependency Analyzer CLI tool
PHP_DEP="../bin/php-dep"

echo "=== PHP Dependency Analyzer CLI Example ==="
echo

# Basic usage (defaults to recursive analysis with markdown output)
echo "1. Basic usage (defaults to recursive analysis with markdown output):"
echo "Note: This will print the full Markdown content to the console. Press Enter to continue..."
read -p ""
$PHP_DEP src/Controllers/UserController.php
echo

# Explicitly specify the analyze command
echo "2. Explicitly specify the analyze command (same as above):"
echo "Note: This will print the full Markdown content to the console. Press Enter to continue..."
read -p ""
$PHP_DEP analyze src/Controllers/UserController.php
echo

# Text output format
echo "3. Text output format:"
$PHP_DEP src/Controllers/UserController.php --format=text
echo

# JSON output
echo "4. JSON output:"
$PHP_DEP src/Controllers/UserController.php --format=json
echo

# Markdown output to file
echo "5. Markdown output to file:"
$PHP_DEP src/Controllers/UserController.php --output=dependencies.md
echo "Markdown output written to dependencies.md"
echo

# Specify composer.json
echo "6. Specify composer.json:"
$PHP_DEP src/Controllers/UserController.php --composer=composer.json --format=text
echo

# Analyze file with dynamic require statements
echo "7. Analyze file with dynamic require statements:"
$PHP_DEP src/DynamicRequireExample.php --format=text
echo

# Analyze file with __DIR__ constant
echo "8. Analyze file with __DIR__ constant:"
$PHP_DEP src/DirConstantExample.php --format=text
echo

# Analyze file with autoload.php included
echo "9. Analyze file with autoload.php included:"
$PHP_DEP src/RealWorldExample.php --format=text --include-autoload
echo

# Analyze file without autoload.php (default)
echo "10. Analyze file without autoload.php (default):"
$PHP_DEP src/RealWorldExample.php --format=text
echo

echo "=== Example completed ==="
