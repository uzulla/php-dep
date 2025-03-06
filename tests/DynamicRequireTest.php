<?php

namespace PhpDep\Tests;

use PhpDep\DependencyAnalyzer;
use PhpDep\DependencyNode;
use PhpDep\Parser\PhpFileParser;
use PHPUnit\Framework\TestCase;

class DynamicRequireTest extends TestCase
{
    /**
     * Test that dynamic require statements are detected.
     */
    public function testDynamicRequireDetection()
    {
        // Create a node with dynamic require statements
        $node = new DependencyNode('/path/to/file.php', '<?php
            // Dynamic require with variable
            $moduleName = "test";
            require_once "modules/{$moduleName}.php";
            
            // Dynamic require with concatenation
            include_once "modules/" . $moduleName . ".class.php";
        ');
        
        // Add dynamic require statements
        $node->addDynamicRequireStatement([
            'type' => 'require_once',
            'expression' => 'modules/{$moduleName}.php',
        ]);
        
        $node->addDynamicRequireStatement([
            'type' => 'include_once',
            'expression' => 'modules/ . $moduleName . .class.php',
        ]);
        
        // Check that dynamic require statements were added
        $dynamicRequires = $node->getDynamicRequireStatements();
        $this->assertCount(2, $dynamicRequires);
        $this->assertEquals('require_once', $dynamicRequires[0]['type']);
        $this->assertEquals('modules/{$moduleName}.php', $dynamicRequires[0]['expression']);
        $this->assertEquals('include_once', $dynamicRequires[1]['type']);
        $this->assertEquals('modules/ . $moduleName . .class.php', $dynamicRequires[1]['expression']);
        
        // Generate markdown
        $markdown = $node->toMarkdown();
        
        // Check that the markdown contains the dynamic dependencies section
        $this->assertStringContainsString('## Dynamic Dependencies', $markdown);
        $this->assertStringContainsString('These dependencies could not be resolved because they use dynamic file paths', $markdown);
        $this->assertStringContainsString('require_once `modules/{$moduleName}.php`', $markdown);
        $this->assertStringContainsString('include_once `modules/ . $moduleName . .class.php`', $markdown);
    }
    
    /**
     * Test that the parser detects dynamic require statements.
     */
    public function testParserDetectsDynamicRequires()
    {
        // Create a temporary file with dynamic require statements
        $tempFile = tempnam(sys_get_temp_dir(), 'php-dep-test-');
        file_put_contents($tempFile, '<?php
            // Dynamic require with variable
            $moduleName = "test";
            require_once "modules/{$moduleName}.php";
            
            // Dynamic require with concatenation
            include_once "modules/" . $moduleName . ".class.php";
        ');
        
        try {
            // Create a parser
            $parser = new PhpFileParser();
            
            // Parse the file
            $parseResult = $parser->parse($tempFile);
            
            // Check that dynamic require statements were detected
            $this->assertArrayHasKey('dynamicRequireStatements', $parseResult);
            $this->assertNotEmpty($parseResult['dynamicRequireStatements']);
            
            // Check that the analyzer adds dynamic require statements to the node
            $analyzer = new DependencyAnalyzer(null, null, true);
            $node = $analyzer->analyze($tempFile);
            
            $dynamicRequires = $node->getDynamicRequireStatements();
            $this->assertNotEmpty($dynamicRequires);
            
            // Generate markdown
            $markdown = $node->toMarkdown();
            
            // Check that the markdown contains the dynamic dependencies section
            $this->assertStringContainsString('## Dynamic Dependencies', $markdown);
            $this->assertStringContainsString('These dependencies could not be resolved because they use dynamic file paths', $markdown);
        } finally {
            // Clean up
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
