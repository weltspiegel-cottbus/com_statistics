<?php

/**
 * @package     Weltspiegel\Component\Statistics\Administrator\Extension
 *
 * @copyright   Weltspiegel Cottbus
 * @license     MIT; see LICENSE file
 */

namespace Weltspiegel\Component\Statistics\Administrator\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\MVCComponent;

/**
 * Component class for com_statistics
 *
 * @since 1.0.0
 */
class StatisticsComponent extends MVCComponent implements RouterServiceInterface
{
    use RouterServiceTrait;
}
