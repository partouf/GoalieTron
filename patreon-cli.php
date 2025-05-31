#!/usr/bin/env php
<?php
/**
 * Patreon CLI - Command line interface for Patreon data
 * 
 * Usage:
 *   php patreon-cli.php <command> [options]
 * 
 * Commands:
 *   user <id>              Get user data by ID
 *   username <username>    Convert username to ID
 *   goal <id>             Get goal information for user
 *   public <username>     Get public campaign data from about page
 *   goals <username>      Get campaign data with custom goal progress
 *   goal-add <id> <type> <target> <title>  Add custom goal
 *   goal-remove <id>      Remove custom goal
 *   goal-list             List all custom goals
 *   cache clear           Clear the cache
 *   cache info            Show cache information
 * 
 * Options:
 *   --no-cache            Don't use cache for this request
 *   --format=json|pretty  Output format (default: pretty)
 *   --timeout=<seconds>   Request timeout (default: 3)
 */

require_once __DIR__ . '/PatreonClient.php';

class PatreonCLI
{
    private $client;
    private $args;
    private $options;
    private $goalsFile = 'patreon-goals.json';
    
    public function __construct($argv)
    {
        $this->client = new PatreonClient();
        $this->parseArguments($argv);
        $this->loadGoals();
    }
    
    private function parseArguments($argv)
    {
        $this->args = [];
        $this->options = [
            'format' => 'pretty',
            'cache' => true,
            'timeout' => 3
        ];
        
        // Skip script name
        array_shift($argv);
        
        foreach ($argv as $arg) {
            if (strpos($arg, '--') === 0) {
                // Parse option
                $parts = explode('=', substr($arg, 2), 2);
                $key = $parts[0];
                $value = isset($parts[1]) ? $parts[1] : true;
                
                if ($key === 'no-cache') {
                    $this->options['cache'] = false;
                } else {
                    $this->options[$key] = $value;
                }
            } else {
                // Regular argument
                $this->args[] = $arg;
            }
        }
    }
    
    public function run()
    {
        if (empty($this->args)) {
            $this->showHelp();
            return;
        }
        
        $command = $this->args[0];
        
        // Set timeout if specified
        if (isset($this->options['timeout'])) {
            $this->client->setFetchTimeout(intval($this->options['timeout']));
        }
        
        switch ($command) {
            case 'user':
                $this->handleUserCommand();
                break;
                
            case 'username':
                $this->handleUsernameCommand();
                break;
                
            case 'goal':
                $this->handleGoalCommand();
                break;
                
            case 'public':
                $this->handlePublicCommand();
                break;
                
            case 'goals':
                $this->handleGoalsCommand();
                break;
                
            case 'goal-add':
                $this->handleGoalAddCommand();
                break;
                
            case 'goal-remove':
                $this->handleGoalRemoveCommand();
                break;
                
            case 'goal-list':
                $this->handleGoalListCommand();
                break;
                
            case 'cache':
                $this->handleCacheCommand();
                break;
                
            case 'help':
            case '--help':
            case '-h':
                $this->showHelp();
                break;
                
            default:
                $this->error("Unknown command: $command");
                $this->showHelp();
                break;
        }
    }
    
    private function loadGoals()
    {
        if (file_exists($this->goalsFile)) {
            $this->client->loadCustomGoalsFromFile($this->goalsFile);
        }
    }
    
    private function saveGoals()
    {
        $this->client->saveCustomGoalsToFile($this->goalsFile);
    }
    
    private function handleUserCommand()
    {
        if (!isset($this->args[1])) {
            $this->error("User ID required");
            return;
        }
        
        $userId = $this->args[1];
        $data = $this->client->getUserData($userId, $this->options['cache']);
        
        if ($data === false) {
            $this->error("Failed to fetch user data for ID: $userId");
            return;
        }
        
        $this->output($data);
    }
    
    private function handleUsernameCommand()
    {
        if (!isset($this->args[1])) {
            $this->error("Username required");
            return;
        }
        
        $username = $this->args[1];
        $userId = $this->client->getUserIdFromUsername($username);
        
        if ($userId === false) {
            $this->error("Failed to get user ID for username: $username");
            return;
        }
        
        if ($this->options['format'] === 'json') {
            $this->output(['username' => $username, 'user_id' => $userId]);
        } else {
            echo "User ID for @$username: $userId\n";
        }
    }
    
    private function handleGoalCommand()
    {
        if (!isset($this->args[1])) {
            $this->error("User ID required");
            return;
        }
        
        $userId = $this->args[1];
        $data = $this->client->getUserData($userId, $this->options['cache']);
        
        if ($data === false) {
            $this->error("Failed to fetch user data for ID: $userId");
            return;
        }
        
        // Extract goal information
        $goalInfo = $this->extractGoalInfo($data);
        
        if ($this->options['format'] === 'json') {
            $this->output($goalInfo);
        } else {
            $this->displayGoalInfo($goalInfo);
        }
    }
    
    private function handlePublicCommand()
    {
        if (!isset($this->args[1])) {
            $this->error("Username required");
            return;
        }
        
        $username = $this->args[1];
        $data = $this->client->getPublicCampaignData($username, $this->options['cache']);
        
        if ($data === false) {
            $this->error("Failed to fetch public campaign data for username: $username");
            return;
        }
        
        if ($this->options['format'] === 'json') {
            $this->output($data);
        } else {
            $this->displayPublicCampaignInfo($data, $username);
        }
    }
    
    private function displayPublicCampaignInfo($data, $username)
    {
        echo "=== Public Campaign Data for @$username ===\n\n";
        
        if (isset($data['campaign_name'])) {
            echo "Campaign: {$data['campaign_name']}\n";
        }
        
        
        if (isset($data['patron_count'])) {
            echo "Total Patrons: {$data['patron_count']}\n";
        }
        
        if (isset($data['paid_member_count'])) {
            echo "Paid Members: {$data['paid_member_count']}\n";
        }
        
        if (isset($data['creation_count'])) {
            echo "Posts Created: {$data['creation_count']}\n";
        }
        
        if (isset($data['pledge_sum'])) {
            $currency = isset($data['currency']) ? $data['currency'] : 'USD';
            echo "Monthly Income: $" . number_format($data['pledge_sum'], 2) . " $currency\n";
        }
        
        if (isset($data['is_monthly'])) {
            $billing = $data['is_monthly'] ? 'Monthly' : 'Per Creation';
            echo "Billing Type: $billing\n";
        }
        
        if (isset($data['earnings_visibility'])) {
            echo "Earnings Visibility: {$data['earnings_visibility']}\n";
        }
        
        if (isset($data['goals']) && !empty($data['goals'])) {
            echo "\nGoals:\n";
            foreach ($data['goals'] as $i => $goal) {
                $goalNum = $i + 1;
                if (isset($goal['amount_cents'])) {
                    $amount = number_format($goal['amount_cents'] / 100, 2);
                    echo "  {$goalNum}. Target: \${$amount}\n";
                }
                if (isset($goal['title'])) {
                    echo "     Title: {$goal['title']}\n";
                }
                if (isset($goal['description'])) {
                    echo "     Description: {$goal['description']}\n";
                }
                echo "\n";
            }
        }
        
        echo "\nData extracted at: " . date('Y-m-d H:i:s', $data['extracted_at']) . "\n";
        echo "Source: {$data['data_source']}\n";
    }
    
    private function handleGoalsCommand()
    {
        if (!isset($this->args[1])) {
            $this->error("Username required");
            return;
        }
        
        $username = $this->args[1];
        $data = $this->client->getCampaignDataWithGoals($username, $this->options['cache']);
        
        if ($data === false) {
            $this->error("Failed to fetch campaign data with goals for username: $username");
            return;
        }
        
        if ($this->options['format'] === 'json') {
            $this->output($data);
        } else {
            $this->displayCampaignDataWithGoals($data, $username);
        }
    }
    
    private function handleGoalAddCommand()
    {
        if (count($this->args) < 5) {
            $this->error("Usage: goal-add <id> <type> <target> <title>");
            $this->error("Types: patrons, members, posts, income");
            return;
        }
        
        $goalId = $this->args[1];
        $type = $this->args[2];
        $target = $this->args[3];
        $title = implode(' ', array_slice($this->args, 4)); // Join remaining args as title
        
        if (!is_numeric($target) || $target <= 0) {
            $this->error("Target must be a positive number");
            return;
        }
        
        $success = $this->client->createCustomGoal($goalId, $type, floatval($target), $title);
        
        if ($success) {
            $this->saveGoals();
            echo "Custom goal '$goalId' created successfully\n";
            echo "Type: $type, Target: $target, Title: $title\n";
        } else {
            $this->error("Failed to create custom goal. Check that type is valid (patrons, members, posts, income)");
        }
    }
    
    private function handleGoalRemoveCommand()
    {
        if (!isset($this->args[1])) {
            $this->error("Goal ID required");
            return;
        }
        
        $goalId = $this->args[1];
        $success = $this->client->removeCustomGoal($goalId);
        
        if ($success) {
            $this->saveGoals();
            echo "Custom goal '$goalId' removed successfully\n";
        } else {
            $this->error("Goal '$goalId' not found");
        }
    }
    
    private function handleGoalListCommand()
    {
        $goals = $this->client->getCustomGoals();
        
        if (empty($goals)) {
            echo "No custom goals defined\n";
            return;
        }
        
        if ($this->options['format'] === 'json') {
            $this->output($goals);
        } else {
            echo "=== Custom Goals ===\n\n";
            foreach ($goals as $goal) {
                echo "ID: {$goal['id']}\n";
                echo "Title: {$goal['title']}\n";
                echo "Type: {$goal['type']}\n";
                echo "Target: {$goal['target']}\n";
                echo "Created: " . date('Y-m-d H:i:s', $goal['created_at']) . "\n";
                echo "\n";
            }
        }
    }
    
    private function displayCampaignDataWithGoals($data, $username)
    {
        echo "=== Campaign Data with Custom Goals for @$username ===\n\n";
        
        // Display campaign info
        if (isset($data['campaign_name'])) {
            echo "Campaign: {$data['campaign_name']}\n";
        }
        
        if (isset($data['patron_count'])) {
            echo "Total Patrons: {$data['patron_count']}\n";
        }
        
        if (isset($data['paid_member_count'])) {
            echo "Paid Members: {$data['paid_member_count']}\n";
        }
        
        if (isset($data['creation_count'])) {
            echo "Posts Created: {$data['creation_count']}\n";
        }
        
        if (isset($data['pledge_sum'])) {
            $currency = isset($data['currency']) ? $data['currency'] : 'USD';
            echo "Monthly Income: $" . number_format($data['pledge_sum'], 2) . " $currency\n";
        }
        
        // Display custom goals
        if (isset($data['custom_goals']) && !empty($data['custom_goals'])) {
            echo "\n=== Custom Goals Progress ===\n";
            foreach ($data['custom_goals'] as $goal) {
                echo "\n{$goal['title']} (ID: {$goal['goal_id']})\n";
                echo "Type: {$goal['type']}\n";
                echo "Progress: {$goal['current']} / {$goal['target']} ({$goal['progress_percentage']}%)\n";
                echo "Status: " . ($goal['completed'] ? 'COMPLETED' : 'In Progress') . "\n";
                
                // Display progress bar
                $this->displayProgressBar($goal['progress_percentage']);
            }
        } else {
            echo "\nNo custom goals defined. Use 'goal-add' to create goals.\n";
        }
        
        echo "\nData extracted at: " . date('Y-m-d H:i:s', $data['extracted_at']) . "\n";
    }
    
    private function handleCacheCommand()
    {
        if (!isset($this->args[1])) {
            $this->error("Cache subcommand required (clear or info)");
            return;
        }
        
        $subcommand = $this->args[1];
        
        switch ($subcommand) {
            case 'clear':
                $this->client->clearCache();
                echo "Cache cleared\n";
                break;
                
            case 'info':
                $info = $this->client->getCacheInfo();
                if (empty($info)) {
                    echo "Cache is empty\n";
                } else {
                    echo "Cache contents:\n";
                    foreach ($info as $userId => $cacheInfo) {
                        $status = $cacheInfo['expired'] ? 'expired' : 'valid';
                        echo "  User $userId: {$cacheInfo['age']}s old ($status)\n";
                    }
                }
                break;
                
            default:
                $this->error("Unknown cache subcommand: $subcommand");
                break;
        }
    }
    
    private function extractGoalInfo($data)
    {
        $info = [
            'creator_name' => null,
            'patron_count' => null,
            'pledge_sum' => null,
            'pledge_currency' => 'USD',
            'goals' => []
        ];
        
        // Extract basic info
        if (isset($data['name'])) {
            $info['creator_name'] = $data['name'];
        }
        
        if (isset($data['patron_count'])) {
            $info['patron_count'] = $data['patron_count'];
        }
        
        if (isset($data['pledge_sum'])) {
            $info['pledge_sum'] = $data['pledge_sum'];
        }
        
        // Extract goals
        if (isset($data['goals']) && is_array($data['goals'])) {
            foreach ($data['goals'] as $goal) {
                $goalData = [
                    'amount' => isset($goal['amount']) ? $goal['amount'] : 0,
                    'title' => isset($goal['title']) ? $goal['title'] : 'Untitled Goal',
                    'description' => isset($goal['description']) ? $goal['description'] : '',
                    'completed_percentage' => 0
                ];
                
                // Calculate completion percentage
                if ($goalData['amount'] > 0 && $info['pledge_sum'] !== null) {
                    $goalData['completed_percentage'] = min(100, round(($info['pledge_sum'] / $goalData['amount']) * 100, 2));
                }
                
                $info['goals'][] = $goalData;
            }
        }
        
        return $info;
    }
    
    private function displayGoalInfo($info)
    {
        echo "=== Patreon Goal Information ===\n\n";
        
        if ($info['creator_name']) {
            echo "Creator: {$info['creator_name']}\n";
        }
        
        if ($info['patron_count'] !== null) {
            echo "Patrons: {$info['patron_count']}\n";
        }
        
        if ($info['pledge_sum'] !== null) {
            $amount = number_format($info['pledge_sum'] / 100, 2);
            echo "Current pledges: \${$amount} {$info['pledge_currency']}/month\n";
        }
        
        if (!empty($info['goals'])) {
            echo "\nGoals:\n";
            foreach ($info['goals'] as $i => $goal) {
                $goalNum = $i + 1;
                $amount = number_format($goal['amount'] / 100, 2);
                echo "\n{$goalNum}. {$goal['title']}\n";
                echo "   Target: \${$amount}/month\n";
                echo "   Progress: {$goal['completed_percentage']}%\n";
                if ($goal['description']) {
                    echo "   Description: {$goal['description']}\n";
                }
                
                // Display progress bar
                $this->displayProgressBar($goal['completed_percentage']);
            }
        } else {
            echo "\nNo goals found\n";
        }
    }
    
    private function displayProgressBar($percentage)
    {
        $barLength = 30;
        $filled = round($barLength * ($percentage / 100));
        $empty = $barLength - $filled;
        
        echo "   [";
        echo str_repeat("=", $filled);
        echo str_repeat("-", $empty);
        echo "] {$percentage}%\n";
    }
    
    private function output($data)
    {
        if ($this->options['format'] === 'json') {
            echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
        } else {
            print_r($data);
        }
    }
    
    private function error($message)
    {
        fwrite(STDERR, "Error: $message\n");
    }
    
    private function showHelp()
    {
        echo <<<HELP
Patreon CLI - Command line interface for Patreon data

Usage:
  php patreon-cli.php <command> [options]

Commands:
  user <id>              Get user data by ID
  username <username>    Convert username to ID
  goal <id>             Get goal information for user
  public <username>     Get public campaign data from about page
  goals <username>      Get campaign data with custom goal progress
  goal-add <id> <type> <target> <title>  Add custom goal
  goal-remove <id>      Remove custom goal
  goal-list             List all custom goals
  cache clear           Clear the cache
  cache info            Show cache information
  help                  Show this help message

Options:
  --no-cache            Don't use cache for this request
  --format=json|pretty  Output format (default: pretty)
  --timeout=<seconds>   Request timeout (default: 3)

Examples:
  php patreon-cli.php user 123456
  php patreon-cli.php username someuser
  php patreon-cli.php goal 123456 --format=json
  php patreon-cli.php public scishow --format=json
  php patreon-cli.php goal-add patrons-1000 patrons 1000 "Reach 1000 patrons"
  php patreon-cli.php goals scishow
  php patreon-cli.php goal-list
  php patreon-cli.php user 123456 --no-cache

Note: Custom goals are automatically saved to and loaded from 'patreon-goals.json'

HELP;
    }
}

// Run the CLI
$cli = new PatreonCLI($argv);
$cli->run();