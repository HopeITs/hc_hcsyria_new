{{--
    The visual editor's half that lives inside the public page (the dashboard's Site content opens the page in a
    frame with ?sc-edit=1, for staff only). Text is already wrapped by PageText::decorate(); this adds the hover
    outline, in-place typing, and the "replace picture" / "back to the original" bar, and reports each change to
    the dashboard with postMessage. It changes nothing itself: the dashboard saves, then answers.
--}}
@php($hint = match (true) {
    $words && $pictures => 'Click any text to type over it · click or drop a file on a picture or logo to replace it',
    $words => 'Click any text to type over it',
    default => 'Click a picture or logo to replace it, or drop a file onto it',
})
<script>window.__scConfig = {!! json_encode(['endpoint' => $endpoint, 'token' => $token, 'leave' => $leave, 'hint' => $hint], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!};</script>
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
    #sc-done{position:fixed;z-index:2147483002;right:14px;bottom:14px;padding:11px 16px;border-radius:999px;background:#1d7a4c;color:#fff!important;font:700 13px/1 system-ui,sans-serif;text-decoration:none!important;box-shadow:0 4px 14px rgb(0 0 0/.3)}
    #sc-toast{position:fixed;z-index:2147483002;right:14px;top:14px;padding:9px 13px;border-radius:10px;background:#1d7a4c;color:#fff;font:600 12.5px/1 system-ui,sans-serif;display:none}
    #sc-toast.bad{background:#be1f24}
    .sc-drop{outline:3px solid #be1f24!important;outline-offset:2px;filter:brightness(.8)}
    .sc-busy{opacity:.5!important;outline:2px dashed #9a6412}
</style>
<div id="sc-box"></div>
<div id="sc-bar"></div>
<div id="sc-hint"></div>
<div id="sc-toast"></div>
<script id="sc-script">
(function () {
    if (window.__scEditor) return;
    window.__scEditor = true;

    var parentWin = window.parent, origin = location.origin;
    var box = document.getElementById('sc-box'), bar = document.getElementById('sc-bar');
    var toastEl = document.getElementById('sc-toast'), hintEl = document.getElementById('sc-hint');
    var current = null, editing = null, hideTimer = null, pending = {}, seq = 0, mouse = {x: 0, y: 0};

    var cfg = window.__scConfig || {}, framed = parentWin !== window;

    // Inside the dashboard the dashboard saves; on the public site itself this page saves, over HTTP.
    function post(message) { if (framed) parentWin.postMessage(Object.assign({sc: 1}, message), origin); else local(message); }

    function request(path, body) {
        var headers = {'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.token, 'X-Requested-With': 'XMLHttpRequest'};
        if (!(body instanceof FormData)) { headers['Content-Type'] = 'application/json'; body = JSON.stringify(body); }
        return fetch(cfg.endpoint + path, {method: 'POST', headers: headers, body: body, credentials: 'same-origin'})
            .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return r.ok ? j : {ok: false, message: j.message || (r.status === 419 ? 'This page has expired: reload it.' : r.status === 403 ? 'Your role cannot change the site.' : 'Not saved.')}; }); })
            .catch(function () { return {ok: false, message: 'Could not reach the server.'}; });
    }
    function local(m) {
        var finish = function (result) { finished(m.id, result); };
        if (m.type === 'text') request('/text', {where: m.where, original: m.original, text: m.text}).then(finish);
        else if (m.type === 'reset') request('/reset', {key: m.key}).then(function (r) { if (r.ok) location.reload(); else finish(r); });
        else if (m.type === 'image') {
            var send = function (file) {
                var form = new FormData();
                form.append('file', file); form.append('slot', m.slot); form.append('key', m.key); form.append('src', m.src); form.append('video', m.video ? '1' : '0');
                toast(m.video ? 'Uploading the film…' : 'Uploading…');
                request('/file', form).then(function (r) { if (r.ok) location.reload(); else finish(r); });
            };
            if (m.file) return send(m.file);
            var input = document.createElement('input');
            input.type = 'file'; input.accept = m.video ? 'video/mp4' : 'image/jpeg,image/png,image/webp';
            input.addEventListener('cancel', function () { finish({ok: false, message: ''}); });
            input.addEventListener('change', function () { if (!input.files[0]) return finish({ok: false, message: ''}); send(input.files[0]); });
            input.click();
        }
    }
    function toast(text, bad) { toastEl.textContent = text; toastEl.className = bad ? 'bad' : ''; toastEl.style.display = 'block'; clearTimeout(toast.t); toast.t = setTimeout(function () { toastEl.style.display = 'none'; }, 2200); }
    hintEl.textContent = cfg.hint || '';
    setTimeout(function () { hintEl.style.opacity = '0'; }, 7000);

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
        if (isText) bar.appendChild(button('✎ Edit text', function () { edit(el); }));
        if (changed) bar.appendChild(button('↺ Back to the original', function () { reset(el); }, 'back'));
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
    function pick(el, file) {
        var id = ++seq; pending[id] = el; el.classList.add('sc-busy');
        post({type: 'image', id: id, slot: el.getAttribute('data-sc-slot') || '', key: el.getAttribute('data-sc-i') || '', video: el.tagName === 'VIDEO',
              src: el.getAttribute('data-sc-s') || '', file: file || null});
    }

    // Drag and drop: a file dropped on a picture, a logo or the film replaces it.
    var dropOn = null;
    function pictureAt(x, y) {
        var els = document.elementsFromPoint(x, y);
        for (var i = 0; i < els.length; i++) { if (els[i].matches && els[i].matches('img[data-sc-slot],img[data-sc-i],video[data-sc-slot]')) return els[i]; }
        return null;
    }
    function hasFile(e) { return e.dataTransfer && Array.prototype.indexOf.call(e.dataTransfer.types || [], 'Files') !== -1; }
    function clearDrop() { if (dropOn) dropOn.classList.remove('sc-drop'); dropOn = null; }
    ['dragenter', 'dragover'].forEach(function (name) {
        document.addEventListener(name, function (e) {
            if (!hasFile(e)) return;
            e.preventDefault();
            var t = pictureAt(e.clientX, e.clientY);
            if (t !== dropOn) { clearDrop(); dropOn = t; if (t) t.classList.add('sc-drop'); }
            e.dataTransfer.dropEffect = t ? 'copy' : 'none';
        }, true);
    });
    document.addEventListener('dragleave', function (e) { if (!e.relatedTarget) clearDrop(); }, true);
    document.addEventListener('drop', function (e) {
        if (!hasFile(e)) return;
        e.preventDefault(); e.stopPropagation();
        var t = pictureAt(e.clientX, e.clientY), file = e.dataTransfer.files[0];
        clearDrop();
        if (!t) { toast('Drop the file onto a picture or a logo', true); return; }
        var video = t.tagName === 'VIDEO';
        if (!file || (video ? file.type !== 'video/mp4' : !/^image\/(jpeg|png|webp)$/.test(file.type))) { toast(video ? 'That needs an MP4 film' : 'That needs a JPEG, PNG or WebP picture', true); return; }
        pick(t, file);
    }, true);
    function reset(el) {
        var id = ++seq; pending[id] = el;
        post({type: 'reset', id: id, key: el.getAttribute('data-sc-slot') || el.getAttribute('data-sc-i') || el.getAttribute('data-sc-t')});
    }

    // The answer, from the dashboard or from the server.
    function finished(id, result) {
        var el = pending[id]; delete pending[id];
        if (!el) return;
        el.classList.remove('sc-busy');
        if (!result.ok) { if (el.hasAttribute('data-sc-t')) el.textContent = el.__before; if (result.message) toast(result.message, true); return; }
        if (el.hasAttribute('data-sc-t')) {
            el.setAttribute('data-sc-c', '1');
            el.setAttribute('data-sc-saved', ''); setTimeout(function () { el.removeAttribute('data-sc-saved'); }, 1000);
            toast('Saved');
        }
    }
    window.addEventListener('message', function (e) {
        if (e.origin !== origin || !e.data || e.data.sc !== 1 || e.data.type !== 'done') return;
        finished(e.data.id, {ok: e.data.ok, message: e.data.message});
    });

    // On the public site there is no dashboard around the page: a way out of editing.
    if (!framed) {
        var done = document.createElement('a');
        done.id = 'sc-done'; done.href = cfg.leave || location.pathname; done.textContent = '✓ Done editing';
        document.body.appendChild(done);
    }
})();
</script>
@endverbatim
