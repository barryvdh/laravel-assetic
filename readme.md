## Assetic for Laravel

A ServiceProvider based on https://github.com/mheap/Silex-Assetic

Install via composer, add the ServiceProvider and configure assets/filters in the config.

Add this package to composer.json

    "require": {
        ..
        "barryvdh/laravel-assetic": "dev-master"
    }
    
And run `composer update`. Then add the ServiceProvider to the providers array in app/config/app.php
    
    'providers' => array(
        ..
        'Barryvdh\Assetic\AsseticServiceProvider',
    )
    
Finally publish the config file (`php artisan config:publish barryvdh/laravel-assetic`) and add your filters to the config.
    
    // app/config/packages/barryvdh/laravel-assetic/config.php
    'filter_manager' => function(FilterManager $fm){
         $fm->set('less', new \Assetic\Filter\LessphpFilter());
         $fm->set('cssmin', new Assetic\Filter\CssMinFilter);
         $fm->set('jsmin', new Assetic\Filter\JSMinFilter);
         $fm->set('cssrewrite', new Assetic\Filter\CssRewriteFilter());
    },
        

When Twig is installed, the Assetic Extension can be used. Be sure to include the AsseticServiceProvider AFTER the TwigServiceProvider
