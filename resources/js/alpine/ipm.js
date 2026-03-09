export function ipmManagement() {
    return {
        validate(
            event,
            validTypes = [
                "application/pdf",
                "application/x-pdf",
                "image/jpeg",
                "image/png",
            ],
        ) {
            const file = event.target.files[0];
            if (!file) return false;

            if (!validTypes.includes(file.type)) {
                Swal.fire({
                    icon: "error",
                    title: "Format Salah",
                    text: "Format file harus PDF, JPG, JPEG, atau PNG.",
                    confirmButtonColor: "#4f46e5",
                });
                event.target.value = "";
                return false;
            }

            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({
                    icon: "error",
                    title: "File Terlalu Besar",
                    text: "Ukuran file maksimal 2MB.",
                    confirmButtonColor: "#4f46e5",
                });
                event.target.value = "";
                return false;
            }

            return true;
        },
        confirmSave(wire) {
            Swal.fire({
                title: "Apakah anda yakin ingin menyimpan<br> perubahan data IPM ini?",
                html: "Pastikan seluruh informasi telah diperiksa dan<br> sesuai sebelum melanjutkan.",
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
