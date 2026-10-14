<?php

namespace App\Support;

use Detection\MobileDetect;
use Illuminate\Http\Request;

/*
 * What kind of device is asking, from its User-Agent.
 *
 * The legacy site chose some page content on the server this way (the home
 * cover is a different image for phones) with jenssegers/agent, whose
 * isPhone() is "mobile and not a tablet". Matching on width in CSS instead
 * would change what a phone held sideways, or a narrow desktop window, shows.
 *
 * Anything cached in front of the app must vary on User-Agent for pages that
 * use this.
 */
final class Device
{
    public static function isPhone(Request $request): bool
    {
        $detect = self::detect($request);

        return $detect->isMobile() && ! $detect->isTablet();
    }

    /** A phone or a tablet: what legacy's Agent::isMobile() answered. */
    public static function isMobile(Request $request): bool
    {
        return self::detect($request)->isMobile();
    }

    private static function detect(Request $request): MobileDetect
    {
        $detect = new MobileDetect;
        $detect->setUserAgent((string) $request->userAgent());

        return $detect;
    }
}
