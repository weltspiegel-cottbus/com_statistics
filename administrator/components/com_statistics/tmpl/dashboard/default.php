<?php

/**
 * Statistics dashboard
 *
 * @package     Weltspiegel\Component\Statistics\Administrator
 *
 * @copyright   Weltspiegel Cottbus
 * @license     MIT; see LICENSE file
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Weltspiegel\Component\Statistics\Administrator\Helper\MetricRegistry;
use Weltspiegel\Component\Statistics\Administrator\Model\DashboardModel;
use Weltspiegel\Component\Statistics\Administrator\View\Dashboard\HtmlView;

/** @var HtmlView $this */

$measuredKeys = array_column($this->panels, 'key');
$pendingKeys  = array_diff($this->enabledMetrics, $measuredKeys);

?>
<div id="j-main-container" class="j-main-container">

    <?php if (!$this->collectorEnabled) : ?>
        <div class="alert alert-warning">
            <h4 class="alert-heading">Es wird gerade nichts gemessen</h4>
            <p class="mb-0">
                Das Plugin <strong>System – Weltspiegel Statistik</strong> ist nicht aktiviert.
                Ohne dieses Plugin werden keine Messpunkte in die Seiten eingebettet und es
                kommen keine neuen Daten hinzu. Bereits erfasste Daten bleiben erhalten.
            </p>
        </div>
    <?php endif; ?>

    <form action="<?= Route::_('index.php?option=com_statistics&view=dashboard') ?>" method="get" class="mb-4">
        <input type="hidden" name="option" value="com_statistics">
        <input type="hidden" name="view" value="dashboard">

        <div class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label" for="days">Zeitraum</label>
                <select class="form-select" name="days" id="days">
                    <?php foreach (DashboardModel::PERIODS as $value => $label) : ?>
                        <option value="<?= (int) $value ?>"<?= $value === $this->days ? ' selected' : '' ?>>
                            <?= $this->escape($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Anzeigen</button>
            </div>
        </div>
    </form>

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
                <?php // A matrix needs the full width; bar lists sit two per row. ?>
                <div class="<?= ($panel['type'] ?? 'bars') === 'matrix' ? 'col-12' : 'col-12 col-xl-6' ?>">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2 class="h5 mb-0"><?= $this->escape($panel['label']) ?></h2>
                            <span class="badge bg-secondary">
                                <?= number_format($panel['total'], 0, ',', '.') ?> Messpunkte
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if ($panel['hint'] !== '') : ?>
                                <p class="small text-secondary"><?= $this->escape($panel['hint']) ?></p>
                            <?php endif; ?>

                            <?php if (($panel['type'] ?? 'bars') === 'matrix') : ?>

                                <p class="small text-secondary">
                                    Die Prozentwerte beziehen sich auf die jeweilige <strong>Zeile</strong>:
                                    Sie zeigen, wie sich die Aufrufe dieser Breite auf die Eingabegeräte verteilen.
                                </p>

                                <div class="table-responsive">
                                    <table class="table table-sm mb-0 align-middle">
                                        <caption class="visually-hidden">
                                            Kreuztabelle: <?= $this->escape($panel['label']) ?>
                                        </caption>
                                        <thead>
                                        <tr>
                                            <th scope="col"><?= $this->escape($panel['rowLabel']) ?></th>
                                            <?php foreach ($panel['columns'] as $column) : ?>
                                                <th scope="col" class="text-end"><?= $this->escape($column['label']) ?></th>
                                            <?php endforeach; ?>
                                            <th scope="col" class="text-end">Summe</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($panel['rows'] as $row) : ?>
                                            <tr>
                                                <th scope="row" class="fw-normal"><?= $this->escape($row['label']) ?></th>
                                                <?php foreach ($row['cells'] as $cell) : ?>
                                                    <?php // Tint is decoration only — every value is also written out. ?>
                                                    <td class="text-end"
                                                        style="background: rgba(var(--bs-primary-rgb, 13, 110, 253), <?= number_format($cell['percent'] / 100 * 0.35, 3, '.', '') ?>)">
                                                        <span class="d-block"><?= number_format($cell['percent'], 1, ',', '.') ?> %</span>
                                                        <span class="d-block small text-secondary">
                                                            <?= number_format($cell['hits'], 0, ',', '.') ?>
                                                        </span>
                                                    </td>
                                                <?php endforeach; ?>
                                                <td class="text-end fw-bold"><?= number_format($row['total'], 0, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <th scope="row">Summe</th>
                                            <?php foreach ($panel['columns'] as $column) : ?>
                                                <td class="text-end"><?= number_format($column['total'], 0, ',', '.') ?></td>
                                            <?php endforeach; ?>
                                            <td class="text-end fw-bold"><?= number_format($panel['total'], 0, ',', '.') ?></td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>

                            <?php else : ?>

                            <table class="table table-sm mb-0">
                                <caption class="visually-hidden">
                                    Verteilung: <?= $this->escape($panel['label']) ?>
                                </caption>
                                <thead>
                                <tr>
                                    <th scope="col">Wert</th>
                                    <th scope="col" class="w-50">Anteil</th>
                                    <th scope="col" class="text-end">Aufrufe</th>
                                    <th scope="col" class="text-end">%</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($panel['rows'] as $row) : ?>
                                    <tr>
                                        <th scope="row" class="fw-normal"><?= $this->escape($row['label']) ?></th>
                                        <td>
                                            <div class="progress" role="progressbar"
                                                 aria-label="Anteil <?= $this->escape($row['label']) ?>"
                                                 aria-valuenow="<?= (float) $row['percent'] ?>"
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <div class="progress-bar"
                                                     style="width: <?= (float) $row['percent'] ?>%"></div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <?= number_format($row['hits'], 0, ',', '.') ?>
                                        </td>
                                        <td class="text-end">
                                            <?= number_format($row['percent'], 1, ',', '.') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

    <?php if (!empty($pendingKeys)) : ?>
        <div class="alert alert-info">
            Aktiviert, aber im gewählten Zeitraum noch ohne Messwerte:
            <?= $this->escape(implode(', ', array_map([MetricRegistry::class, 'metricLabel'], $pendingKeys))) ?>.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <h3 class="h6">Zur Einordnung</h3>
            <ul class="mb-0 small">
                <li>
                    Gemessen wird ausschließlich über CSS: Der Browser lädt je nach Bildschirm ein
                    anderes unsichtbares Bild. Es werden <strong>keine Daten aus dem Gerät ausgelesen</strong>,
                    keine Cookies gesetzt und keine IP-Adressen gespeichert.
                </li>
                <li>
                    Gespeichert wird nur, wie oft ein Wert pro Tag vorkam — es gibt keine Einzelaufrufe
                    und damit nichts, was sich einer Person zuordnen ließe.
                </li>
                <li>
                    Ein „Messpunkt" entspricht einem Seitenaufruf, bei dem der Browser CSS ausgeführt
                    hat. Suchmaschinen-Roboter tauchen dadurch in der Regel nicht auf.
                </li>
                <?php if ($this->firstDay !== null) : ?>
                    <li>
                        Messung läuft seit <?= $this->escape((new DateTime($this->firstDay))->format('d.m.Y')) ?>.
                    </li>
                <?php endif; ?>
                <li>
                    <?php if ($this->retentionDays > 0) : ?>
                        Daten älter als <?= (int) $this->retentionDays ?> Tage werden automatisch gelöscht
                        (einstellbar über „Optionen").
                    <?php else : ?>
                        Automatisches Löschen alter Daten ist deaktiviert (einstellbar über „Optionen").
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>

</div>
