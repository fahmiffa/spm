<?php

namespace App\Livewire;

use App\Models\Akreditasi;
use App\Models\Asesor;
use App\Models\Pesantren;
use App\Models\Assessment;
use Livewire\Component;

class Home extends Component
{
    public function render()
    {
        $isAdmin = auth()->user()->isAdmin();

        // Stats for cards
        $stats = [
            'total_aktif' => Akreditasi::whereIn('status', [3, 4, 5, 6])->count(),
            'verifikasi' => Akreditasi::where('status', 3)->count(),
            'assessment' => Akreditasi::where('status', 5)->count(),
            'visitasi' => Akreditasi::where('status', 4)->count(),
            'terakreditasi' => Akreditasi::where('status', 1)->count(),
            'ditolak' => Akreditasi::where('status', 2)->count(),
        ];

        // Chart Data: Submissions per month (current year)
        $monthlySubmissions = Akreditasi::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('count', 'month')
            ->toArray();

        $chartData = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartData[] = $monthlySubmissions[$i] ?? 0;
        }

        // Monitoring Asesor
        $totalAsesor = Asesor::count();
        $totalTugasAktif = Assessment::whereHas('akreditasi', function ($q) {
            $q->whereIn('status', [3, 4, 5]);
        })->count();

        $asesorPunyaTugasIds = Assessment::whereHas('akreditasi', function ($q) {
            $q->whereIn('status', [3, 4, 5]);
        })->pluck('asesor_id')->unique()->toArray();

        $asesorTanpaTugas = $totalAsesor - count($asesorPunyaTugasIds);
        $avgBeban = $totalAsesor > 0 ? round($totalTugasAktif / $totalAsesor, 1) : 0;

        // Extra for non-admin if needed (current logic uses these variables)
        $prosesPengajuan = $stats['total_aktif'];
        $ditolak = $stats['ditolak'];
        $selesai = $stats['terakreditasi'];
        $totalPesantren = Pesantren::count();

        return view('livewire.home', compact(
            'isAdmin',
            'stats',
            'chartData',
            'totalAsesor',
            'totalTugasAktif',
            'asesorTanpaTugas',
            'avgBeban',
            'prosesPengajuan',
            'ditolak',
            'selesai',
            'totalPesantren'
        ));
    }
}
