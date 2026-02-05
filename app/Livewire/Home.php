<?php

namespace App\Livewire;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Pesantren;
use Livewire\Component;

class Home extends Component
{
    public function render()
    {
        // 1. Proses Pengajuan: Status 6 (Pengajuan), 3 (Verifikasi), 4 (Validasi), 5 (Assesment)
        $prosesPengajuan = Akreditasi::whereIn('status', [3, 4, 5, 6])->count();

        // 2. Di Tolak: Status 2
        $ditolak = Akreditasi::where('status', 2)->count();

        // 3. Selesai: Status 1 (Berhasil)
        $selesai = Akreditasi::where('status', 1)->count();

        // 4. Total Pesantren
        $totalPesantren = Pesantren::count();

        // 5. Total Asesor
        $totalAsesor = Asesor::count();

        return view('livewire.home', compact(
            'prosesPengajuan',
            'ditolak',
            'selesai',
            'totalPesantren',
            'totalAsesor'
        ));
    }
}
