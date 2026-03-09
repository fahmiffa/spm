export function edpmManagement() {
    return {
        confirmSimpan(wire) {
            Swal.fire({
                title: "Apakah anda yakin ingin menyimpan<br> permanen data EDPM ini?",
                html: "Pastikan seluruh informasi telah diperiksa dan<br> sesuai sebelum melanjutkan.",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#1e3a5f",
                cancelButtonColor: "#ef4444",
                confirmButtonText: "YA, SIMPAN PERMANEN",
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
        validateAndNext(wire) {
            const cards = document.querySelectorAll(
                ".bg-white.border.rounded-lg",
            );
            const errors = [];

            cards.forEach((card) => {
                const selectElement = card.querySelector(
                    'select[wire\\:model\\.live^="evaluasis"]',
                );
                const linkElement = card.querySelector(
                    'input[wire\\:model\\.live^="links"]',
                );
                const butirBadge = card.querySelector(".bg-indigo-50");
                const butirText = butirBadge
                    ? butirBadge.textContent.trim()
                    : "Unknown";

                if (
                    selectElement &&
                    (!selectElement.value || selectElement.value.trim() === "")
                ) {
                    errors.push(butirText + " (Nilai Evaluasi kosong)");
                }

                if (
                    linkElement &&
                    (!linkElement.value || linkElement.value.trim() === "")
                ) {
                    errors.push(butirText + " (Tautan Bukti kosong)");
                } else if (
                    linkElement &&
                    linkElement.value &&
                    !linkElement.validity.valid
                ) {
                    errors.push(butirText + " (Format URL Bukti tidak valid)");
                }
            });

            if (errors.length > 0) {
                Swal.fire({
                    icon: "error",
                    title: "Peringatan Validasi",
                    html:
                        "Data belum lengkap / terjadi kesalahan format:<br><br>" +
                        errors.map((b) => "• " + b).join("<br>"),
                    confirmButtonColor: "#4f46e5",
                    confirmButtonText: "OK",
                    customClass: {
                        title: "text-xl font-bold text-slate-800",
                        htmlContainer: "text-sm text-slate-500 text-left pl-6",
                        confirmButton:
                            "px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest",
                    },
                });
                return;
            }

            // If validation passes, call Livewire method
            wire.nextStep();
        },
    };
}
