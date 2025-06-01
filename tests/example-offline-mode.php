#!/usr/bin/env php
<?php
/**
 * Example: Using PatreonClient in offline/mocking mode
 * 
 * This demonstrates how to enable offline mode for testing
 * without making actual HTTP calls to Patreon.
 */

require_once dirname(__DIR__) . '/PatreonClient.php';

// Create a new PatreonClient instance
$client = new PatreonClient();

// Enable offline mode - no HTTP calls will be made
$client->setOfflineMode(true);

echo "PatreonClient Offline Mode Example\n";
echo "==================================\n\n";

// Test with different usernames
$usernames = ['testuser', 'creator123', 'offline-test'];

foreach ($usernames as $username) {
    echo "Testing with username: $username\n";
    
    // Get campaign data (will return mocked data)
    $data = $client->getPublicCampaignData($username);
    
    if ($data !== false) {
        echo "- Campaign Name: " . $data['campaign_name'] . "\n";
        echo "- Patron Count: " . $data['patron_count'] . "\n";
        echo "- Pledge Sum: $" . number_format($data['pledge_sum'] / 100, 2) . "\n";
        echo "- Data Source: " . $data['data_source'] . "\n";
    } else {
        echo "- Failed to get data\n";
    }
    
    echo "\n";
}

// Demonstrate that offline mode generates consistent data
echo "Consistency Test:\n";
echo "-----------------\n";
$data1 = $client->getPublicCampaignData('testuser');
$data2 = $client->getPublicCampaignData('testuser');

if ($data1['patron_count'] === $data2['patron_count']) {
    echo "✓ Mock data is consistent for the same username\n";
} else {
    echo "✗ Mock data is not consistent\n";
}

// Check if offline mode is enabled
echo "\nOffline Mode Status: " . ($client->isOfflineMode() ? "ENABLED" : "DISABLED") . "\n";

// Disable offline mode
$client->setOfflineMode(false);
echo "Offline Mode Status: " . ($client->isOfflineMode() ? "ENABLED" : "DISABLED") . "\n";