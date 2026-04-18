<?php

function releaseDryRunPrint($message)
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function releaseDryRunFail($message)
{
    fwrite(STDERR, $message . PHP_EOL);
}

function releaseDryRunHasOption(array $arguments, $option)
{
    return in_array($option, $arguments, true);
}

function releaseDryRunGetOptionValue(array $arguments, $prefix)
{
    foreach ($arguments as $argument) {
        if (strpos($argument, $prefix) === 0) {
            return substr($argument, strlen($prefix));
        }
    }

    return null;
}

function releaseDryRunRunCommand($command, &$output, &$exitCode)
{
    $output = array();
    exec($command . ' 2>&1', $output, $exitCode);

    return implode("\n", $output);
}

function releaseDryRunLoadJsonFile($path)
{
    if (!is_file($path)) {
        throw new RuntimeException(sprintf('Required file not found: %s', $path));
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException(sprintf('Unable to read file: %s', $path));
    }

    $decoded = json_decode($contents, true);
    if (!is_array($decoded)) {
        throw new RuntimeException(sprintf('Invalid JSON file: %s', $path));
    }

    return $decoded;
}

function releaseDryRunCheckSchemaSync($rootPath, &$failures)
{
    $pairs = array(
        array(
            $rootPath . '/database/schema/mysql.sql',
            $rootPath . '/packages/warehouse-pdo-adapter/resources/schema/mysql.sql',
            'MySQL schema copy matches root source of truth'
        ),
        array(
            $rootPath . '/database/schema/postgresql.sql',
            $rootPath . '/packages/warehouse-pdo-adapter/resources/schema/postgresql.sql',
            'PostgreSQL schema copy matches root source of truth'
        ),
    );

    foreach ($pairs as $pair) {
        $source = file_get_contents($pair[0]);
        $copy = file_get_contents($pair[1]);

        if ($source === false || $copy === false) {
            $failures[] = 'Unable to read schema files for sync check.';
            continue;
        }

        if ($source !== $copy) {
            $failures[] = $pair[2] . ': FAILED';
            continue;
        }

        releaseDryRunPrint('[ok] ' . $pair[2]);
    }
}

$arguments = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();
$allowDirty = releaseDryRunHasOption($arguments, '--allow-dirty');
$checkRemotes = releaseDryRunHasOption($arguments, '--check-remotes');
$rootOverride = releaseDryRunGetOptionValue($arguments, '--root=');
$rootPath = $rootOverride !== null ? $rootOverride : dirname(__DIR__);
$rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
$failures = array();
$rootShellPath = escapeshellarg($rootPath);

releaseDryRunPrint('Release dry-run preflight');
releaseDryRunPrint('Repository: ' . $rootPath);

$gitCheck = releaseDryRunRunCommand('git -C ' . $rootShellPath . ' rev-parse --is-inside-work-tree', $gitCheckOutput, $gitCheckExitCode);
if ($gitCheckExitCode !== 0 || trim($gitCheck) !== 'true') {
    releaseDryRunFail('[fail] Current directory is not a git worktree.');
    exit(1);
}
releaseDryRunPrint('[ok] Real git worktree detected');

$statusOutput = releaseDryRunRunCommand('git -C ' . $rootShellPath . ' status --short', $statusLines, $statusExitCode);
if ($statusExitCode !== 0) {
    $failures[] = 'Unable to inspect git status.';
} elseif (trim($statusOutput) !== '') {
    if ($allowDirty) {
        releaseDryRunPrint('[warn] Worktree is dirty, but --allow-dirty was used');
    } else {
        $failures[] = 'Worktree is dirty. Commit or stash release-relevant changes before a strict dry-run.';
    }
} else {
    releaseDryRunPrint('[ok] Worktree is clean');
}

$composerCheck = releaseDryRunRunCommand('composer --version', $composerVersionOutput, $composerVersionExitCode);
if ($composerVersionExitCode !== 0) {
    $failures[] = 'Composer is not available in PATH.';
} else {
    releaseDryRunPrint('[ok] Composer detected');
}

if ($composerVersionExitCode === 0) {
    $rootValidate = releaseDryRunRunCommand(
        'cd ' . $rootShellPath . ' && composer validate --strict',
        $rootValidateOutput,
        $rootValidateExitCode
    );
    if ($rootValidateExitCode !== 0) {
        $failures[] = 'Root composer.json failed composer validate --strict.';
    } else {
        releaseDryRunPrint('[ok] Root composer.json validates strictly');
    }

    $adapterValidate = releaseDryRunRunCommand(
        'cd ' . $rootShellPath . ' && composer validate --strict '
            . escapeshellarg($rootPath . '/packages/warehouse-pdo-adapter/composer.json'),
        $adapterValidateOutput,
        $adapterValidateExitCode
    );
    if ($adapterValidateExitCode !== 0) {
        $failures[] = 'Adapter composer.json failed composer validate --strict.';
    } else {
        releaseDryRunPrint('[ok] Adapter composer.json validates strictly');
    }
}

try {
    $rootComposer = releaseDryRunLoadJsonFile($rootPath . '/composer.json');
    $adapterComposer = releaseDryRunLoadJsonFile($rootPath . '/packages/warehouse-pdo-adapter/composer.json');

    if (!isset($rootComposer['name']) || $rootComposer['name'] !== 'storepackage/warehouse-core') {
        $failures[] = 'Root composer package name is not storepackage/warehouse-core.';
    } else {
        releaseDryRunPrint('[ok] Root package name matches warehouse-core');
    }

    if (!isset($adapterComposer['require']['storepackage/warehouse-core'])) {
        $failures[] = 'Adapter package does not declare a warehouse-core requirement.';
    } else {
        releaseDryRunPrint('[ok] Adapter package declares a warehouse-core compatibility line');
    }
} catch (RuntimeException $exception) {
    $failures[] = $exception->getMessage();
}

releaseDryRunCheckSchemaSync($rootPath, $failures);

$remotesOutput = releaseDryRunRunCommand('git -C ' . $rootShellPath . ' remote', $remoteLines, $remoteExitCode);
if ($remoteExitCode !== 0) {
    $failures[] = 'Unable to inspect git remotes.';
} else {
    $remoteNames = array_filter(array_map('trim', explode("\n", $remotesOutput)));

    if (!in_array('origin', $remoteNames, true)) {
        $failures[] = 'origin remote is not configured.';
    } else {
        releaseDryRunPrint('[ok] origin remote is configured');
    }

    if (!in_array('adapter-remote', $remoteNames, true)) {
        $failures[] = 'adapter-remote is not configured.';
    } else {
        releaseDryRunPrint('[ok] adapter-remote is configured');
    }

    if ($checkRemotes && empty($failures)) {
        $originReachable = releaseDryRunRunCommand(
            'git -C ' . $rootShellPath . ' ls-remote --exit-code origin HEAD',
            $originRemoteOutput,
            $originRemoteExitCode
        );
        if ($originRemoteExitCode !== 0) {
            $failures[] = 'origin remote is not reachable from this environment.';
        } else {
            releaseDryRunPrint('[ok] origin remote is reachable');
        }

        $adapterReachable = releaseDryRunRunCommand(
            'git -C ' . $rootShellPath . ' ls-remote --exit-code adapter-remote HEAD',
            $adapterRemoteOutput,
            $adapterRemoteExitCode
        );
        if ($adapterRemoteExitCode !== 0) {
            $failures[] = 'adapter-remote is not reachable from this environment.';
        } else {
            releaseDryRunPrint('[ok] adapter-remote is reachable');
        }
    }
}

$treeOutput = releaseDryRunRunCommand(
    'git -C ' . $rootShellPath . ' ls-tree --name-only -r HEAD ' . escapeshellarg('packages/warehouse-pdo-adapter'),
    $treeLines,
    $treeExitCode
);
if ($treeExitCode !== 0 || trim($treeOutput) === '') {
    $failures[] = 'packages/warehouse-pdo-adapter is not present in committed HEAD.';
} else {
    releaseDryRunPrint('[ok] Adapter package exists in committed HEAD');
}

if (empty($failures)) {
    $splitCommit = releaseDryRunRunCommand(
        'cd ' . $rootShellPath . ' && git subtree split -q --prefix=' . escapeshellarg('packages/warehouse-pdo-adapter') . ' HEAD',
        $splitOutput,
        $splitExitCode
    );

    if ($splitExitCode !== 0) {
        $failures[] = 'git subtree split failed for packages/warehouse-pdo-adapter.';
    } else {
        $splitLines = array_filter(array_map('trim', explode("\n", $splitCommit)));
        $splitSha = end($splitLines);
        releaseDryRunPrint('[ok] Subtree split dry-run produced commit ' . $splitSha);
    }
}

if (!empty($failures)) {
    releaseDryRunFail('');
    releaseDryRunFail('Release dry-run failed:');
    foreach ($failures as $failure) {
        releaseDryRunFail('- ' . $failure);
    }
    exit(1);
}

releaseDryRunPrint('');
releaseDryRunPrint('Release dry-run passed.');
if (!$checkRemotes) {
    releaseDryRunPrint('Remote reachability was not checked. Re-run with --check-remotes in a release-ready environment.');
}
