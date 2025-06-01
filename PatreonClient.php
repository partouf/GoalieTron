<?php
/**
 * PatreonClient - A standalone class for interacting with Patreon public data
 * 
 * This class provides methods to:
 * - Fetch public campaign data from Patreon creator pages (no authentication required)
 * - Create and manage custom goals with progress tracking
 * - Convert usernames to user IDs
 * - Built-in caching functionality
 * 
 * Note: Legacy Patreon API v1 methods have been removed as they no longer work
 * without OAuth authentication. This class now focuses on public data scraping
 * and custom goal management.
 */
class PatreonClient
{
    const PATREON_WEBSITE_URL = "https://www.patreon.com/";
    
    // Custom goals storage
    private $customGoals = [];
    
    private $cacheTimeout = 60; // seconds
    private $fetchTimeout = 3; // seconds
    private $cache = [];
    
    // Offline/mocking mode for testing
    private $offlineMode = false;
    
    /**
     * Set the cache timeout in seconds
     * 
     * @param int $seconds
     */
    public function setCacheTimeout($seconds)
    {
        $this->cacheTimeout = max(0, intval($seconds));
    }
    
    /**
     * Set the fetch timeout in seconds
     * 
     * @param int $seconds
     */
    public function setFetchTimeout($seconds)
    {
        $this->fetchTimeout = max(1, intval($seconds));
    }
    
    /**
     * Enable or disable offline/mocking mode
     * When enabled, getPublicCampaignData() will return mocked data instead of making HTTP calls
     * 
     * @param bool $enabled
     */
    public function setOfflineMode($enabled = true)
    {
        $this->offlineMode = (bool)$enabled;
    }
    
    /**
     * Check if offline/mocking mode is enabled
     * 
     * @return bool
     */
    public function isOfflineMode()
    {
        return $this->offlineMode;
    }
    
    
    
    /**
     * Get public campaign data from Patreon about page (no authentication required)
     * 
     * @param string $username The Patreon username (without @ or URL)
     * @param bool $useCache Whether to use cache (default: true)
     * @return array|false Returns array with public campaign data or false on failure
     */
    public function getPublicCampaignData($username, $useCache = true)
    {
        if (empty($username)) {
            return false;
        }
        
        // Remove @ if present
        $username = ltrim($username, '@');
        $cacheKey = 'public_' . $username;
        
        // Check cache first
        if ($useCache && isset($this->cache[$cacheKey])) {
            $cachedData = $this->cache[$cacheKey];
            if (time() - $cachedData['timestamp'] <= $this->cacheTimeout) {
                return $cachedData['data'];
            }
        }
        
        // If offline mode is enabled, return mocked data instead of making HTTP calls
        if ($this->offlineMode) {
            return $this->getMockedCampaignData($username);
        }
        
        $url = self::PATREON_WEBSITE_URL . $username . '/about';
        
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->fetchTimeout,
                'ignore_errors' => true,
                'user_agent' => 'GoalieTron/2.0 (+https://github.com/partouf/GoalieTron)'
            ],
            'https' => [
                'timeout' => $this->fetchTimeout,
                'ignore_errors' => true,
                'user_agent' => 'GoalieTron/2.0 (+https://github.com/partouf/GoalieTron)'
            ]
        ]);
        
        $pageData = @file_get_contents($url, false, $context);
        
        if ($pageData === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("PatreonClient: Failed to fetch public page for username: $username");
            }
            // Return cached data if available, even if expired
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey]['data'];
            }
            return false;
        }
        
        // Extract JSON data from the page
        $campaignData = $this->extractCampaignDataFromHtml($pageData);
        
        if ($campaignData === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("PatreonClient: Failed to extract campaign data from HTML for $username");
            }
            // Return cached data if available
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey]['data'];
            }
            return false;
        }
        
        // Update cache
        $this->cache[$cacheKey] = [
            'timestamp' => time(),
            'data' => $campaignData
        ];
        
        return $campaignData;
    }
    
    /**
     * Extract campaign data from HTML page content
     * 
     * @param string $html The HTML content
     * @return array|false Returns extracted data or false on failure
     */
    private function extractCampaignDataFromHtml($html)
    {
        $result = [];
        
        // Look for patron_count
        if (preg_match('/"patron_count":\s*(\d+)/', $html, $matches)) {
            $result['patron_count'] = intval($matches[1]);
        }
        
        // Look for paid_member_count
        if (preg_match('/"paid_member_count":\s*(\d+)/', $html, $matches)) {
            $result['paid_member_count'] = intval($matches[1]);
        }
        
        // Look for creation_count (posts)
        if (preg_match('/"creation_count":\s*(\d+)/', $html, $matches)) {
            $result['creation_count'] = intval($matches[1]);
        }
        
        // Look for campaign name
        if (preg_match('/"name":\s*"([^"]+)"/', $html, $matches)) {
            $result['campaign_name'] = $matches[1];
        }
        
        
        // Look for currency
        if (preg_match('/"currency":\s*"([^"]+)"/', $html, $matches)) {
            $result['currency'] = $matches[1];
        }
        
        // Look for earnings visibility (monthly earnings)
        if (preg_match('/"earnings_visibility":\s*"([^"]+)"/', $html, $matches)) {
            $result['earnings_visibility'] = $matches[1];
        }
        
        // Look for pledge sum (if visible)
        if (preg_match('/"pledge_sum":\s*(\d+)/', $html, $matches)) {
            $result['pledge_sum_cents'] = intval($matches[1]);
            $result['pledge_sum'] = intval($matches[1]) / 100; // Convert cents to dollars
        }
        
        // Look for goals data
        if (preg_match('/"goals":\s*\[([^\]]+)\]/', $html, $matches)) {
            $goalsJson = '[' . $matches[1] . ']';
            $goals = json_decode($goalsJson, true);
            if ($goals !== null) {
                $result['goals'] = $goals;
            }
        }
        
        // Look for avatar URL
        if (preg_match('/"avatar_photo_url":\s*"([^"]+)"/', $html, $matches)) {
            $result['avatar_url'] = $matches[1];
        }
        
        // Look for cover photo URL
        if (preg_match('/"cover_photo_url":\s*"([^"]+)"/', $html, $matches)) {
            $result['cover_photo_url'] = $matches[1];
        }
        
        // Look for is_monthly flag
        if (preg_match('/"is_monthly":\s*(true|false)/', $html, $matches)) {
            $result['is_monthly'] = $matches[1] === 'true';
        }
        
        // Return false if we couldn't extract any meaningful data
        if (empty($result)) {
            return false;
        }
        
        // Add metadata
        $result['extracted_at'] = time();
        $result['data_source'] = 'public_about_page';
        
        return $result;
    }
    
    /**
     * Create a custom goal based on publicly available data
     * 
     * @param string $goalId Unique identifier for the goal
     * @param string $type Goal type: 'patrons', 'members', 'posts', 'income'
     * @param int|float $target Target value to reach
     * @param string $title Goal title/description
     * @return bool Returns true on success, false on failure
     */
    public function createCustomGoal($goalId, $type, $target, $title)
    {
        $validTypes = ['patrons', 'members', 'posts', 'income'];
        
        if (!in_array($type, $validTypes)) {
            return false;
        }
        
        if ($target <= 0) {
            return false;
        }
        
        $this->customGoals[$goalId] = [
            'id' => $goalId,
            'type' => $type,
            'target' => $target,
            'title' => $title,
            'created_at' => time()
        ];
        
        return true;
    }
    
    /**
     * Remove a custom goal
     * 
     * @param string $goalId Goal identifier
     * @return bool Returns true if goal existed and was removed
     */
    public function removeCustomGoal($goalId)
    {
        if (isset($this->customGoals[$goalId])) {
            unset($this->customGoals[$goalId]);
            return true;
        }
        return false;
    }
    
    /**
     * Get all custom goals
     * 
     * @return array Array of custom goals
     */
    public function getCustomGoals()
    {
        return $this->customGoals;
    }
    
    /**
     * Calculate goal progress using public campaign data
     * 
     * @param string $username Patreon username
     * @param string $goalId Goal identifier (optional, if not provided returns progress for all goals)
     * @param bool $useCache Whether to use cache for data fetching
     * @return array|false Returns goal progress data or false on failure
     */
    public function calculateGoalProgress($username, $goalId = null, $useCache = true)
    {
        // Get public campaign data
        $campaignData = $this->getPublicCampaignData($username, $useCache);
        
        if ($campaignData === false) {
            return false;
        }
        
        $goals = $goalId ? [$goalId => $this->customGoals[$goalId]] : $this->customGoals;
        $results = [];
        
        foreach ($goals as $id => $goal) {
            if (!isset($this->customGoals[$id])) {
                continue;
            }
            
            $current = $this->getCurrentValueForGoalType($campaignData, $goal['type']);
            $progress = ($current / $goal['target']) * 100;
            $progress = min(100, round($progress, 2)); // Cap at 100% and round to 2 decimals
            
            $results[$id] = [
                'goal_id' => $id,
                'title' => $goal['title'],
                'type' => $goal['type'],
                'target' => $goal['target'],
                'current' => $current,
                'progress_percentage' => $progress,
                'completed' => $progress >= 100,
                'campaign_data_timestamp' => $campaignData['extracted_at']
            ];
        }
        
        return $goalId ? ($results[$goalId] ?? false) : $results;
    }
    
    /**
     * Get combined campaign data with custom goal progress
     * 
     * @param string $username Patreon username
     * @param bool $useCache Whether to use cache for data fetching
     * @return array|false Returns combined data or false on failure
     */
    public function getCampaignDataWithGoals($username, $useCache = true)
    {
        $campaignData = $this->getPublicCampaignData($username, $useCache);
        
        if ($campaignData === false) {
            return false;
        }
        
        $goalProgress = $this->calculateGoalProgress($username, null, $useCache);
        
        $campaignData['custom_goals'] = $goalProgress;
        $campaignData['has_custom_goals'] = !empty($goalProgress);
        
        return $campaignData;
    }
    
    /**
     * Get current value for a specific goal type from campaign data
     * 
     * @param array $campaignData Campaign data array
     * @param string $type Goal type
     * @return int|float Current value
     */
    private function getCurrentValueForGoalType($campaignData, $type)
    {
        switch ($type) {
            case 'patrons':
                return isset($campaignData['patron_count']) ? $campaignData['patron_count'] : 0;
                
            case 'members':
                return isset($campaignData['paid_member_count']) ? $campaignData['paid_member_count'] : 0;
                
            case 'posts':
                return isset($campaignData['creation_count']) ? $campaignData['creation_count'] : 0;
                
            case 'income':
                return isset($campaignData['pledge_sum']) ? $campaignData['pledge_sum'] : 0;
                
            default:
                return 0;
        }
    }
    
    /**
     * Load custom goals from a JSON file
     * 
     * @param string $filePath Path to JSON file
     * @return bool Returns true on success, false on failure
     */
    public function loadCustomGoalsFromFile($filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $json = file_get_contents($filePath);
        if ($json === false) {
            return false;
        }
        
        $goals = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($goals)) {
            return false;
        }
        
        // Validate and sanitize the goals data structure
        $sanitizedGoals = array();
        foreach ($goals as $goalId => $goal) {
            if ($this->validateGoalData($goalId, $goal)) {
                $sanitizedGoals[$goalId] = $this->sanitizeGoalData($goal);
            }
        }
        
        $this->customGoals = $sanitizedGoals;
        return true;
    }
    
    /**
     * Save custom goals to a JSON file
     * 
     * @param string $filePath Path to JSON file
     * @return bool Returns true on success, false on failure
     */
    public function saveCustomGoalsToFile($filePath)
    {
        // Filter out runtime fields that shouldn't be persisted
        $goalsToSave = array();
        foreach ($this->customGoals as $goalId => $goal) {
            // Only save configuration fields, not runtime data
            $cleanGoal = array(
                'type' => $goal['type'],
                'target' => $goal['target'],
                'title' => $goal['title']
            );
            
            // Preserve optional fields if they exist
            if (isset($goal['id'])) {
                $cleanGoal['id'] = $goal['id'];
            }
            if (isset($goal['created_at'])) {
                $cleanGoal['created_at'] = $goal['created_at'];
            }
            
            $goalsToSave[$goalId] = $cleanGoal;
        }
        
        $json = json_encode($goalsToSave, JSON_PRETTY_PRINT);
        return file_put_contents($filePath, $json) !== false;
    }
    
    
    
    /**
     * Validate goal data structure
     * 
     * @param string $goalId
     * @param array $goal
     * @return bool
     */
    private function validateGoalData($goalId, $goal)
    {
        // Validate goal ID format
        if (!is_string($goalId) || !preg_match('/^[a-zA-Z0-9_-]+$/', $goalId)) {
            return false;
        }
        
        // Validate goal structure
        if (!is_array($goal)) {
            return false;
        }
        
        // Required fields
        $requiredFields = array('type', 'target', 'title');
        foreach ($requiredFields as $field) {
            if (!isset($goal[$field])) {
                return false;
            }
        }
        
        // Validate goal type
        $validTypes = array('patrons', 'members', 'posts', 'income');
        if (!in_array($goal['type'], $validTypes)) {
            return false;
        }
        
        // Validate target is positive number
        if (!is_numeric($goal['target']) || $goal['target'] <= 0) {
            return false;
        }
        
        // Validate title is string
        if (!is_string($goal['title']) || strlen($goal['title']) > 255) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize goal data
     * 
     * @param array $goal
     * @return array
     */
    private function sanitizeGoalData($goal)
    {
        return array(
            'type' => $goal['type'], // Already validated
            'target' => max(1, intval($goal['target'])), // Ensure positive integer
            'title' => substr(trim($goal['title']), 0, 255), // Limit title length
            'current' => isset($goal['current']) ? max(0, intval($goal['current'])) : 0,
            'progress_percentage' => isset($goal['progress_percentage']) ? max(0, min(100, floatval($goal['progress_percentage']))) : 0
        );
    }

    /**
     * Check if a string is valid JSON
     * 
     * @param string $string
     * @return bool
     */
    private function isValidJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Generate mocked campaign data for offline/testing mode
     * 
     * @param string $username The username to generate mock data for
     * @return array Mocked campaign data
     */
    private function getMockedCampaignData($username)
    {
        // Generate consistent mock data based on username
        $hash = crc32($username);
        $baseValue = $hash % 100;
        
        // Create deterministic mock data that varies by username
        $mockData = [
            'patron_count' => 10 + ($baseValue % 50),
            'paid_member_count' => 5 + ($baseValue % 25),
            'creation_count' => 20 + ($baseValue % 80),
            'pledge_sum' => 5000 + ($baseValue * 100), // $50-$150 in cents
            'campaign_name' => 'Mock Campaign for ' . $username,
            'currency' => 'USD',
            'earnings_visibility' => 'public',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'cover_photo_url' => 'https://example.com/cover.jpg',
            'is_monthly' => true,
            'goals' => [],
            'extracted_at' => time(),
            'data_source' => 'mock_offline_mode'
        ];
        
        // Update cache even in offline mode for consistency
        $cacheKey = 'public_' . $username;
        $this->cache[$cacheKey] = [
            'timestamp' => time(),
            'data' => $mockData
        ];
        
        return $mockData;
    }
}