export const akreditasiPesantren = () => ({
    init() {
        window.addEventListener("show-validation-alert", (event) => {
            Swal.fire({
                icon: "error",
                title: event.detail.title,
                html: event.detail.html,
                confirmButtonColor: "#ef4444",
                customClass: {
                    title: "text-xl font-bold text-slate-800",
                    htmlContainer: "text-sm text-slate-500",
                    confirmButton:
                        "px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest",
                },
            });
        });
    },
    confirmCreate() {
        Swal.fire({
            title: "Konfirmasi Pengajuan",
            html: "Apakah Anda yakin ingin membuat pengajuan akreditasi baru?<br>Data profil dan administrasi akan dikunci selama proses berlangsung.",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#1e3a5f",
            cancelButtonColor: "#6b7280",
            confirmButtonText: "YA, AJUKAN SEKARANG",
            cancelButtonText: "BATAL",
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
                this.$wire.create();
            }
        });
    },
    confirmDelete(id) {
        Swal.fire({
            title: "Hapus Pengajuan?",
            html: "Data pengajuan ini akan dihapus secara permanen.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#e11d48",
            cancelButtonColor: "#6b7280",
            confirmButtonText: "YA, HAPUS",
            cancelButtonText: "BATAL",
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
                this.$wire.delete(id);
            }
        });
    },
    confirmBanding(id) {
        Swal.fire({
            title: "Ajukan Banding",
            html: "Tuliskan alasan atau catatan banding Anda:",
            input: "textarea",
            inputPlaceholder: "Masukkan catatan di sini...",
            icon: "info",
            showCancelButton: true,
            confirmButtonColor: "#2563eb",
            cancelButtonColor: "#6b7280",
            confirmButtonText: "KIRIM BANDING",
            cancelButtonText: "BATAL",
            customClass: {
                title: "text-xl font-bold text-slate-800",
                htmlContainer: "text-sm text-slate-500",
                confirmButton:
                    "px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest",
                cancelButton:
                    "px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest",
            },
            inputValidator: (value) => {
                if (!value) {
                    return "Alasan banding wajib diisi!";
                }
            },
        }).then((result) => {
            if (result.isConfirmed) {
                this.$wire.banding(id, result.value);
            }
        });
    },
    confirmCancel(id, year) {
        Swal.fire({
            title: "Batal Pengajuan?",
            html: `Pengajuan periode ${year} akan dibatalkan dan <br> tidak dapat dilanjutkan kembali.`,
            icon: "error",
            showCancelButton: true,
            confirmButtonColor: "#f1f5f9",
            cancelButtonColor: "#f43f5e",
            confirmButtonText:
                '<span style="color: #1e293b; font-weight: bold;">YA, BATALKAN</span>',
            cancelButtonText:
                '<span style="color: #ffffff; font-weight: bold;">TIDAK</span>',
            customClass: {
                confirmButton:
                    "px-8 py-3 rounded-xl shadow-sm border border-gray-100",
                cancelButton: "px-8 py-3 rounded-xl shadow-sm",
            },
        }).then((result) => {
            if (result.isConfirmed) {
                this.$wire.cancelSubmission(id);
            }
        });
    },
    confirmResubmit(id) {
        Swal.fire({
            title: "Ajukan Ulang Akreditasi",
            html: "Pastikan seluruh dokumen telah diperbaiki <br> sebelum mengirim ulang pengajuan.",
            icon: "success",
            showCancelButton: true,
            confirmButtonColor: "#10b981",
            cancelButtonColor: "#f3f4f6",
            confirmButtonText:
                '<span style="color: #ffffff; font-weight: bold;">KIRIM PENGAJUAN ULANG</span>',
            cancelButtonText:
                '<span style="color: #1e293b; font-weight: bold;">TIDAK</span>',
            customClass: {
                confirmButton: "px-6 py-3 rounded-xl shadow-sm",
                cancelButton:
                    "px-8 py-3 rounded-xl shadow-sm border border-gray-100",
            },
        }).then((result) => {
            if (result.isConfirmed) {
                this.$wire.create(id);
            }
        });
    },
    confirmUploadKartu(wire) {
        Swal.fire({
            title: "Unggah Kartu Kendali?",
            html: "Pastikan data pada Kartu Kendali sudah sesuai.<br>Setelah diunggah, dokumen TIDAK dapat diganti kembali.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#1e3a5f",
            cancelButtonColor: "#94a3b8",
            confirmButtonText: "YA, UNGGAH SEKARANG",
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
                wire.uploadKartuKendali();
            }
        });
    },
});
