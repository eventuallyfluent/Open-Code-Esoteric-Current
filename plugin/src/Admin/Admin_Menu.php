<?php
namespace EsotericCurrent\Core\Admin;

class Admin_Menu {
    public static function register(): void {
        add_menu_page(
            'Esoteric Current',
            'Esoteric Current',
            'manage_options',
            'ec-dashboard',
            [Dashboard_Page::class, 'render'],
            'dashicons-welcome-learn-more',
            30
        );

        $pages = [
            'ec-dashboard'       => ['Dashboard', Dashboard_Page::class],
            'ec-research-topics' => ['Research Briefs', Research_Topics_Page::class],
            'ec-agent-runs'      => ['Agent Runs', Agent_Runs_Page::class],
            'ec-findings'        => ['Findings', Findings_Page::class],
            'ec-sources'         => ['Sources', Sources_Page::class],
            'ec-editorial'       => ['Editorial Queue', Editorial_Queue_Page::class],
            'ec-resources'       => ['Resources', Resources_Page::class],
            'ec-issues'          => ['Issues', Issues_Page::class],
            'ec-submissions'     => ['Submissions', Submissions_Page::class],
            'ec-automation'      => ['Automation', Automation_Page::class],
            'ec-settings'        => ['Settings', Settings_Page::class],
            'ec-health'          => ['System Health', System_Health_Page::class],
        ];

        foreach ($pages as $slug => [$title, $class]) {
            add_submenu_page(
                'ec-dashboard',
                $title,
                $title,
                'manage_options',
                $slug,
                [$class, 'render']
            );
        }
    }
}
