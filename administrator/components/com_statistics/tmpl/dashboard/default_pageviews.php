<?php

/**
 * Page views tab: which pages are viewed, where from, and where people go next.
 *
 * @package     Weltspiegel\Component\Statistics\Administrator
 *
 * @copyright   Weltspiegel Cottbus
 * @license     MIT; see LICENSE file
 */

\defined('_JEXEC') or die;

use Weltspiegel\Component\Statistics\Administrator\View\Dashboard\HtmlView;

/** @var HtmlView $this */

?>
<?php if (!$this->pageViewCountingEnabled) : ?>
    <div class="alert alert-warning">
        <h4 class="alert-heading">Seitenaufrufe werden nicht gezählt</h4>
        <p class="mb-0">
            In den Einstellungen des Plugins <strong>System – Weltspiegel Statistik</strong> ist
            „Seitenaufrufe zählen“ ausgeschaltet. Bereits gezählte Aufrufe bleiben erhalten.
        </p>
    </div>
<?php endif; ?>

<?php if ($this->pageViews['total'] === 0) : ?>

    <div class="alert alert-info">
        <h4 class="alert-heading">Noch keine Seitenaufrufe</h4>
        <p>
            Für den gewählten Zeitraum liegen keine Seitenaufrufe vor.
            <?php if ($this->collectorEnabled && $this->pageViewCountingEnabled) : ?>
                Gezählt wird, seit das Plugin in Version 1.1 oder neuer läuft; sobald die Seite
                besucht wird, erscheinen hier Zahlen.
            <?php endif; ?>
        </p>
        <p class="mb-0">
            Gezählt werden nur Aufrufe, die sich als Navigation eines Browsers ausweisen.
            Suchmaschinen-Roboter, Skripte und sehr alte Browser tauchen deshalb nicht auf.
        </p>
    </div>

<?php else : ?>

    <div class="row mb-4">
        <?php foreach ($this->pageViews['facts'] as $fact) : ?>
            <div class="col-12 col-md-4 mb-3 mb-md-0">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="small text-secondary"><?= $this->escape($fact['label']) ?></div>
                        <div class="fs-2 fw-bold lh-1 my-2"><?= $this->escape($fact['value']) ?></div>
                        <div class="small text-secondary"><?= $this->escape($fact['note']) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row">
        <?php foreach ($this->pageViews['panels'] as $panel) : ?>
            <?php
            $this->panel = $panel;
            echo $this->loadTemplate('panel');
            ?>
        <?php endforeach; ?>
    </div>

<?php endif; ?>
