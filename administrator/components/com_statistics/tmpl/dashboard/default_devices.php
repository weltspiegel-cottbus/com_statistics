<?php

/**
 * Devices tab: how the site is displayed (viewport, input device, preferences).
 *
 * @package     Weltspiegel\Component\Statistics\Administrator
 *
 * @copyright   Weltspiegel Cottbus
 * @license     MIT; see LICENSE file
 */

\defined('_JEXEC') or die;

use Weltspiegel\Component\Statistics\Administrator\Helper\MetricRegistry;
use Weltspiegel\Component\Statistics\Administrator\View\Dashboard\HtmlView;

/** @var HtmlView $this */

$measuredKeys = array_column($this->panels, 'key');
$pendingKeys  = array_diff($this->enabledMetrics, $measuredKeys);

?>
<?php if (empty($this->panels)) : ?>
    <div class="alert alert-info">
        <h4 class="alert-heading">Noch keine Daten</h4>
        <p>
            Für den gewählten Zeitraum liegen keine Messwerte vor.
            <?php if ($this->collectorEnabled) : ?>
                Die Messung läuft — sobald die Seite besucht wird, erscheinen hier Zahlen.
                Gezählt werden nur Aufrufe der öffentlichen Website; je nach Einstellung des
                Plugins bleiben Aufrufe angemeldeter Redakteure außen vor.
            <?php endif; ?>
        </p>
        <p class="mb-0">
            Falls du gerade erst installiert hast: Wähle oben einen größeren Zeitraum,
            falls die Messung schon länger läuft.
        </p>
    </div>
<?php else : ?>

    <div class="row">
        <?php foreach ($this->panels as $panel) : ?>
            <?php
            $this->panel = $panel;
            echo $this->loadTemplate('panel');
            ?>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php if (!empty($pendingKeys)) : ?>
    <div class="alert alert-info">
        Aktiviert, aber im gewählten Zeitraum noch ohne Messwerte:
        <?= $this->escape(implode(', ', array_map([MetricRegistry::class, 'metricLabel'], $pendingKeys))) ?>.
    </div>
<?php endif; ?>
