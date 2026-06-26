<?php
namespace EsotericCurrent\Core\Tests;

use EsotericCurrent\Core\Editorial\Workflow_Engine;
use PHPUnit\Framework\TestCase;

class Test_Workflow_Engine extends TestCase {
    public function test_discovered_can_transition_to_collected(): void {
        $this->assertTrue(Workflow_Engine::can_transition('discovered', 'collected'));
    }

    public function test_discovered_cannot_skip_to_approved(): void {
        $this->assertFalse(Workflow_Engine::can_transition('discovered', 'approved'));
    }

    public function test_published_can_transition_to_archived(): void {
        $this->assertTrue(Workflow_Engine::can_transition('published', 'archived'));
    }

    public function test_unknown_state_has_no_transitions(): void {
        $this->assertEmpty(Workflow_Engine::get_valid_transitions('nonexistent'));
    }

    public function test_rejected_can_return_to_review(): void {
        $this->assertTrue(Workflow_Engine::can_transition('rejected', 'awaiting_review'));
    }
}
