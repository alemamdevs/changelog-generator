<?php

use App\Services\Changelog\ChangelogGeneratorService;
use App\Services\Changelog\CommitParserService;
use App\Services\AI\IAIService;
use App\Services\AI\AIResponse;
use App\Repositories\ChangelogRunRepositoryInterface;
use App\Repositories\CommitRepositoryInterface;
use App\Repositories\ChangelogEntryRepositoryInterface;
use App\Models\Release;
use App\Models\Commit;
use App\Models\Changelog;

it('generates changelog from commit messages', function () {
    $parser = new CommitParserService();

    $fakeAi = new class implements IAIService {
        public function generateChangelog(array $parsedCommits, array $options = []): AIResponse
        {
            $structured = [
                'sections' => [
                    ['kind' => 'Features', 'items' => ['Add feature X']],
                    ['kind' => 'Bug Fixes', 'items' => ['Fix bug Z']],
                ],
            ];
            return new AIResponse(json_encode($structured), $structured);
        }
    };

    $commitStore = [];
    $fakeCommitRepo = new class($commitStore) implements CommitRepositoryInterface {
        public function __construct(private array &$store) {}
        public function createCommit(array $data): Commit
        {
            $c = new Commit();
            $c->id = rand(1000, 9999);
            $c->release_id = $data['release_id'] ?? null;
            $c->commit_hash = $data['commit_hash'] ?? null;
            $c->message = $data['message'] ?? null;
            $c->author = $data['author'] ?? null;
            $this->store[] = $c;
            return $c;
        }
    };

    $entryStore = [];
    $fakeEntryRepo = new class($entryStore) implements ChangelogEntryRepositoryInterface {
        public function __construct(private array &$store) {}
        public function createEntry(array $data): Changelog
        {
            $e = new Changelog();
            $e->category = $data['category'] ?? null;
            $e->description = $data['description'] ?? null;
            $this->store[] = $e;
            return $e;
        }
    };

    $fakeRunRepo = new class implements ChangelogRunRepositoryInterface {
        public function createRun(array $data): Release
        {
            $r = new Release();
            $r->id = 999;
            $r->version = $data['version'] ?? 'v0.0.0';
            return $r;
        }
    };

    $generator = new ChangelogGeneratorService($parser, $fakeAi, $fakeRunRepo, $fakeCommitRepo, $fakeEntryRepo);

    $rawCommits = [
        ['id' => 'abc123', 'message' => 'feat(cart): add bulk discount', 'author' => 'Alice', 'timestamp' => '2026-03-30T11:00:00Z'],
        ['id' => 'def456', 'message' => 'fix(cart): coupon bug', 'author' => 'Bob', 'timestamp' => '2026-03-30T10:50:00Z'],
    ];

    $release = $generator->generateFromRawCommits($rawCommits, ['version' => 'v1.0.0', 'branch' => 'main']);

    expect($release->version)->toBe('v1.0.0');
    expect(count($commitStore))->toBe(2);
    expect(count($entryStore))->toBeGreaterThan(0);
});

it('handles commits without conventional prefix', function () {
    $parser = new CommitParserService();

    $fakeAi = new class implements IAIService {
        public function generateChangelog(array $parsedCommits, array $options = []): AIResponse
        {
            return new AIResponse('{}', ['sections' => []]);
        }
    };

    $commitStore = [];
    $fakeCommitRepo = new class($commitStore) implements CommitRepositoryInterface {
        public function __construct(private array &$store) {}
        public function createCommit(array $data): Commit
        {
            $c = new Commit();
            $c->message = $data['message'] ?? null;
            $this->store[] = $c;
            return $c;
        }
    };

    $fakeEntryRepo = new class implements ChangelogEntryRepositoryInterface {
        public function createEntry(array $data): Changelog
        {
            return new Changelog();
        }
    };

    $fakeRunRepo = new class implements ChangelogRunRepositoryInterface {
        public function createRun(array $data): Release
        {
            $r = new Release();
            $r->id = 999;
            return $r;
        }
    };

    $generator = new ChangelogGeneratorService($parser, $fakeAi, $fakeRunRepo, $fakeCommitRepo, $fakeEntryRepo);

    $rawCommits = [
        ['id' => 'xyz789', 'message' => 'Random commit without prefix', 'author' => 'Charlie'],
    ];

    $release = $generator->generateFromRawCommits($rawCommits);

    expect($release->id)->toBe(999);
    expect(count($commitStore))->toBe(1);
    expect($commitStore[0]->message)->toBe('Random commit without prefix');
});
