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

            if ($total === 0) {
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
                'type'  => 'bars',
                'rows'  => $rows,
            ];
        }

        return $panels;
    }

    /**
     * Turn the flat counters of a cross tabulation into a matrix panel.
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

        // Split the stored composite keys back into their two axes.
        $matrix = [];

        foreach ($buckets as $bucket => $hits) {
            if (!str_contains($bucket, '_')) {
                continue;
            }

            [$rowKey, $columnKey] = explode('_', $bucket, 2);

            $matrix[$rowKey][$columnKey] = ($matrix[$rowKey][$columnKey] ?? 0) + $hits;
        }

        // Keep the registry's order, but drop columns nobody ever hit — an
        // always-empty "keines" column would only add noise.
        $columnKeys = [];

        foreach (array_keys(MetricRegistry::get($columnMetric)['buckets'] ?? []) as $columnKey) {
            foreach ($matrix as $cells) {
                if (!empty($cells[$columnKey])) {
                    $columnKeys[] = $columnKey;

                    break;
                }
            }
        }

        $columnTotals = array_fill_keys($columnKeys, 0);
        $rows         = [];

        foreach (array_keys(MetricRegistry::get($rowMetric)['buckets'] ?? []) as $rowKey) {
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
                'label' => MetricRegistry::bucketLabel($rowMetric, $rowKey),
                'cells' => $cells,
                'total' => $rowTotal,
            ];
        }

        $columns = [];

        foreach ($columnKeys as $columnKey) {
            $columns[] = [
                'label' => MetricRegistry::bucketLabel($columnMetric, $columnKey),
                'total' => $columnTotals[$columnKey],
            ];
        }

        return [
            'key'      => $metric,
            'label'    => MetricRegistry::metricLabel($metric),
            'hint'     => $definition['hint'] ?? '',
            'total'    => $total,
            'type'     => 'matrix',
            'rowLabel' => MetricRegistry::metricLabel($rowMetric),
            'columns'  => $columns,
            'rows'     => $rows,
        ];
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
