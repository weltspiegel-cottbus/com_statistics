<?php

/**
 * @package     Weltspiegel\Component\Statistics\Administrator\Helper
 *
 * @copyright   Weltspiegel Cottbus
 * @license     MIT; see LICENSE file
 */

namespace Weltspiegel\Component\Statistics\Administrator\Helper;

\defined('_JEXEC') or die;

/**
 * Turns what a page request carries into the kind of page and the kind of origin
 * that the page view statistics count.
 *
 * Everything here is a pure function of its arguments: it reads no request and
 * stores nothing, which keeps the rules testable on their own. The referrer
 * arrives only to be sorted into one of a handful of kinds and is then gone —
 * neither the address nor the host is ever written anywhere.
 *
 * @since 1.1.0
 */
abstract class PageViewClassifier
{
    /**
     * Categories of the component-managed content (Vorschau and Veranstaltungen).
     *
     * The same ids the template's article and blog overrides key their special
     * layout on, and the ones com_weltspiegel writes its articles to.
     *
     * @since 1.1.0
     */
    private const PREVIEW_CATEGORIES = [8, 9];

    /**
     * Menu path of the service pages (Eintrittspreise, Kontakt, FSK …).
     *
     * @since 1.1.0
     */
    private const SERVICE_PATH = 'service';

    /**
     * Hosts of search engines. A name matches itself and its subdomains; the
     * Google entry also matches every country domain.
     *
     * @since 1.1.0
     */
    private const SEARCH_HOSTS = [
        'bing.com', 'duckduckgo.com', 'ecosia.org', 'qwant.com', 'startpage.com',
        'yahoo.com', 'yandex.com', 'yandex.ru', 'search.brave.com',
    ];

    /**
     * Hosts of social networks and messengers.
     *
     * @since 1.1.0
     */
    private const SOCIAL_HOSTS = [
        'facebook.com', 'fb.com', 'instagram.com', 'threads.net', 'x.com', 't.co', 'twitter.com',
        'youtube.com', 'youtu.be', 'tiktok.com', 'whatsapp.com', 'wa.me', 'linkedin.com',
        'pinterest.com', 'reddit.com', 'telegram.org', 't.me',
    ];

    /**
     * The origin of a page view.
     *
     * Decided by `Sec-Fetch-Site`, which the browser sets on every request and
     * which says where the navigation was started: not on a page at all (an
     * address typed in, a bookmark, a link from another app), on this very site,
     * or on another one. Only for the last case the referrer is looked at, and
     * only to tell search engines and social networks from the rest.
     *
     * @param   string  $secFetchSite  Value of the Sec-Fetch-Site header
     * @param   string  $referer       Value of the Referer header, may be empty
     *
     * @return  string|null  Key of {@see PageViewRegistry::SOURCES}; null when the
     *                       request does not say (no browser navigation we know)
     *
     * @since 1.1.0
     */
    public static function sourceOf(string $secFetchSite, string $referer): ?string
    {
        return match (strtolower(trim($secFetchSite))) {
            'none'                   => 'direct',
            'same-origin', 'same-site' => 'internal',
            'cross-site'             => static::kindOfForeignHost(static::hostOf($referer)),
            default                  => null,
        };
    }

    /**
     * The kind of page a request is for, once routing has run.
     *
     * @param   string       $option  Requested component
     * @param   string       $view    Requested view
     * @param   object|null  $active  The active menu item
     *
     * @return  string  Key of {@see PageViewRegistry::TYPES}
     *
     * @since 1.1.0
     */
    public static function typeOfRequest(string $option, string $view, ?object $active): string
    {
        // The cinema's own pages are told apart by view, whatever menu item they
        // were reached through.
        if ($option === 'com_weltspiegel') {
            if ($view === 'movie') {
                return 'movie';
            }

            if ($view === 'movies') {
                return 'programme';
            }
        }

        if ($active === null) {
            return 'other';
        }

        // An article that was reached through the home menu item (a "read more"
        // from the front page) is an article, not the front page.
        if (!empty($active->home) && $view !== '' && $view !== 'featured') {
            return 'other';
        }

        return static::typeOfMenuItem($active, false);
    }

    /**
     * The kind of page behind a URL path, as the site's menu knows it.
     *
     * Used for the page a visitor came from, whose URL is all there is. A path
     * that is a menu item's own address takes that item's kind; one that lies
     * below a menu item takes the kind of the pages found below it — a film
     * under the programme, an article under Vorschau.
     *
     * @param   string  $path        Path without the site's base and without query
     * @param   array   $menuByRoute Menu items keyed by their route
     *
     * @return  string  Key of {@see PageViewRegistry::TYPES}
     *
     * @since 1.1.0
     */
    public static function typeOfPath(string $path, array $menuByRoute): string
    {
        $path = static::normalizePath($path);

        if ($path === '') {
            return 'home';
        }

        if (isset($menuByRoute[$path])) {
            return static::typeOfMenuItem($menuByRoute[$path], false);
        }

        $best = null;

        foreach ($menuByRoute as $route => $item) {
            $route = (string) $route;

            if ($route !== '' && str_starts_with($path, $route . '/') && ($best === null || \strlen($route) > \strlen($best))) {
                $best = $route;
            }
        }

        return $best === null ? 'other' : static::typeOfMenuItem($menuByRoute[$best], true);
    }

    /**
     * The kind of page a menu item stands for.
     *
     * @param   object  $item   Menu item (route, home, component, query)
     * @param   bool    $below  True for a page that lies below the item rather than being it
     *
     * @return  string  Key of {@see PageViewRegistry::TYPES}
     *
     * @since 1.1.0
     */
    public static function typeOfMenuItem(object $item, bool $below): string
    {
        $query = (array) ($item->query ?? []);
        $view  = (string) ($query['view'] ?? '');

        if (!empty($item->home)) {
            return $below ? 'other' : 'home';
        }

        if (($item->component ?? '') === 'com_weltspiegel') {
            if ($view === 'movies') {
                return $below ? 'movie' : 'programme';
            }

            if ($view === 'movie') {
                return 'movie';
            }
        }

        if (($item->component ?? '') === 'com_content'
            && $view === 'category'
            && \in_array((int) ($query['id'] ?? 0), self::PREVIEW_CATEGORIES, true)
        ) {
            return 'preview';
        }

        $route = (string) ($item->route ?? '');

        if ($route === self::SERVICE_PATH || str_starts_with($route, self::SERVICE_PATH . '/')) {
            return 'service';
        }

        return 'other';
    }

    /**
     * The path of a referrer on this site, or null if it comes from elsewhere.
     *
     * @param   string  $referer   The Referer header
     * @param   string  $ownHost   Host of this site
     * @param   string  $basePath  Path the site is installed under ('' for the root)
     *
     * @return  string|null
     *
     * @since 1.1.0
     */
    public static function ownPathOf(string $referer, string $ownHost, string $basePath = ''): ?string
    {
        $parts = parse_url(trim($referer));

        if (!\is_array($parts) || empty($parts['host'])) {
            return null;
        }

        if (static::stripWww($parts['host']) !== static::stripWww($ownHost)) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '');
        $base = rtrim($basePath, '/');

        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, \strlen($base));
        }

        return static::normalizePath($path);
    }

    /**
     * Lower-case path without leading or trailing slashes and without the
     * `index.php` that sites without URL rewriting carry.
     *
     * @param   string  $path
     *
     * @return  string
     *
     * @since 1.1.0
     */
    private static function normalizePath(string $path): string
    {
        $path = strtolower(trim(rawurldecode($path), '/'));

        if ($path === 'index.php') {
            return '';
        }

        if (str_starts_with($path, 'index.php/')) {
            $path = substr($path, \strlen('index.php/'));
        }

        return trim($path, '/');
    }

    /**
     * Kind of origin of a request from another site.
     *
     * @param   string  $host  Host of the referrer, empty if the browser sent none
     *
     * @return  string
     *
     * @since 1.1.0
     */
    private static function kindOfForeignHost(string $host): string
    {
        if ($host === '') {
            return 'web';
        }

        if (preg_match('/(^|\.)google\.[a-z.]+$/', $host) === 1 || static::hostIn($host, self::SEARCH_HOSTS)) {
            return 'search';
        }

        return static::hostIn($host, self::SOCIAL_HOSTS) ? 'social' : 'web';
    }

    /**
     * Whether a host is one of the given domains or a subdomain of one.
     *
     * @param   string    $host
     * @param   string[]  $domains
     *
     * @return  bool
     *
     * @since 1.1.0
     */
    private static function hostIn(string $host, array $domains): bool
    {
        foreach ($domains as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Host of a URL, lower-case, or '' when there is none.
     *
     * @param   string  $url
     *
     * @return  string
     *
     * @since 1.1.0
     */
    private static function hostOf(string $url): string
    {
        $host = parse_url(trim($url), PHP_URL_HOST);

        return \is_string($host) ? strtolower($host) : '';
    }

    /**
     * Host without a leading "www.".
     *
     * @param   string  $host
     *
     * @return  string
     *
     * @since 1.1.0
     */
    private static function stripWww(string $host): string
    {
        $host = strtolower($host);

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
