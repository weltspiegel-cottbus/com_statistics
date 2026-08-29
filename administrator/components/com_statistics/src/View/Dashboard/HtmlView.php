<?php

/**
 * @package     Weltspiegel\Component\Statistics\Administrator\View\Dashboard
 *
 * @copyright   Weltspiegel Cottbus
 * @license     MIT; see LICENSE file
 */

namespace Weltspiegel\Component\Statistics\Administrator\View\Dashboard;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Weltspiegel\Component\Statistics\Administrator\Model\DashboardModel;

/**
 * Dashboard view showing the collected visitor statistics.
 *
 * @since 1.0.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * One panel per measured metric
     *
     * @var array
     *
     * @since 1.0.0
     */
    protected array $panels = [];

    /**
     * Selected period in days (0 = everything)
     *
     * @var int
     *
     * @since 1.0.0
     */
    protected int $days = 30;

    /**
     * Date of the first stored measurement
     *
     * @var string|null
     *
     * @since 1.0.0
     */
    protected ?string $firstDay = null;

    /**
     * Whether the collecting plugin is enabled
     *
     * @var bool
     *
     * @since 1.0.0
     */
    protected bool $collectorEnabled = false;

    /**
     * Metric keys currently being collected
     *
     * @var string[]
     *
     * @since 1.0.0
     */
    protected array $enabledMetrics = [];

    /**
     * Configured retention period in days
     *
     * @var int
     *
     * @since 1.0.0
     */
    protected int $retentionDays = 0;

    /**
     * Execute and display a template script.
     *
     * @param   string|null  $tpl  The name of the template file to parse
     *
     * @return  void
     *
     * @throws  \Exception
     *
     * @since 1.0.0
     */
    public function display($tpl = null): void
    {
        /** @var DashboardModel $model */
        $model = $this->getModel();

        // Enforce the retention policy on every dashboard visit — this is the
        // only regularly executed admin request of the component.
        $model->pruneOldData();

        $this->days             = $model->getDays();
        $this->panels           = $model->getPanels();
        $this->firstDay         = $model->getFirstDay();
        $this->collectorEnabled = $model->isCollectorEnabled();
        $this->enabledMetrics   = $model->getEnabledMetrics();
        $this->retentionDays    = $model->getRetentionDays();

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since 1.0.0
     */
    protected function addToolbar(): void
    {
        ToolbarHelper::title('Besucher-Statistik', 'fa fa-chart-bar');
        ToolbarHelper::preferences('com_statistics');
    }
}
