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
 * Central registry of all measurable metrics.
 *
 * Every metric is a set of mutually exclusive buckets, each described by a CSS
 * media query condition. The registry is the single source of truth for three
 * consumers:
 *
 *  - the system plugin, which generates one probe rule per bucket,
 *  - the collect endpoint, which validates incoming values against it,
 *  - the admin dashboard, which turns the stored keys into readable labels.
 *
 * Adding a metric therefore means adding one entry here — no database schema
 * change is required, because the counter table stores metric/bucket as plain
 * strings.
 *
 * Note on syntax: the conditions deliberately use the classic
 * `min-width`/`max-width` form instead of the modern range syntax used in the
 * site's own stylesheets. A browser that cannot parse the query would silently
 * never report, which would bias the measurement towards newer browsers — the
 * very thing we are trying to measure.
 *
 * @since 1.0.0
 */
abstract class MetricRegistry
{
    /**
     * Metrics enabled when the plugin has not been configured yet.
     *
     * @var string[]
     *
     * @since 1.0.0
     */
    public const DEFAULT_METRICS = ['width', 'pointer'];

    /**
     * All known metrics.
     *
     * Structure: key => ['label' => string, 'hint' => string, 'buckets' => [bucketKey => mediaCondition]]
     *
     * @return array
     *
     * @since 1.0.0
     */
    public static function all(): array
    {
        $metrics = [
            'width' => [
                'label'   => 'Viewport-Breite',
                'hint'    => 'Breite des sichtbaren Bereichs in CSS-Pixeln. Die Grenzen folgen den Breakpoints des Templates.',
                'buckets' => [
                    'lt360'     => '(max-width: 359px)',
                    '360-389'   => '(min-width: 360px) and (max-width: 389px)',
                    '390-413'   => '(min-width: 390px) and (max-width: 413px)',
                    '414-479'   => '(min-width: 414px) and (max-width: 479px)',
                    '480-599'   => '(min-width: 480px) and (max-width: 599px)',
                    '600-767'   => '(min-width: 600px) and (max-width: 767px)',
                    '768-1023'  => '(min-width: 768px) and (max-width: 1023px)',
                    '1024-1279' => '(min-width: 1024px) and (max-width: 1279px)',
                    '1280-1599' => '(min-width: 1280px) and (max-width: 1599px)',
                    'gte1600'   => '(min-width: 1600px)',
                ],
            ],
            'height' => [
                'label'   => 'Viewport-Höhe',
                'hint'    => 'Höhe des sichtbaren Bereichs in CSS-Pixeln.',
                'buckets' => [
                    'lt600'    => '(max-height: 599px)',
                    '600-699'  => '(min-height: 600px) and (max-height: 699px)',
                    '700-799'  => '(min-height: 700px) and (max-height: 799px)',
                    '800-899'  => '(min-height: 800px) and (max-height: 899px)',
                    '900-1079' => '(min-height: 900px) and (max-height: 1079px)',
                    'gte1080'  => '(min-height: 1080px)',
                ],
            ],
            'pointer' => [
                'label'   => 'Eingabegerät',
                'hint'    => 'Genauigkeit des primären Zeigegeräts — "grob" bedeutet in der Praxis Touch.',
                'buckets' => [
                    'coarse' => '(pointer: coarse)',
                    'fine'   => '(pointer: fine)',
                    'none'   => '(pointer: none)',
                ],
            ],
            'hover' => [
                'label'   => 'Hover möglich',
                'hint'    => 'Ob das primäre Eingabegerät Hover-Zustände auslösen kann.',
                'buckets' => [
                    'hover' => '(hover: hover)',
                    'none'  => '(hover: none)',
                ],
            ],
            'orientation' => [
                'label'   => 'Ausrichtung',
                'hint'    => 'Hoch- oder Querformat zum Zeitpunkt des Seitenaufrufs.',
                'buckets' => [
                    'portrait'  => '(orientation: portrait)',
                    'landscape' => '(orientation: landscape)',
                ],
            ],
            'dpr' => [
                'label'   => 'Pixeldichte',
                'hint'    => 'Geräte-Pixelverhältnis — relevant für die Auflösung von Plakaten und Bildern.',
                'buckets' => [
                    '1x'    => '(max-resolution: 1dppx)',
                    '1_5x'  => '(min-resolution: 1.01dppx) and (max-resolution: 1.99dppx)',
                    '2x'    => '(min-resolution: 2dppx) and (max-resolution: 2.99dppx)',
                    'gte3x' => '(min-resolution: 3dppx)',
                ],
            ],
            'color_scheme' => [
                'label'   => 'Farbschema-Wunsch',
                'hint'    => 'Vom Betriebssystem gemeldete Vorliebe für helle oder dunkle Darstellung.',
                'buckets' => [
                    'dark'  => '(prefers-color-scheme: dark)',
                    'light' => '(prefers-color-scheme: light)',
                ],
            ],
            'reduced_motion' => [
                'label'   => 'Reduzierte Bewegung',
                'hint'    => 'Barrierefreiheit: Nutzer, die Animationen systemweit abgeschaltet haben.',
                'buckets' => [
                    'reduce'        => '(prefers-reduced-motion: reduce)',
                    'no-preference' => '(prefers-reduced-motion: no-preference)',
                ],
            ],
            'contrast' => [
                'label'   => 'Kontrast-Wunsch',
                'hint'    => 'Barrierefreiheit: systemweit erhöhter oder verringerter Kontrast.',
                'buckets' => [
                    'more'          => '(prefers-contrast: more)',
                    'less'          => '(prefers-contrast: less)',
                    'custom'        => '(prefers-contrast: custom)',
                    'no-preference' => '(prefers-contrast: no-preference)',
                ],
            ],
            'forced_colors' => [
                'label'   => 'Erzwungene Farben',
                'hint'    => 'Barrierefreiheit: aktiver Hochkontrastmodus des Betriebssystems.',
                'buckets' => [
                    'active' => '(forced-colors: active)',
                    'none'   => '(forced-colors: none)',
                ],
            ],
            'scripting' => [
                'label'   => 'JavaScript',
                'hint'    => 'Ob JavaScript ausgeführt wird. Nur neuere Browser melden dieses Merkmal.',
                'buckets' => [
                    'enabled'      => '(scripting: enabled)',
                    'none'         => '(scripting: none)',
                    'initial-only' => '(scripting: initial-only)',
                ],
            ],
        ];

        // Cross tabulation. A metric with an 'axes' key is not a flat list of
        // buckets but a matrix: its bucket keys combine one key per axis, and
        // its media conditions are the conjunction of both. Deriving it from the
        // two source metrics keeps the ranges in sync automatically — editing a
        // width bucket above changes the matrix along with it.
        $metrics['width_pointer'] = [
            'label'   => 'Breite × Eingabegerät',
            'hint'    => 'Kreuztabelle beider Merkmale. Sie beantwortet, was die Einzelwerte nicht können — etwa ob sehr schmale Fenster von Telefonen stammen oder von stark vergrößerten Desktop-Ansichten.',
            'axes'    => ['width', 'pointer'],
            'buckets' => static::crossBuckets($metrics['width']['buckets'], $metrics['pointer']['buckets']),
        ];

        return $metrics;
    }

    /**
     * Combine two bucket sets into the bucket set of a cross tabulation.
     *
     * Keys are joined with an underscore, which stays unambiguous because no
     * key of the participating metrics contains one. Conditions are joined with
     * `and`, so a visit is counted in exactly one cell.
     *
     * @param   array  $rows     Buckets of the row axis
     * @param   array  $columns  Buckets of the column axis
     *
     * @return  array  bucketKey => mediaCondition
     *
     * @since 1.0.0
     */
    private static function crossBuckets(array $rows, array $columns): array
    {
        $buckets = [];

        foreach ($rows as $rowKey => $rowCondition) {
            foreach ($columns as $columnKey => $columnCondition) {
                $buckets[$rowKey . '_' . $columnKey] = $rowCondition . ' and ' . $columnCondition;
            }
        }

        return $buckets;
    }

    /**
     * Return a single metric definition.
     *
     * @param   string  $metric  Metric key
     *
     * @return  array|null
     *
     * @since 1.0.0
     */
    public static function get(string $metric): ?array
    {
        return static::all()[$metric] ?? null;
    }

    /**
     * Normalise a configured metric list into valid, known metric keys.
     *
     * The value may arrive either as an array (how the checkboxes field stores
     * it) or as a comma separated string (the form default, and what a manually
     * edited configuration may contain). Handling only one of the two would
     * silently disable the entire measurement, so both are accepted here — in
     * one place, for every consumer.
     *
     * @param   mixed  $metrics  Raw configuration value
     *
     * @return  string[]  Known metric keys, duplicates removed
     *
     * @since 1.0.0
     */
    public static function normalize(mixed $metrics): array
    {
        if (\is_string($metrics)) {
            $metrics = explode(',', $metrics);
        }

        if (!\is_array($metrics)) {
            return [];
        }

        $keys = array_map(static fn($metric): string => trim((string) $metric), $metrics);
        $keys = array_filter($keys, static fn(string $metric): bool => static::get($metric) !== null);

        return array_values(array_unique($keys));
    }

    /**
     * Check whether a metric/bucket combination is known.
     *
     * Used by the collect endpoint: anything not defined here is discarded, so
     * the counter table can only ever contain values this registry produced.
     *
     * @param   string  $metric  Metric key
     * @param   string  $bucket  Bucket key
     *
     * @return  bool
     *
     * @since 1.0.0
     */
    public static function isValid(string $metric, string $bucket): bool
    {
        return isset(static::all()[$metric]['buckets'][$bucket]);
    }

    /**
     * Human readable label for a metric key.
     *
     * @param   string  $metric  Metric key
     *
     * @return  string  The label, or the raw key for metrics no longer defined
     *
     * @since 1.0.0
     */
    public static function metricLabel(string $metric): string
    {
        return static::all()[$metric]['label'] ?? $metric;
    }

    /**
     * Human readable label for a bucket key.
     *
     * Pixel ranges are rendered from the key itself so that adding a bucket
     * never requires a second translation table.
     *
     * @param   string  $metric  Metric key
     * @param   string  $bucket  Bucket key
     *
     * @return  string
     *
     * @since 1.0.0
     */
    public static function bucketLabel(string $metric, string $bucket): string
    {
        // A cross tabulation carries one key per axis; each part is labelled by
        // its own metric, so a matrix never needs its own translation table.
        $axes = static::all()[$metric]['axes'] ?? null;

        if ($axes !== null && str_contains($bucket, '_')) {
            [$rowKey, $columnKey] = explode('_', $bucket, 2);

            return static::bucketLabel($axes[0], $rowKey) . ' · ' . static::bucketLabel($axes[1], $columnKey);
        }

        $unit = \in_array($metric, ['width', 'height'], true) ? ' px' : '';

        if (str_starts_with($bucket, 'lt')) {
            return 'unter ' . substr($bucket, 2) . $unit;
        }

        if (str_starts_with($bucket, 'gte')) {
            return substr($bucket, 3) . $unit . ' und mehr';
        }

        if (preg_match('/^(\d+)-(\d+)$/', $bucket, $m)) {
            return $m[1] . '–' . $m[2] . $unit;
        }

        return match ($bucket) {
            'coarse'       => 'grob (Touch)',
            'fine'         => 'fein (Maus/Stift)',
            'none'         => 'keines',
            'hover'        => 'ja',
            'portrait'     => 'Hochformat',
            'landscape'    => 'Querformat',
            'dark'         => 'dunkel',
            'light'        => 'hell',
            'reduce'       => 'reduziert',
            'no-preference' => 'keine Angabe',
            'more'         => 'erhöht',
            'less'         => 'verringert',
            'custom'       => 'benutzerdefiniert',
            'active'       => 'aktiv',
            'enabled'      => 'aktiv',
            'initial-only' => 'nur beim Laden',
            '1_5x'         => '1,5×',
            '1x'           => '1×',
            '2x'           => '2×',
            'gte3x'        => '3× und mehr',
            default        => $bucket,
        };
    }
}
