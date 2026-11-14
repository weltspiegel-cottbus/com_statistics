<?php

/**
 * @package     Weltspiegel\Component\Statistics\Administrator\Model
 *
 * @copyright   Weltspiegel Cottbus
 * @license     MIT; see LICENSE file
 */

namespace Weltspiegel\Component\Statistics\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Weltspiegel\Component\Statistics\Administrator\Helper\CounterHelper;
use Weltspiegel\Component\Statistics\Administrator\Helper\MetricRegistry;
use Weltspiegel\Component\Statistics\Administrator\Helper\PageViewRegistry;

/**
 * Model behind the statistics dashboard.
 *
 * Turns the raw counter rows into ready-to-render panels — one per metric, with
 * per-bucket shares — and exposes the few facts the view needs to explain the
 * numbers (collection period, whether the collector is running at all).
 *
 * @since 1.0.0
 */
class DashboardModel extends BaseDatabaseModel
{
    /**
     * Selectable periods for the dashboard filter.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     */
    public const PERIODS = [
        7   => 'Letzte 7 Tage',
        30  => 'Letzte 30 Tage',
        90  => 'Letzte 90 Tage',
        365 => 'Letztes Jahr',
        0   => 'Gesamter Zeitraum',
    ];

    /**
     * Selected period in days (0 = everything).
     *
     * @return  int
     *
     * @since 1.0.0
     */
    public function getDays(): int
    {
        $days = Factory::getApplication()->getInput()->getInt('days', 30);

        return \array_key_exists($days, self::PERIODS) ? $days : 30;
    }

    /**
     * Build one panel per metric that has data in the selected period.
     *
     * @return  array  List of ['key', 'label', 'hint', 'total', 'rows' => [['label', 'hits', 'percent']]]
     *
     * @since 1.0.0
     */
    public function getPanels(): array
    {
        $totals = CounterHelper::getTotals($this->getDays());
        $panels = [];

        foreach ($totals as $metric => $buckets) {
            $total = array_sum($buckets);

            // The page view counters have their own tab and their own labels.
            if ($total === 0 || PageViewRegistry::isPageMetric($metric)) {
                continue;
            }

            $definition = MetricRegistry::get($metric);

            // Cross tabulations carry two axes and are rendered as a matrix
            // instead of a bar list — thirty bars would be unreadable, and the
            // interesting figure is the split within each row anyway.
            if (!empty($definition['axes'])) {
                $panels[] = $this->buildMatrixPanel($metric, $definition, $buckets, $total);

                continue;
            }

            $rows = [];

            foreach ($buckets as $bucket => $hits) {
                $rows[] = [
                    'label'   => MetricRegistry::bucketLabel($metric, $bucket),
                    'hits'    => $hits,
                    'percent' => round($hits / $total * 100, 1),
                ];
            }

            $panels[] = [
                'key'   => $metric,
                'label' => MetricRegistry::metricLabel($metric),
                'hint'  => $definition['hint'] ?? '',
                'total' => $total,
                'unit'  => 'Messpunkte',
                'type'  => 'bars',
                'rows'  => $rows,
            ];
        }

        return $panels;
    }

    /**
     * Turn the flat counters of a registry cross tabulation into a matrix panel.
     *
     * Percentages are relative to the *row*, because that is the question a
     * cross tabulation answers: given this viewport width, how do the input
     * devices split? Column totals are carried along so the matrix can be
     * checked against the single-metric panels.
     *
     * @param   string  $metric      Metric key
     * @param   array   $definition  Registry definition (must carry 'axes')
     * @param   array   $buckets     bucketKey => hits
     * @param   int     $total       Total hits of the metric
     *
     * @return  array
     *
     * @since 1.0.0
     */
    private function buildMatrixPanel(string $metric, array $definition, array $buckets, int $total): array
    {
        [$rowMetric, $columnMetric] = $definition['axes'];

        $labelled = static function (string $axis): array {
            $labels = [];

            foreach (array_keys(MetricRegistry::get($axis)['buckets'] ?? []) as $key) {
                $labels[$key] = MetricRegistry::bucketLabel($axis, $key);
            }

            return $labels;
        };

        return $this->buildMatrix(
            $metric,
            MetricRegistry::metricLabel($metric),
            $definition['hint'] ?? '',
            'Die Prozentwerte beziehen sich auf die jeweilige Zeile: Sie zeigen, wie sich die Aufrufe '
                . 'dieser Zeile auf die Spalten verteilen.',
            'Messpunkte',
            MetricRegistry::metricLabel($rowMetric),
            $labelled($rowMetric),
            $labelled($columnMetric),
            $buckets,
            $total
        );
    }

    /**
     * Build a matrix panel from composite counters.
     *
     * Splits the stored keys (`row_column`) back into their two axes, keeps the
     * order the caller gives for rows and columns, and drops what nobody ever
     * hit — an always-empty column would only add noise.
     *
     * @param   string  $key        Panel key
     * @param   string  $label      Panel title
     * @param   string  $hint       Explanation under the title
     * @param   string  $note       How to read the percentages
     * @param   string  $unit       What the badge counts ("Messpunkte", "Aufrufe")
     * @param   string  $rowLabel   Heading of the row axis
     * @param   array   $rowDefs    rowKey => label, in display order
     * @param   array   $columnDefs columnKey => label, in display order
     * @param   array   $buckets    "row_column" => hits
     * @param   int     $total      Total hits
     *
     * @return  array
     *
     * @since 1.1.0
     */
    private function buildMatrix(
        string $key,
        string $label,
        string $hint,
        string $note,
        string $unit,
        string $rowLabel,
        array $rowDefs,
        array $columnDefs,
        array $buckets,
        int $total
    ): array {
        $matrix = [];

        foreach ($buckets as $bucket => $hits) {
            if (!str_contains($bucket, '_')) {
                continue;
            }

            [$rowKey, $columnKey] = explode('_', $bucket, 2);

            $matrix[$rowKey][$columnKey] = ($matrix[$rowKey][$columnKey] ?? 0) + $hits;
        }

        $columnKeys = [];

        foreach (array_keys($columnDefs) as $columnKey) {
            foreach ($matrix as $cells) {
                if (!empty($cells[$columnKey])) {
                    $columnKeys[] = $columnKey;

                    break;
                }
            }
        }

        $columnTotals = array_fill_keys($columnKeys, 0);
        $rows         = [];

        foreach ($rowDefs as $rowKey => $rowText) {
            if (empty($matrix[$rowKey])) {
                continue;
            }

            $rowTotal = array_sum($matrix[$rowKey]);
            $cells    = [];

            foreach ($columnKeys as $columnKey) {
                $hits                     = $matrix[$rowKey][$columnKey] ?? 0;
                $columnTotals[$columnKey] += $hits;

                $cells[] = [
                    'hits'    => $hits,
                    'percent' => $rowTotal > 0 ? round($hits / $rowTotal * 100, 1) : 0.0,
                ];
            }

            $rows[] = [
                'label' => $rowText,
                'cells' => $cells,
                'total' => $rowTotal,
            ];
        }

        $columns = [];

        foreach ($columnKeys as $columnKey) {
            $columns[] = [
                'label' => $columnDefs[$columnKey],
                'total' => $columnTotals[$columnKey],
            ];
        }

        return [
            'key'      => $key,
            'label'    => $label,
            'hint'     => $hint,
            'note'     => $note,
            'total'    => $total,
            'unit'     => $unit,
            'type'     => 'matrix',
            'rowLabel' => $rowLabel,
            'columns'  => $columns,
            'rows'     => $rows,
        ];
    }

    /**
     * The page view statistics: key figures and panels.
     *
     * @return  array  ['total' => int, 'facts' => [...], 'panels' => [...]]; total 0 when there is no data
     *
     * @since 1.1.0
     */
    public function getPageViews(): array
    {
        $totals = CounterHelper::getTotals($this->getDays());
        $entry  = $totals[PageViewRegistry::METRIC_ENTRY] ?? [];
        $flow   = $totals[PageViewRegistry::METRIC_FLOW] ?? [];

        $byType   = array_fill_keys(array_keys(PageViewRegistry::TYPES), 0);
        $bySource = array_fill_keys(array_keys(PageViewRegistry::SOURCES), 0);
        $entering = array_fill_keys(array_keys(PageViewRegistry::TYPES), 0);
        $valid    = [];

        foreach ($entry as $bucket => $hits) {
            [$type, $source] = array_pad(explode('_', $bucket, 2), 2, '');

            // Buckets of a vocabulary that has since changed stay out of the sums,
            // so the matrix and the single panels always agree.
            if (!isset($byType[$type], $bySource[$source])) {
                continue;
            }

            $valid[$bucket]    = $hits;
            $byType[$type]    += $hits;
            $bySource[$source] += $hits;

            if ($source !== 'internal') {
                $entering[$type] += $hits;
            }
        }

        $total = array_sum($byType);

        if ($total === 0) {
            return ['total' => 0, 'facts' => [], 'panels' => []];
        }

        $entries = array_sum($entering);

        $panels = [
            $this->buildBars(
                'entries',
                'Einstiegsseiten',
                'Auf welcher Seite beginnt ein Besuch? Gezählt sind alle Aufrufe, die nicht von einer Seite '
                    . 'dieser Website kamen — Adresse eingegeben, Lesezeichen, Suchmaschine, Facebook, Instagram oder ein Link anderswo.',
                $entering,
                PageViewRegistry::TYPES
            ),
            $this->buildBars(
                'types',
                'Aufrufe je Seitentyp',
                'Alle Seitenaufrufe, gleich woher sie kamen.',
                $byType,
                PageViewRegistry::TYPES
            ),
            $this->buildBars(
                'sources',
                'Woher kommen die Aufrufe?',
                'Direkt heißt: kein Verweis vorhanden — eingetippte Adresse, Lesezeichen oder ein Link aus einer anderen App. '
                    . 'Manche Browser und Programme unterdrücken die Herkunft; das landet hier ebenfalls.',
                $bySource,
                PageViewRegistry::SOURCES
            ),
            $this->buildMatrix(
                'entry',
                'Herkunft je Seitentyp',
                'Wie kommen die Besucher auf die jeweilige Seite?',
                'Die Prozentwerte beziehen sich auf die jeweilige Zeile: Sie zeigen, woher die Aufrufe dieser Seite kamen.',
                'Aufrufe',
                'Seite',
                PageViewRegistry::TYPES,
                PageViewRegistry::SOURCES,
                $valid,
                $total
            ),
        ];

        $flowValid = [];

        foreach ($flow as $bucket => $hits) {
            [$from, $to] = array_pad(explode('_', $bucket, 2), 2, '');

            if (isset(PageViewRegistry::flowOrigins()[$from], PageViewRegistry::TYPES[$to])) {
                $flowValid[$bucket] = $hits;
            }
        }

        if ($flowValid !== []) {
            $panels[] = $this->buildMatrix(
                'flow',
                'Wege durch die Website',
                'Von welcher Seite aus gehen die Besucher auf welche weiter? Nur Wechsel innerhalb der Website.',
                'Die Prozentwerte beziehen sich auf die jeweilige Zeile: Sie zeigen, wohin die Besucher von dieser Seite aus gingen.',
                'Wechsel',
                'Von',
                PageViewRegistry::flowOrigins(),
                PageViewRegistry::TYPES,
                $flowValid,
                array_sum($flowValid)
            );
        }

        $facts = [
            [
                'label' => 'Seitenaufrufe',
                'value' => number_format($total, 0, ',', '.'),
                'note'  => 'im gewählten Zeitraum',
            ],
            [
                'label' => 'Von unserer eigenen Seite',
                'value' => number_format($bySource['internal'] / $total * 100, 1, ',', '.') . ' %',
                'note'  => 'Der Rest sind Einstiege von außen: ' . number_format($entries, 0, ',', '.') . ' Aufrufe.',
            ],
        ];

        if ($byType['programme'] > 0) {
            $facts[] = [
                'label' => 'Film-Detail je 100 Programm-Aufrufe',
                'value' => number_format($byType['movie'] / $byType['programme'] * 100, 0, ',', '.'),
                'note'  => 'Verhältnis von Seitenaufrufen — kein Anteil der Besucher, denn dieselbe Person zählt bei jedem Aufruf neu.',
            ];
        }

        return ['total' => $total, 'facts' => $facts, 'panels' => $panels];
    }

    /**
     * Build a bar list panel from counts keyed like the given labels.
     *
     * @param   string  $key     Panel key
     * @param   string  $label   Title
     * @param   string  $hint    Explanation
     * @param   array   $counts  key => hits
     * @param   array   $labels  key => label, in display order
     *
     * @return  array
     *
     * @since 1.1.0
     */
    private function buildBars(string $key, string $label, string $hint, array $counts, array $labels): array
    {
        $total = array_sum($counts);
        $rows  = [];

        foreach ($labels as $bucket => $text) {
            $hits = $counts[$bucket] ?? 0;

            if ($hits === 0) {
                continue;
            }

            $rows[] = [
                'label'   => $text,
                'hits'    => $hits,
                'percent' => $total > 0 ? round($hits / $total * 100, 1) : 0.0,
            ];
        }

        return [
            'key'   => $key,
            'label' => $label,
            'hint'  => $hint,
            'total' => $total,
            'unit'  => 'Aufrufe',
            'type'  => 'bars',
            'rows'  => $rows,
        ];
    }

    /**
     * Whether the collecting plugin is set to count page views.
     *
     * @return  bool
     *
     * @since 1.1.0
     */
    public function isPageViewCountingEnabled(): bool
    {
        $plugin = PluginHelper::getPlugin('system', 'statistics');

        if (!$plugin) {
            return false;
        }

        return (bool) (new Registry($plugin->params))->get('count_pageviews', 1);
    }

    /**
     * Date of the first stored measurement.
     *
     * @return  string|null
     *
     * @since 1.0.0
     */
    public function getFirstDay(): ?string
    {
        return CounterHelper::getFirstDay();
    }

    /**
     * Whether the collecting system plugin is installed and enabled.
     *
     * Without it no probe is ever rendered, which is the most likely reason for
     * an empty dashboard — so the view says so explicitly.
     *
     * @return  bool
     *
     * @since 1.0.0
     */
    public function isCollectorEnabled(): bool
    {
        return PluginHelper::isEnabled('system', 'statistics');
    }

    /**
     * Metric keys the collector is currently configured to measure.
     *
     * @return  string[]
     *
     * @since 1.0.0
     */
    public function getEnabledMetrics(): array
    {
        $plugin = PluginHelper::getPlugin('system', 'statistics');

        if (!$plugin) {
            return [];
        }

        $params = new Registry($plugin->params);

        return MetricRegistry::normalize($params->get('metrics', MetricRegistry::DEFAULT_METRICS));
    }

    /**
     * Configured retention period in days (0 = keep forever).
     *
     * @return  int
     *
     * @since 1.0.0
     */
    public function getRetentionDays(): int
    {
        return (int) ComponentHelper::getParams('com_statistics')->get('retention_days', 365);
    }

    /**
     * Apply the retention policy.
     *
     * Runs when the dashboard is opened so the collect endpoint stays free of
     * any maintenance work on the hot path.
     *
     * @return  int  Number of deleted rows
     *
     * @since 1.0.0
     */
    public function pruneOldData(): int
    {
        return CounterHelper::prune($this->getRetentionDays());
    }
}
