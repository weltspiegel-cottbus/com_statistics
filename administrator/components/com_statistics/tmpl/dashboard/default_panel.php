<?php

/**
 * One statistics panel: a list of bars or a matrix.
 *
 * Rendered for $this->panel; used by both tabs.
 *
 * @package     Weltspiegel\Component\Statistics\Administrator
 *
 * @copyright   Weltspiegel Cottbus
 * @license     MIT; see LICENSE file
 */

\defined('_JEXEC') or die;

use Weltspiegel\Component\Statistics\Administrator\View\Dashboard\HtmlView;

/** @var HtmlView $this */

$panel    = $this->panel;
$isMatrix = ($panel['type'] ?? 'bars') === 'matrix';

?>
<?php // A matrix needs the full width; bar lists sit two per row. ?>
<div class="<?= $isMatrix ? 'col-12' : 'col-12 col-xl-6' ?>">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0"><?= $this->escape($panel['label']) ?></h2>
            <span class="badge bg-secondary">
                <?= number_format($panel['total'], 0, ',', '.') ?> <?= $this->escape($panel['unit'] ?? 'Messpunkte') ?>
            </span>
        </div>
        <div class="card-body">
            <?php if ($panel['hint'] !== '') : ?>
                <p class="small text-secondary"><?= $this->escape($panel['hint']) ?></p>
            <?php endif; ?>

            <?php if ($isMatrix) : ?>

                <?php if (!empty($panel['note'])) : ?>
                    <p class="small text-secondary"><?= $this->escape($panel['note']) ?></p>
                <?php endif; ?>

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
