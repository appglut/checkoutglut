<?php
/**
 * CheckoutGlut Release Script
 * Works on Windows, Linux, and macOS
 *
 * Usage: php release.php 1.5.0
 */

$version = $argv[1] ?? '';

if (empty($version)) {
    echo "Usage: php release.php VERSION\n";
    echo "Example: php release.php 1.5.0\n";
    exit(1);
}

if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
    echo "Error: Version must be in format x.y.z\n";
    exit(1);
}

$pluginFile = __DIR__ . '/checkoutglut.php';

if (!file_exists($pluginFile)) {
    echo "Error: checkoutglut.php not found\n";
    exit(1);
}

$content = file_get_contents($pluginFile);
$content = preg_replace('/Version:\s*\d+\.\d+\.\d+/', 'Version: ' . $version, $content);
file_put_contents($pluginFile, $content);
echo "✅ Updated version to $version\n";

echo "📝 Committing...\n";
passthru('git add checkoutglut.php');
passthru('git commit -m "Version ' . $version . '"');

echo "🏷️  Creating tag v$version...\n";
passthru('git tag v' . $version);

echo "📤 Pushing...\n";
passthru('git push origin main');
passthru('git push origin v' . $version);

echo "\n✅ CheckoutGlut v$version released!\n";
