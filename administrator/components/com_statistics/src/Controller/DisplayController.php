<?php

/**
 * @package     Weltspiegel\Component\Statistics\Administrator\Controller
 *
 * @copyright   Weltspiegel Cottbus
 * @license     MIT; see LICENSE file
 */

namespace Weltspiegel\Component\Statistics\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Statistics master display controller.
 *
 * @since 1.0.0
 */
class DisplayController extends BaseController
{
    /**
     * The default view
     *
     * @var string
     *
     * @since 1.0.0
     */
    protected $default_view = 'dashboard';
}
