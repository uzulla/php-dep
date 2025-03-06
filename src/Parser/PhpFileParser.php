<?php

namespace PhpDep\Parser;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Parser for PHP files to extract dependencies.
 */
class PhpFileParser
{
    /**
     * @var \PhpParser\Parser
     */
    private $parser;

    /**
     * @var NodeTraverser
     */
    private $traverser;

    /**
     * @var DependencyVisitor
     */
    private $visitor;

    public function __construct()
    {
        // Create a parser instance
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        
        // Create a node traverser
        $this->traverser = new NodeTraverser();
        
        // Add a name resolver visitor to resolve names
        $this->traverser->addVisitor(new NameResolver());
        
        // Add our custom visitor to collect dependencies
        $this->visitor = new DependencyVisitor();
        $this->traverser->addVisitor($this->visitor);
    }

    /**
     * Parse a PHP file and extract dependencies.
     *
     * @param string $filePath
     * @return array
     */
    public function parse(string $filePath): array
    {
        // Reset the visitor
        $this->visitor->reset();
        
        // Set the current file path for resolving __DIR__
        $this->visitor->setCurrentFilePath($filePath);
        
        try {
            // Read the file content
            $code = file_get_contents($filePath);
            
            if ($code === false) {
                throw new \RuntimeException("Failed to read file: {$filePath}");
            }
            
            // Parse the code
            $ast = $this->parser->parse($code);
            
            if ($ast === null) {
                throw new \RuntimeException("Failed to parse file: {$filePath}");
            }
            
            // Traverse the AST
            $this->traverser->traverse($ast);
            
            // Return the collected dependencies
            return [
                'useStatements' => $this->visitor->getUseStatements(),
                'requireStatements' => $this->visitor->getRequireStatements(),
                'dynamicRequireStatements' => $this->visitor->getDynamicRequireStatements(),
                'classDefinitions' => $this->visitor->getClassDefinitions(),
            ];
        } catch (Error $e) {
            throw new \RuntimeException("Parse error: {$e->getMessage()} in {$filePath}");
        }
    }
}

/**
 * Custom visitor to collect dependencies.
 */
class DependencyVisitor extends NodeVisitorAbstract
{
    /**
     * @var array
     */
    private $useStatements = [];

    /**
     * @var array
     */
    private $requireStatements = [];

    /**
     * @var array
     */
    private $dynamicRequireStatements = [];

    /**
     * @var array
     */
    private $classDefinitions = [];

    /**
     * @var string|null
     */
    private $currentFilePath = null;

    /**
     * Reset the visitor.
     */
    public function reset(): void
    {
        $this->useStatements = [];
        $this->requireStatements = [];
        $this->dynamicRequireStatements = [];
        $this->classDefinitions = [];
    }

    /**
     * Set the current file path for resolving __DIR__.
     *
     * @param string $filePath
     */
    public function setCurrentFilePath(string $filePath): void
    {
        $this->currentFilePath = $filePath;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        // Collect use statements
        if ($node instanceof Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                $this->useStatements[] = $use->name->toString();
            }
        }
        
        // Collect require/include statements
        if ($node instanceof Node\Expr\Include_) {
            // Check if it's a static string
            if ($node->expr instanceof Node\Scalar\String_) {
                $this->requireStatements[] = $node->expr->value;
            } 
            // Check if it's a __DIR__ . '/path' expression or similar
            elseif ($this->canResolveExpression($node->expr)) {
                $path = $this->resolveExpression($node->expr);
                if ($path !== null) {
                    $this->requireStatements[] = $path;
                } else {
                    // It's a dynamic include/require that we couldn't fully resolve
                    $dynamicInfo = $this->extractDynamicIncludeInfo($node);
                    $this->dynamicRequireStatements[] = $dynamicInfo;
                }
            }
            else {
                // It's a dynamic include/require
                $dynamicInfo = $this->extractDynamicIncludeInfo($node);
                $this->dynamicRequireStatements[] = $dynamicInfo;
            }
        }
        
        // Collect class definitions
        if ($node instanceof Node\Stmt\Class_) {
            if ($node->namespacedName !== null) {
                $this->classDefinitions[] = $node->namespacedName->toString();
            } elseif ($node->name !== null) {
                $this->classDefinitions[] = $node->name->toString();
            }
        }
        
        // Collect interface definitions
        if ($node instanceof Node\Stmt\Interface_) {
            if ($node->namespacedName !== null) {
                $this->classDefinitions[] = $node->namespacedName->toString();
            } elseif ($node->name !== null) {
                $this->classDefinitions[] = $node->name->toString();
            }
        }
        
        // Collect trait definitions
        if ($node instanceof Node\Stmt\Trait_) {
            if ($node->namespacedName !== null) {
                $this->classDefinitions[] = $node->namespacedName->toString();
            } elseif ($node->name !== null) {
                $this->classDefinitions[] = $node->name->toString();
            }
        }
    }

    /**
     * Check if we can resolve this expression (e.g., __DIR__ . '/path').
     *
     * @param Node\Expr $expr
     * @return bool
     */
    private function canResolveExpression(Node\Expr $expr): bool
    {
        // If it's a __DIR__ constant
        if ($expr instanceof Node\Scalar\MagicConst\Dir) {
            return true;
        }
        
        // If it's a concatenation
        if ($expr instanceof Node\Expr\BinaryOp\Concat) {
            // If left side is __DIR__ or can be resolved
            if ($expr->left instanceof Node\Scalar\MagicConst\Dir || $this->canResolveExpression($expr->left)) {
                // If right side is a string or can be resolved
                if ($expr->right instanceof Node\Scalar\String_ || $this->canResolveExpression($expr->right)) {
                    return true;
                }
            }
        }
        
        // If it's a string
        if ($expr instanceof Node\Scalar\String_) {
            return true;
        }
        
        return false;
    }

    /**
     * Resolve an expression to a path (e.g., __DIR__ . '/path').
     *
     * @param Node\Expr $expr
     * @return string|null
     */
    private function resolveExpression(Node\Expr $expr): ?string
    {
        if ($this->currentFilePath === null) {
            return null;
        }
        
        $currentDir = dirname($this->currentFilePath);
        
        // If it's a __DIR__ constant
        if ($expr instanceof Node\Scalar\MagicConst\Dir) {
            return $currentDir;
        }
        
        // If it's a string
        if ($expr instanceof Node\Scalar\String_) {
            return $expr->value;
        }
        
        // If it's a concatenation
        if ($expr instanceof Node\Expr\BinaryOp\Concat) {
            $leftResolved = $this->resolveExpression($expr->left);
            $rightResolved = $this->resolveExpression($expr->right);
            
            if ($leftResolved !== null && $rightResolved !== null) {
                $path = $leftResolved . $rightResolved;
                
                // Handle parent directory references
                if (strpos($path, '..') !== false) {
                    $path = $this->normalizePath($path);
                }
                
                return $path;
            }
        }
        
        return null;
    }

    /**
     * Normalize a path, resolving parent directory references.
     *
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        // Replace backslashes with forward slashes
        $path = str_replace('\\', '/', $path);
        
        // Split the path into segments
        $segments = explode('/', $path);
        $result = [];
        
        foreach ($segments as $segment) {
            if ($segment === '..') {
                // Remove the last segment (go up one directory)
                if (!empty($result)) {
                    array_pop($result);
                }
            } elseif ($segment !== '.' && $segment !== '') {
                // Add the segment to the result
                $result[] = $segment;
            }
        }
        
        // Join the segments back together
        $normalizedPath = implode('/', $result);
        
        // Add a leading slash if the original path had one
        if (strpos($path, '/') === 0 && strpos($normalizedPath, '/') !== 0) {
            $normalizedPath = '/' . $normalizedPath;
        }
        
        return $normalizedPath;
    }

    /**
     * Extract information about a dynamic include/require statement.
     *
     * @param Node\Expr\Include_ $node
     * @return array
     */
    private function extractDynamicIncludeInfo(Node\Expr\Include_ $node): array
    {
        $type = $this->getIncludeTypeString($node->type);
        $expr = $this->getExpressionAsString($node->expr);
        
        return [
            'type' => $type,
            'expression' => $expr,
        ];
    }

    /**
     * Get the include type as a string.
     *
     * @param int $type
     * @return string
     */
    private function getIncludeTypeString(int $type): string
    {
        switch ($type) {
            case Node\Expr\Include_::TYPE_INCLUDE:
                return 'include';
            case Node\Expr\Include_::TYPE_INCLUDE_ONCE:
                return 'include_once';
            case Node\Expr\Include_::TYPE_REQUIRE:
                return 'require';
            case Node\Expr\Include_::TYPE_REQUIRE_ONCE:
                return 'require_once';
            default:
                return 'unknown';
        }
    }

    /**
     * Try to convert an expression to a string representation.
     *
     * @param Node\Expr $expr
     * @return string
     */
    private function getExpressionAsString(Node\Expr $expr): string
    {
        if ($expr instanceof Node\Scalar\String_) {
            return $expr->value;
        }
        
        if ($expr instanceof Node\Expr\BinaryOp\Concat) {
            return $this->getExpressionAsString($expr->left) . ' . ' . $this->getExpressionAsString($expr->right);
        }
        
        if ($expr instanceof Node\Expr\Variable) {
            if (is_string($expr->name)) {
                return '$' . $expr->name;
            }
        }
        
        if ($expr instanceof Node\Expr\ConstFetch) {
            return $expr->name->toString();
        }
        
        if ($expr instanceof Node\Scalar\MagicConst\Dir) {
            return '__DIR__';
        }
        
        // For other types, return a generic description
        return get_class($expr);
    }

    /**
     * Get the collected use statements.
     *
     * @return array
     */
    public function getUseStatements(): array
    {
        return $this->useStatements;
    }

    /**
     * Get the collected require statements.
     *
     * @return array
     */
    public function getRequireStatements(): array
    {
        return $this->requireStatements;
    }

    /**
     * Get the collected dynamic require statements.
     *
     * @return array
     */
    public function getDynamicRequireStatements(): array
    {
        return $this->dynamicRequireStatements;
    }

    /**
     * Get the collected class definitions.
     *
     * @return array
     */
    public function getClassDefinitions(): array
    {
        return $this->classDefinitions;
    }
}
