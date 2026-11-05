<?php

// Run after npm ci && npm run build. Composer runs in an isolated temporary
// directory; the development vendor directory and .env are never changed.
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;

require dirname(__DIR__).'/vendor/autoload.php';

$base = dirname(__DIR__);
$outputDirectory = $base.'/storage/app/releases';
$stage = sys_get_temp_dir().'/simaster-cpanel-'.bin2hex(random_bytes(8));
$zipPath = $outputDirectory.'/simaster-cpanel-filament-php82-'.date('Ymd-His').'.zip';
$copy = function (string $source, string $target) use (&$copy): void {
    if (is_link($source)) {
        return;
    }
    if (is_dir($source)) {
        if (! is_dir($target) && ! mkdir($target, 0755, true)) {
            throw new RuntimeException('Cannot create package directory.');
        }
        foreach (new DirectoryIterator($source) as $entry) {
            if ($entry->isDot()) {
                continue;
            }
            $name = $entry->getFilename();
            if (in_array($name, ['hot', '.DS_Store', 'database.sqlite'], true)
                || str_ends_with($name, '.xlsx')
                || (basename($source) === 'cache' && str_ends_with($name, '.php'))
                || ($source === dirname(__DIR__).'/public/data' && str_starts_with($name, 'equipment_'))) {
                continue;
            }
            $copy($entry->getPathname(), $target.'/'.$name);
        }
    } elseif (! copy($source, $target)) {
        throw new RuntimeException('Cannot copy package file.');
    }
};
$removeStage = function (string $directory) use ($stage): void {
    $resolved = realpath($directory);
    $expected = realpath($stage);
    if ($resolved === false || $expected === false || $resolved !== $expected
        || ! str_starts_with(basename($resolved), 'simaster-cpanel-')) {
        throw new RuntimeException('Unsafe temporary cleanup path.');
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $entry) {
        $entry->isDir() && ! $entry->isLink() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
    }
    rmdir($resolved);
};

$exitCode = 0;
try {
    if (is_file($base.'/public/hot')) {
        throw new RuntimeException('Stop Vite dev and remove public/hot before packaging.');
    }
    if (! is_file($base.'/public/build/manifest.json')) {
        throw new RuntimeException('Build Vite assets before packaging.');
    }
    foreach (['telkomsel-logo.png', 'filter-icon.png'] as $image) {
        if (! is_file($base.'/public/images/'.$image) || filesize($base.'/public/images/'.$image) === 0) {
            throw new RuntimeException('Required public image is missing: '.$image);
        }
    }
    mkdir($stage, 0755, true);
    foreach (['app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'deploy'] as $directory) {
        $copy($base.'/'.$directory, $stage.'/'.$directory);
    }
    $copy($base.'/scripts/cpanel-deploy-check.php', $stage.'/deploy/cpanel/deploy-check.php');
    foreach (['artisan', 'composer.json', 'composer.lock', '.env.cpanel.example'] as $file) {
        $copy($base.'/'.$file, $stage.'/'.$file);
    }
    mkdir($stage.'/docs', 0755, true);
    $copy($base.'/docs/deploy-cpanel.md', $stage.'/docs/deploy-cpanel.md');
    foreach (['app/private', 'app/public', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
        mkdir($stage.'/storage/'.$directory, 0755, true);
    }

    $composer = (new ExecutableFinder)->find('composer');
    if ($composer === null) {
        throw new RuntimeException('Composer executable is unavailable.');
    }
    $process = new Process([PHP_BINARY, $composer, 'install', '--no-dev', '--prefer-dist', '--optimize-autoloader', '--no-interaction'], $stage);
    $process->setTimeout(600);
    $process->mustRun(function ($type, $buffer): void { echo $buffer; });
    $platform = new Process([PHP_BINARY, $composer, 'check-platform-reqs', '--no-dev'], $stage);
    $platform->mustRun(function ($type, $buffer): void { echo $buffer; });
    // package:discover regenerates only server-independent package metadata.
    // No cached environment, routes, views, logs or operational data ship.
    foreach (['config.php', 'events.php', 'routes-v7.php'] as $cache) {
        if (is_file($stage.'/bootstrap/cache/'.$cache)) {
            unlink($stage.'/bootstrap/cache/'.$cache);
        }
    }
    if (! is_dir($outputDirectory)) {
        mkdir($outputDirectory, 0700, true);
    }
    $zip = new ZipArchive;
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
        throw new RuntimeException('Cannot create release ZIP.');
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $entry) {
        $relative = substr($entry->getPathname(), strlen($stage) + 1);
        $archivePath = 'simaster/'.str_replace('\\', '/', $relative);
        if ($entry->isLink()) {
            continue;
        }
        $ok = $entry->isDir() ? $zip->addEmptyDir($archivePath) : $zip->addFile($entry->getPathname(), $archivePath);
        if (! $ok) {
            throw new RuntimeException('Cannot add release file.');
        }
    }
    $zip->addFromString('RELEASE.json', json_encode([
        'built_at' => date(DATE_ATOM),
        'php_minimum' => '8.2.0',
        'php_build_version' => PHP_VERSION,
        'filament_version' => \Composer\InstalledVersions::getPrettyVersion('filament/filament'),
        'composer_lock_sha256' => hash_file('sha256', $base.'/composer.lock'),
        'vite_manifest_sha256' => hash_file('sha256', $base.'/public/build/manifest.json'),
        'source_commit' => getenv('GITHUB_SHA') ?: null,
        'includes_credentials' => false,
        'includes_database_or_documents' => false,
    ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    if (! $zip->close()) {
        throw new RuntimeException('Cannot finish release ZIP.');
    }
    chmod($zipPath, 0600);
    $verify = new ZipArchive;
    if ($verify->open($zipPath, ZipArchive::CHECKCONS) !== true) {
        throw new RuntimeException('Release ZIP verification failed.');
    }
    $verify->close();
    file_put_contents($zipPath.'.sha256', hash_file('sha256', $zipPath).'  '.basename($zipPath).PHP_EOL);
    echo 'Release verified: '.$zipPath.PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Packaging failed: '.$exception->getMessage().PHP_EOL);
    $exitCode = 1;
} finally {
    if (is_dir($stage)) {
        $removeStage($stage);
    }
}
exit($exitCode);
