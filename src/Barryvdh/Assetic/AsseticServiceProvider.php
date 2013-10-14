<?php namespace Barryvdh\Assetic;

use Illuminate\Support\ServiceProvider;

use Assetic\AssetManager;
use Assetic\FilterManager;
use Assetic\AssetWriter;
use Assetic\Asset\AssetCache;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\LazyAssetManager;
use Assetic\Cache\FilesystemCache;
use Assetic\Extension\Twig\TwigFormulaLoader;
use Assetic\Extension\Twig\AsseticExtension;
use Assetic\Factory\Worker\CacheBustingWorker;

/**
 * Class AsseticServiceProvider
 * @package Barryvdh\Assetic
 *
 * Based on mheap/silex-assetic:
 * https://github.com/mheap/Silex-Assetic/blob/master/src/SilexAssetic/AsseticServiceProvider.php
 */
class AsseticServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->package('barryvdh/laravel-assetic');

        $app = $this->app;

        /**
         * Asset Factory configuration happens here
         */
        $app['assetic'] = $app->share(function () use ($app) {
                $app['assetic.path_to_web'] = $app['config']->get('laravel-assetic::config.path_to_web');
                if( $app['config']->has('laravel-assetic::config.path_to_source')){
                    $app['assetic.path_to_source'] = $app['config']->get('laravel-assetic::config.options.path_to_source');
                }
                $app['assetic.options'] = $app['config']->get('laravel-assetic::config.options');

                // initializing lazy asset manager
                if (isset($app['assetic.formulae']) &&
                    !is_array($app['assetic.formulae']) &&
                    !empty($app['assetic.formulae'])
                ) {
                    $app['assetic.lazy_asset_manager'];
                }

                return $app['assetic.factory'];
            });

        /**
         * Factory
         *
         * @return \Assetic\Factory\AssetFactory
         */
        $app['assetic.factory'] = $app->share(function () use ($app) {
                $root = isset($app['assetic.path_to_source']) ? $app['assetic.path_to_source'] : $app['assetic.path_to_web'];
                $factory = new AssetFactory($root, $app['assetic.options']['debug']);
                $factory->setAssetManager($app['assetic.asset_manager']);
                $factory->setFilterManager($app['assetic.filter_manager']);


                if($app['config']->get('laravel-assetic::config.cachebusting') and ! $app['assetic.options']['debug'] ){
                    $factory->addWorker(new CacheBustingWorker());
                }


                return $factory;
            });

        /**
         * Asset writer, writes to the 'assetic.path_to_web' folder
         *
         * @return \Assetic\AssetWriter
         */
        $app['assetic.asset_writer'] = $app->share(function () use ($app) {
                return new AssetWriter($app['assetic.path_to_web']);
            });

        /**
         * Asset manager
         *
         * @return \Assetic\AssetManager
         */
        $app['assetic.asset_manager'] = $app->share(function () use ($app) {
                $am = new AssetManager();
                if($app['config']->has('laravel-assetic::config.asset_manager')){
                    $callback = $app['config']->get('laravel-assetic::config.asset_manager');
                    if(is_callable($callback)){
                        $callback($am);
                    }
                }
                return $am;
            });

        /**
         * Filter manager
         *
         * @return \Assetic\FilterManager
         */
        $app['assetic.filter_manager'] = $app->share(function () use ($app) {
                $fm = new FilterManager();

                if($app['config']->has('laravel-assetic::config.filter_manager')){
                    $callback = $app['config']->get('laravel-assetic::config.filter_manager');
                    if(is_callable($callback)){
                        $callback($fm);
                    }
                }

                return $fm;
            });

        /**
         * Lazy asset manager for loading assets from $app['assetic.formulae']
         * (will be later maybe removed)
         */
        $app['assetic.lazy_asset_manager'] = $app->share(function () use ($app) {
                $formulae = isset($app['assetic.formulae']) ? $app['assetic.formulae'] : array();
                $options  = $app['assetic.options'];
                $lazy     = new LazyAssetmanager($app['assetic.factory']);

                if (empty($formulae)) {
                    return $lazy;
                }

                foreach ($formulae as $name => $formula) {
                    $lazy->setFormula($name, $formula);
                }

                if ($options['formulae_cache_dir'] !== null && $options['debug'] !== true) {
                    foreach ($lazy->getNames() as $name) {
                        $lazy->set($name, new AssetCache(
                                $lazy->get($name),
                                new FilesystemCache($options['formulae_cache_dir'])
                            ));
                    }
                }

                return $lazy;
            });

        $app['assetic.dumper'] = $app->share(function () use ($app) {
                return new Dumper(
                    $app['assetic.asset_manager'],
                    $app['assetic.lazy_asset_manager'],
                    $app['assetic.asset_writer'],
                    $app['view']->getFinder()
                );
            });

        if (isset($app['twig'])) {

           $app['twig']->addExtension(new AsseticExtension($app['assetic']));

            $app->extend('assetic.lazy_asset_manager', function ($am, $app) {
                    $am->setLoader('twig', new TwigFormulaLoader($app['twig']));

                    return $am;
                });

            $app->extend('assetic.dumper', function ($helper, $app) {
                    $helper->setTwig($app['twig'], $app['twig.loader.filesystem']);

                    return $helper;
                });
        }

        $app['command.assetic.build'] = $app->share(function($app)
            {
                return new Console\AsseticBuildCommand();
            });
        $this->commands('command.assetic.build');
	}

    public function boot(){
        $app = $this->app;
        // Register our filters to use
        if (isset($app['assetic.filters']) && is_callable($app['assetic.filters'])) {
            $app['assetic.filters']($app['assetic.filter_manager']);
        }

        /**
         * Writes down all lazy asset manager and asset managers assets
         */
        $app->after(function () use ($app) {
                // Boot assetic
                $assetic = $app['assetic'];

                if (!isset($app['assetic.options']['auto_dump_assets']) ||
                    !$app['assetic.options']['auto_dump_assets']) {
                    return;
                }
                $helper = $app['assetic.dumper'];
                if (isset($app['twig'])) {
                    $helper->addTwigAssets();
                }
                $helper->dumpAssets();
            });
    }

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('assetic', 'assetic.factory', 'assetic.dumper', 'assetic.filters',
            'assetic.asset_manager', 'assetic.filtermanager', 'assetic.lazy_asset_manager',
            'assetic.asset_writer', 'command.assetic.build');
	}

}