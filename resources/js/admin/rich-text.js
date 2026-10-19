/*
 * The story box: bold, italic, underline and two kinds of list, saved as the few
 * tags the server keeps (SafeHtml). Replaces legacy's CKEditor 12 from a CDN.
 * Pasting is plain text, so nothing comes in from Word or a web page.
 */
export default (value) => ({
    value,

    init() {
        this.$refs.body.innerHTML = this.value ?? '';
        this.$watch('value', (html) => {
            if (html !== this.$refs.body.innerHTML) this.$refs.body.innerHTML = html ?? '';
        });
    },

    input() {
        this.value = this.$refs.body.innerHTML;
    },

    format(command) {
        this.$refs.body.focus();
        document.execCommand(command);
        this.input();
    },

    paste(event) {
        event.preventDefault();
        document.execCommand('insertText', false, event.clipboardData.getData('text/plain'));
    },
});
