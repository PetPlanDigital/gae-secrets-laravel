<?php

namespace Petplan\GAESecrets;

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
        $datastore = new DatastoreClient([
            'namespaceId' => config('GAESecrets.namespace')
        ]);
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
