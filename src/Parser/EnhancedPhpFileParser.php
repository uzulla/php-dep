<?php

namespace PhpDep\Parser;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\Node;

/**
 * Enhanced parser for PHP files with better dependency detection.
 */
class EnhancedPhpFileParser extends PhpFileParser
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
     * @var EnhancedDependencyVisitor
     */
    private $visitor;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        
        $this->traverser = new NodeTraverser();
        
        $this->traverser->addVisitor(new NameResolver());
        
        $this->visitor = new EnhancedDependencyVisitor();
        $this->traverser->addVisitor($this->visitor);
    }

    /**
     * Parse a PHP file and extract dependencies with enhanced detection.
     *
     * @param string $filePath
     * @return array
     */
    public function parse(string $filePath): array
    {
        $this->visitor->reset();
        
        $this->visitor->setCurrentFilePath($filePath);
        
        try {
            $code = file_get_contents($filePath);
            
            if ($code === false) {
                throw new \RuntimeException("Failed to read file: {$filePath}");
            }
            
            $ast = $this->parser->parse($code);
            
            if ($ast === null) {
                throw new \RuntimeException("Failed to parse file: {$filePath}");
            }
            
            $this->traverser->traverse($ast);
            
            return [
                'useStatements' => $this->visitor->getUseStatements(),
                'requireStatements' => $this->visitor->getRequireStatements(),
                'dynamicRequireStatements' => $this->visitor->getDynamicRequireStatements(),
                'classDefinitions' => $this->visitor->getClassDefinitions(),
                'typeHints' => $this->visitor->getTypeHints(),
                'extendedClasses' => $this->visitor->getExtendedClasses(),
                'implementedInterfaces' => $this->visitor->getImplementedInterfaces(),
                'usedTraits' => $this->visitor->getUsedTraits(),
            ];
        } catch (\PhpParser\Error $e) {
            throw new \RuntimeException("Parse error: {$e->getMessage()} in {$filePath}");
        }
    }
}
