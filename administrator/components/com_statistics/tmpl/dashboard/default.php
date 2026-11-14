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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Weltspiegel\Component\Statistics\Administrator\Model\DashboardModel;
use Weltspiegel\Component\Statistics\Administrator\View\Dashboard\HtmlView;

/** @var HtmlView $this */

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

    <?php
    // Page views come first: that is the question asked most. The tab last used
    // is remembered, so choosing the period does not throw anyone back.
    echo HTMLHelper::_('uitab.startTabSet', 'wsStatsTabs', ['active' => 'pageviews', 'recall' => true, 'breakpoint' => 768]);

    echo HTMLHelper::_('uitab.addTab', 'wsStatsTabs', 'pageviews', 'Seitenaufrufe');
    echo $this->loadTemplate('pageviews');
    echo HTMLHelper::_('uitab.endTab');

    echo HTMLHelper::_('uitab.addTab', 'wsStatsTabs', 'devices', 'Geräte & Anzeige');
    echo $this->loadTemplate('devices');
    echo HTMLHelper::_('uitab.endTab');

    echo HTMLHelper::_('uitab.endTabSet');
    ?>

    <div class="card">
        <div class="card-body">
            <h3 class="h6">Zur Einordnung</h3>
            <ul class="mb-0 small">
                <li>
                    <strong>Geräte &amp; Anzeige</strong> wird ausschließlich über CSS gemessen: Der Browser
                    lädt je nach Bildschirm ein anderes unsichtbares Bild. Es werden
                    <strong>keine Daten aus dem Gerät ausgelesen</strong>.
                </li>
                <li>
                    <strong>Seitenaufrufe</strong> zählt der Server aus dem, was der Browser mit jeder
                    Seitenanfrage ohnehin mitschickt: welche Seite, und von wo der Besucher kam. Die
                    Herkunftsadresse wird dabei nur in eine von fünf Arten einsortiert und nicht
                    gespeichert. Es gibt <strong>keine Besucher</strong>, nur Aufrufe — ohne Kennung lässt
                    sich nicht sagen, ob zwei Aufrufe von derselben Person stammen.
                </li>
                <li>
                    Beides ohne Cookies, ohne JavaScript und ohne IP-Adressen. Gespeichert wird nur, wie
                    oft etwas pro Tag vorkam — es gibt keine Einzelaufrufe und damit nichts, was sich einer
                    Person zuordnen ließe.
                </li>
                <li>
                    Ein „Messpunkt“ entspricht einem Seitenaufruf, bei dem der Browser CSS ausgeführt
                    hat. Gezählte „Aufrufe“ sind Seitenaufrufe, die sich als Navigation eines Browsers
                    ausweisen. Suchmaschinen-Roboter tauchen in beiden Fällen nicht auf; sehr alte
                    Browser (vor 2023) fehlen bei den Seitenaufrufen.
                </li>
                <li>
                    Neuladen und „Zurück“ zählen als Aufruf. Die Zahlen eignen sich für Vergleiche und
                    Größenordnungen, nicht für Einzelfälle.
                </li>
                <li>
                    Sehr kleine Werte sind bei den Geräte-Messpunkten mit Vorsicht zu lesen: Programme,
                    die stur alle im Stylesheet gefundenen Adressen abrufen, hinterlassen in <em>jedem</em>
                    Wert eine kleine Grundzahl. Einstellige Werte bedeuten daher meist „praktisch
                    niemand“, nicht „ein paar Besucher“.
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
