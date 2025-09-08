<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SmartCamera\CameraApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CameraController extends Controller
{
    private CameraApiService $cameraService;

    public function __construct(CameraApiService $cameraService)
    {
        $this->cameraService = $cameraService;
    }

    /**
     * Display camera management page
     */
    public function index()
    {
        return view('cameras.index');
    }

    /**
     * Get all cameras
     */
    public function getCameras(): JsonResponse
    {
        try {
            $cameras = $this->cameraService->getCameras();
            return response()->json(['cameras' => $cameras]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new camera
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'rtspUrl' => 'required|string',
            'description' => 'nullable|string',
            'isActive' => 'boolean'
        ]);

        try {
            $camera = $this->cameraService->createCamera($request->all());
            return response()->json(['camera' => $camera, 'message' => 'Camera created successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Update camera
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'rtspUrl' => 'required|string',
            'description' => 'nullable|string',
            'isActive' => 'boolean'
        ]);

        try {
            $camera = $this->cameraService->updateCamera($id, $request->all());
            return response()->json(['camera' => $camera, 'message' => 'Camera updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete camera
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->cameraService->deleteCamera($id);
            if ($result) {
                return response()->json(['message' => 'Camera deleted successfully']);
            }
            return response()->json(['error' => 'Failed to delete camera'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Test camera connection
     */
    public function testCamera(int $id): JsonResponse
    {
        try {
            $result = $this->cameraService->testCameraConnection($id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Test API connection
     */
    public function testConnection(): JsonResponse
    {
        try {
            $connected = $this->cameraService->testConnection();
            return response()->json(['connected' => $connected]);
        } catch (\Exception $e) {
            return response()->json(['connected' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Get statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $stats = $this->cameraService->getStatistics();
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}