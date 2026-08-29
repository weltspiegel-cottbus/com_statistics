<?php

/**
 * @package     Weltspiegel\Component\Statistics\Site\Controller
 *
 * @copyright   Weltspiegel Cottbus
 * @license     MIT; see LICENSE file
 */

namespace Weltspiegel\Component\Statistics\Site\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Default site controller.
 *
 * The component has no public views — it exists on the frontend solely to serve
 * the collect endpoint, so any other request is answered with a 404.
 *
 * @since 1.0.0
 */
class DisplayController extends BaseController
{
    /**
     * Refuse to display anything.
     *
     * @param   bool   $cachable   Ignored
     * @param   array  $urlparams  Ignored
     *
     * @return  void
     *
     * @throws  \Exception
     *
     * @since 1.0.0
     */
    public function display($cachable = false, $urlparams = []): void
    {
        throw new \Exception('Not Found', 404);
    }
}
