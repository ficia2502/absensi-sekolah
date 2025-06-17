<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Murid;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AbsensiHariIniController extends Controller
{
    protected $statusMap = [
        1 => ['label' => 'Hadir', 'class' => 'success'],
        2 => ['label' => 'Terlambat', 'class' => 'warning'],
        3 => ['label' => 'Tidak Hadir', 'class' => 'danger']
    ];

    public function index(Request $request)
    {
        $today = Carbon::now();
        
        // Build the query with relationships
        $query = Absensi::with(['murid', 'murid.kelas'])
            ->whereDate('created_at', $today);

        // Apply class filter
        if ($request->filled('kelas_id')) {
            $query->whereHas('murid', function($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
        }

        // Apply search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('murid', function($q) use ($searchTerm) {
                $q->where('nama', 'like', '%' . $searchTerm . '%')
                  ->orWhere('nis', 'like', '%' . $searchTerm . '%');
            });
        }

        // Apply sorting
        switch ($request->get('sort')) {
            case 'nama':
                $query->orderByHas('murid', function($q) {
                    $q->orderBy('nama');
                });
                break;
            case 'nis':
                $query->orderByHas('murid', function($q) {
                    $q->orderBy('nis');
                });
                break;
            case 'status':
                $query->orderBy('status');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Get all attendance records
        $semuaAbsensi = $query->get();
        
        // Get filtered total students count
        $muridQuery = Murid::query();
        if ($request->filled('kelas_id')) {
            $muridQuery->where('kelas_id', $request->kelas_id);
        }
        if ($request->filled('search')) {
            $muridQuery->where(function($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->search . '%')
                  ->orWhere('nis', 'like', '%' . $request->search . '%');
            });
        }
        $totalMurid = $muridQuery->count();

        // Group attendance by status for statistics
        $absensiByStatus = $semuaAbsensi->groupBy('status');
        $totalHadir = $absensiByStatus->get(1, collect())->count();
        $totalTerlambat = $absensiByStatus->get(2, collect())->count();
        $totalTidakHadir = $absensiByStatus->get(3, collect())->count();

        // Get all classes for filter dropdown
        $kelasList = Kelas::orderBy('kelas')->get();

        return view('pages.absensi.hari_ini', [
            'title' => 'Absensi Hari Ini',
            'titlepage' => 'Absensi Hari Ini',
            'semuaAbsensi' => $semuaAbsensi,
            'statusMap' => $this->statusMap,
            'totalMurid' => $totalMurid,
            'totalHadir' => $totalHadir,
            'totalTerlambat' => $totalTerlambat,
            'totalTidakHadir' => $totalTidakHadir,
            'tanggal' => $today->format('d'),
            'bulan' => $today->locale('id')->format('F'),
            'tahun' => $today->format('Y'),
            'hari' => $today->locale('id')->format('l'),
            'kelasList' => $kelasList,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:1,2,3'
            ]);

            $absensi = Absensi::findOrFail($id);
            $absensi->status = $request->status;
            $absensi->save();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status berhasil diupdate'
                ]);
            }

            return redirect()->back()->with('success', 'Status berhasil diupdate');
            
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengupdate status: ' . $e->getMessage()
                ]);
            }

            return redirect()->back()->with('error', 'Gagal mengupdate status');
        }
    }

    public function create()
    {
        // Return view for adding new attendance
        return view('pages.absensi.create', [
            'title' => 'Tambah Absensi',
            'titlepage' => 'Tambah Absensi Manual',
            'kelasList' => Kelas::orderBy('kelas')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'murid_id' => 'required|exists:murids,id',
            'status' => 'required|in:1,2,3',
            'tanggal' => 'required|date',
            'waktu' => 'required',
        ]);

        $dateTime = Carbon::parse($request->tanggal . ' ' . $request->waktu);

        Absensi::create([
            'murid_id' => $request->murid_id,
            'status' => $request->status,
            'created_at' => $dateTime,
        ]);

        return redirect()
            ->route('absensi.hari_ini')
            ->with('success', 'Absensi berhasil ditambahkan');
    }
}
