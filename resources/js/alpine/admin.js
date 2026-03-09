export function adminManagement() {
    return {
        confirmSaveNV(wire) {
            Swal.fire({
                title: "Simpan Nilai Verifikasi?",
                html: "Pastikan seluruh nilai NV sudah sesuai<br>sebelum disimpan ke sistem.",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#1e3a5f",
                cancelButtonColor: "#94a3b8",
                confirmButtonText: "YA, SIMPAN",
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
                    wire.saveAdminNv();
                }
            });
        },
        confirmApprove(wire) {
            Swal.fire({
                title: "Setujui Akreditasi?",
                html: "Data hasil akreditasi akan disimpan dan<br>sertifikat akan diterbitkan.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#059669",
                cancelButtonColor: "#94a3b8",
                confirmButtonText: "YA, SETUJUI",
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
                    wire.approve();
                }
            });
        },
        confirmReject(wire) {
            Swal.fire({
                title: "Tolak Akreditasi?",
                html: "Berikan alasan penolakan yang jelas<br>kepada pihak pesantren.",
                icon: "error",
                showCancelButton: true,
                confirmButtonColor: "#dc2626",
                cancelButtonColor: "#94a3b8",
                confirmButtonText: "YA, TOLAK",
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
                    wire.reject();
                }
            });
        },
        confirmToggleStatus(wire, userId, currentStatus, name, label = "Akun") {
            const isActivating = currentStatus == 0;
            Swal.fire({
                title: isActivating
                    ? `Aktifkan ${label}?`
                    : `Nonaktifkan ${label}?`,
                html: isActivating
                    ? `Apakah Anda yakin ingin mengaktifkan kembali <b>${name}</b>?<br>Akun akan dapat kembali digunakan untuk login.`
                    : `Apakah Anda yakin ingin menonaktifkan <b>${name}</b>?<br>Pengguna tidak akan dapat login sementara waktu.`,
                icon: isActivating ? "question" : "warning",
                showCancelButton: true,
                confirmButtonColor: isActivating ? "#059669" : "#dc2626",
                cancelButtonColor: "#94a3b8",
                confirmButtonText: isActivating
                    ? "YA, AKTIFKAN"
                    : "YA, NONAKTIFKAN",
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
                    wire.toggleStatus(userId);
                }
            });
        },
        confirmDeleteUser(wire, userId, name) {
            Swal.fire({
                title: "Hapus Akun Ini?",
                html: `Akun <b>${name}</b> akan dihapus secara permanen.<br>Tindakan ini tidak dapat dibatalkan.`,
                icon: "error",
                showCancelButton: true,
                confirmButtonColor: "#dc2626",
                cancelButtonColor: "#94a3b8",
                confirmButtonText: "YA, HAPUS",
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
                    wire.deleteUser(userId);
                }
            });
        },
    };
}
