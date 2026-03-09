export function sdmManagement() {
    return {
        confirmSave(wire) {
            Swal.fire({
                title: "Apakah anda yakin ingin menyimpan <br> perubahan data SDM ini?",
                html: "Pastikan seluruh informasi telah diperiksa dan <br> sesuai sebelum melanjutkan.",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#1e3a5f",
                cancelButtonColor: "#ef4444",
                confirmButtonText: "YA, SIMPAN PERUBAHAN",
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
                    wire.save();
                }
            });
        },
    };
}
