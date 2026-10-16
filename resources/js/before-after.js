/*
 * The before-and-after slider (legacy: the twentytwenty jQuery plugin, with its
 * defaults): two photos of one place, the "before" on the left of a draggable
 * handle and the "after" on the right, starting at the middle. Only the handle
 * drags. While it is held the dimming and the labels go away; the labels also
 * go when the handle is within 15% of the edge they sit on.
 */
export default () => ({
    pct: 0.5,
    dragging: false,

    start(event) {
        event.preventDefault();
        this.dragging = true;
        event.currentTarget.setPointerCapture(event.pointerId);
    },

    move(event) {
        if (! this.dragging) return;

        const box = this.$root.getBoundingClientRect();

        this.pct = Math.max(0, Math.min(1, (event.clientX - box.left) / box.width));
    },

    end() {
        this.dragging = false;
    },

    get beforeClip() {
        return `inset(0 ${(1 - this.pct) * 100}% 0 0)`;
    },

    get afterClip() {
        return `inset(0 0 0 ${this.pct * 100}%)`;
    },
});
