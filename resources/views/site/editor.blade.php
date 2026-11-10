{{--
    The visual editor's half that lives inside the public page (the dashboard's Site content opens the page in a
    frame with ?sc-edit=1, for staff only). Text is already wrapped by PageText::decorate(); this adds the hover
    outline, in-place typing, and the "replace picture" / "back to the original" bar, and reports each change to
    the dashboard with postMessage. It changes nothing itself: the dashboard saves, then answers.
--}}
@verbatim
<style id="sc-style">
    /* Everything must be there to click: no loading screen, nothing waiting to scroll into view. */
    .preloader,.preloader__gif{display:none!important}
    .reveal{opacity:1!important;pointer-events:auto!important;transform:none!important;transition:none!important}
    .fade-in-up{animation:none!important;opacity:1!important}
    html,body{overflow:auto!important;height:auto!important}
    [data-sc-t]{cursor:text}
    [data-sc-c]{text-decoration:underline dotted #d99b00;text-underline-offset:4px}
    [data-sc-t][contenteditable]{outline:2px solid #be1f24!important;outline-offset:3px;background:rgb(255 255 255/.92);color:#222!important;text-decoration:none;border-radius:3px;min-width:1ch;cursor:text;-webkit-user-select:text;user-select:text;text-shadow:none}
    [data-sc-saved]{animation:scsaved 1s ease}
    @keyframes scsaved{0%{background:#b6e8cd}100%{background:transparent}}
    #sc-box{position:fixed;z-index:2147483000;pointer-events:none;border:2px dashed #be1f24;border-radius:4px;display:none;box-shadow:0 0 0 9999px rgb(0 0 0/0)}
    #sc-bar{position:fixed;z-index:2147483001;display:none;gap:6px;font:600 12px/1 system-ui,sans-serif}
    #sc-bar button{all:unset;cursor:pointer;display:inline-flex;align-items:center;gap:6px;padding:8px 11px;border-radius:999px;background:#be1f24;color:#fff;box-shadow:0 2px 8px rgb(0 0 0/.3)}
    #sc-bar button.back{background:#fff;color:#444;border:1px solid #ccc}
    #sc-bar button:hover{filter:brightness(1.1)}
    #sc-hint{position:fixed;z-index:2147483002;left:50%;bottom:14px;transform:translateX(-50%);padding:8px 14px;border-radius:999px;background:rgb(21 22 24/.86);color:#fff;font:500 12.5px/1 system-ui,sans-serif;pointer-events:none;opacity:1;transition:opacity .6s}
    #sc-toast{position:fixed;z-index:2147483002;right:14px;top:14px;padding:9px 13px;border-radius:10px;background:#1d7a4c;color:#fff;font:600 12.5px/1 system-ui,sans-serif;display:none}
    #sc-toast.bad{background:#be1f24}
    .sc-busy{opacity:.5!important;outline:2px dashed #9a6412}
</style>
<div id="sc-box"></div>
<div id="sc-bar"></div>
<div id="sc-hint">Click any text to type over it · click a picture to replace it</div>
<div id="sc-toast"></div>
<script id="sc-script">
(function () {
    if (window.__scEditor) return;
    window.__scEditor = true;

    var parentWin = window.parent, origin = location.origin;
    var box = document.getElementById('sc-box'), bar = document.getElementById('sc-bar');
    var toastEl = document.getElementById('sc-toast'), hintEl = document.getElementById('sc-hint');
    var current = null, editing = null, hideTimer = null, pending = {}, seq = 0, mouse = {x: 0, y: 0};

    function post(message) { parentWin.postMessage(Object.assign({sc: 1}, message), origin); }
    function toast(text, bad) { toastEl.textContent = text; toastEl.className = bad ? 'bad' : ''; toastEl.style.display = 'block'; clearTimeout(toast.t); toast.t = setTimeout(function () { toastEl.style.display = 'none'; }, 2200); }
    setTimeout(function () { hintEl.style.opacity = '0'; }, 6000);

    // A video's <source> carries the slot; the video is what gets clicked.
    document.querySelectorAll('source[data-sc-slot]').forEach(function (s) { if (s.parentElement) s.parentElement.setAttribute('data-sc-slot', s.getAttribute('data-sc-slot')); });

    function targetAt(x, y) {
        var els = document.elementsFromPoint(x, y), i, el;
        for (i = 0; i < els.length; i++) { el = els[i].closest && els[i].closest('[data-sc-t]'); if (el) return el; }
        for (i = 0; i < els.length; i++) { if (els[i].matches && els[i].matches('img[data-sc-slot],img[data-sc-i],video[data-sc-slot]')) return els[i]; }
        return null;
    }

    function show(el) {
        if (editing) return;
        current = el; clearTimeout(hideTimer);
        var r = el.getBoundingClientRect();
        box.style.cssText = 'display:block;left:' + (r.left - 3) + 'px;top:' + (r.top - 3) + 'px;width:' + (r.width + 6) + 'px;height:' + (r.height + 6) + 'px';
        var isText = el.hasAttribute('data-sc-t'), changed = el.hasAttribute('data-sc-c');
        bar.innerHTML = '';
        if (!isText) bar.appendChild(button('Replace picture', function () { pick(el); }));
        if (changed) bar.appendChild(button('↺ Back to the original', function () { reset(el); }, 'back'));
        if (isText && !changed) { bar.style.display = 'none'; return; }
        bar.style.display = 'flex';
        var top = Math.max(6, r.top - 38), left = Math.min(Math.max(6, r.left), window.innerWidth - bar.offsetWidth - 6);
        bar.style.top = top + 'px'; bar.style.left = left + 'px';
    }
    function hide() { hideTimer = setTimeout(function () { if (editing) return; box.style.display = 'none'; bar.style.display = 'none'; current = null; }, 220); }
    function button(label, fn, cls) { var b = document.createElement('button'); b.type = 'button'; b.textContent = label; if (cls) b.className = cls; b.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); fn(); }); return b; }

    document.addEventListener('mousemove', function (e) {
        mouse = {x: e.clientX, y: e.clientY};
        if (editing || bar.contains(e.target)) { clearTimeout(hideTimer); return; }
        var t = targetAt(e.clientX, e.clientY);
        if (t) { if (t !== current) show(t); else clearTimeout(hideTimer); } else if (current) hide();
    }, true);
    window.addEventListener('scroll', function () { if (current && !editing) show(current); }, true);

    // Nothing on the page may act while it is being edited: no links, no forms.
    document.addEventListener('submit', function (e) { e.preventDefault(); }, true);
    document.addEventListener('click', function (e) {
        if (bar.contains(e.target)) return;
        if (editing && editing.contains(e.target)) return;
        var t = targetAt(e.clientX, e.clientY);
        if (t) {
            e.preventDefault(); e.stopPropagation();
            if (t.hasAttribute('data-sc-t')) edit(t); else pick(t);
            return;
        }
        if (editing) { editing.blur(); }
        var a = e.target.closest && e.target.closest('a[href]');
        if (a) e.preventDefault();
    }, true);

    // Text: type over it where it stands.
    function edit(el) {
        if (editing === el) return;
        if (editing) editing.blur();
        editing = el; el.__before = el.textContent;
        el.setAttribute('contenteditable', 'true'); el.setAttribute('spellcheck', 'true');
        box.style.display = 'none'; bar.style.display = 'none';
        el.focus();
        var range = document.createRange(); range.selectNodeContents(el);
        var sel = getSelection(); sel.removeAllRanges(); sel.addRange(range);
    }
    document.addEventListener('keydown', function (e) {
        if (!editing) return;
        if (e.key === 'Enter') { e.preventDefault(); editing.blur(); }
        else if (e.key === 'Escape') { e.preventDefault(); editing.textContent = editing.__before; editing.blur(); }
        e.stopPropagation();
    }, true);
    document.addEventListener('paste', function (e) {
        if (!editing) return;
        e.preventDefault();
        document.execCommand('insertText', false, (e.clipboardData || window.clipboardData).getData('text').replace(/\s+/g, ' '));
    }, true);
    document.addEventListener('focusout', function (e) {
        var el = e.target;
        if (!el || el !== editing) return;
        editing = null; el.removeAttribute('contenteditable');
        var text = el.textContent.replace(/\s+/g, ' ').trim();
        if (text === el.__before.replace(/\s+/g, ' ').trim()) { el.textContent = el.__before; return; }
        if (text === '') { el.textContent = el.__before; toast('A line cannot be empty', true); return; }
        var id = ++seq;
        pending[id] = el; el.classList.add('sc-busy');
        post({type: 'text', id: id, key: el.getAttribute('data-sc-t'), where: el.getAttribute('data-sc-w'), original: el.getAttribute('data-sc-o'), text: text});
    }, true);

    // Pictures: the dashboard opens the file chooser and uploads.
    function pick(el) {
        var id = ++seq; pending[id] = el; el.classList.add('sc-busy');
        post({type: 'image', id: id, slot: el.getAttribute('data-sc-slot') || '', key: el.getAttribute('data-sc-i') || '', video: el.tagName === 'VIDEO',
              src: el.getAttribute('data-sc-s') || ''});
    }
    function reset(el) {
        var id = ++seq; pending[id] = el;
        post({type: 'reset', id: id, key: el.getAttribute('data-sc-slot') || el.getAttribute('data-sc-i') || el.getAttribute('data-sc-t')});
    }

    // The dashboard's answers.
    window.addEventListener('message', function (e) {
        if (e.origin !== origin || !e.data || e.data.sc !== 1 || e.data.type !== 'done') return;
        var el = pending[e.data.id]; delete pending[e.data.id];
        if (!el) return;
        el.classList.remove('sc-busy');
        if (!e.data.ok) { if (el.hasAttribute('data-sc-t')) el.textContent = el.__before; if (e.data.message) toast(e.data.message, true); return; }
        if (el.hasAttribute('data-sc-t')) {
            el.setAttribute('data-sc-c', '1');
            if (e.data.original) el.textContent = e.data.original, el.removeAttribute('data-sc-c');
            el.setAttribute('data-sc-saved', ''); setTimeout(function () { el.removeAttribute('data-sc-saved'); }, 1000);
            toast('Saved');
        }
    });
})();
</script>
@endverbatim
