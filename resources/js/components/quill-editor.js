export default () => ({
    content: '',
    quill: null,
    init() {
        // Tunggu satu tick untuk memastikan x-modelable telah mensinkronkan nilai awal
        this.$nextTick(() => {
            if (this.quill) return;

            const container = this.$refs.quillEditor;
            const placeholder = this.$el.dataset.placeholder || '';
            const readOnly = this.$el.dataset.readOnly === 'true';

            this.quill = new Quill(container, {
                theme: 'snow',
                placeholder: placeholder,
                readOnly: readOnly,
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        ['clean']
                    ]
                }
            });

            // Set konten awal jika ada dan bertipe string
            if (this.content && typeof this.content === 'string') {
                this.quill.root.innerHTML = this.content;
            }

            // Sinkronisasi: Quill -> Alpine -> Livewire
            this.quill.on('text-change', () => {
                let html = this.quill.root.innerHTML;
                if (html === '<p><br></p>') html = '';
                this.content = html;
            });

            // Sinkronisasi: Livewire -> Alpine -> Quill
            this.$watch('content', value => {
                // Pastikan nilai adalah string dan berbeda dengan isi editor saat ini
                if (typeof value === 'string' && value !== this.quill.root.innerHTML) {
                    this.quill.root.innerHTML = value || '';
                }
            });
        });
    }
})
