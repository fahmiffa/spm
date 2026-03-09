export function asesorManagement() {
    return {
        confirmSaveDraft(wire) {
            Swal.fire({
                title: "Simpan Draft?",
                html: "Simpan penilaian sementara?<br>Anda masih dapat mengubahnya nanti.",
                icon: "info",
                showCancelButton: true,
                confirmButtonColor: "#1e3a5f",
                cancelButtonColor: "#94a3b8",
                confirmButtonText: "YA, SIMPAN DRAFT",
                cancelButtonText: "BATAL",
                reverseButtons: true,
                customClass: {
                    title: "text-xl font-bold text-slate-800",
                    htmlContainer: "text-sm text-slate-500",
                    confirmButton:
                        "px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest",
                    cancelButton:
                        "px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest",
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    wire.saveAsesorEdpm(false);
                }
            });
        },
        confirmVerification(wire) {
            Swal.fire({
                title: "Selesaikan Verifikasi?",
                html: "Pastikan seluruh data telah diperiksa.<br>Data tidak dapat diubah setelah diselesaikan.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#059669",
                cancelButtonColor: "#94a3b8",
                confirmButtonText: "YA, SELESAIKAN",
                cancelButtonText: "BATAL",
                reverseButtons: true,
                customClass: {
                    title: "text-xl font-bold text-slate-800",
                    htmlContainer: "text-sm text-slate-500",
                    confirmButton:
                        "px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest",
                    cancelButton:
                        "px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest",
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    wire.finalizeVerification();
                }
            });
        },
        confirmAsesor2Final(wire) {
            Swal.fire({
                title: "Selesaikan Penilaian (Final)?",
                html: "Nilai NA akan dikirimkan dan data akan dikunci untuk<br>Asesor 1 melakukan verifikasi NK.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#059669",
                cancelButtonColor: "#94a3b8",
                confirmButtonText: "YA, KIRIM FINAL",
                cancelButtonText: "BATAL",
                reverseButtons: true,
                customClass: {
                    title: "text-xl font-bold text-slate-800",
                    htmlContainer: "text-sm text-slate-500",
                    confirmButton:
                        "px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest",
                    cancelButton:
                        "px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest",
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    wire.saveAsesorEdpm(true);
                }
            });
        },
        confirmSaveProfile(wire) {
            Swal.fire({
                title: "Simpan Profil Asesor?",
                html: "Pastikan seluruh informasi telah diperiksa<br>dan sesuai sebelum melanjutkan.",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#1e3a5f",
                cancelButtonColor: "#94a3b8",
                confirmButtonText: "YA, SIMPAN PERUBAHAN",
                cancelButtonText: "BATAL",
                reverseButtons: true,
                customClass: {
                    title: "text-xl font-bold text-slate-800",
                    htmlContainer: "text-sm text-slate-500",
                    confirmButton:
                        "px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest",
                    cancelButton:
                        "px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest",
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    wire.save();
                }
            });
        },
    };
}
