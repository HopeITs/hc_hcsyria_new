{{--
    The bottom two bands of every footer: contact details and social links on
    red, then the newsletter link, copyright and "Back to Top". Shared by the
    default footer and the programme footers, which put their own strip above
    it. Differences from the legacy partial are in docs/parity-allowlist.json:
      - "Visit our Newsletter" pointed at /newsletter/10/1, a 404 on the live
        site; it points at /our-news, the newsletter page.
      - "Back to Top" was an <a> without href; it is a button, which is what
        it does.
--}}
<div class="site-footer__middle">
    <div class="site-footer__inner">
        <div class="site-footer__row items-center">
            <div class="site-footer__col flex flex-col justify-center sm:justify-start">
                <div>contact us:</div>
                <div class="site-footer__office">hope center</div>
            </div>

            <div class="site-footer__col flex flex-col justify-center text-center">
                <div>
                    <span class="site-footer__label">head office </span><span class="site-footer__label">address: </span>
                    <a class="site-footer__info-link" target="_blank" rel="noopener"
                       href="https://www.google.com/maps/place//@36.218683,37.154985,17z/data=!4m6!1m5!3m4!2zMzbCsDEzJzA3LjMiTiAzN8KwMDknMTguMCJF!8m2!3d36.218683!4d37.154985?entry=ttu&amp;g_ep=EgoyMDI0MTEwNS4wIKXMDSoASAFQAw%3D%3D">
                        Eastern Villat Street <b>Aleppo, Syria</b>
                    </a>
                </div>
                <div class="flex justify-center gap-[1.2vw]">
                    <span class="site-footer__label">phone:
                        <a class="site-footer__info-link" href="tel:+963993310227">+963 993 310 227</a>
                    </span> |
                    <span class="site-footer__label">email:
                        <a class="site-footer__info-link lowercase" href="mailto:info@hcsyria.org">info@hcsyria.org</a>
                    </span>
                </div>
            </div>

            {{-- Legacy: the icons sit in links with whitespace after the icon, which renders as a space. --}}
            <div class="site-footer__col items-center sm:flex sm:flex-col sm:content-end md:items-end lg:justify-end">
                <div>
                    <a href="https://hcsyria.org" target="_blank" rel="noopener" aria-label="hcsyria.org"><span class="site-footer__social"><x-ui.icon name="fa-home"/></span> </a><a href="https://www.youtube.com/channel/UC777Xf3lHtQufwaYjgxsiRA/videos" target="_blank" rel="noopener" aria-label="YouTube"><span class="site-footer__social"><x-ui.icon name="fa-youtube-play"/></span> </a><a href="https://www.linkedin.com/company/christian-hope-center/?viewAsMember=true" target="_blank" rel="noopener" aria-label="LinkedIn"><span class="site-footer__social"><x-ui.icon name="fa-linkedin"/></span> </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="site-footer__bottom">
    <div class="site-footer__inner">
        <div class="site-footer__row items-baseline">
            <div class="site-footer__col flex justify-center sm:justify-start">
                <div class="capitalize"><strong><a class="text-white" href="/our-news">Visit our Newsletter</a></strong></div>
            </div>

            <div class="site-footer__col flex justify-center">
                <div class="site-footer__copyright">{!! __('main.footer') !!}</div>
            </div>

            <div class="site-footer__col hidden sm:flex sm:justify-end">
                <button type="button" class="site-footer__to-top" x-data @click="window.scrollTo(0, 0)">Back to Top</button>
            </div>
        </div>
    </div>
</div>
