@extends('layouts/main')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="/beranda">Beranda</a> / Absensi Hari Ini</li>
@endsection

@section('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .status-select {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            border: 1px solid #dee2e6;
            background-color: white;
            margin-right: 0.5rem;
        }
        .status-select.success { background-color: #28a745 !important; color: white; }
        .status-select.warning { background-color: #ffc107 !important; }
        .status-select.danger { background-color: #dc3545 !important; color: white; }
        .btn-update {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            display: none;
        }
        select.changed + .btn-update {
            display: inline-block;
        }
    </style>
@endsection

@section('content')
    <div class="content">
        <div class="container-fluid">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success shadow rounded-lg">
                        <div class="inner">
                            <h3>{{ $totalHadir }}</h3>
                            <p>Hadir</p>
                        </div>
                        <div class="icon"><i class="fas fa-user-check"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning shadow rounded-lg">
                        <div class="inner">
                            <h3>{{ $totalTerlambat }}</h3>
                            <p>Terlambat</p>
                        </div>
                        <div class="icon"><i class="fas fa-user-clock"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger shadow rounded-lg">
                        <div class="inner">
                            <h3>{{ $totalTidakHadir }}</h3>
                            <p>Tidak Hadir</p>
                        </div>
                        <div class="icon"><i class="fas fa-user-times"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info shadow rounded-lg">
                        <div class="inner">
                            <h3>{{ $totalMurid }}</h3>
                            <p>Total Murid</p>
                        </div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card mb-3 shadow-sm border-0 rounded">
                <div class="card-body">
                    <form method="GET" action="{{ route('absensi.hari_ini') }}" id="filterForm">
                        <div class="form-row align-items-end">
                            
                            <!-- Filter Kelas -->
                            <div class="col-md-3 mb-2">
                                <label class="mb-0 font-weight-bold">📚 Filter Kelas</label>
                                <select name="kelas_id" class="form-control rounded-pill" onchange="document.getElementById('filterForm').submit();">
                                    <option value="">Semua Kelas</option>
                                    @foreach ($kelasList as $kelas)
                                        <option value="{{ $kelas->id }}" {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}>
                                            {{ $kelas->kelas }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Pencarian -->
                            <div class="col-md-4 mb-2">
                                <label class="mb-0 font-weight-bold">🔍 Cari Nama / NIS</label>
                                <input type="text" name="search" class="form-control rounded-pill"
                                    placeholder="Masukkan nama atau NIS..." value="{{ request('search') }}"
                                    onkeydown="if (event.key === 'Enter') this.form.submit();">
                            </div>

                            <!-- Sort By -->
                            <div class="col-md-3 mb-2">
                                <label class="mb-0 font-weight-bold">🔃 Urutkan Berdasarkan</label>
                                <select name="sort" class="form-control rounded-pill" onchange="document.getElementById('filterForm').submit();">
                                    <option value="">Default</option>
                                    <option value="nama" {{ request('sort') == 'nama' ? 'selected' : '' }}>Nama</option>
                                    <option value="nis" {{ request('sort') == 'nis' ? 'selected' : '' }}>NIS</option>
                                    <option value="status" {{ request('sort') == 'status' ? 'selected' : '' }}>Status</option>
                                </select>
                            </div>

                            <!-- Tambah Absensi Button -->
                            <div class="col-md-2 mb-2 text-right">
                                <label class="d-block mb-0 invisible">Tambah</label>
                                <a href="{{ route('absensi.create') }}" class="btn btn-info rounded-pill btn-block">
                                    <i class="fas fa-plus-circle mr-1"></i> Tambah Absensi
                                </a>
                            </div>

                        </div>
                    </form>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="card shadow-sm mb-5" data-aos="fade-up">
                <div class="card-header bg-primary">
                    <h3 class="card-title text-white">📋 Daftar Absensi Hari Ini</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="absensiTable" class="table table-bordered table-striped">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>NIS</th>
                                    <th>Nama</th>
                                    <th>Kelas</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                    <th>Jam</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1; @endphp
                                @foreach ($semuaAbsensi as $absen)
                                    <tr>
                                        <td>{{ $no++ }}</td>
                                        <td>{{ $absen->murid->nis }}</td>
                                        <td>{{ $absen->murid->nama }}</td>
                                        <td>{{ $absen->murid->kelas->kelas ?? '-' }}</td>
                                        <!-- STATUS -->
                                        <td>
                                            <form action="{{ route('absensi.update_status', $absen->id) }}" method="POST" class="status-form">
                                                @csrf
                                                <select name="status" class="form-control form-control-sm text-white bg-{{ $statusMap[$absen->status]['class'] ?? 'secondary' }}">
                                                    @foreach($statusMap as $value => $data)
                                                        <option value="{{ $value }}" {{ $absen->status == $value ? 'selected' : '' }}>
                                                            {{ $data['label'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                        </td>

                                        <!-- BUTTON -->
                                        <td>
                                                <button type="submit" class="btn btn-sm btn-primary w-100">Update</button>
                                            </form>
                                        </td>
                                        <td>{{ $absen->status == 3 ? '-' : \Carbon\Carbon::parse($absen->created_at)->format('H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Date Display -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <h4 class="mb-0">{{ $hari }}, {{ $tanggal }} {{ $bulan }} {{ $tahun }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#absensiTable').DataTable({
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json"
                },
                pageLength: 25,
                order: [[0, "asc"]],
                responsive: true
            });

            // Show update button when status changes
            $('.status-select').on('change', function() {
                $(this).addClass('changed');
                updateSelectColor(this);
            });

            // Handle form submission
            $('.status-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = form.find('.btn-update');
                submitBtn.prop('disabled', true).text('Updating...');

                $.post(form.attr('action'), form.serialize())
                    .done(function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            alert('Gagal mengupdate status');
                            submitBtn.prop('disabled', false).text('Update');
                        }
                    })
                    .fail(function() {
                        alert('Terjadi kesalahan saat mengupdate status');
                        submitBtn.prop('disabled', false).text('Update');
                    });
            });

            function updateSelectColor(select) {
                const value = $(select).val();
                const classMap = {
                    '1': 'success',
                    '2': 'warning',
                    '3': 'danger'
                };
                $(select)
                    .removeClass('success warning danger')
                    .addClass(classMap[value] || 'secondary');
            }
        });
    </script>
@endsection
