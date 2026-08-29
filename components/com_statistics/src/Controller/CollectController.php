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
use Weltspiegel\Component\Statistics\Administrator\Helper\CounterHelper;
use Weltspiegel\Component\Statistics\Administrator\Helper\MetricRegistry;

/**
 * Collect endpoint for the CSS probes.
 *
 * The browser requests this URL as a background image whenever one of the
 * generated media queries matches. Nothing is read from the device: the server
 * only learns which of several static resources was requested, exactly like a
 * responsive image served via srcset.
 *
 * @since 1.0.0
 */
class CollectController extends BaseController
{
    /**
     * A 1x1 transparent GIF, returned for every request regardless of outcome.
     *
     * @var string
     *
     * @since 1.0.0
     */
    private const PIXEL = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    /**
     * Record one measurement and return the pixel.
     *
     * Unknown metric/bucket combinations are silently ignored — the response is
     * identical either way, so the endpoint never reveals what it accepts.
     *
     * @return  void
     *
     * @since 1.0.0
     */
    public function track(): void
    {
        $input  = $this->app->getInput();
        $metric = $input->getCmd('m', '');
        $bucket = $input->getCmd('b', '');

        if ($metric !== '' && $bucket !== '' && MetricRegistry::isValid($metric, $bucket)) {
            try {
                CounterHelper::increment($metric, $bucket);
            } catch (\Throwable $e) {
                // Never let a statistics failure surface on the site — the
                // pixel is returned either way.
            }
        }

        $this->sendPixel();
    }

    /**
     * Emit the tracking pixel with caching disabled and terminate.
     *
     * Without no-store the browser would serve the image from its cache and
     * only the very first page view of a visitor would ever be counted.
     *
     * @return  void
     *
     * @since 1.0.0
     */
    private function sendPixel(): void
    {
        $body = base64_decode(self::PIXEL);

        $this->app->setHeader('Content-Type', 'image/gif', true);
        $this->app->setHeader('Content-Length', (string) \strlen($body), true);
        $this->app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $this->app->setHeader('Pragma', 'no-cache', true);
        $this->app->setHeader('X-Content-Type-Options', 'nosniff', true);
        $this->app->sendHeaders();

        echo $body;

        $this->app->close();
    }
}
