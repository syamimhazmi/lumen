<?php

namespace Laravel\Lumen;

use Closure;
use RuntimeException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Lumen\Routing\Router;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Orchestra\Contracts\Foundation\Application as ApplicationContract;

class Application extends Container implements ApplicationContract
{
    use Concerns\Compatibility,
        Concerns\CoreBindings,
        Concerns\FoundationSupports,
        Concerns\RoutesRequests,
        Concerns\RegistersExceptionHandlers;

    /**
     * The array of booting callbacks.
     *
     * @var array
     */
    protected $bootingCallbacks = [];

    /**
     * The array of booted callbacks.
     *
     * @var array
     */
    protected $bootedCallbacks = [];

    /**
     * The array of terminating callbacks.
     *
     * @var array
     */
    protected $terminatingCallbacks = [];

    /**
     * Indicates if the class aliases have been registered.
     *
     * @var bool
     */
    protected static $aliasesRegistered = false;

    /**
     * The base path of the application installation.
     *
     * @var string
     */
    protected $basePath;

    /**
     * All of the loaded configuration files.
     *
     * @var array
     */
    protected $loadedConfigurations = [];

    /**
     * Indicates if the application has "booted".
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * The loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = [];

    /**
     * The service binding methods that have been executed.
     *
     * @var array
     */
    protected $ranServiceBinders = [];

    /**
     * The custom storage path defined by the developer.
     *
     * @var string
     */
    protected $storagePath;

    /**
     * The application namespace.
     *
     * @var string
     */
    protected $namespace;

    /**
     * The Router instance.
     *
     * @var \Laravel\Lumen\Routing\Router
     */
    public $router;

    /**
     * Create a new Lumen application instance.
     *
     * @param  string|null  $basePath
     */
    public function __construct($basePath = null)
    {
        \date_default_timezone_set(\env('APP_TIMEZONE', 'UTC'));

        $this->basePath = $basePath;

        $this->bootstrapContainer();
        $this->registerErrorHandling();
        $this->bootstrapRouter();
    }

    /**
     * Bootstrap the application container.
     *
     * @return void
     */
    protected function bootstrapContainer()
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(self::class, $this);

        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
        $this->instance('path.config', $this->basePath('resources/config'));
        $this->instance('path.database', $this->databasePath());
        $this->instance('path.storage', $this->storagePath());
        $this->instance('path.resources', $this->resourcePath());
        $this->instance('path.bootstrap', $this->bootstrapPath());

        $this->instance('env', $this->environment());

        $this->registerContainerAliases();
    }

    /**
     * Bootstrap the router instance.
     *
     * @return void
     */
    public function bootstrapRouter()
    {
        $this->router = new Router($this);
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version()
    {
        return 'Lumen (5.8.7) (Laravel Components 5.8.*)';
    }

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDownForMaintenance()
    {
        return \file_exists($this->storagePath('framework/down'));
    }

    /**
     * Get or check the current application environment.
     *
     * @param  mixed
     *
     * @return string
     */
    public function environment(...$environments)
    {
        $env = \env('APP_ENV', 'production');

        if (\func_num_args() > 0) {
            $patterns = \is_array($environments[0]) ? $environments[0] : $environments;

            foreach ($patterns as $pattern) {
                if (Str::is($pattern, $env)) {
                    return true;
                }
            }

            return false;
        }

        return $env;
    }

    /**
     * Register a service provider with the application.
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @param  array  $options
     * @param  bool   $force
     *
     * @return void
     */
    public function register($provider, $options = [], $force = false)
    {
        if (! $provider instanceof ServiceProvider) {
            $provider = $this->resolveProvider($provider);
        }

        if (\array_key_exists($providerName = \get_class($provider), $this->loadedProviders)) {
            return;
        }

        $this->loadedProviders[$providerName] = $provider;

        if (\method_exists($provider, 'register')) {
            $provider->register();
        }

        if ($this->booted) {
            $this->bootProvider($provider);
        }
    }

    /**
     * Register a deferred provider and service.
     *
     * @param  string  $provider
     * @param  string|null  $service
     *
     * @return void
     */
    public function registerDeferredProvider($provider, $service = null)
    {
        return $this->register($provider);
    }

    /**
     * Boots the registered providers.
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        \array_walk($this->loadedProviders, function ($p) {
            $this->bootProvider($p);
        });

        // Once the application has booted we will also fire some "booted" callbacks
        // for any listeners that need to do work after this initial booting gets
        // finished. This is useful when ordering the boot-up processes we run.
        $this->fireAppCallbacks($this->bootingCallbacks);

        $this->booted = true;

        $this->fireAppCallbacks($this->bootedCallbacks);
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string  $provider
     *
     * @return \Illuminate\Support\ServiceProvider
     */
    public function resolveProvider($provider)
    {
        return new $provider($this);
    }

    /**
     * Boot the given service provider.
     *
     * @param  \Illuminate\Support\ServiceProvider  $provider
     *
     * @return mixed
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        if (\method_exists($provider, 'boot')) {
            return $this->call([$provider, 'boot']);
        }
    }

    /**
     * Register a new boot listener.
     *
     * @param  mixed  $callback
     *
     * @return void
     */
    public function booting($callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    /**
     * Register a new "booted" listener.
     *
     * @param  mixed  $callback
     *
     * @return void
     */
    public function booted($callback)
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->booted) {
            $this->fireAppCallbacks([$callback]);
        }
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        if (! $this->bound($abstract) &&
            \array_key_exists($abstract, $this->availableBindings) &&
            ! \array_key_exists($this->availableBindings[$abstract], $this->ranServiceBinders)) {
            $this->{$method = $this->availableBindings[$abstract]}();

            $this->ranServiceBinders[$method] = true;
        }

        return parent::make($abstract, $parameters);
    }

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public function flush()
    {
        parent::flush();

        $this->middleware = [];
        $this->currentRoute = [];
        $this->routeMiddleware = [];
        $this->loadedProviders = [];
        $this->bootingCallbacks = [];
        $this->bootedCallbacks = [];
        $this->reboundCallbacks = [];
        $this->resolvingCallbacks = [];
        $this->terminatingCallbacks = [];
        $this->availableBindings = [];
        $this->ranServiceBinders = [];
        $this->loadedConfigurations = [];
        $this->afterResolvingCallbacks = [];

        $this->router = null;
        $this->dispatcher = null;
        static::$instance = null;
    }

    /**
     * Call the booting callbacks for the application.
     *
     * @param  array  $callbacks
     *
     * @return void
     */
    protected function fireAppCallbacks(array $callbacks)
    {
        foreach ($callbacks as $callback) {
            \call_user_func($callback, $this);
        }
    }

    /**
     * Get the path to the application's language files.
     *
     * @return string
     */
    protected function getLanguagePath()
    {
        return $this->resourcePath('lang');
    }

    /**
     * Configure and load the given component and provider.
     *
     * @param  string  $config
     * @param  array|string  $providers
     * @param  string|null  $return
     *
     * @return mixed
     */
    public function loadComponent($config, $providers, $return = null)
    {
        $this->configure($config);

        foreach ((array) $providers as $provider) {
            $this->register($provider);
        }

        return $this->make($return ?: $config);
    }

    /**
     * Load a configuration file into the application.
     *
     * @param  string  $name
     *
     * @return void
     */
    public function configure($name)
    {
        if (isset($this->loadedConfigurations[$name])) {
            return;
        }

        $this->loadedConfigurations[$name] = true;

        $path = $this->getConfigurationPath($name);

        if (! \is_null($path)) {
            $this->make('config')->set(Arr::dot([
                $name => require $path,
            ]));
        }
    }

    /**
     * Get the path to the given configuration file.
     *
     * If no name is provided, then we'll return the path to the config folder.
     *
     * @param  string|null  $name
     *
     * @return string|null
     */
    public function getConfigurationPath($name = null)
    {
        if (\is_null($name)) {
            return \config_path();
        } elseif (\file_exists($path = $this->basePath('lumen/config/').$name.'.php')) {
            return $path;
        }
    }

    /**
     * Get the registered service provider instances if any exist.
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     *
     * @return array
     */
    public function getProviders($provider)
    {
        $name = \is_string($provider) ? $provider : \get_class($provider);

        return Arr::where($this->loadedProviders, function ($value) use ($name) {
            return $value instanceof $name;
        });
    }

    /**
     * Register the facades for the application.
     *
     * @param  bool  $aliases
     * @param  array $custom
     *
     * @return void
     */
    public function withFacades($aliases = true, $custom = [])
    {
        Facade::setFacadeApplication($this);

        if ($aliases) {
            $this->withAliases($custom);
        }

        return $this;
    }

    /**
     * Register the aliases for the application.
     *
     * @param  array  $custom
     *
     * @return void
     */
    public function withAliases($custom = [])
    {
        $defaults = [
            'Illuminate\Support\Facades\Auth' => 'Auth',
            'Illuminate\Support\Facades\Cache' => 'Cache',
            'Illuminate\Support\Facades\DB' => 'DB',
            'Illuminate\Support\Facades\Crypt' => 'Crypt',
            'Illuminate\Support\Facades\Event' => 'Event',
            'Illuminate\Support\Facades\Gate' => 'Gate',
            'Illuminate\Support\Facades\Hash' => 'Hash',
            'Illuminate\Support\Facades\Log' => 'Log',
            'Illuminate\Support\Facades\Queue' => 'Queue',
            'Illuminate\Support\Facades\Route' => 'Route',
            'Illuminate\Support\Facades\Schema' => 'Schema',
            'Illuminate\Support\Facades\Session' => 'Session',
            'Illuminate\Support\Facades\Storage' => 'Storage',
            'Illuminate\Support\Facades\URL' => 'URL',
            'Illuminate\Support\Facades\Validator' => 'Validator',
        ];

        if (! static::$aliasesRegistered) {
            static::$aliasesRegistered = true;

            $merged = \array_merge($defaults, $custom);

            foreach ($merged as $original => $alias) {
                \class_alias($original, $alias);
            }
        }

        return $this;
    }

    /**
     * Load the Eloquent library for the application.
     *
     * @return $this
     */
    public function withEloquent()
    {
        $this->make('db');

        return $this;
    }

    /**
     * Get the path to the application "app" directory.
     *
     * @return string
     */
    public function path()
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'lumen'.DIRECTORY_SEPARATOR.'app';
    }

    /**
     * Get the base path for the application.
     *
     * @param  string|null  $path
     *
     * @return string
     */
    public function basePath($path = null)
    {
        if (isset($this->basePath)) {
            return $this->basePath.($path ? '/'.$path : $path);
        }

        if ($this->runningInConsole()) {
            $this->basePath = getcwd();
        } else {
            $this->basePath = realpath(getcwd().'/../');
        }

        return $this->basePath($path);
    }

    /**
     * Get the path to the database directory.
     *
     * @param  string  $path
     *
     * @return string
     */
    public function databasePath($path = '')
    {
        return $this->resourcePath('database').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Get the resource path for the application.
     *
     * @param  string|null  $path
     *
     * @return string
     */
    public function resourcePath($path = '')
    {
        if ($this->resourcePath) {
            return $this->resourcePath.($path ? '/'.$path : $path);
        }

        return $this->basePath('resources'.($path ? '/'.$path : $path));
    }

    /**
     * Set the storage directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useStoragePath($path)
    {
        $this->storagePath = $path;

        $this->instance('path.storage', $path);

        return $this;
    }

    /**
     * Get the storage path for the application.
     *
     * @param  string|null  $path
     * @param  string|null  $path
     *
     * @return string
     */
    public function storagePath($path = '')
    {
        if ($this->storagePath) {
            return $this->storagePath.($path ? '/'.$path : $path);
        }

        return $this->basePath('storage'.($path ? '/'.$path : $path));
    }

    /**
     * Determine if the application routes are cached.
     *
     * @return bool
     */
    public function routesAreCached()
    {
        return false;
    }

    /**
     * Determine if the application is running in the console.
     *
     * @return bool
     */
    public function runningInConsole()
    {
        return \php_sapi_name() === 'cli' || \php_sapi_name() === 'phpdbg';
    }

    /**
     * Determine if we are running unit tests.
     *
     * @return bool
     */
    public function runningUnitTests()
    {
        return $this->environment() == 'testing';
    }

    /**
     * Prepare the application to execute a console command.
     *
     * @param  bool  $aliases
     *
     * @return void
     */
    public function prepareForConsoleCommand($aliases = true)
    {
        $this->withFacades($aliases);

        $this->configure('database');

        $this->register(\Illuminate\Database\MigrationServiceProvider::class);
        $this->register(\Orchestra\Database\ConsoleServiceProvider::class);
        $this->register(Console\ConsoleServiceProvider::class);
        $this->register(\Orchestra\Publisher\PublisherServiceProvider::class);
        $this->register(\Orchestra\Foundation\Providers\SupportServiceProvider::class);
    }

    /**
     * Get the application namespace.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getNamespace()
    {
        if (! \is_null($this->namespace)) {
            return $this->namespace;
        }

        $composer = \json_decode(\file_get_contents($this->basePath('composer.json')), true);

        foreach ((array) ($composer['autoload']['psr-4'] ?? []) as $namespace => $path) {
            foreach ((array) $path as $pathChoice) {
                if (\realpath($this->path()) == \realpath($this->basePath().'/'.$pathChoice)) {
                    return $this->namespace = $namespace;
                }
            }
        }

        throw new RuntimeException('Unable to detect application namespace.');
    }

    /**
     * Register a terminating callback with the application.
     *
     * @param  \Closure  $callback
     *
     * @return $this
     */
    public function terminating(Closure $callback)
    {
        $this->terminatingCallbacks[] = $callback;

        return $this;
    }

    /**
     * Terminate the application.
     *
     * @return void
     */
    public function terminate()
    {
        foreach ($this->terminatingCallbacks as $terminating) {
            $this->call($terminating);
        }
    }
}
