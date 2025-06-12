<?php

namespace App\Http\Controllers;

use App\Models\Murid;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Tahun;
use App\Models\IsAdmin;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Http\Request;

class MuridController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index_input()
    {        
        // Verifikasi untuk User yang login apakah dia Admin
            $verifikasiAdmin = new IsAdmin();
            $verifikasiAdmin->isAdmin(); 
        // Jika status=1, maka akan lanjut kode di bawah
        // Jika status != 1, maka akan 403 Forbidden

        $kelas = Kelas::orderBy('kelas')->get();
        $tahun = Tahun::orderBy('tahun')->get();
        return view('pages/murid/input', [            
            "title" => "Input Murid",
            "titlepage" => "Input Murid",
            "kelas" => $kelas,
            "tahun" => $tahun
        ]);
    }

    public function index_daftar()
    {
        // Verifikasi untuk User yang login apakah dia Admin
            $verifikasiAdmin = new IsAdmin();
            $verifikasiAdmin->isAdmin(); 
        // Jika status=1, maka akan lanjut kode di bawah
        // Jika status != 1, maka akan 403 Forbidden

        $murid = Murid::with(['kelas','tahun'])->get();
        return view('pages/murid/daftar', [            
            "title" => "Daftar Murid",
            "titlepage" => "Daftar Murid",
            "murid" => $murid
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Verifikasi untuk User yang login apakah dia Admin
            $verifikasiAdmin = new IsAdmin();
            $verifikasiAdmin->isAdmin(); 
        // Jika status=1, maka akan lanjut kode di bawah
        // Jika status != 1, maka akan 403 Forbidden

        $kelas_id = Kelas::where('kelas', $request->kelas)->first()->id;
        $tahun_id = Tahun::where('tahun', $request->tahun)->first()->id;

        $validasi = $request->validate([
            'nis' => 'required|integer|unique:murids',
            'nama' => 'required|min:3|max:255',
            'kelas' => 'required'            
        ]);

        $validasi['kelas_id'] = $kelas_id;
        $validasi['tahun_id'] = $tahun_id;
        
        Murid::create($validasi);

        return redirect('/input-murid')->with('success','');
    }

    /**
     * Display the specified resource.
     */
    public function show(Murid $murid)
    {
        
    }

    public function show_detail(Murid $murid)
    {
        // Verifikasi untuk User yang login apakah dia Admin
            $verifikasiAdmin = new IsAdmin();
            $verifikasiAdmin->isAdmin(); 
        // Jika status=1, maka akan lanjut kode di bawah
        // Jika status != 1, maka akan 403 Forbidden

        $kelas = Kelas::all();  // Get all columns
        $tahun = Tahun::all();  // Get all columns
        return view('pages/murid/detail', [
            "title" => "Detail Murid",
            "titlepage" => "Detail Murid",
            "kelas" => $kelas,
            "murid" => $murid,
            "tahun" => $tahun,
            "absensi" => Absensi::latest()->where('murid_id', $murid->id)->limit(30)->get(),
            "qr" => QrCode::size(200)->generate($murid->nis)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Murid $murid)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Murid $murid)
    {
        // Debug: log all request data
        \Log::info('Edit Murid Request', $request->all());

        // Verifikasi untuk User yang login apakah dia Admin
        $verifikasiAdmin = new IsAdmin();
        $verifikasiAdmin->isAdmin(); 
        // Jika status=1, maka akan lanjut kode di bawah
        // Jika status != 1, maka akan 403 Forbidden

        $validated = $request->validate([
            'nama' => 'required|min:3|max:255',
            'kelas_id' => 'required|exists:kelas,id',
            'tahun_id' => 'required|exists:tahuns,id', // Fixed: table name is tahuns
        ]);

        try {
            $murid->nama = $validated['nama'];
            $murid->kelas_id = $validated['kelas_id'];
            $murid->tahun_id = $validated['tahun_id'];
            $murid->save();
            return redirect()->back()->with('success', 'Data murid berhasil diubah.');
        } catch (\Exception $e) {
            return redirect()->back()->with('fail', 'Gagal mengubah data murid: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        // Verifikasi untuk User yang login apakah dia Admin
        $verifikasiAdmin = new IsAdmin();
        $verifikasiAdmin->isAdmin(); 
        
        $getId = $request->murid;

        try {
            $murid = Murid::findOrFail($getId);
            
            // Delete related absensis records first (although cascade should handle this)
            Absensi::where('murid_id', $getId)->delete();
            
            // Delete the murid
            $murid->delete();
            
            return redirect('/daftar-murid')->with('deleted', 'Data Murid berhasil di hapus!');
        } catch (\Exception $e) {
            return redirect('/detail-murid/'.$getId)->with('fail', 'Gagal menghapus data murid: ' . $e->getMessage());
        }
    }
}
