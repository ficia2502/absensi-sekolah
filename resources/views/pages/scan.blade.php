@extends('layouts/main')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="/beranda">Beranda</a> / Scan QR</li>
@endsection

@section('content')
    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- /.col-md-6 -->
                <div class="col-lg-6">
                    <!-- Message Container for Success/Error Messages -->
                    <div id="messageContainer"></div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="m-0">Menu Scan QR</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">Pilih kamera belakang pada kolom di bawah, kemudian arahkan ke kode QR pada
                                kartu siswa.</p>
                            <section class="section">
                                <div class="card mb-3" style="max-width: 100%;">
                                    <div class="row no-gutters">
                                        <div class="col-lg-12">
                                            <div class="card-body">
                                                <!-- QR Scanner Container -->
                                                <div id="qr-reader" style="width: 100%; max-width: 600px; margin: 0 auto;"></div>
                                                
                                                <!-- Camera Selection -->
                                                <div class="mt-3">
                                                    <label for="cameraSelect" class="form-label">Pilih Kamera:</label>
                                                    <select id="cameraSelect" class="form-select" style="max-width: 400px;">
                                                        <option value="">Memuat kamera...</option>
                                                    </select>
                                                </div>

                                                <!-- Start/Stop Scanner Buttons -->
                                                <div class="mt-3">
                                                    <button id="startScanBtn" class="btn btn-success me-2">Mulai Scan</button>
                                                    <button id="stopScanBtn" class="btn btn-danger" disabled>Berhenti Scan</button>
                                                </div>

                                                <!-- Hidden input for scan result -->
                                                <input type="hidden" id="hasilscan" />

                                                <!-- Include html5-qrcode library from CDN -->
                                                <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
                                                <script src="https://code.jquery.com/jquery-3.5.1.min.js"
                                                    integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>

                                                <script>
                                                    let html5QrcodeScanner = null;
                                                    let cameras = [];
                                                    let selectedCameraId = null;
                                                    let isScanning = false;

                                                    // Sound for successful scan
                                                    const scanSound = new Audio('/beep.mp3');

                                                    // Initialize the QR scanner
                                                    function initQRScanner() {
                                                        // Get available cameras
                                                        Html5Qrcode.getCameras().then(devices => {
                                                            if (devices && devices.length) {
                                                                cameras = devices;
                                                                populateCameraSelect(devices);
                                                                
                                                                // Select back camera by default if available
                                                                const backCamera = devices.find(device => 
                                                                    device.label.toLowerCase().includes('back') || 
                                                                    device.label.toLowerCase().includes('rear')
                                                                );
                                                                selectedCameraId = backCamera ? backCamera.id : devices[0].id;
                                                                
                                                                $('#cameraSelect').val(selectedCameraId);
                                                            } else {
                                                                showMessage('Tidak ada kamera ditemukan!', 'error');
                                                            }
                                                        }).catch(err => {
                                                            console.error('Error getting cameras:', err);
                                                            showMessage('Error mengakses kamera: ' + err, 'error');
                                                        });
                                                    }

                                                    // Populate camera select dropdown
                                                    function populateCameraSelect(cameras) {
                                                        const select = $('#cameraSelect');
                                                        select.empty();
                                                        
                                                        cameras.forEach((camera, index) => {
                                                            const option = $('<option></option>')
                                                                .attr('value', camera.id)
                                                                .text(camera.label || `Kamera ${index + 1}`);
                                                            select.append(option);
                                                        });
                                                    }

                                                    // Start QR code scanning
                                                    function startScanning() {
                                                        if (!selectedCameraId) {
                                                            showMessage('Silakan pilih kamera terlebih dahulu!', 'error');
                                                            return;
                                                        }

                                                        const config = {
                                                            fps: 10,
                                                            qrbox: { width: 250, height: 250 },
                                                            aspectRatio: 1.0,
                                                            disableFlip: false
                                                        };

                                                        html5QrcodeScanner = new Html5Qrcode("qr-reader");
                                                        
                                                        html5QrcodeScanner.start(
                                                            selectedCameraId, 
                                                            config, 
                                                            onScanSuccess,
                                                            onScanFailure
                                                        ).then(() => {
                                                            isScanning = true;
                                                            $('#startScanBtn').prop('disabled', true);
                                                            $('#stopScanBtn').prop('disabled', false);
                                                            $('#cameraSelect').prop('disabled', true);
                                                        }).catch(err => {
                                                            console.error('Error starting scanner:', err);
                                                            showMessage('Error memulai scanner: ' + err, 'error');
                                                        });
                                                    }

                                                    // Stop QR code scanning
                                                    function stopScanning() {
                                                        if (html5QrcodeScanner && isScanning) {
                                                            html5QrcodeScanner.stop().then(() => {
                                                                isScanning = false;
                                                                $('#startScanBtn').prop('disabled', false);
                                                                $('#stopScanBtn').prop('disabled', true);
                                                                $('#cameraSelect').prop('disabled', false);
                                                                html5QrcodeScanner.clear();
                                                            }).catch(err => {
                                                                console.error('Error stopping scanner:', err);
                                                            });
                                                        }
                                                    }

                                                    // Handle successful QR code scan
                                                    function onScanSuccess(decodedText, decodedResult) {
                                                        console.log('QR Code scanned:', decodedText);
                                                        $('#hasilscan').val(decodedText);
                                                        
                                                        // Send AJAX request
                                                        $.ajax({
                                                            url: '/scan-qr/' + encodeURIComponent(decodedText),
                                                            method: 'GET',
                                                            success: function(response) {
                                                                // Play scan sound on successful attendance
                                                                scanSound.play().catch(e => console.log('Sound play failed:', e));
                                                                showMessage('Absensi berhasil!', 'success');
                                                            },
                                                            error: function(xhr, status, error) {
                                                                const message = xhr.responseJSON && xhr.responseJSON.message 
                                                                    ? xhr.responseJSON.message 
                                                                    : 'Terjadi kesalahan saat memproses absensi';
                                                                showMessage(message, 'error');
                                                            }
                                                        });
                                                    }

                                                    // Handle scan failure (optional)
                                                    function onScanFailure(error) {
                                                        // This callback would be called in case of failure to detect QR code
                                                        // We don't need to show error for every failed attempt
                                                        // console.log('Scan failed:', error);
                                                    }

                                                    // Show success or error message
                                                    function showMessage(message, type) {
                                                        // Clear any existing messages first
                                                        $('#messageContainer').empty();
                                                        
                                                        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                                                        const messageDiv = $(`
                                                            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                                                                ${message}
                                                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                                            </div>
                                                        `);
                                                        
                                                        $('#messageContainer').append(messageDiv);
                                                        
                                                        // Auto remove after 5 seconds
                                                        setTimeout(() => {
                                                            messageDiv.fadeOut(() => messageDiv.remove());
                                                        }, 5000);
                                                    }

                                                    // Event listeners
                                                    $(document).ready(function() {
                                                        // Initialize scanner
                                                        initQRScanner();

                                                        // Camera selection change
                                                        $('#cameraSelect').on('change', function() {
                                                            selectedCameraId = $(this).val();
                                                            if (isScanning) {
                                                                stopScanning();
                                                            }
                                                        });

                                                        // Start scan button
                                                        $('#startScanBtn').on('click', function() {
                                                            startScanning();
                                                        });

                                                        // Stop scan button
                                                        $('#stopScanBtn').on('click', function() {
                                                            stopScanning();
                                                        });

                                                        // Clean up when page is unloaded
                                                        $(window).on('beforeunload', function() {
                                                            if (isScanning) {
                                                                stopScanning();
                                                            }
                                                        });
                                                    });
                                                </script>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <a href="#" class="btn btn-warning">Perlu bantuan ?</a>
                        </div>
                    </div>
                </div>
                <!-- /.col-md-6 -->
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
@endsection