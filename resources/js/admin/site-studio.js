/*
 * The dashboard's half of the visual editor (Site content, "Edit the page"). The public page opens in a
 * frame with the editor inside it (resources/views/site/editor.blade.php); that editor reports a typed-over
 * line, a picture to replace or a reset with postMessage, and this component asks the Livewire page to save
 * it (saveEdit, applyImage, resetEdit) and answers the frame so it can show "Saved". A picture is chosen
 * here, not in the frame, so it goes up through Livewire's own upload.
 */
export default function siteStudio($wire) {
    return {
        loading: true,
        scroll: 0,

        loaded() {
            this.loading = false;
            try { if (this.scroll) this.$refs.frame.contentWindow.scrollTo(0, this.scroll); } catch (e) { /* another origin: leave it */ }
        },

        // Reload the page, to the same place on it.
        reload() {
            const frame = this.$refs.frame;
            if (!frame) return;
            try { this.scroll = frame.contentWindow.scrollY; } catch (e) { this.scroll = 0; }
            this.loading = true;
            frame.src = frame.dataset.url + '?sc-edit=1&t=' + Date.now();
        },

        answer(id, result) {
            const frame = this.$refs.frame;
            if (frame && frame.contentWindow) frame.contentWindow.postMessage({ sc: 1, type: 'done', id, ok: result.ok, message: result.message || '' }, location.origin);
        },

        async onMessage(event) {
            const frame = this.$refs.frame;
            if (!frame || event.source !== frame.contentWindow || event.origin !== location.origin) return;
            const d = event.data;
            if (!d || d.sc !== 1) return;

            if (d.type === 'text') {
                this.answer(d.id, await $wire.saveEdit(d.where, d.original, d.text));
            } else if (d.type === 'image') {
                this.choose(d);
            } else if (d.type === 'reset') {
                await $wire.resetEdit(d.key);
                this.reload();
            }
        },

        choose(d) {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = d.video ? 'video/mp4' : 'image/jpeg,image/png,image/webp';
            input.addEventListener('cancel', () => this.answer(d.id, { ok: false }));
            input.addEventListener('change', () => {
                const file = input.files[0];
                if (!file) return this.answer(d.id, { ok: false });
                $wire.upload('drop', file, async () => {
                    const result = await $wire.applyImage(d.slot, d.key, d.src, d.video);
                    this.answer(d.id, result);
                    if (result.ok) this.reload();
                }, () => this.answer(d.id, { ok: false, message: 'The upload failed.' }));
            });
            input.click();
        },
    };
}
