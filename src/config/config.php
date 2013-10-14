<?php

return array(


 /*
  |--------------------------------------------------------------------------
  | Assetic Options
  |--------------------------------------------------------------------------
  |
  | Note: CacheBusting doesn't seem to work with seperate files yet.
  | It works fine when debug is false.
  |
  */

  'cachebusting' =>  true,

  'options' => array(

      'debug' => \Config::get('app.debug'),

      'formulae_cache_dir' => storage_path().'/cache/assetic',

      'auto_dump_assets' => \Config::get('app.debug')

  ),

  'path_to_web' => public_path(),


);
