<?php

namespace Concrete5\Cloudfront;

use Concrete\Core\Console\Application;
use Concrete\Core\Foundation\Service\Provider;
use Symfony\Component\HttpFoundation\Request;

final class CloudfrontServiceProvider extends Provider
{

    /**
     * Registers the services provided by this provider.
     */
    public function register()
    {
        $this->registerProxy();
        $this->registerCommands();
    }

    /**
     * Register known Cloudfront proxy IPs
     */
    private function registerProxy()
    {
        $config = $this->app->make('config');
        $ips = (array) $config['cloudfront_proxy::ips.user'];

        Request::setTrustedProxies($ips);
    }

    /**
     * Register the commands
     * `concrete5 cf:ip:update`
     * `concrete5 cf:ip:list`
     */
    private function registerCommands()
    {
        $app = $this->app;
        $this->app->extend('console', function (Application $console) use ($app) {
            $console->addCommands([
                $app->make(CloudfrontUpdateCommand::class),
                $app->make(CloudfrontListCommand::class)
            ]);

            return $console;
        });
    }
}
