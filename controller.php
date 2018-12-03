<?php

namespace Concrete\Package\CloudfrontProxy;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Foundation\Psr4ClassLoader;
use Concrete\Core\Package\Package;
use Concrete5\Cloudfront\CloudfrontServiceProvider;

class Controller extends Package
{

    protected $appVersionRequired = '8.2.0';
    protected $pkgVersion = '1.0.0';
    protected $pkgHandle = 'cloudfront_proxy';
    protected $pkgName = 'CloudFront IP Proxy';
    protected $pkgDescription = 'A package that configures your concrete5 site to work with cloudfront';

    public function on_start()
    {
        // Make sure that we are registered to autoload
        $this->forceAutoload();

        // Add our service provider
        $provider = new CloudfrontServiceProvider($this->app);
        $provider->register();
    }

    /**
     * In the event that composer hasn't been included, register our own classloader
     */
    private function forceAutoload()
    {
        // If we're not included with composer, add our autoloader manually
        if (!class_exists(CloudfrontServiceProvider::class)) {
            $autoload = new Psr4ClassLoader();
            $autoload->addPrefix('Concrete5\\Cloudfront', __DIR__ . '/src');
            $autoload->register();
        }
    }
}
