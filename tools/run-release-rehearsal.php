<?php

function releaseRehearsalPrint($message)
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function releaseRehearsalFail($message)
{
    fwrite(STDERR, $message . PHP_EOL);
}

function releaseRehearsalHasOption(array $arguments, $option)
{
    return in_array($option, $arguments, true);
}

function releaseRehearsalRunCommand($command, &$output, &$exitCode)
{
    $output = array();
    exec($command . ' 2>&1', $output, $exitCode);

    return implode("\n", $output);
}

function releaseRehearsalCreateTempDir($baseDir)
{
    $tempFile = tempnam($baseDir, 'storepackage-release-');
    if ($tempFile === false) {
        throw new RuntimeException('Unable to allocate a temporary path for release rehearsal.');
    }

    if (file_exists($tempFile) && !unlink($tempFile)) {
        throw new RuntimeException('Unable to prepare the release rehearsal directory.');
    }

    if (!mkdir($tempFile, 0777, true)) {
        throw new RuntimeException('Unable to create the release rehearsal directory.');
    }

    return $tempFile;
}

$arguments = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();
$checkRemotes = releaseRehearsalHasOption($arguments, '--check-remotes');
$keepWorktree = releaseRehearsalHasOption($arguments, '--keep-worktree');
$rootPath = dirname(__DIR__);
$worktreePath = null;

releaseRehearsalPrint('Release rehearsal');
releaseRehearsalPrint('Repository: ' . $rootPath);

$gitCheck = releaseRehearsalRunCommand('git rev-parse --is-inside-work-tree', $gitCheckOutput, $gitCheckExitCode);
if ($gitCheckExitCode !== 0 || trim($gitCheck) !== 'true') {
    releaseRehearsalFail('Current directory is not a git worktree.');
    exit(1);
}

$statusOutput = releaseRehearsalRunCommand('git -C ' . escapeshellarg($rootPath) . ' status --short', $statusLines, $statusExitCode);
if ($statusExitCode === 0 && trim($statusOutput) !== '') {
    releaseRehearsalPrint('Note: rehearsal uses committed HEAD only; uncommitted workspace changes are intentionally excluded.');
}

$headCommit = releaseRehearsalRunCommand('git rev-parse --verify HEAD', $headOutput, $headExitCode);
if ($headExitCode !== 0) {
    releaseRehearsalFail('Unable to resolve HEAD for release rehearsal.');
    exit(1);
}
$headCommit = trim($headCommit);
releaseRehearsalPrint('Rehearsal commit: ' . $headCommit);

try {
    $worktreePath = releaseRehearsalCreateTempDir('/tmp');
    $addWorktreeCommand = 'git worktree add --detach ' . escapeshellarg($worktreePath) . ' ' . escapeshellarg($headCommit);
    $addWorktreeOutput = releaseRehearsalRunCommand($addWorktreeCommand, $addWorktreeLines, $addWorktreeExitCode);

    if ($addWorktreeExitCode !== 0) {
        throw new RuntimeException('Unable to create temporary release worktree.' . ($addWorktreeOutput !== '' ? "\n" . $addWorktreeOutput : ''));
    }

    releaseRehearsalPrint('Temporary clean worktree: ' . $worktreePath);

    $preflightCommand = 'php ' . escapeshellarg($rootPath . '/tools/run-release-dry-run.php')
        . ' --root=' . escapeshellarg($worktreePath);
    if ($checkRemotes) {
        $preflightCommand .= ' --check-remotes';
    }

    $preflightOutput = releaseRehearsalRunCommand($preflightCommand, $preflightLines, $preflightExitCode);
    if ($preflightOutput !== '') {
        releaseRehearsalPrint('');
        releaseRehearsalPrint($preflightOutput);
    }

    if ($preflightExitCode !== 0) {
        throw new RuntimeException('Release rehearsal failed inside the temporary clean worktree.');
    }

    releaseRehearsalPrint('');
    releaseRehearsalPrint('Release rehearsal passed.');
    if ($checkRemotes) {
        releaseRehearsalPrint('Remote reachability was checked in the clean worktree.');
    } else {
        releaseRehearsalPrint('Remote reachability was not checked. Re-run with --check-remotes in a release-ready environment.');
    }
} catch (RuntimeException $exception) {
    releaseRehearsalFail('');
    releaseRehearsalFail($exception->getMessage());
    if ($worktreePath !== null) {
        releaseRehearsalFail('Temporary worktree: ' . $worktreePath);
    }
    exit(1);
} finally {
    if ($worktreePath !== null && !$keepWorktree) {
        releaseRehearsalRunCommand(
            'git worktree remove --force ' . escapeshellarg($worktreePath),
            $removeOutput,
            $removeExitCode
        );
    }
}
