export function fileManagement() {
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
    };
}

export function wilayahSelector(config = {}) {
    return {
        provinsiList: [],
        kabupatenList: [],
        provinsiSearch: "",
        kabupatenSearch: "",
        showProvinsiConfig: false,
        showKabupatenConfig: false,
        selectedProvinsiKode: config.selectedProvinsiKode || null,
        selectedKabupatenKode: config.selectedKabupatenKode || null,
        selectedProvinsiNama: config.selectedProvinsiNama || null,
        selectedKabupatenNama: config.selectedKabupatenNama || null,
        currentProvinsiKode: null,

        async init() {
            await this.loadProvinsi();

            // Initialize search fields if values exist from entangle
            if (this.selectedProvinsiNama) {
                this.provinsiSearch = this.selectedProvinsiNama;
                // If we have name but no code, find code to load kabupaten
                if (!this.selectedProvinsiKode) {
                    const prov = this.provinsiList.find(
                        (p) =>
                            p.nama.toLowerCase() ===
                            this.selectedProvinsiNama.toLowerCase(),
                    );
                    if (prov) {
                        this.currentProvinsiKode = prov.kode;
                        await this.loadKabupaten(this.currentProvinsiKode);
                    }
                }
            }

            if (this.selectedKabupatenNama) {
                this.kabupatenSearch = this.selectedKabupatenNama;
            }

            if (this.selectedProvinsiKode) {
                await this.loadKabupaten(this.selectedProvinsiKode);
            }

            // Watch for external changes from entangle
            this.$watch("selectedProvinsiNama", (value) => {
                if (value && value !== this.provinsiSearch)
                    this.provinsiSearch = value;
            });
            this.$watch("selectedKabupatenNama", (value) => {
                if (value && value !== this.kabupatenSearch)
                    this.kabupatenSearch = value;
            });
        },

        async loadProvinsi() {
            try {
                const response = await fetch(
                    "https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json",
                );
                const data = await response.json();
                this.provinsiList = data.map((p) => ({
                    kode: p.id,
                    nama: p.name,
                }));
            } catch (error) {
                console.error("Error loading provinsi:", error);
            }
        },

        async loadKabupaten(provinsiKode) {
            if (!provinsiKode) return;
            try {
                const response = await fetch(
                    `https://www.emsifa.com/api-wilayah-indonesia/api/regencies/${provinsiKode}.json`,
                );
                const data = await response.json();
                this.kabupatenList = data.map((p) => ({
                    kode: p.id,
                    nama: p.name,
                }));
            } catch (error) {
                console.error("Error loading kabupaten:", error);
            }
        },

        get filteredProvinsi() {
            if (this.provinsiSearch === "") return this.provinsiList;
            return this.provinsiList.filter((item) =>
                item.nama
                    .toLowerCase()
                    .includes(this.provinsiSearch.toLowerCase()),
            );
        },

        get filteredKabupaten() {
            if (this.kabupatenSearch === "") return this.kabupatenList;
            return this.kabupatenList.filter((item) =>
                item.nama
                    .toLowerCase()
                    .includes(this.kabupatenSearch.toLowerCase()),
            );
        },

        selectProvinsi(item) {
            this.currentProvinsiKode = item.kode;
            if (this.selectedProvinsiKode !== undefined)
                this.selectedProvinsiKode = item.kode;
            this.selectedProvinsiNama = item.nama;
            this.provinsiSearch = item.nama;
            this.showProvinsiConfig = false;

            // Reset Kabupaten
            if (this.selectedKabupatenKode !== undefined)
                this.selectedKabupatenKode = "";
            this.selectedKabupatenNama = "";
            this.kabupatenSearch = "";
            this.kabupatenList = [];

            this.loadKabupaten(item.kode);
        },

        selectKabupaten(item) {
            if (this.selectedKabupatenKode !== undefined)
                this.selectedKabupatenKode = item.kode;
            this.selectedKabupatenNama = item.nama;
            this.kabupatenSearch = item.nama;
            this.showKabupatenConfig = false;
        },
    };
}
