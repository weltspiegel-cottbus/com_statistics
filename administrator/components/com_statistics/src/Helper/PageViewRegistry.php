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
 * Vocabulary of the page view statistics: which kinds of pages and which kinds
 * of origin there are, and how they are labelled.
 *
 * Kept apart from {@see MetricRegistry}: those metrics are measured by CSS
 * probes and arrive through the public collect endpoint, which must never be
 * able to write page view counters. These are written server side by the
 * system plugin and are not known to the endpoint's validation.
 *
 * Two counters make up the whole statistic, both stored as plain
 * metric/bucket/hits rows like everything else:
 *
 *  - `page_entry`: one row per kind of page × kind of origin
 *  - `page_flow`:  one row per previous kind of page × kind of page, for
 *                  navigations within the site only
 *
 * Their keys are joined with an underscore, so no key may contain one.
 *
 * @since 1.1.0
 */
abstract class PageViewRegistry
{
    /**
     * Counter of page views by kind of page and origin.
     *
     * @since 1.1.0
     */
    public const METRIC_ENTRY = 'page_entry';

    /**
     * Counter of navigations within the site, by previous and new kind of page.
     *
     * @since 1.1.0
     */
    public const METRIC_FLOW = 'page_flow';

    /**
     * Kinds of pages.
     *
     * @since 1.1.0
     */
    public const TYPES = [
        'home'      => 'Startseite',
        'programme' => 'Programm',
        'movie'     => 'Film-Detail',
        'preview'   => 'Vorschau / Veranstaltungen',
        'service'   => 'Service-Seiten',
        'other'     => 'Übrige',
    ];

    /**
     * The page a navigation came from, when it cannot be told (the browser
     * sent no usable referrer).
     *
     * @since 1.1.0
     */
    public const UNKNOWN = 'unknown';

    /**
     * Kinds of origin of a page view.
     *
     * @since 1.1.0
     */
    public const SOURCES = [
        'direct'   => 'Direkt (Adresse, Lesezeichen, andere App)',
        'internal' => 'Von unserer Seite',
        'search'   => 'Suchmaschine',
        'social'   => 'Soziale Netze',
        'web'      => 'Andere Websites',
    ];

    /**
     * Whether a metric belongs to the page view statistics.
     *
     * @param   string  $metric  Metric key
     *
     * @return  bool
     *
     * @since 1.1.0
     */
    public static function isPageMetric(string $metric): bool
    {
        return $metric === self::METRIC_ENTRY || $metric === self::METRIC_FLOW;
    }

    /**
     * Bucket key of a page view.
     *
     * @param   string  $type    Key of {@see TYPES}
     * @param   string  $source  Key of {@see SOURCES}
     *
     * @return  string
     *
     * @since 1.1.0
     */
    public static function entryBucket(string $type, string $source): string
    {
        return $type . '_' . $source;
    }

    /**
     * Bucket key of a navigation within the site.
     *
     * @param   string  $from  Key of {@see TYPES} or {@see UNKNOWN}
     * @param   string  $to    Key of {@see TYPES}
     *
     * @return  string
     *
     * @since 1.1.0
     */
    public static function flowBucket(string $from, string $to): string
    {
        return $from . '_' . $to;
    }

    /**
     * Kinds of page the previous page of a navigation can be.
     *
     * @return  array<string, string>  key => label
     *
     * @since 1.1.0
     */
    public static function flowOrigins(): array
    {
        return self::TYPES + [self::UNKNOWN => 'unbekannt'];
    }
}
