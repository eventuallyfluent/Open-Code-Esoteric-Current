<?php
namespace EsotericCurrent\Core\Blocks;

class Block_Registrar {
    public static function register_all(): void {
        $blocks = [
            'ec/unified-search'     => Unified_Search_Block::class,
            'ec/editorial-feed'     => Editorial_Feed_Block::class,
            'ec/resource-index'     => Resource_Index_Block::class,
            'ec/source-record'      => Source_Record_Block::class,
            'ec/issue-contents'     => Issue_Contents_Block::class,
            'ec/submission-form'    => Submission_Form_Block::class,
        ];

        foreach ($blocks as $name => $class) {
            register_block_type($name, [
                'render_callback' => [$class, 'render'],
                'attributes' => $class::attributes(),
            ]);
        }
    }
}
