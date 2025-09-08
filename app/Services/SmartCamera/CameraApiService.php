<?php

namespace App\Services\SmartCamera;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CameraApiService
{
    private string $baseUrl;
    private string $token;
    private int $timeout;
    private bool $verifySSL;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->baseUrl = config('services.camera_api.base_url', env('CAMERA_API_URL', 'https://localhost:7001/api'));
        $this->timeout = config('services.camera_api.timeout', env('CAMERA_API_TIMEOUT', 30));
        $this->verifySSL = config('services.camera_api.verify_ssl', env('CAMERA_API_VERIFY_SSL', false));
        $this->username = env('CAMERA_API_USERNAME', 'admin');
        $this->password = env('CAMERA_API_PASSWORD', 'Admin@123');
        
        $this->token = $this->getAuthToken();
    }

    /**
     * Get authentication token from API
     */
    private function getAuthToken(): string
    {
        try {
            // Check if token exists in cache
            $cachedToken = Cache::get('camera_api_token');
            if ($cachedToken) {
                return $cachedToken;
            }

            Log::info('Requesting new authentication token from Camera API');
            
            $response = Http::timeout($this->timeout)
                ->withOptions(['verify' => $this->verifySSL])
                ->post("{$this->baseUrl}/auth/login", [
                    'username' => $this->username,
                    'password' => $this->password
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['token'] ?? '';
                
                if ($token) {
                    // Cache token for 50 minutes (assuming 1-hour expiry)
                    Cache::put('camera_api_token', $token, now()->addMinutes(50));
                    Log::info('Successfully obtained authentication token');
                    return $token;
                }
            }

            Log::error('Failed to authenticate with Camera API', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

        } catch (\Exception $e) {
            Log::error('API Authentication failed: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        }

        return '';
    }

    /**
     * Make HTTP request to API with automatic token refresh
     */
    private function makeRequest(string $method, string $endpoint, array $data = [])
    {
        try {
            $url = "{$this->baseUrl}{$endpoint}";
            
            Log::debug("Making API request: {$method} {$url}", [
                'data' => $data
            ]);

            $response = Http::timeout($this->timeout)
                ->withOptions(['verify' => $this->verifySSL])
                ->withToken($this->token);

            // Make the actual request based on method
            switch (strtolower($method)) {
                case 'get':
                    $response = $response->get($url, $data);
                    break;
                case 'post':
                    $response = $response->post($url, $data);
                    break;
                case 'put':
                    $response = $response->put($url, $data);
                    break;
                case 'delete':
                    $response = $response->delete($url, $data);
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
            }

            // Handle token expiration
            if ($response->status() === 401) {
                Log::warning('Token expired, refreshing...');
                Cache::forget('camera_api_token');
                $this->token = $this->getAuthToken();
                
                if ($this->token) {
                    // Retry request with new token
                    $response = Http::timeout($this->timeout)
                        ->withOptions(['verify' => $this->verifySSL])
                        ->withToken($this->token);

                    switch (strtolower($method)) {
                        case 'get':
                            $response = $response->get($url, $data);
                            break;
                        case 'post':
                            $response = $response->post($url, $data);
                            break;
                        case 'put':
                            $response = $response->put($url, $data);
                            break;
                        case 'delete':
                            $response = $response->delete($url, $data);
                            break;
                    }
                }
            }

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error("API request failed: {$method} {$url}", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                throw new \Exception("API request failed with status: {$response->status()}");
            }

        } catch (\Exception $e) {
            Log::error("API Request failed: {$method} {$endpoint} - " . $e->getMessage(), [
                'exception' => $e,
                'data' => $data
            ]);
            throw $e;
        }
    }

    // ============================================
    // CAMERA API METHODS
    // ============================================

    /**
     * Get all cameras
     */
    public function getCameras(): array
    {
        try {
            $cameras = $this->makeRequest('get', '/Cameras');
            Log::info('Retrieved cameras from API', ['count' => count($cameras ?? [])]);
            return $cameras ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get cameras: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get single camera by ID
     */
    public function getCamera(int $id): ?array
    {
        try {
            $camera = $this->makeRequest('get', "/cameras/{$id}");
            Log::info("Retrieved camera {$id} from API");
            return $camera;
        } catch (\Exception $e) {
            Log::error("Failed to get camera {$id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create new camera
     */
    public function createCamera(array $data): ?array
    {
        try {
            // Validate required fields
            $this->validateCameraData($data);
            
            $camera = $this->makeRequest('post', '/cameras', $data);
            Log::info('Created new camera via API', ['camera' => $camera]);
            return $camera;
        } catch (\Exception $e) {
            Log::error('Failed to create camera: ' . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

    /**
     * Update camera
     */
    public function updateCamera(int $id, array $data): ?array
    {
        try {
            $camera = $this->makeRequest('put', "/cameras/{$id}", $data);
            Log::info("Updated camera {$id} via API", ['camera' => $camera]);
            return $camera;
        } catch (\Exception $e) {
            Log::error("Failed to update camera {$id}: " . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

    /**
     * Delete camera
     */
    public function deleteCamera(int $id): bool
    {
        try {
            $this->makeRequest('delete', "/cameras/{$id}");
            Log::info("Deleted camera {$id} via API");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete camera {$id}: " . $e->getMessage());
            return false;
        }
    }

    // ============================================
    // RECORDING API METHODS
    // ============================================

    /**
     * Get all recordings or recordings for specific camera
     */
    public function getRecordings(int $cameraId = null): array
    {
        try {
            $endpoint = $cameraId ? "/recordings/camera/{$cameraId}" : '/recordings';
            $recordings = $this->makeRequest('get', $endpoint);
            
            Log::info('Retrieved recordings from API', [
                'count' => count($recordings ?? []),
                'camera_id' => $cameraId
            ]);
            
            return $recordings ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get recordings: ' . $e->getMessage(), ['camera_id' => $cameraId]);
            return [];
        }
    }

    /**
     * Get single recording by ID
     */
    public function getRecording(int $id): ?array
    {
        try {
            $recording = $this->makeRequest('get', "/recordings/{$id}");
            Log::info("Retrieved recording {$id} from API");
            return $recording;
        } catch (\Exception $e) {
            Log::error("Failed to get recording {$id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete recording
     */
    public function deleteRecording(int $id): bool
    {
        try {
            $this->makeRequest('delete', "/recordings/{$id}");
            Log::info("Deleted recording {$id} via API");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete recording {$id}: " . $e->getMessage());
            return false;
        }
    }

    // ============================================
    // USER API METHODS (if needed)
    // ============================================

    /**
     * Get all users
     */
    public function getUsers(): array
    {
        try {
            $users = $this->makeRequest('get', '/users');
            Log::info('Retrieved users from API', ['count' => count($users ?? [])]);
            return $users ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get users: ' . $e->getMessage());
            return [];
        }
    }

    // ============================================
    // UTILITY METHODS
    // ============================================

    /**
     * Test API connection
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::timeout(5)
                ->withOptions(['verify' => $this->verifySSL])
                ->get("{$this->baseUrl}/health");
                
            $isConnected = $response->successful();
            
            Log::info('API connection test', [
                'connected' => $isConnected,
                'status' => $response->status()
            ]);
            
            return $isConnected;
        } catch (\Exception $e) {
            Log::error('API connection test failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test specific camera connection
     */
    public function testCameraConnection(int $cameraId): array
    {
        try {
            $result = $this->makeRequest('post', "/cameras/{$cameraId}/test");
            Log::info("Camera {$cameraId} connection test completed", ['result' => $result]);
            return $result ?? ['success' => false, 'message' => 'No response'];
        } catch (\Exception $e) {
            Log::error("Camera {$cameraId} connection test failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get API statistics
     */
    public function getStatistics(): array
    {
        try {
            $cameras = $this->getCameras();
            $recordings = $this->getRecordings();
            
            $totalCameras = count($cameras);
            $activeCameras = collect($cameras)->where('isActive', true)->count();
            $totalRecordings = count($recordings);
            
            // Count today's recordings
            $todayRecordings = collect($recordings)->filter(function ($recording) {
                return Carbon::parse($recording['startTime'] ?? $recording['created_at'])->isToday();
            })->count();
            
            $stats = [
                'total_cameras' => $totalCameras,
                'active_cameras' => $activeCameras,
                'offline_cameras' => $totalCameras - $activeCameras,
                'total_recordings' => $totalRecordings,
                'today_recordings' => $todayRecordings,
                'api_connected' => true,
                'last_updated' => now()->toDateTimeString()
            ];
            
            Log::info('Retrieved API statistics', $stats);
            return $stats;
            
        } catch (\Exception $e) {
            Log::error('Failed to get statistics: ' . $e->getMessage());
            return [
                'total_cameras' => 0,
                'active_cameras' => 0,
                'offline_cameras' => 0,
                'total_recordings' => 0,
                'today_recordings' => 0,
                'api_connected' => false,
                'error' => $e->getMessage(),
                'last_updated' => now()->toDateTimeString()
            ];
        }
    }

    /**
     * Validate camera data before sending to API
     */
    private function validateCameraData(array $data): void
    {
        $required = ['name', 'location', 'rtspUrl'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Field '{$field}' is required");
            }
        }

        // Validate RTSP URL format
        if (!filter_var($data['rtspUrl'], FILTER_VALIDATE_URL) && !preg_match('/^rtsp:\/\//', $data['rtspUrl'])) {
            throw new \InvalidArgumentException("Invalid RTSP URL format");
        }
    }

    /**
     * Clear authentication cache
     */
    public function clearAuthCache(): void
    {
        Cache::forget('camera_api_token');
        Log::info('Cleared API authentication cache');
    }

    /**
     * Get current token (for debugging)
     */
    public function getCurrentToken(): string
    {
        return $this->token;
    }

    /**
     * Check if API is authenticated
     */
    public function isAuthenticated(): bool
    {
        return !empty($this->token);
    }

    /**
     * Batch update cameras
     */
    public function batchUpdateCameras(array $updates): array
    {
        $results = [];
        
        foreach ($updates as $update) {
            if (!isset($update['id']) || !isset($update['data'])) {
                continue;
            }
            
            try {
                $result = $this->updateCamera($update['id'], $update['data']);
                $results[] = [
                    'id' => $update['id'],
                    'success' => true,
                    'data' => $result
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'id' => $update['id'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        Log::info('Batch update cameras completed', [
            'total' => count($updates),
            'successful' => collect($results)->where('success', true)->count(),
            'failed' => collect($results)->where('success', false)->count()
        ]);
        
        return $results;
    }

    /**
     * Get recordings with filters
     */
    public function getRecordingsWithFilters(array $filters = []): array
    {
        try {
            $recordings = $this->getRecordings($filters['camera_id'] ?? null);
            
            // Apply client-side filters if needed
            if (!empty($filters)) {
                $recordings = collect($recordings)->filter(function ($recording) use ($filters) {
                    // Filter by detection type
                    if (isset($filters['detection_type']) && $recording['detectionType'] !== $filters['detection_type']) {
                        return false;
                    }
                    
                    // Filter by date range
                    if (isset($filters['start_date'])) {
                        $recordingDate = Carbon::parse($recording['startTime']);
                        if ($recordingDate->lt(Carbon::parse($filters['start_date']))) {
                            return false;
                        }
                    }
                    
                    if (isset($filters['end_date'])) {
                        $recordingDate = Carbon::parse($recording['startTime']);
                        if ($recordingDate->gt(Carbon::parse($filters['end_date']))) {
                            return false;
                        }
                    }
                    
                    return true;
                })->values()->all();
            }
            
            return $recordings;
        } catch (\Exception $e) {
            Log::error('Failed to get recordings with filters: ' . $e->getMessage(), ['filters' => $filters]);
            return [];
        }
    }
}