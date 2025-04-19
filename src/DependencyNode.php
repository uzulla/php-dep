<?php

namespace PhpDep;

/**
 * Represents a node in the dependency tree.
 */
class DependencyNode
{
    /**
     * @var string The file path of this node
     */
    private string $filePath;

    /**
     * @var DependencyNode[] Child dependencies of this node
     */
    private array $dependencies = [];

    /**
     * @var array Parsed use statements from this file
     */
    private array $useStatements = [];

    /**
     * @var array Parsed require/include statements from this file
     */
    private array $requireStatements = [];

    /**
     * @var array Parsed dynamic require/include statements from this file
     */
    private array $dynamicRequireStatements = [];

    /**
     * @var array Parsed class definitions from this file
     */
    private array $classDefinitions = [];

    /**
     * @var string|null The content of the file
     */
    private ?string $fileContent = null;

    /**
     * @param string $filePath The file path of this node
     * @param string|null $fileContent The content of the file
     */
    public function __construct(string $filePath, ?string $fileContent = null)
    {
        $this->filePath = $filePath;
        $this->fileContent = $fileContent;
    }

    /**
     * Get the file path of this node.
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Check if the file content has been loaded.
     *
     * @return bool
     */
    public function hasFileContent(): bool
    {
        return $this->fileContent !== null;
    }

    /**
     * Get the file content of this node.
     *
     * @return string|null
     */
    public function getFileContent(): ?string
    {
        if ($this->fileContent === null && file_exists($this->filePath)) {
            $this->fileContent = file_get_contents($this->filePath);
        }
        
        return $this->fileContent;
    }

    /**
     * Set the file content of this node.
     *
     * @param string $fileContent
     * @return self
     */
    public function setFileContent(string $fileContent): self
    {
        $this->fileContent = $fileContent;
        return $this;
    }

    /**
     * Add a dependency to this node.
     *
     * @param DependencyNode $dependency
     * @return self
     */
    public function addDependency(DependencyNode $dependency): self
    {
        $this->dependencies[$dependency->getFilePath()] = $dependency;
        return $this;
    }

    /**
     * Get all dependencies of this node.
     *
     * @return DependencyNode[]
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Add a use statement to this node.
     *
     * @param string $useStatement
     * @return self
     */
    public function addUseStatement(string $useStatement): self
    {
        $this->useStatements[] = $useStatement;
        return $this;
    }

    /**
     * Get all use statements of this node.
     *
     * @return array
     */
    public function getUseStatements(): array
    {
        return $this->useStatements;
    }

    /**
     * Add a require statement to this node.
     *
     * @param string $requireStatement
     * @return self
     */
    public function addRequireStatement(string $requireStatement): self
    {
        $this->requireStatements[] = $requireStatement;
        return $this;
    }

    /**
     * Get all require statements of this node.
     *
     * @return array
     */
    public function getRequireStatements(): array
    {
        return $this->requireStatements;
    }

    /**
     * Add a dynamic require statement to this node.
     *
     * @param array $dynamicRequireStatement
     * @return self
     */
    public function addDynamicRequireStatement(array $dynamicRequireStatement): self
    {
        $this->dynamicRequireStatements[] = $dynamicRequireStatement;
        return $this;
    }

    /**
     * Get all dynamic require statements of this node.
     *
     * @return array
     */
    public function getDynamicRequireStatements(): array
    {
        return $this->dynamicRequireStatements;
    }

    /**
     * Add a class definition to this node.
     *
     * @param string $className
     * @return self
     */
    public function addClassDefinition(string $className): self
    {
        $this->classDefinitions[] = $className;
        return $this;
    }

    /**
     * Get all class definitions of this node.
     *
     * @return array
     */
    public function getClassDefinitions(): array
    {
        return $this->classDefinitions;
    }

    /**
     * Get all dependency file paths recursively.
     *
     * @return array
     */
    public function getAllDependencyPaths(): array
    {
        $paths = [];
        
        foreach ($this->dependencies as $dependency) {
            $paths[] = $dependency->getFilePath();
            $paths = array_merge($paths, $dependency->getAllDependencyPaths());
        }
        
        return array_unique($paths);
    }

    /**
     * Get all dependency nodes recursively.
     *
     * @return DependencyNode[]
     */
    public function getAllDependencyNodes(): array
    {
        $nodes = [];
        
        foreach ($this->dependencies as $dependency) {
            $nodes[$dependency->getFilePath()] = $dependency;
            $nodes = array_merge($nodes, $dependency->getAllDependencyNodes());
        }
        
        return $nodes;
    }

    /**
     * Generate a Markdown representation of this node and its dependencies.
     *
     * @return string
     */
    public function toMarkdown(): string
    {
        $markdown = "# File: " . $this->getFilePath() . "\n\n";
        $markdown .= "```php\n" . $this->getFileContent() . "\n```\n\n";
        
        // Add dynamic require statements if any
        if (!empty($this->dynamicRequireStatements)) {
            $markdown .= "## Dynamic Dependencies\n\n";
            $markdown .= "These dependencies could not be resolved because they use dynamic file paths:\n\n";
            
            foreach ($this->dynamicRequireStatements as $dynamicRequire) {
                $markdown .= "- " . $dynamicRequire['type'] . " `" . $dynamicRequire['expression'] . "`\n";
            }
            
            $markdown .= "\n";
        }
        
        $markdown .= "## Dependencies\n\n";
        
        $dependencies = $this->getAllDependencyNodes();
        
        if (empty($dependencies)) {
            $markdown .= "No dependencies found.\n\n";
        } else {
            foreach ($dependencies as $dependency) {
                $markdown .= "### File: " . $dependency->getFilePath() . "\n\n";
                $markdown .= "```php\n" . $dependency->getFileContent() . "\n```\n\n";
                
                // Add dynamic require statements for this dependency if any
                $dynamicRequires = $dependency->getDynamicRequireStatements();
                if (!empty($dynamicRequires)) {
                    $markdown .= "#### Dynamic Dependencies\n\n";
                    $markdown .= "These dependencies could not be resolved because they use dynamic file paths:\n\n";
                    
                    foreach ($dynamicRequires as $dynamicRequire) {
                        $markdown .= "- " . $dynamicRequire['type'] . " `" . $dynamicRequire['expression'] . "`\n";
                    }
                    
                    $markdown .= "\n";
                }
            }
        }
        
        return $markdown;
    }

    /**
     * Generate a tree representation of this node and its dependencies.
     *
     * @param bool $useFullPath Whether to use full file paths or try to convert to FQCN
     * @return string
     */
    public function toTree(bool $useFullPath = false): string
    {
        return $this->generateTreeOutput($this, '', true, [], $useFullPath, false, null);
    }
    
    /**
     * Generate a tree representation of this node and its dependencies with interface implementations.
     *
     * @param bool $useFullPath Whether to use full file paths or try to convert to FQCN
     * @param string|null $searchDir Directory to search for interface implementations
     * @return string
     */
    public function toTreeFull(bool $useFullPath = false, ?string $searchDir = null): string
    {
        return $this->generateTreeOutput($this, '', true, [], $useFullPath, true, $searchDir);
    }

    /**
     * Helper method to generate the tree output recursively.
     *
     * @param DependencyNode $node The current node
     * @param string $prefix The prefix for the current line
     * @param bool $isLast Whether this is the last child of its parent
     * @param array $visited Array of visited file paths to avoid infinite recursion
     * @param bool $useFullPath Whether to use full file paths or try to convert to FQCN
     * @param bool $showImplementations Whether to show interface implementations
     * @param string|null $searchDir Directory to search for interface implementations
     * @return string
     */
    private function generateTreeOutput(
        DependencyNode $node, 
        string $prefix, 
        bool $isLast, 
        array $visited, 
        bool $useFullPath, 
        bool $showImplementations = false, 
        ?string $searchDir = null
    ): string {
        $filePath = $node->getFilePath();
        
        if (in_array($filePath, $visited)) {
            return '';
        }
        
        $visited[] = $filePath;
        
        $displayPath = $useFullPath ? $filePath : $this->filePathToFQCN($filePath);
        
        $result = $prefix . ($isLast ? '└── ' : '├── ') . $displayPath . PHP_EOL;
        
        // If showing implementations and this looks like an interface, find implementations
        if ($showImplementations && $searchDir && $this->isInterface($displayPath)) {
            $implementations = $this->findInterfaceImplementations($displayPath, $searchDir);
            if (!empty($implementations)) {
                $implPrefix = $prefix . ($isLast ? '    ' : '│   ') . '    ';
                foreach ($implementations as $implementation) {
                    $result .= $implPrefix . '└── ( ' . $implementation . ' )' . PHP_EOL;
                }
            }
        }
        
        $dependencies = $node->getDependencies();
        uksort($dependencies, 'strnatcmp');
        
        $i = 0;
        $dependencyCount = count($dependencies);
        
        foreach ($dependencies as $dependency) {
            $isLastDependency = (++$i === $dependencyCount);
            $newPrefix = $prefix . ($isLast ? '    ' : '│   ');
            $result .= $this->generateTreeOutput(
                $dependency, 
                $newPrefix, 
                $isLastDependency, 
                $visited, 
                $useFullPath, 
                $showImplementations, 
                $searchDir
            );
        }
        
        return $result;
    }
    
    /**
     * Check if a path represents an interface.
     *
     * @param string $path
     * @return bool
     */
    private function isInterface(string $path): bool
    {
        if (strpos($path, 'Interface') !== false) {
            return true;
        }
        
        if (strpos($path, '\Psr\\') !== false && substr($path, -9) === 'Interface') {
            return true;
        }
        
        $commonInterfaces = [
            'ClientInterface',
            'PromiseInterface',
            'RequestInterface',
            'ResponseInterface',
            'UriInterface',
            'StreamInterface',
            'MessageInterface'
        ];
        
        foreach ($commonInterfaces as $interface) {
            if (substr($path, -strlen($interface)) === $interface) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Find implementations of an interface in a directory.
     *
     * @param string $interfaceName
     * @param string $searchDir
     * @return array
     */
    private function findInterfaceImplementations(string $interfaceName, string $searchDir): array
    {
        $implementations = [];
        
        if (!is_dir($searchDir)) {
            return $implementations;
        }
        
        $phpFiles = $this->findPhpFiles($searchDir);
        
        foreach ($phpFiles as $phpFile) {
            $content = file_get_contents($phpFile);
            if ($content === false) {
                continue;
            }
            
            if ($this->implementsInterface($content, $interfaceName)) {
                $className = $this->extractClassName($content);
                if ($className) {
                    $implementations[] = $className;
                } else {
                    $implementations[] = $this->filePathToFQCN($phpFile);
                }
            }
        }
        
        sort($implementations);
        return $implementations;
    }
    
    /**
     * Find all PHP files in a directory recursively.
     *
     * @param string $dir
     * @return array
     */
    private function findPhpFiles(string $dir): array
    {
        $phpFiles = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }
        
        return $phpFiles;
    }
    
    /**
     * Check if a file implements a specific interface.
     *
     * @param string $content
     * @param string $interfaceName
     * @return bool
     */
    private function implementsInterface(string $content, string $interfaceName): bool
    {
        $shortInterfaceName = $interfaceName;
        $lastBackslash = strrpos($interfaceName, '\\');
        if ($lastBackslash !== false) {
            $shortInterfaceName = substr($interfaceName, $lastBackslash + 1);
        }
        
        $pattern = '/class\s+\w+(?:\s+extends\s+\w+)?\s+implements\s+(?:[^{]+,\s*)?(?:\\\\)?(' . preg_quote($shortInterfaceName, '/') . ')(?:\s*,|\s*{)/i';
        if (preg_match($pattern, $content)) {
            return true;
        }
        
        $usePattern = '/use\s+(?:[^;]+\\\\)?(' . preg_quote($interfaceName, '/') . ')(?:\s+as\s+([^;]+))?;/i';
        if (preg_match($usePattern, $content, $matches)) {
            $alias = isset($matches[2]) ? $matches[2] : $shortInterfaceName;
            $implementsPattern = '/implements\s+(?:[^{]+,\s*)?(?:\\\\)?(' . preg_quote($alias, '/') . ')(?:\s*,|\s*{)/i';
            return preg_match($implementsPattern, $content);
        }
        
        return false;
    }
    
    /**
     * Extract the class name from file content.
     *
     * @param string $content
     * @return string|null
     */
    private function extractClassName(string $content): ?string
    {
        $namespace = '';
        if (preg_match('/namespace\s+([^;]+);/i', $content, $matches)) {
            $namespace = $matches[1] . '\\';
        }
        
        if (preg_match('/class\s+(\w+)(?:\s+extends|\s+implements|\s*{)/i', $content, $matches)) {
            return $namespace . $matches[1];
        }
        
        return null;
    }

    /**
     * Convert a file path to a FQCN-like format.
     *
     * @param string $filePath
     * @return string
     */
    private function filePathToFQCN(string $filePath): string
    {
        $path = preg_replace('/\.php$/', '', $filePath);
        
        $sourceDirectories = ['/src/', '/lib/', '/app/', '/classes/'];
        
        foreach ($sourceDirectories as $sourceDir) {
            $pos = strpos($path, $sourceDir);
            if ($pos !== false) {
                $path = substr($path, $pos + strlen($sourceDir));
                break;
            }
        }
        
        $path = str_replace('/', '\\', $path);
        
        return $path;
    }
}
