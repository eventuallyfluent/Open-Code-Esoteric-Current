<?php
namespace EsotericCurrent\Core\Editorial;

class Workflow_Engine {
    private const VALID_TRANSITIONS = [
        'discovered'          => ['collected', 'rejected', 'duplicate'],
        'collected'           => ['awaiting_research', 'rejected', 'duplicate'],
        'awaiting_research'   => ['researching', 'rejected'],
        'researching'         => ['awaiting_review', 'failed'],
        'awaiting_review'     => ['approved', 'rejected', 'duplicate'],
        'approved'            => ['scheduled', 'archived', 'awaiting_review'],
        'scheduled'           => ['published', 'awaiting_review'],
        'published'           => ['archived'],
        'archived'            => ['published'],
        'rejected'            => ['awaiting_review'],
        'duplicate'           => ['awaiting_review'],
        'failed'              => ['awaiting_research'],
    ];

    public static function can_transition(string $from, string $to): bool {
        return in_array($to, self::VALID_TRANSITIONS[$from] ?? []);
    }

    public static function transition(\EsotericCurrent\Core\Repository\Editorial_Queue_Repository $repo, int $queue_id, string $new_state): bool {
        $item = $repo->get_by_id($queue_id);
        if ($item === null) {
            return false;
        }

        if (!self::can_transition($item['workflow_state'], $new_state)) {
            return false;
        }

        return $repo->transition($queue_id, $new_state);
    }

    public static function get_valid_transitions(string $state): array {
        return self::VALID_TRANSITIONS[$state] ?? [];
    }
}
