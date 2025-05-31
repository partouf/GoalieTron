<?php
/**
 * Simple HTTP server for testing GoalieTron pages
 * 
 * Usage: php serve-test-pages.php
 * Then open: http://localhost:8080
 */

$host = 'localhost';
$port = 8080;
$docroot = __DIR__ . '/test-pages';

if (!file_exists($docroot)) {
    echo "Error: Test pages not found. Run 'php test-page-generator.php' first.\n";
    exit(1);
}

echo "🎯 GoalieTron Test Server\n";
echo "========================\n";
echo "Starting server at: http://$host:$port\n";
echo "Document root: $docroot\n";
echo "Press Ctrl+C to stop\n\n";

// Start PHP's built-in web server
$command = "php -S $host:$port -t " . escapeshellarg($docroot);
passthru($command);