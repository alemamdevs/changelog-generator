<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Changelog;
use App\Models\Commit;
use App\Models\Release;
use Illuminate\Database\Seeder;

class ChangelogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Release 1.0.0
        $release1 = Release::create([
            'version' => 'v1.0.0',
            'branch' => 'main',
            'generated_at' => now()->subDays(30),
        ]);

        // Add commits for Release 1.0.0
        Commit::create([
            'release_id' => $release1->id,
            'commit_hash' => 'abc123def456',
            'author' => 'John Doe <john@example.com>',
            'message' => 'feat: add user authentication system',
            'type' => 'feature',
            'authored_at' => now()->subDays(30),
        ]);

        Commit::create([
            'release_id' => $release1->id,
            'commit_hash' => 'def456ghi789',
            'author' => 'Jane Smith <jane@example.com>',
            'message' => 'fix: resolve session timeout bug',
            'type' => 'bug fix',
            'authored_at' => now()->subDays(29),
        ]);

        Commit::create([
            'release_id' => $release1->id,
            'commit_hash' => 'ghi789jkl012',
            'author' => 'John Doe <john@example.com>',
            'message' => 'feat: implement dashboard widgets',
            'type' => 'feature',
            'authored_at' => now()->subDays(28),
        ]);

        Commit::create([
            'release_id' => $release1->id,
            'commit_hash' => 'jkl012mno345',
            'author' => 'Bob Johnson <bob@example.com>',
            'message' => 'chore: update dependencies',
            'type' => 'chore',
            'authored_at' => now()->subDays(27),
        ]);

        // Add changelogs for Release 1.0.0
        Changelog::create([
            'release_id' => $release1->id,
            'category' => 'feature',
            'description' => 'User Authentication System',
            'details' => 'Implemented complete user authentication system with email verification and password reset functionality.',
            'position' => 1,
        ]);

        Changelog::create([
            'release_id' => $release1->id,
            'category' => 'feature',
            'description' => 'Dashboard Widgets',
            'details' => 'Added customizable dashboard widgets for better user experience and quick access to key metrics.',
            'position' => 2,
        ]);

        Changelog::create([
            'release_id' => $release1->id,
            'category' => 'bug fix',
            'description' => 'Session Timeout Issue',
            'details' => 'Fixed an issue where user sessions were timing out prematurely after 15 minutes of inactivity.',
            'position' => 3,
        ]);

        Changelog::create([
            'release_id' => $release1->id,
            'category' => 'chore',
            'description' => 'Dependency Updates',
            'details' => 'Updated Laravel to v11, Pest to v3, and other critical dependencies to latest stable versions.',
            'position' => 4,
        ]);

        // Create Release 1.1.0
        $release2 = Release::create([
            'version' => 'v1.1.0',
            'branch' => 'main',
            'generated_at' => now()->subDays(15),
        ]);

        // Add commits for Release 1.1.0
        Commit::create([
            'release_id' => $release2->id,
            'commit_hash' => 'mno345pqr678',
            'author' => 'Alice Cooper <alice@example.com>',
            'message' => 'feat: add export to PDF functionality',
            'type' => 'feature',
            'authored_at' => now()->subDays(15),
        ]);

        Commit::create([
            'release_id' => $release2->id,
            'commit_hash' => 'pqr678stu901',
            'author' => 'John Doe <john@example.com>',
            'message' => 'feat: implement email notifications',
            'type' => 'feature',
            'authored_at' => now()->subDays(14),
        ]);

        Commit::create([
            'release_id' => $release2->id,
            'commit_hash' => 'stu901vwx234',
            'author' => 'Jane Smith <jane@example.com>',
            'message' => 'fix: correct calculation bug in reporting module',
            'type' => 'bug fix',
            'authored_at' => now()->subDays(13),
        ]);

        Commit::create([
            'release_id' => $release2->id,
            'commit_hash' => 'vwx234yza567',
            'author' => 'Bob Johnson <bob@example.com>',
            'message' => 'perf: optimize database queries',
            'type' => 'performance',
            'authored_at' => now()->subDays(12),
        ]);

        Commit::create([
            'release_id' => $release2->id,
            'commit_hash' => 'yza567bcd890',
            'author' => 'Alice Cooper <alice@example.com>',
            'message' => 'docs: update API documentation',
            'type' => 'documentation',
            'authored_at' => now()->subDays(11),
        ]);

        // Add changelogs for Release 1.1.0
        Changelog::create([
            'release_id' => $release2->id,
            'category' => 'feature',
            'description' => 'PDF Export Feature',
            'details' => 'Users can now export reports and data to PDF format with customizable layouts and branding.',
            'position' => 1,
        ]);

        Changelog::create([
            'release_id' => $release2->id,
            'category' => 'feature',
            'description' => 'Email Notifications',
            'details' => 'Implemented comprehensive email notification system for important events and alerts.',
            'position' => 2,
        ]);

        Changelog::create([
            'release_id' => $release2->id,
            'category' => 'bug fix',
            'description' => 'Reporting Module Calculation Error',
            'details' => 'Fixed a critical bug in the reporting module that was causing incorrect calculations in summary reports.',
            'position' => 3,
        ]);

        Changelog::create([
            'release_id' => $release2->id,
            'category' => 'performance',
            'description' => 'Database Query Optimization',
            'details' => 'Optimized slow database queries resulting in 40% faster page load times.',
            'position' => 4,
        ]);

        Changelog::create([
            'release_id' => $release2->id,
            'category' => 'documentation',
            'description' => 'API Documentation Updates',
            'details' => 'Updated API documentation with new endpoints and improved examples.',
            'position' => 5,
        ]);

        // Create Release 2.0.0
        $release3 = Release::create([
            'version' => 'v2.0.0',
            'branch' => 'main',
            'generated_at' => now()->subDays(5),
        ]);

        // Add commits for Release 2.0.0
        Commit::create([
            'release_id' => $release3->id,
            'commit_hash' => 'bcd890efg123',
            'author' => 'Charlie Brown <charlie@example.com>',
            'message' => 'feat: migrate to modern architecture',
            'type' => 'feature',
            'authored_at' => now()->subDays(5),
        ]);

        Commit::create([
            'release_id' => $release3->id,
            'commit_hash' => 'efg123hij456',
            'author' => 'Diana Prince <diana@example.com>',
            'message' => 'feat: add real-time collaboration',
            'type' => 'feature',
            'authored_at' => now()->subDays(4),
        ]);

        Commit::create([
            'release_id' => $release3->id,
            'commit_hash' => 'hij456klm789',
            'author' => 'Eve Johnson <eve@example.com>',
            'message' => 'feat: implement advanced search',
            'type' => 'feature',
            'authored_at' => now()->subDays(3),
        ]);

        Commit::create([
            'release_id' => $release3->id,
            'commit_hash' => 'klm789nop012',
            'author' => 'Frank Miller <frank@example.com>',
            'message' => 'fix: security vulnerability in auth token',
            'type' => 'bug fix',
            'authored_at' => now()->subDays(2),
        ]);

        Commit::create([
            'release_id' => $release3->id,
            'commit_hash' => 'nop012qrs345',
            'author' => 'Grace Lee <grace@example.com>',
            'message' => 'chore: release version 2.0.0',
            'type' => 'chore',
            'authored_at' => now()->subDay(1),
        ]);

        // Add changelogs for Release 2.0.0
        Changelog::create([
            'release_id' => $release3->id,
            'category' => 'feature',
            'description' => 'Modern Architecture Migration',
            'details' => 'Complete rewrite of the application using modern Laravel 12 patterns including Livewire 3 and Alpine.js.',
            'position' => 1,
        ]);

        Changelog::create([
            'release_id' => $release3->id,
            'category' => 'feature',
            'description' => 'Real-Time Collaboration',
            'details' => 'Multiple users can now collaborate on documents in real-time with live cursor positions and automatic sync.',
            'position' => 2,
        ]);

        Changelog::create([
            'release_id' => $release3->id,
            'category' => 'feature',
            'description' => 'Advanced Search',
            'details' => 'Powerful full-text search with filters, saved searches, and advanced query syntax support.',
            'position' => 3,
        ]);

        Changelog::create([
            'release_id' => $release3->id,
            'category' => 'bug fix',
            'description' => 'Security Vulnerability - Auth Token',
            'details' => 'Fixed a critical security vulnerability in authentication token validation that could allow privilege escalation.',
            'position' => 4,
        ]);

        // Create Release 2.1.0
        $release4 = Release::create([
            'version' => 'v2.1.0',
            'branch' => 'main',
            'generated_at' => now(),
        ]);

        // Add commits for Release 2.1.0
        Commit::create([
            'release_id' => $release4->id,
            'commit_hash' => 'qrs345tuz678',
            'author' => 'Henry Wilson <henry@example.com>',
            'message' => 'feat: add dark mode support',
            'type' => 'feature',
            'authored_at' => now()->subHours(5),
        ]);

        Commit::create([
            'release_id' => $release4->id,
            'commit_hash' => 'tuz678uvw901',
            'author' => 'Ivy Chen <ivy@example.com>',
            'message' => 'feat: mobile app support',
            'type' => 'feature',
            'authored_at' => now()->subHours(4),
        ]);

        Commit::create([
            'release_id' => $release4->id,
            'commit_hash' => 'uvw901xyz234',
            'author' => 'Jack Davis <jack@example.com>',
            'message' => 'fix: memory leak in background service',
            'type' => 'bug fix',
            'authored_at' => now()->subHours(3),
        ]);

        Commit::create([
            'release_id' => $release4->id,
            'commit_hash' => 'xyz234abc567',
            'author' => 'Karen Martinez <karen@example.com>',
            'message' => 'perf: reduce bundle size by 30%',
            'type' => 'performance',
            'authored_at' => now()->subHours(2),
        ]);

        // Add changelogs for Release 2.1.0
        Changelog::create([
            'release_id' => $release4->id,
            'category' => 'feature',
            'description' => 'Dark Mode Support',
            'details' => 'Full dark mode support with automatic switching based on system preferences.',
            'position' => 1,
        ]);

        Changelog::create([
            'release_id' => $release4->id,
            'category' => 'feature',
            'description' => 'Mobile App Support',
            'details' => 'Native mobile app is now available for iOS and Android with offline support.',
            'position' => 2,
        ]);

        Changelog::create([
            'release_id' => $release4->id,
            'category' => 'bug fix',
            'description' => 'Memory Leak Fix',
            'details' => 'Fixed a memory leak in background service that was causing crashes after 24 hours of continuous use.',
            'position' => 3,
        ]);

        Changelog::create([
            'release_id' => $release4->id,
            'category' => 'performance',
            'description' => 'Bundle Size Reduction',
            'details' => 'Reduced JavaScript bundle size by 30% through code splitting and tree-shaking optimization.',
            'position' => 4,
        ]);
    }
}
