<?php
return [
    /*
    |--------------------------------------------------------------------------
    | List of variables that are stored in Datastore
    |--------------------------------------------------------------------------
    |
    | List all of the keys that should be loaded from datastore -these must all exist. These variables will be loaded into the env()
    |
    */
    'variables' => [
    ],
    /*
    |--------------------------------------------------------------------------
    | Variables that require overwriting the config
    |--------------------------------------------------------------------------
    |
    | Some (not all) variables are set into the config, as such updating the env() will not overwrite the config cached values. The variables below will overwrite the config.
    |
    */
    'variables-config' => [
    ],
    /*
    |--------------------------------------------------------------------------
    | Cache Expiry
    |--------------------------------------------------------------------------
    |
    | The length of time that the Cache should be enabled for in seconds. default is null: indefinite.
    |
    */
    'cache-expiry' => null, //seconds
    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | Define the cache store that you wish to use (this must be configured in your config.cache file). Note you can only use a store that ddoes not require credentials to access it. As such file is suggested.
    |
    */
    'cache-store' => 'file',
    /*
    |--------------------------------------------------------------------------
    | Is GAE
    |--------------------------------------------------------------------------
    |
    | Check for the existence of the env var GAE_ENV to determine if we are on App Engine.
    |
    */
    'is-GAE' => env('GAE_ENV') != null,
];
