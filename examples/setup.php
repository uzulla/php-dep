<?php

// This script sets up the examples directory as a Composer package

// Create the vendor directory
if (!is_dir(__DIR__ . '/vendor')) {
    mkdir(__DIR__ . '/vendor', 0755, true);
}

// Create the autoload.php file
$autoloadContent = <<<'EOT'
<?php

// This is a simple autoloader for the examples

spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $prefix = 'App\\';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // No, move to the next registered autoloader
        return;
    }
    
    // Get the relative class name
    $relativeClass = substr($class, $len);
    
    // Convert namespace separators to directory separators
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

EOT;

file_put_contents(__DIR__ . '/vendor/autoload.php', $autoloadContent);

echo "Examples setup complete. The autoloader has been created.\n";
