<?php
/**
 * PatreonClient - A standalone class for interacting with Patreon API
 * 
 * IMPORTANT: As of 2024, Patreon API v2 requires OAuth authentication for all endpoints.
 * This class currently uses deprecated v1 endpoints that no longer work without authentication.
 * 
 * To use this class, you need to:
 * 1. Register as a Patreon creator and create an OAuth client
 * 2. Obtain access tokens through OAuth flow
 * 3. Update the API endpoints to v2 format
 * 
 * This class provides methods to fetch Patreon user data and convert usernames to user IDs.
 * It includes built-in caching functionality.
 */
class PatreonClient
{
    const PATREON_WEBSITE_URL = "https://www.patreon.com/";
    const PATREON_USER_API_URL = "https://api.patreon.com/user/"; // DEPRECATED: v1 endpoint, requires OAuth in v2
    const PATREON_API_V2_BASE = "https://www.patreon.com/api/oauth2/v2/";
    
    // OAuth configuration (to be set by user)
    private $accessToken = null;
    private $clientId = null;
    private $clientSecret = null;
    
    // Custom goals storage
    private $customGoals = [];
    
    private $cacheTimeout = 60; // seconds
    private $fetchTimeout = 3; // seconds
    private $cache = [];
    
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
     * Set OAuth access token for API v2 authentication
     * 
     * @param string $accessToken The OAuth access token
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }
    
    /**
     * Set OAuth client credentials
     * 
     * @param string $clientId The OAuth client ID
     * @param string $clientSecret The OAuth client secret
     */
    public function setOAuthCredentials($clientId, $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }
    
    /**
     * Get user data from Patreon API
     * 
     * NOTE: This method uses deprecated v1 endpoints and will fail without OAuth.
     * Consider using getUserDataV2() for authenticated requests.
     * 
     * @param string|int $userId The Patreon user ID
     * @param bool $useCache Whether to use cache (default: true)
     * @return array|false Returns decoded JSON data as array or false on failure
     */
    public function getUserData($userId, $useCache = true)
    {
        if (empty($userId)) {
            return false;
        }
        
        // Check cache first
        if ($useCache && isset($this->cache[$userId])) {
            $cachedData = $this->cache[$userId];
            if (time() - $cachedData['timestamp'] <= $this->cacheTimeout) {
                return $cachedData['data'];
            }
        }
        
        // Fetch from API (DEPRECATED v1 endpoint - will likely fail)
        $url = self::PATREON_USER_API_URL . $userId;
        $headers = ['Connection: close'];
        
        // Add OAuth header if access token is available
        if ($this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }
        
        $context = stream_context_create([
            'http' => [
                'header' => $headers,
                'timeout' => $this->fetchTimeout,
                'ignore_errors' => true
            ],
            'https' => [
                'header' => $headers,
                'timeout' => $this->fetchTimeout,
                'ignore_errors' => true
            ]
        ]);
        
        $dataRaw = @file_get_contents($url, false, $context);
        
        if ($dataRaw === false) {
            error_log("PatreonClient: Failed to fetch data from v1 endpoint. OAuth authentication required for API v2.");
            // Return cached data if available, even if expired
            if (isset($this->cache[$userId])) {
                return $this->cache[$userId]['data'];
            }
            return false;
        }
        
        $data = json_decode($dataRaw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Return cached data if available
            if (isset($this->cache[$userId])) {
                return $this->cache[$userId]['data'];
            }
            return false;
        }
        
        // Update cache
        $this->cache[$userId] = [
            'timestamp' => time(),
            'data' => $data
        ];
        
        return $data;
    }
    
    /**
     * Get raw JSON data from Patreon API
     * 
     * @param string|int $userId The Patreon user ID
     * @param bool $useCache Whether to use cache (default: true)
     * @return string Returns raw JSON string or "{}" on failure
     */
    public function getUserDataRaw($userId, $useCache = true)
    {
        if (empty($userId)) {
            return "{}";
        }
        
        // Check cache first
        if ($useCache && isset($this->cache[$userId])) {
            $cachedData = $this->cache[$userId];
            if (time() - $cachedData['timestamp'] <= $this->cacheTimeout) {
                return json_encode($cachedData['data']);
            }
        }
        
        // Fetch from API (DEPRECATED v1 endpoint - will likely fail)
        $url = self::PATREON_USER_API_URL . $userId;
        $headers = ['Connection: close'];
        
        // Add OAuth header if access token is available
        if ($this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }
        
        $context = stream_context_create([
            'http' => [
                'header' => $headers,
                'timeout' => $this->fetchTimeout,
                'ignore_errors' => true
            ],
            'https' => [
                'header' => $headers,
                'timeout' => $this->fetchTimeout,
                'ignore_errors' => true
            ]
        ]);
        
        $dataRaw = @file_get_contents($url, false, $context);
        
        if ($dataRaw === false || !$this->isValidJson($dataRaw)) {
            error_log("PatreonClient: Failed to fetch raw data from v1 endpoint. OAuth authentication required for API v2.");
            // Return cached data if available, even if expired
            if (isset($this->cache[$userId])) {
                return json_encode($this->cache[$userId]['data']);
            }
            return "{}";
        }
        
        // Update cache with decoded then re-encoded data to ensure validity
        $data = json_decode($dataRaw, true);
        $this->cache[$userId] = [
            'timestamp' => time(),
            'data' => $data
        ];
        
        return $dataRaw;
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
        
        $url = self::PATREON_WEBSITE_URL . $username . '/about';
        
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->fetchTimeout,
                'ignore_errors' => true,
                'user_agent' => 'Mozilla/5.0 (compatible; PatreonClient/1.0)'
            ],
            'https' => [
                'timeout' => $this->fetchTimeout,
                'ignore_errors' => true,
                'user_agent' => 'Mozilla/5.0 (compatible; PatreonClient/1.0)'
            ]
        ]);
        
        $pageData = @file_get_contents($url, false, $context);
        
        if ($pageData === false) {
            // Return cached data if available, even if expired
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey]['data'];
            }
            return false;
        }
        
        // Extract JSON data from the page
        $campaignData = $this->extractCampaignDataFromHtml($pageData);
        
        if ($campaignData === false) {
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
        $goals = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($goals)) {
            return false;
        }
        
        $this->customGoals = $goals;
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
        $json = json_encode($this->customGoals, JSON_PRETTY_PRINT);
        return file_put_contents($filePath, $json) !== false;
    }
    
    /**
     * Convert a Patreon username to user ID
     * 
     * @param string $username The Patreon username (without @ or URL)
     * @return int|false Returns the user ID or false on failure
     */
    public function getUserIdFromUsername($username)
    {
        if (empty($username)) {
            return false;
        }
        
        // Remove @ if present
        $username = ltrim($username, '@');
        
        $url = self::PATREON_WEBSITE_URL . $username;
        
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->fetchTimeout,
                'ignore_errors' => true
            ],
            'https' => [
                'timeout' => $this->fetchTimeout,
                'ignore_errors' => true
            ]
        ]);
        
        $pageData = @file_get_contents($url, false, $context);
        
        if ($pageData === false) {
            return false;
        }
        
        // Look for creator_id in the page source
        $creatorIdPos = strpos($pageData, '"creator_id": ');
        if ($creatorIdPos === false) {
            return false;
        }
        
        $pageData = substr($pageData, $creatorIdPos + 14);
        $endIdPos = strpos($pageData, "\n");
        if ($endIdPos === false) {
            $endIdPos = strpos($pageData, "}");
        }
        
        if ($endIdPos === false) {
            return false;
        }
        
        $userId = trim(substr($pageData, 0, $endIdPos));
        
        // Validate it's numeric
        if (!is_numeric($userId) || $userId <= 0) {
            return false;
        }
        
        return intval($userId);
    }
    
    /**
     * Clear the cache for a specific user or all users
     * 
     * @param string|int|null $userId User ID to clear, or null to clear all
     */
    public function clearCache($userId = null)
    {
        if ($userId === null) {
            $this->cache = [];
        } else {
            unset($this->cache[$userId]);
        }
    }
    
    /**
     * Get cache data for debugging
     * 
     * @return array
     */
    public function getCacheInfo()
    {
        $info = [];
        foreach ($this->cache as $userId => $cacheData) {
            $info[$userId] = [
                'timestamp' => $cacheData['timestamp'],
                'age' => time() - $cacheData['timestamp'],
                'expired' => (time() - $cacheData['timestamp']) > $this->cacheTimeout
            ];
        }
        return $info;
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
}