<?php

/**
 * @package     Weltspiegel\Component\Statistics\Administrator\Helper
 *
 * @copyright   Weltspiegel Cottbus
 * @license     MIT; see LICENSE file
 */

namespace Weltspiegel\Component\Statistics\Administrator\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Read and write access to the aggregated counter table.
 *
 * The table holds nothing but (date, metric, bucket, hits). There is no event
 * log, no identifier, no IP address and no time of day — aggregation happens on
 * write, so there is never any raw data that could be traced back to a visitor.
 *
 * @since 1.0.0
 */
abstract class CounterHelper
{
    /**
     * Increment the counter for one metric/bucket on today's date.
     *
     * Uses INSERT ... ON DUPLICATE KEY UPDATE so the very first hit of a day
     * creates the row and every later one is a single atomic increment.
     *
     * @param   string  $metric  Metric key (must be validated beforehand)
     * @param   string  $bucket  Bucket key (must be validated beforehand)
     *
     * @return  void
     *
     * @since 1.0.0
     */
    public static function increment(string $metric, string $bucket): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $sql = 'INSERT INTO ' . $db->quoteName('#__ws_stats_counter')
            . ' (' . $db->quoteName('stat_date') . ', ' . $db->quoteName('metric') . ', '
            . $db->quoteName('bucket') . ', ' . $db->quoteName('hits') . ')'
            . ' VALUES (' . $db->quote(date('Y-m-d')) . ', ' . $db->quote($metric) . ', '
            . $db->quote($bucket) . ', 1)'
            . ' ON DUPLICATE KEY UPDATE ' . $db->quoteName('hits') . ' = ' . $db->quoteName('hits') . ' + 1';

        $db->setQuery($sql)->execute();
    }

    /**
     * Aggregate the stored counters over a period.
     *
     * @param   int  $days  Number of days back from today; 0 means everything
     *
     * @return  array  metric => [bucket => hits], ordered by the registry
     *
     * @since 1.0.0
     */
    public static function getTotals(int $days = 30): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery();

        $query->select([$db->quoteName('metric'), $db->quoteName('bucket'), 'SUM(' . $db->quoteName('hits') . ') AS ' . $db->quoteName('total')])
            ->from($db->quoteName('#__ws_stats_counter'))
            ->group([$db->quoteName('metric'), $db->quoteName('bucket')]);

        if ($days > 0) {
            $from = (new \DateTimeImmutable('today'))->modify('-' . ($days - 1) . ' days')->format('Y-m-d');
            $query->where($db->quoteName('stat_date') . ' >= :from')
                ->bind(':from', $from);
        }

        $rows = $db->setQuery($query)->loadObjectList() ?: [];

        $totals = [];

        foreach ($rows as $row) {
            $totals[$row->metric][$row->bucket] = (int) $row->total;
        }

        // Order buckets the way the registry defines them, so the dashboard
        // shows pixel ranges in ascending order rather than by hit count.
        $ordered = [];

        foreach (MetricRegistry::all() as $metricKey => $definition) {
            if (!isset($totals[$metricKey])) {
                continue;
            }

            foreach (array_keys($definition['buckets']) as $bucketKey) {
                if (isset($totals[$metricKey][$bucketKey])) {
                    $ordered[$metricKey][$bucketKey] = $totals[$metricKey][$bucketKey];
                }
            }

            // Buckets from an older registry version that no longer exist are
            // appended rather than dropped — the data was still measured.
            foreach ($totals[$metricKey] as $bucketKey => $hits) {
                if (!isset($ordered[$metricKey][$bucketKey])) {
                    $ordered[$metricKey][$bucketKey] = $hits;
                }
            }
        }

        // Same for metrics that vanished from the registry.
        foreach ($totals as $metricKey => $buckets) {
            if (!isset($ordered[$metricKey])) {
                $ordered[$metricKey] = $buckets;
            }
        }

        return $ordered;
    }

    /**
     * Date of the earliest stored measurement.
     *
     * @return  string|null  Y-m-d, or null when nothing has been collected yet
     *
     * @since 1.0.0
     */
    public static function getFirstDay(): ?string
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
            ->select('MIN(' . $db->quoteName('stat_date') . ')')
            ->from($db->quoteName('#__ws_stats_counter'));

        return $db->setQuery($query)->loadResult() ?: null;
    }

    /**
     * Delete measurements older than the retention period.
     *
     * Called when the dashboard is opened, which keeps the collect endpoint
     * free of any maintenance work.
     *
     * @param   int  $retentionDays  Days to keep; 0 disables pruning
     *
     * @return  int  Number of deleted rows
     *
     * @since 1.0.0
     */
    public static function prune(int $retentionDays): int
    {
        if ($retentionDays <= 0) {
            return 0;
        }

        $db     = Factory::getContainer()->get(DatabaseInterface::class);
        $cutoff = (new \DateTimeImmutable('today'))->modify('-' . $retentionDays . ' days')->format('Y-m-d');

        $query = $db->createQuery()
            ->delete($db->quoteName('#__ws_stats_counter'))
            ->where($db->quoteName('stat_date') . ' < :cutoff')
            ->bind(':cutoff', $cutoff);

        $db->setQuery($query)->execute();

        return $db->getAffectedRows();
    }
}
