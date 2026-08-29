<?php

/**
 * @package     Weltspiegel\Component\Statistics
 *
 * @copyright   Weltspiegel Cottbus
 * @license     MIT; see LICENSE file
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Weltspiegel\Component\Statistics\Administrator\Extension\StatisticsComponent;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container
     *
     * @return  void
     *
     * @since 1.0.0
     */
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\Weltspiegel\\Component\\Statistics'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Weltspiegel\\Component\\Statistics'));
        $container->registerServiceProvider(new RouterFactory('\\Weltspiegel\\Component\\Statistics'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new StatisticsComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                return $component;
            }
        );
    }
};
