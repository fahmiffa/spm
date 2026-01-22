import "./bootstrap";
import Swal from "sweetalert2";

// Make Swal globally available
window.Swal = Swal;

document.addEventListener("alpine:init", () => {
    Alpine.data("deleteConfirmation", () => ({
        confirmDelete(id, methodName, text = "Hapus data ini?") {
            Swal.fire({
                title: "Apakah Anda yakin?",
                text: text,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                cancelButtonColor: "#6b7280",
                confirmButtonText: "Ya, Hapus!",
                cancelButtonText: "Batal",
            }).then((result) => {
                if (result.isConfirmed) {
                    this.$wire.call(methodName, id);
                }
            });
        },
        confirmAction(
            id,
            methodName,
            text = "Apakah Anda yakin?",
            confirmButtonText = "Ya, Lanjutkan!",
        ) {
            Swal.fire({
                title: "Konfirmasi",
                text: text,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#6b7280",
                confirmButtonText: confirmButtonText,
                cancelButtonText: "Batal",
            }).then((result) => {
                if (result.isConfirmed) {
                    this.$wire.call(methodName, id);
                }
            });
        },
    }));

    Alpine.data("fileManagement", () => ({
        validate(e) {
            const file = e.target.files[0];
            if (!file) return true;

            const isPdf = file.type === "application/pdf";
            const isSmallEnough = file.size <= 2 * 1024 * 1024; // 2MB

            if (!isPdf) {
                Swal.fire({
                    icon: "error",
                    title: "Format Salah",
                    text: "Hanya file PDF yang diperbolehkan.",
                    confirmButtonColor: "#4f46e5",
                });
                e.target.value = "";
                e.stopImmediatePropagation();
                return false;
            }

            if (!isSmallEnough) {
                Swal.fire({
                    icon: "error",
                    title: "File Terlalu Besar",
                    text: "Maksimal ukuran file adalah 2MB.",
                    confirmButtonColor: "#4f46e5",
                });
                e.target.value = "";
                e.stopImmediatePropagation();
                return false;
            }
            return true;
        },
    }));

    Alpine.data("akreditasiManagement", () => ({
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

            window.addEventListener("validation-failed", (event) => {
                Swal.fire({
                    title: event.detail.title,
                    html: event.detail.html,
                    icon: "warning",
                    confirmButtonText: "Lengkapi Sekarang",
                    confirmButtonColor: "#f59e0b",
                }).then(() => {
                    // Find first input with error and scroll to it
                    setTimeout(() => {
                        const firstError =
                            document.querySelector(".text-red-500");
                        if (firstError) {
                            const input =
                                firstError.previousElementSibling ||
                                firstError.parentElement.querySelector(
                                    "select, textarea, input",
                                );
                            if (input) {
                                input.focus();
                                input.scrollIntoView({
                                    behavior: "smooth",
                                    block: "center",
                                });
                                input.classList.add(
                                    "ring-2",
                                    "ring-red-500",
                                    "ring-offset-2",
                                );
                                setTimeout(
                                    () =>
                                        input.classList.remove(
                                            "ring-2",
                                            "ring-red-500",
                                            "ring-offset-2",
                                        ),
                                    3000,
                                );
                            }
                        }
                    }, 100);
                });
            });
        },
        confirmAction(
            methodName,
            text = "Apakah Anda yakin?",
            confirmButtonText = "Ya, Lanjutkan!",
        ) {
            Swal.fire({
                title: "Konfirmasi Akreditasi",
                text: text,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#4f46e5",
                cancelButtonColor: "#6b7280",
                confirmButtonText: confirmButtonText,
                cancelButtonText: "Batal",
            }).then((result) => {
                if (result.isConfirmed) {
                    this.$wire.call(methodName);
                }
            });
        },
    }));
});
