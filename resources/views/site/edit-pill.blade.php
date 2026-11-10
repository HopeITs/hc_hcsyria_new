{{--
    Shown on a public page, to a signed-in account that may change the site, instead of the editor: one button
    that opens the same page ready to edit. Hidden inside the dashboard's own preview frame.
--}}
<a id="sc-pill" href="{{ $href }}" style="position:fixed;z-index:2147483000;left:14px;bottom:14px;display:inline-flex;align-items:center;gap:8px;padding:11px 16px;border-radius:999px;background:#be1f24;color:#fff;font:700 13px/1 system-ui,sans-serif;text-decoration:none;box-shadow:0 4px 14px rgb(0 0 0/.3)">✎ Edit this page</a>
<script>if (window.parent !== window) { var p = document.getElementById('sc-pill'); if (p) p.remove(); }</script>
