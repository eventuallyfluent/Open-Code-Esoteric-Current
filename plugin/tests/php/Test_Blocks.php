<?php
namespace EsotericCurrent\Core\Tests;

use EsotericCurrent\Core\Blocks\Block_Registrar;
use EsotericCurrent\Core\Blocks\Unified_Search_Block;
use EsotericCurrent\Core\Blocks\Editorial_Feed_Block;
use PHPUnit\Framework\TestCase;

class Test_Blocks extends TestCase {
    public function test_block_attributes_have_required_keys(): void {
        $blocks = [
            'Unified_Search_Block',
            'Editorial_Feed_Block',
            'Submission_Form_Block',
            'Resource_Index_Block',
            'Source_Record_Block',
            'Issue_Contents_Block',
        ];

        foreach ($blocks as $block) {
            $class = 'EsotericCurrent\\Core\\Blocks\\' . $block;
            $attrs = $class::attributes();
            $this->assertIsArray($attrs, "$block attributes should return array");
        }
    }

    public function test_unified_search_placeholder_default(): void {
        $attrs = Unified_Search_Block::attributes();
        $this->assertSame('Search findings, resources, editorial...', $attrs['placeholder']['default']);
    }

    public function test_editorial_feed_default_count(): void {
        $attrs = Editorial_Feed_Block::attributes();
        $this->assertSame(10, $attrs['count']['default']);
    }
}
