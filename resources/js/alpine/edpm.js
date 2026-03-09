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
            // Get all select elements in current step
            const selects = document.querySelectorAll(
                'select[wire\\:model\\.live^="evaluasis"]',
            );
            const emptySelects = [];

            selects.forEach((select) => {
                if (!select.value || select.value === "") {
                    // Find the butir number from the card
                    const card = select.closest(".bg-white.border.rounded-lg");
                    const butirBadge = card?.querySelector(".bg-indigo-50");
                    const butirText =
                        butirBadge?.textContent.trim() || "Unknown";
                    emptySelects.push(butirText);
                }
            });

            if (emptySelects.length > 0) {
                Swal.fire({
                    icon: "error",
                    title: "Invalid Nilai",
                    html:
                        "Harap pilih nilai evaluasi untuk:<br><br>" +
                        emptySelects.map((b) => "• " + b).join("<br>"),
                    confirmButtonColor: "#4f46e5",
                    confirmButtonText: "OK",
                    customClass: {
                        title: "text-xl font-bold text-slate-800",
                        htmlContainer: "text-sm text-slate-500",
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
