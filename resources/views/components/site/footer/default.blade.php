{{--
    The footer of 23 of the public pages and the error pages. Ported from
    new/public/footer.blade.php; text, links and order exactly as there,
    styles in resources/css/site/footer.css.

    The legacy partial was a whole HTML document (doctype, head, body) pasted
    into every page; only the <footer> is ported. The contact and copyright
    bands are the shared site.footer.bar.
--}}
<footer id="footer" class="site-footer">
    <div class="site-footer__separator"></div>

    <x-site.footer.bar/>
</footer>
