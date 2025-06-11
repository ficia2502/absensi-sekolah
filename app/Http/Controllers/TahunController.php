<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tahun;
use App\Models\IsAdmin;

class TahunController extends Controller
{
    public function index()
    {
        // Verifikasi untuk User yang login apakah dia Admin
            $verifikasiAdmin = new IsAdmin();
            $verifikasiAdmin->isAdmin(); 
        // Jika status=1, maka akan lanjut kode di bawah
        // Jika status != 1, maka akan 403 Forbidden

        $tahun = Tahun::orderBy('tahun')->get();
        return view('pages/tahun/daftar', [
            "title" => "Data Tahun",
            "titlepage" => "Data Tahun",
            "tahun" => $tahun
        ]);
    }

    public function store(Request $request)
    {
        // Verifikasi untuk User yang login apakah dia Admin
            $verifikasiAdmin = new IsAdmin();
            $verifikasiAdmin->isAdmin(); 
        // Jika status=1, maka akan lanjut kode di bawah
        // Jika status != 1, maka akan 403 Forbidden

        $tahun = new Tahun;
        $tahun->tahun = $request->tahun;
        $tahun->created_at = now();
        $tahun->updated_at = now();
        $tahun->save();

        return redirect('/tahun')->with('success','');
    }

    public function destroy(Request $request)
    {
        // Verifikasi untuk User yang login apakah dia Admin
        $verifikasiAdmin = new IsAdmin();
        $verifikasiAdmin->isAdmin(); 
        
        $getId = $request->id;

        try {
            $tahun = Tahun::findOrFail($getId);
            
            // Delete all related murids (cascade will handle absensis)
            Murid::where('tahun_id', $getId)->delete();
            
            // Delete the tahun
            $tahun->delete();
            
            return redirect('/tahun')->with('deleted', 'Tahun ajaran berhasil dihapus');
        } catch (\Exception $e) {
            return redirect('/tahun')->with('fail', 'Gagal menghapus tahun ajaran: ' . $e->getMessage());
        }
    }
}
