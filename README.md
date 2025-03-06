# PHP Dependency Analyzer

A tool to analyze PHP file dependencies. This tool helps with refactoring and understanding PHP projects by identifying all dependencies of a PHP file.

## Features

- Analyze dependencies for a PHP file
- Identify dependencies recursively (enabled by default)
- Parse PHP `use` statements
- Parse PHP `require`/`require_once`/`include`/`include_once` statements, including conditional ones
- Resolve `__DIR__` constants in require/include statements
- Detect dynamic require/include statements and report them
- Support for Composer autoloading
- Exclude vendor/autoload.php by default (can be included with an option)
- Output in text, JSON, or Markdown format (Markdown is default)
- Use as a CLI tool or as a library

## sample output

`$ bin/php-dep src/DependencyAnalyzer.php`

```
# File: /Users/zishida/php-dep/src/DependencyAnalyzer.php


<?php

namespace PhpDep;

use PhpDep\Parser\PhpFileParser;
use PhpDep\Resolver\ComposerResolver;

/**
 * Main class for analyzing PHP file dependencies.
 */
class DependencyAnalyzer
{
<snip>
}



## Dependencies

### File: /Users/zishida/php-dep/src//Parser/PhpFileParser.php

```php
<?php

namespace PhpDep\Parser;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

<snip>



### File: /Users/zishida/php-dep/src//Resolver/ComposerResolver.php


<?php

namespace PhpDep\Resolver;

/**
 * Resolver for Composer autoloaded dependencies.
 */
class ComposerResolver
{
<snip>
}

```

## Installation

```bash
# Install dependencies
composer install

# Optionally, symlink to make it globally available
ln -s $(pwd)/bin/php-dep /usr/local/bin/php-dep
```

## Usage

### CLI Usage

```bash
# Basic usage (defaults to recursive analysis with markdown output)
php-dep path/to/your/file.php

# Explicitly specify the analyze command (same as above)
php-dep analyze path/to/your/file.php

# Specify a composer.json file for autoloading
php-dep path/to/your/file.php --composer=path/to/composer.json

# Include autoload.php in the analysis (excluded by default)
php-dep path/to/your/file.php --include-autoload

# Output in text format
php-dep path/to/your/file.php --format=text

# Output in JSON format
php-dep path/to/your/file.php --format=json

# Output in Markdown format to a file
php-dep path/to/your/file.php --output=dependencies.md
```

### Library Usage

```php
<?php

require_once 'vendor/autoload.php';

use PhpDep\DependencyAnalyzer;

// Create an analyzer
$analyzer = new DependencyAnalyzer();

// Analyze a file (recursive by default)
$node = $analyzer->analyze('path/to/your/file.php');

// Get all dependency paths
$dependencies = $node->getAllDependencyPaths();

// Print dependencies
foreach ($dependencies as $dependency) {
    echo "- {$dependency}\n";
}

// Generate Markdown output with file contents
$analyzer = new DependencyAnalyzer(null, null, true); // Set loadContents to true

// Include autoload.php in the analysis (excluded by default)
$analyzer->setIncludeAutoload(true);

$node = $analyzer->analyze('path/to/your/file.php');
$markdown = $node->toMarkdown();

// Print to standard output
echo $markdown;

// Or save to a file
file_put_contents('dependencies.md', $markdown);
```

## Markdown Format

The Markdown format is particularly useful for loading the code and its dependencies into an LLM (Large Language Model) for analysis. It includes:

- The main file path and its content
- All dependency file paths and their contents
- Dynamic dependencies that could not be resolved
- Formatted with proper code blocks for syntax highlighting

Example Markdown output:

```markdown
# File: path/to/your/file.php

```php
<?php
// File content here
?>
```

## Dynamic Dependencies

These dependencies could not be resolved because they use dynamic file paths:

- require_once `modules/{$moduleName}.php`
- include_once `modules/ . $moduleName . .class.php`

## Dependencies

### File: path/to/dependency1.php

```php
<?php
// Dependency 1 content here
?>
```

### File: path/to/dependency2.php

```php
<?php
// Dependency 2 content here
?>
```
```

## How It Works

The PHP Dependency Analyzer works by:

1. Parsing PHP files using [PHP-Parser](https://github.com/nikic/PHP-Parser)
2. Extracting `use` statements, `require`/`include` statements, and class definitions
3. Resolving namespaces to file paths using Composer's autoloading rules
4. Building a dependency tree
5. Recursively analyzing all dependencies
6. Detecting dynamic require/include statements and reporting them

## Handling of Different Dependency Types

- **Static includes**: Files included with static paths like `require 'file.php'` are fully resolved and included in the dependency tree.
- **Conditional includes**: Files included in conditional statements like `if (...) require 'file.php'` are always included in the dependency tree.
- **__DIR__ constant**: Paths using the `__DIR__` constant like `require_once __DIR__ . '/file.php'` are resolved to absolute paths.
- **Dynamic includes**: Files included with dynamic paths like `require $variable` or `require "path/{$variable}.php"` are reported as dynamic dependencies that could not be resolved.
- **Autoload.php**: The vendor/autoload.php file is excluded by default to reduce noise, but can be included with the `--include-autoload` option.

## Limitations

- Dynamic includes (e.g., `require $variable`) cannot be fully resolved
- Some framework-specific patterns may not be detected

## License

MIT
