<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Murid;
use App\Models\Absensi;
use App\Models\IsAdmin;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;


class KelasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {   
        // Verifikasi untuk User yang login apakah dia Admin
            $verifikasiAdmin = new IsAdmin();
            $verifikasiAdmin->isAdmin(); 
        // Jika status=1, maka akan lanjut kode di bawah
        // Jika status != 1, maka akan 403 Forbidden  
        
        $kelas = Kelas::orderBy('kelas')->get(); 
        return view('pages/kelas/daftar', [
            "title" => "Daftar Kelas",
            "titlepage" => "Daftar Kelas",
            "kelas" => $kelas            
        ]);
    }

    public function index_detail(Request $request)
    {
        // Verifikasi untuk User yang login apakah dia Admin
            $verifikasiAdmin = new IsAdmin();
            $verifikasiAdmin->isAdmin(); 
        // Jika status=1, maka akan lanjut kode di bawah
        // Jika status != 1, maka akan 403 Forbidden    

        $tanggalHariIni = Carbon::now()->format('d-m-Y');
        $hariIni = Carbon::now()->translatedFormat('l');

        $absensi = Absensi::where('kelas_id', $request->id)->get();        

        $verifikasiTanggalHariIni = Carbon::now()->format('Y-m-d');

        $kelas = Kelas::where('id', $request->id)->first(); 
        $murid = Murid::with(['kelas', 'tahun'])->where('kelas_id', $request->id)->orderBy('nama')->get();        
        
        return view('pages/kelas/detail', [
            "title" => "Detail Kelas $kelas->kelas",
            "titlepage" => "Detail Kelas : $kelas->kelas",
            "hari" => $hariIni,
            "tanggal" => $tanggalHariIni, 
            "verifikasiWaktu" => $verifikasiTanggalHariIni,    
            "kelas" => $kelas,
            "murid" => $murid,
            "absensi" =>$absensi                       
        ]);
    }

    // public function index_master(Kelas $kelas)
    // {
    //     $kelas = Kelas::orderBy('kelas')->get(); 
    //     return view('pages/master/kelas', [
    //         "title" => "Data Kelas",
    //         "titlepage" => "Data Kelas",
    //         "kelas" => $kelas            
    //     ]);
    // }

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

        $validasi = $request->validate([
            'kelas' => 'required|unique:kelas'
        ]);

        Kelas::create($validasi);
        
        return redirect('/kelas/daftar')->with('success','');
    }

    /**
     * Display the specified resource.
     */
    public function show(Kelas $kelas)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Kelas $kelas)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Kelas $kelas)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        // Verifikasi untuk User yang login apakah dia Admin
            $verifikasiAdmin = new IsAdmin();
            $verifikasiAdmin->isAdmin(); 
        // Jika status=1, maka akan lanjut kode di bawah
        // Jika status != 1, maka akan 403 Forbidden
        
        $getId = $request->id;

        try {
            $kelas = Kelas::findOrFail($getId);
            
            // Delete all related absensis records (although cascade should handle this)
            Absensi::where('kelas_id', $getId)->delete();
            
            // Delete all related murids (cascade will handle absensis)
            Murid::where('kelas_id', $getId)->delete();
            
            // Delete the kelas
            $kelas->delete();
            
            return redirect('/kelas/daftar')->with('deleted', 'Kelas berhasil dihapus');
        } catch (\Exception $e) {
            return redirect('/kelas/daftar')->with('fail', 'Gagal menghapus kelas: ' . $e->getMessage());
        }
    }
}
