<?php

return array(


 /*
  |--------------------------------------------------------------------------
  | Assetic Options
  |--------------------------------------------------------------------------
  |
  */

  'options' => array(

      'debug' => \Config::get('app.debug'),

      'formulae_cache_dir' => storage_path().'/cache/assetic',

      'auto_dump_assets' => \Config::get('app.debug')

  ),

  'path_to_web' => public_path(),


);
