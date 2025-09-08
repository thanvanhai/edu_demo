<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - Quản lý Camera</title>
</head>
<body>
    <!-- Nội dung HTML từ artifact sẽ được paste vào đây -->
     <!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản lý Camera</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }

        .header h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px 0 rgba(31, 38, 135, 0.5);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: white;
        }

        .stat-card h3 {
            color: white;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: rgba(255, 255, 255, 0.8);
        }

        .control-panel {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }

        .controls {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #333;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .camera-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .camera-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .camera-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .camera-header {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .camera-header h3 {
            font-size: 1.3rem;
            margin-bottom: 5px;
        }

        .camera-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .camera-body {
            padding: 20px;
        }

        .camera-info {
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .info-label {
            color: #666;
            font-weight: 500;
        }

        .info-value {
            color: #333;
            font-weight: 400;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-online {
            background: #d4edda;
            color: #155724;
        }

        .status-offline {
            background: #f8d7da;
            color: #721c24;
        }

        .camera-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 0.8rem;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            transform: scale(0.7);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .modal.show .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f3f4;
        }

        .modal-header h2 {
            color: #333;
            font-size: 1.5rem;
        }

        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 25px;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ddd;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #333;
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .camera-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-video"></i> Hệ thống Quản lý Camera</h1>
            <p>Quản lý và giám sát các camera thông minh</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-video"></i>
                <h3 id="totalCameras">0</h3>
                <p>Tổng Camera</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h3 id="activeCameras">0</h3>
                <p>Đang Hoạt Động</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-times-circle"></i>
                <h3 id="offlineCameras">0</h3>
                <p>Ngoại Tuyến</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-film"></i>
                <h3 id="totalRecordings">0</h3>
                <p>Video Ghi Âm</p>
            </div>
        </div>

        <!-- Control Panel -->
        <div class="control-panel">
            <div class="alert alert-success" id="successAlert"></div>
            <div class="alert alert-error" id="errorAlert"></div>
            
            <div class="controls">
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Thêm Camera
                </button>
                <button class="btn btn-success" onclick="loadCameras()">
                    <i class="fas fa-sync-alt"></i> Làm Mới
                </button>
                <button class="btn btn-warning" onclick="testApiConnection()">
                    <i class="fas fa-plug"></i> Kiểm Tra Kết Nối
                </button>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Tìm kiếm camera..." onkeyup="filterCameras()">
                </div>
            </div>

            <!-- Camera Grid -->
            <div id="cameraGrid" class="camera-grid">
                <div class="empty-state">
                    <i class="fas fa-video-slash"></i>
                    <h3>Đang tải dữ liệu...</h3>
                    <p>Vui lòng chờ trong giây lát</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Camera Modal -->
    <div class="modal" id="cameraModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Thêm Camera Mới</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <form id="cameraForm" onsubmit="saveCamera(event)">
                <input type="hidden" id="cameraId">
                <div class="form-group">
                    <label for="cameraName">Tên Camera *</label>
                    <input type="text" id="cameraName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="cameraLocation">Vị Trí *</label>
                    <input type="text" id="cameraLocation" name="location" required>
                </div>
                <div class="form-group">
                    <label for="rtspUrl">RTSP URL *</label>
                    <input type="text" id="rtspUrl" name="rtspUrl" required 
                           placeholder="rtsp://username:password@ip:port/path">
                </div>
                <div class="form-group">
                    <label for="cameraDescription">Mô Tả</label>
                    <textarea id="cameraDescription" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="isActive">Trạng Thái</label>
                    <select id="isActive" name="isActive">
                        <option value="true">Hoạt Động</option>
                        <option value="false">Tạm Dừng</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                    <button type="submit" class="btn btn-primary" id="saveButton">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Global variables
        let cameras = [];
        let currentCameraId = null;

        // API endpoints - thay đổi theo Laravel routes của bạn
        const API_BASE = '/api/cameras'; // Điều chỉnh theo route Laravel của bạn
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadCameras();
            loadStatistics();
        });

        // Load cameras from API
        async function loadCameras() {
            try {
                showLoading();
                
                // Gọi API Laravel - bạn cần tạo route này
                const response = await fetch(`${API_BASE}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                cameras = data.cameras || data || [];
                displayCameras(cameras);
                updateStatistics();
                
            } catch (error) {
                console.error('Error loading cameras:', error);
                showError('Không thể tải danh sách camera: ' + error.message);
                showEmptyState('Lỗi kết nối', 'Không thể tải dữ liệu từ server');
            }
        }

        // Display cameras
        function displayCameras(cameraList) {
            const grid = document.getElementById('cameraGrid');
            
            if (!cameraList || cameraList.length === 0) {
                showEmptyState('Không có camera', 'Hãy thêm camera đầu tiên của bạn');
                return;
            }

            grid.innerHTML = cameraList.map(camera => `
                <div class="camera-card" data-camera-id="${camera.id}">
                    <div class="camera-header">
                        <h3>${camera.name || 'Unnamed Camera'}</h3>
                        <p><i class="fas fa-map-marker-alt"></i> ${camera.location || 'Unknown Location'}</p>
                    </div>
                    <div class="camera-body">
                        <div class="camera-info">
                            <div class="info-row">
                                <span class="info-label">Trạng thái:</span>
                                <span class="status-badge ${camera.isActive ? 'status-online' : 'status-offline'}">
                                    ${camera.isActive ? 'Hoạt động' : 'Ngoại tuyến'}
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">RTSP:</span>
                                <span class="info-value">${camera.rtspUrl ? 'Đã cấu hình' : 'Chưa cấu hình'}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Mô tả:</span>
                                <span class="info-value">${camera.description || 'Không có mô tả'}</span>
                            </div>
                        </div>
                        <div class="camera-actions">
                            <button class="btn btn-small btn-warning" onclick="testCamera(${camera.id})">
                                <i class="fas fa-plug"></i> Test
                            </button>
                            <button class="btn btn-small btn-primary" onclick="editCamera(${camera.id})">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="btn btn-small btn-danger" onclick="deleteCamera(${camera.id})">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Show empty state
        function showEmptyState(title, description) {
            const grid = document.getElementById('cameraGrid');
            grid.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-video-slash"></i>
                    <h3>${title}</h3>
                    <p>${description}</p>
                </div>
            `;
        }

        // Show loading
        function showLoading() {
            const grid = document.getElementById('cameraGrid');
            grid.innerHTML = `
                <div class="empty-state">
                    <div class="loading"></div>
                    <h3>Đang tải...</h3>
                    <p>Vui lòng chờ trong giây lát</p>
                </div>
            `;
        }

        // Filter cameras
        function filterCameras() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const filtered = cameras.filter(camera => 
                (camera.name || '').toLowerCase().includes(searchTerm) ||
                (camera.location || '').toLowerCase().includes(searchTerm)
            );
            displayCameras(filtered);
        }

        // Show add modal
        function showAddModal() {
            currentCameraId = null;
            document.getElementById('modalTitle').textContent = 'Thêm Camera Mới';
            document.getElementById('cameraForm').reset();
            document.getElementById('cameraId').value = '';
            document.getElementById('cameraModal').classList.add('show');
        }

        // Edit camera
        function editCamera(id) {
            const camera = cameras.find(c => c.id === id);
            if (!camera) return;

            currentCameraId = id;
            document.getElementById('modalTitle').textContent = 'Chỉnh Sửa Camera';
            document.getElementById('cameraId').value = id;
            document.getElementById('cameraName').value = camera.name || '';
            document.getElementById('cameraLocation').value = camera.location || '';
            document.getElementById('rtspUrl').value = camera.rtspUrl || '';
            document.getElementById('cameraDescription').value = camera.description || '';
            document.getElementById('isActive').value = camera.isActive ? 'true' : 'false';
            document.getElementById('cameraModal').classList.add('show');
        }

        // Close modal
        function closeModal() {
            document.getElementById('cameraModal').classList.remove('show');
            currentCameraId = null;
        }

        // Save camera
        async function saveCamera(event) {
            event.preventDefault();
            
            const button = document.getElementById('saveButton');
            const originalText = button.innerHTML;
            button.innerHTML = '<div class="loading"></div> Đang lưu...';
            button.disabled = true;

            try {
                const formData = new FormData(event.target);
                const data = {
                    name: formData.get('name'),
                    location: formData.get('location'),
                    rtspUrl: formData.get('rtspUrl'),
                    description: formData.get('description'),
                    isActive: formData.get('isActive') === 'true'
                };

                const url = currentCameraId ? `${API_BASE}/${currentCameraId}` : API_BASE;
                const method = currentCameraId ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(data)
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || `HTTP ${response.status}`);
                }

                showSuccess(currentCameraId ? 'Camera đã được cập nhật' : 'Camera đã được thêm thành công');
                closeModal();
                loadCameras();

            } catch (error) {
                console.error('Error saving camera:', error);
                showError('Lỗi khi lưu camera: ' + error.message);
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }

        // Delete camera
        async function deleteCamera(id) {
            if (!confirm('Bạn có chắc muốn xóa camera này?')) return;

            try {
                const response = await fetch(`${API_BASE}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                showSuccess('Camera đã được xóa');
                loadCameras();

            } catch (error) {
                console.error('Error deleting camera:', error);
                showError('Lỗi khi xóa camera: ' + error.message);
            }
        }

        // Test camera connection
        async function testCamera(id) {
            try {
                const response = await fetch(`${API_BASE}/${id}/test`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });

                const result = await response.json();
                
                if (result.success) {
                    showSuccess(`Camera ${id}: Kết nối thành công`);
                } else {
                    showError(`Camera ${id}: ${result.message || 'Kết nối thất bại'}`);
                }

            } catch (error) {
                showError(`Camera ${id}: Lỗi khi kiểm tra kết nối`);
            }
        }

        // Test API connection
        async function testApiConnection() {
            try {
                const response = await fetch(`${API_BASE}/test-connection`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();
                
                if (result.connected) {
                    showSuccess('Kết nối API thành công');
                } else {
                    showError('Kết nối API thất bại');
                }

            } catch (error) {
                showError('Lỗi khi kiểm tra kết nối API');
            }
        }

        // Load statistics
        async function loadStatistics() {
            try {
                const response = await fetch(`${API_BASE}/statistics`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const stats = await response.json();
                    updateStatisticsDisplay(stats);
                }
            } catch (error) {
                console.error('Error loading statistics:', error);
            }
        }

        // Update statistics display
        function updateStatisticsDisplay(stats) {
            document.getElementById('totalCameras').textContent = stats.total_cameras || 0;
            document.getElementById('activeCameras').textContent = stats.active_cameras || 0;
            document.getElementById('offlineCameras').textContent = stats.offline_cameras || 0;
            document.getElementById('totalRecordings').textContent = stats.total_recordings || 0;
        }

        // Update statistics from current cameras
        function updateStatistics() {
            const total = cameras.length;
            const active = cameras.filter(c => c.isActive).length;
            const offline = total - active;

            document.getElementById('totalCameras').textContent = total;
            document.getElementById('activeCameras').textContent = active;
            document.getElementById('offlineCameras').textContent = offline;
        }

        // Show success message
        function showSuccess(message) {
            const alert = document.getElementById('successAlert');
            alert.textContent = message;
            alert.classList.add('show');
            setTimeout(() => alert.classList.remove('show'), 5000);
        }

        // Show error message
        function showError(message) {
            const alert = document.getElementById('errorAlert');
            alert.textContent = message;
            alert.classList.add('show');
            setTimeout(() => alert.classList.remove('show'), 8000);
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('cameraModal');
            if (event.target === modal) {
                closeModal();
            }
        });

        // Handle escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
</body>
</html>