<?php

declare(strict_types=1);

namespace App\Services\Git;

use Symfony\Component\Process\Process;

final class GitRangeReaderService
{
    /**
     * @return array<int,array{hash:string,message:string,author:string,timestamp:string}>
     */
    public function read(string $repoPath, string $fromRef, string $toRef): array
    {
        $process = new Process([
            'git',
            '-C',
            $repoPath,
            'log',
            '--pretty=format:%H|%an|%aI|%s%n%b<<<EOC>>>',
            $fromRef.'..'.$toRef,
        ]);

        $process->setTimeout(90);
        $process->mustRun();

        $output = trim($process->getOutput());

        if ($output === '') {
            return [];
        }

        $rawChunks = array_filter(explode('<<<EOC>>>', $output));

        return array_values(array_filter(array_map(function (string $chunk): ?array {
            $lines = preg_split('/\R/', trim($chunk)) ?: [];
            $header = array_shift($lines);

            if ($header === null) {
                return null;
            }

            [$hash, $author, $timestamp, $subject] = array_pad(explode('|', $header, 4), 4, '');

            return [
                'hash' => trim($hash),
                'message' => trim($subject."\n".implode("\n", $lines)),
                'author' => trim($author),
                'timestamp' => trim($timestamp),
            ];
        }, $rawChunks)));
    }
}
