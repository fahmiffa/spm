<?php

namespace App\Livewire\Pages\Asesor;

use App\Models\Akreditasi;
use App\Models\Pesantren;
use App\Models\Ipm;
use App\Models\SdmPesantren;
use App\Models\MasterEdpmKomponen;
use App\Models\Edpm;
use App\Models\EdpmCatatan;
use App\Models\AkreditasiEdpm;
use App\Models\AkreditasiEdpmCatatan;
use App\Models\User;
use App\Models\Assessment;
use App\Notifications\AkreditasiNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AkreditasiDetail extends Component
{
    public $akreditasi;
    public $pesantren;
    public $ipm;
    public $sdm;
    public $levels = [];
    public $fields = [
        'santri_l',
        'santri_p',
        'ustadz_dirosah_l',
        'ustadz_dirosah_p',
        'ustadz_non_dirosah_l',
        'ustadz_non_dirosah_p',
        'pamong_l',
        'pamong_p',
        'musyrif_l',
        'musyrif_p',
        'tendik_l',
        'tendik_p',
    ];
    public $komponens;

    // Pesantren's EDPM data (read only)
    public $pesantrenEvaluasis = [];
    public $pesantrenCatatans = [];

    // Assessor's EDPM evaluation (editable)
    public $asesorEvaluasis = [];
    public $asesorCatatans = [];
    public $asesorNks = [];
    public $asesorCatatanNks = [];
    public $asesorButirCatatans = [];

    // Values from the other assessor (for preview)
    public $otherAsesorEvaluasis = [];
    public $otherAsesorCatatans = [];
    public $otherAsesorButirCatatans = [];

    public $asesorTipe;
    public $activeTab = 'profil';
    public $isLocked = false;

    // Overall Accreditation Scores


    public function mount($uuid)
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->isAsesor()) {
            abort(403);
        }

        $this->akreditasi = Akreditasi::with(['user.pesantren', 'assessments.asesor.user', 'assessment1.asesor.user', 'assessment2.asesor.user'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        /** @var User $user */
        $user = Auth::user();
        // Security check: only assigned assessor can see this
        $currentAssessment = $this->akreditasi->assessments->where('asesor_id', $user->asesor->id)->first();
        if (!$currentAssessment) {
            abort(403);
        }
        $this->asesorTipe = $currentAssessment->tipe;

        $userId = $this->akreditasi->user_id;
        $this->pesantren = Pesantren::with('units')->where('user_id', $userId)->first();
        $this->ipm = Ipm::where('user_id', $userId)->first();
        $this->sdm = SdmPesantren::where('user_id', $userId)->get()->keyBy('tingkat');
        if ($this->pesantren && $this->pesantren->relationLoaded('units')) {
            $this->levels = $this->pesantren->units->pluck('unit')->toArray();
        }
        $this->komponens = MasterEdpmKomponen::with('butirs')->get();

        // Load Pesantren EDPM
        $pEvaluasis = Edpm::where('user_id', $userId)->get()->pluck('isian', 'butir_id');
        $pCatatans = EdpmCatatan::where('user_id', $userId)->get()->pluck('catatan', 'komponen_id');

        /** @var User $user */
        $user = Auth::user();
        // Load Assessor EDPM (filtered by current assessor)
        $asesorId = $user->asesor->id;
        $aEdpms = AkreditasiEdpm::where('akreditasi_id', $this->akreditasi->id)->where('asesor_id', $asesorId)->get();
        $aEvaluasis = $aEdpms->pluck('isian', 'butir_id');
        $aNks = $aEdpms->pluck('nk', 'butir_id');
        $aButirCatatans = $aEdpms->pluck('catatan', 'butir_id');

        $aCatatansModels = AkreditasiEdpmCatatan::where('akreditasi_id', $this->akreditasi->id)->where('asesor_id', $asesorId)->get();
        $aCatatans = $aCatatansModels->pluck('catatan', 'komponen_id');
        $aCatatanNks = $aCatatansModels->pluck('nk', 'komponen_id');

        if ($this->asesorTipe == 1 && $aEdpms->isNotEmpty()) {
            $this->isLocked = true;
        }

        // Load the other assessor's data if current is Asesor 1
        $otherEvaluasis = collect();
        $otherCatatans = collect();
        $otherButirCatatans = collect();
        if ($this->asesorTipe == 1) {
            $otherAssessment = $this->akreditasi->assessments->where('tipe', 2)->first();
            if ($otherAssessment) {
                $oEdpms = AkreditasiEdpm::where('akreditasi_id', $this->akreditasi->id)->where('asesor_id', $otherAssessment->asesor_id)->get();
                $otherEvaluasis = $oEdpms->pluck('isian', 'butir_id');
                $otherButirCatatans = $oEdpms->pluck('catatan', 'butir_id');
                $otherCatatans = AkreditasiEdpmCatatan::where('akreditasi_id', $this->akreditasi->id)->where('asesor_id', $otherAssessment->asesor_id)->get()->pluck('catatan', 'komponen_id');
            }
        }

        foreach ($this->komponens as $komponen) {
            $this->pesantrenCatatans[$komponen->id] = $pCatatans[$komponen->id] ?? '-';
            $this->asesorCatatans[$komponen->id] = $aCatatans[$komponen->id] ?? '';
            $this->asesorCatatanNks[$komponen->id] = $aCatatanNks[$komponen->id] ?? '';

            foreach ($komponen->butirs as $butir) {
                $this->pesantrenEvaluasis[$butir->id] = $pEvaluasis[$butir->id] ?? '-';
                $this->asesorEvaluasis[$butir->id] = $aEvaluasis[$butir->id] ?? '';
                $this->asesorNks[$butir->id] = $aNks[$butir->id] ?? '';
                $this->asesorButirCatatans[$butir->id] = $aButirCatatans[$butir->id] ?? '';
                $this->otherAsesorEvaluasis[$butir->id] = $otherEvaluasis[$butir->id] ?? '';
                $this->otherAsesorButirCatatans[$butir->id] = $otherButirCatatans[$butir->id] ?? '';
            }
            $this->otherAsesorCatatans[$komponen->id] = $otherCatatans[$komponen->id] ?? '';
        }
    }

    protected function messages()
    {
        return [
            'asesorEvaluasis.*.required' => 'Nilai NA wajib diisi.',
            'asesorEvaluasis.*.integer' => 'Nilai NA harus berupa angka.',
            'asesorEvaluasis.*.between' => 'Nilai NA harus antara 1 sampai 4.',
            'asesorNks.*.required' => 'Nilai NK wajib diisi.',
            'asesorNks.*.integer' => 'Nilai NK harus berupa angka.',
            'asesorNks.*.between' => 'Nilai NK harus antara 1 sampai 4.',
        ];
    }

    protected function validationAttributes()
    {
        $attributes = [];
        foreach ($this->komponens as $k) {
            foreach ($k->butirs as $b) {
                $attributes["asesorEvaluasis.{$b->id}"] = "Nilai NA Butir {$b->nomor_butir}";
                $attributes["asesorNks.{$b->id}"] = "Nilai NK Butir {$b->nomor_butir}";
            }
        }
        return $attributes;
    }

    public function saveAsesorEdpm($isFinal = false)
    {
        if ($this->akreditasi->status != 4) {
            session()->flash('error', 'Data tidak dapat diubah karena status bukan Visitasi.');
            return;
        }

        $rules = [
            'asesorEvaluasis.*' => ($isFinal ? 'required' : 'nullable') . '|integer|between:1,4',
            'asesorCatatans.*' => 'nullable|string',
            'asesorButirCatatans.*' => 'nullable|string',

        ];

        // Check for missing items
        $missingItems = [];
        foreach ($this->komponens as $komponen) {
            foreach ($komponen->butirs as $butir) {
                // Check current assessor's NA
                if (empty($this->asesorEvaluasis[$butir->id])) {
                    $missingItems[] = "<li><b>NA {$this->asesorTipe}</b>: Butir {$butir->nomor_butir} ({$komponen->nama})</li>";
                }

                if ($this->asesorTipe == 1) {
                    // Asesor 1 needs to ensure Asesor 2 (other) has filled their part for finalization
                    if ($isFinal && empty($this->otherAsesorEvaluasis[$butir->id])) {
                        $this->dispatch(
                            'validation-failed',
                            title: 'Validasi Gagal',
                            html: "Asesor 2 belum menyelesaikan penilaian (Butir {$butir->nomor_butir} masih kosong)."
                        );
                        return false;
                    }

                    // Asesor 1 needs to fill NK if both NA are filled OR if finalizing
                    $hasAllNa = !empty($this->asesorEvaluasis[$butir->id]) && !empty($this->otherAsesorEvaluasis[$butir->id]);
                    if (($isFinal || $hasAllNa) && empty($this->asesorNks[$butir->id])) {
                        $missingItems[] = "<li><b>NK</b>: Butir {$butir->nomor_butir} ({$komponen->nama})</li>";
                    }
                }
            }
        }

        if ($isFinal && !empty($missingItems)) {
            $htmlList = '<ul class="text-left list-disc pl-5 mt-2 space-y-1 text-[11px]">' . implode('', array_unique($missingItems)) . '</ul>';
            $this->dispatch(
                'validation-failed',
                title: 'Data Belum Lengkap',
                html: "Mohon lengkapi seluruh penilaian sebelum menyelesaikan:<br>" . $htmlList
            );
            return false;
        }

        try {
            $this->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Fallback for Laravel validation errors
            throw $e;
        }

        /** @var User $user */
        $user = Auth::user();
        $asesorId = $user->asesor->id;
        foreach ($this->asesorEvaluasis as $butirId => $isian) {
            if (empty($isian)) continue;

            $data = [
                'pesantren_id' => $this->akreditasi->user_id,
                'isian' => $isian,
                'catatan' => $this->asesorButirCatatans[$butirId] ?? null
            ];
            if ($this->asesorTipe == 1) {
                $data['nk'] = !empty($this->asesorNks[$butirId]) ? $this->asesorNks[$butirId] : null;
            }
            AkreditasiEdpm::updateOrCreate(['akreditasi_id' => $this->akreditasi->id, 'butir_id' => $butirId, 'asesor_id' => $asesorId], $data);
        }

        foreach ($this->asesorCatatans as $komponenId => $catatan) {
            $data = ['pesantren_id' => $this->akreditasi->user_id, 'catatan' => $catatan];
            if ($this->asesorTipe == 1) {
                $data['nk'] = !empty($this->asesorCatatanNks[$komponenId]) ? $this->asesorCatatanNks[$komponenId] : null;
            }
            AkreditasiEdpmCatatan::updateOrCreate(['akreditasi_id' => $this->akreditasi->id, 'komponen_id' => $komponenId, 'asesor_id' => $asesorId], $data);
        }

        // No longer updating overall na1, na2, nk from assessor manual inputs as requested

        // Notify Admin and Assessor 2 when Asesor 1 saves draft
        if ($this->asesorTipe == 1) {
            $this->isLocked = true;
            try {
                $admins = User::whereHas('role', function ($q) {
                    $q->where('id', 1);
                })->get();

                $message = 'Asesor 1 (' . Auth::user()->name . ') telah mengisi draf nilai NA untuk ' . ($this->pesantren->nama_pesantren ?? $this->akreditasi->user->name);

                Notification::send($admins, new AkreditasiNotification(
                    'na1_diisi',
                    'Nilai NA 1 diisi',
                    $message,
                    route('admin.akreditasi-detail', $this->akreditasi->uuid)
                ));

                $assessor2 = $this->akreditasi->assessment2;
                if ($assessor2 && $assessor2->asesor && $assessor2->asesor->user) {
                    $assessor2->asesor->user->notify(new AkreditasiNotification(
                        'na1_diisi',
                        'Nilai NA 1 diisi',
                        $message,
                        route('asesor.akreditasi-detail', $this->akreditasi->uuid)
                    ));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send NA1 notification: ' . $e->getMessage());
            }
        }

        // Notify Admin and Assessor 1 when Asesor 2 fills NA
        if ($this->asesorTipe == 2 && $isFinal) {
            try {
                $admins = User::whereHas('role', function ($q) {
                    $q->where('id', 1);
                })->get();

                /** @var User $user */
                $user = Auth::user();
                $message = 'Asesor 2 (' . $user->name . ') telah mengisi nilai NA untuk ' . ($this->pesantren->nama_pesantren ?? $this->akreditasi->user->name);

                Notification::send($admins, new AkreditasiNotification(
                    'na2_diisi',
                    'Nilai NA 2 diisi',
                    $message,
                    route('admin.akreditasi-detail', $this->akreditasi->uuid)
                ));

                $assessor1 = $this->akreditasi->assessment1;
                if ($assessor1 && $assessor1->asesor && $assessor1->asesor->user) {
                    $assessor1->asesor->user->notify(new AkreditasiNotification(
                        'na2_diisi',
                        'Nilai NA 2 diisi',
                        $message,
                        route('asesor.akreditasi-detail', $this->akreditasi->uuid)
                    ));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send NA2 notification: ' . $e->getMessage());
            }
        }

        $this->dispatch(
            'notification-received',
            type: 'success',
            title: 'Berhasil!',
            message: 'Instrumen Akreditasi berhasil disimpan.'
        );

        return true;
    }

    public function finalizeVerification()
    {
        if ($this->asesorTipe != 1) {
            abort(403);
        }

        // For finalization, we enforce strict validation
        if (!$this->saveAsesorEdpm(isFinal: true)) {
            return;
        }

        $this->akreditasi->update(['status' => 3]); // 3. Validasi

        /** @var User $user */
        $user = Auth::user();
        // Notify Admin
        $admins = User::whereHas('role', function ($q) {
            $q->where('id', 1);
        })->get();
        Notification::send($admins, new AkreditasiNotification('assessment_selesai', 'Assessment Selesai', 'Asesor ' . $user->name . ' telah menyelesaikan assessment untuk ' . ($this->pesantren->nama_pesantren ?? $this->akreditasi->user->name), route('admin.akreditasi')));

        // Notify Pesantren
        $this->akreditasi->user->notify(new AkreditasiNotification('validasi', 'Update Status: Validasi', 'Assessment telah selesai. Silakan unduh Kartu Kendali di menu dokumen, kemudian unggah kembali di menu akreditasi untuk melanjutkan proses validasi.', route('pesantren.akreditasi')));

        session()->flash('status', 'Assessment berhasil diselesaikan. Status berubah menjadi Validasi Admin.');
        return redirect()->route('asesor.akreditasi');
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function getTotal($field)
    {
        $total = 0;
        foreach ($this->levels as $level) {
            $total += (int)($this->sdm[$level]->$field ?? 0);
        }
        return $total;
    }

    public function render()
    {
        return view('livewire.pages.asesor.akreditasi-detail');
    }
}
