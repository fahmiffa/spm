export const akreditasiPesantren = () => ({
    init() {
        window.addEventListener("show-validation-alert", (event) => {
            Swal.fire({
                title: event.detail.title,
                html: event.detail.html,
                icon: "error",
                confirmButtonText: "OK",
                confirmButtonColor: "#4f46e5",
            });
        });
    },
    confirmDelete(id) {
        Swal.fire({
            title: "Apakah Anda yakin?",
            text: "Pengajuan akreditasi yang dihapus tidak dapat dikembalikan!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#6b7280",
            confirmButtonText: "Ya, Hapus!",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.isConfirmed) {
                this.$wire.delete(id);
            }
        });
    },
    confirmBanding(id) {
        Swal.fire({
            title: "Pengajuan Banding",
            text: "Masukkan alasan banding Anda (wajib diisi):",
            input: "textarea",
            inputPlaceholder: "Jelaskan alasan banding secara detail...",
            inputAttributes: {
                "aria-label": "Alasan banding",
            },
            showCancelButton: true,
            confirmButtonColor: "#4f46e5",
            cancelButtonColor: "#6b7280",
            confirmButtonText: "Kirim Banding",
            cancelButtonText: "Batal",
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
});
