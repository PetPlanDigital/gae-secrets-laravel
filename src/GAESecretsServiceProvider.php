<?php

/*
 *  Based heavily on the following library under the MIT license: https://github.com/tommerrett/laravel-GAE-secret-manager
 *
    MIT License

    Copyright (c) 2019 tommerrett

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Google\Cloud\Datastore\DatastoreClient;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Carbon\Carbon;

class GAESecretsServiceProvider extends ServiceProvider
{

    protected $cache;

    protected $variables;
    protected $configVariables;
    protected $cacheExpiry;
    protected $cacheStore;

    //Set variables on class construction from config
    public function __construct () {
        $this->variables = config('GAESecrets.variables');
        $this->configVariables = config('GAESecrets.variables-config');
        $this->cacheExpiry = config('GAESecrets.cache-expiry', null);
        $this->cacheStore = config('GAESecrets.cache-store', 'file');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //No classes need registration
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(CacheRepository $cache)
    {
        $this->cache = $cache;

        $this->publishes([
            __DIR__.'/config/config.php' => config_path('GAESecrets.php'),
        ]);

        //Load secrets
        $this->LoadSecrets();
    }

    protected function LoadSecrets()
    {
        // Only run this if the evironment is GAE
        if(config('GAESecrets.is-GAE'))
        {
            if(!$this->checkCache())
            {
                // Cache has expired need to refresh the cache from Datastore
                $this->getVariables();
            }
        }
    }


    protected function checkCache()
    {
        try {
            if (!$this->variables) {
                return false;
            }
            else {
                foreach($this->variables as $variable)
                {
                    $val = $this->cache->get($variable);
                    if (!is_null($val))
                    {
                        putenv("$variable=$val");
                        // Update config if needed
                        if (array_key_exists($variable, $this->configVariables)) {
                            config([ $this->configVariables[$variable] => $val ]);
                        }
                    }
                    else
                    {
                        // If any value returns null, refresh
                        return false;
                    }
                }
            }
        }
        catch (\Exception $e) {
            // Swallow failures and return false
            return false;
        }

        return true;
    }

    protected function getVariables()
    {
        $datastore = new DatastoreClient();
        $query = $datastore->query();
        $query->kind('Parameter');

        $res = $datastore->runQuery($query);
        foreach ($res as $parameter) {
            $name = $parameter['name'];
            $val = $parameter['value'];
            putenv("$name=$val");
            $this->storeToCache($name, $val);

            // Update config if needed
            if (array_key_exists($name, $this->configVariables)) {
                config([ $this->configVariables[$name] => $val ]);
            }
        }
    }

    protected function storeToCache($name, $val)
    {
        $this->cache->put($name, $val, $this->cacheExpiry);
    }


}
