<?php namespace Laravel\Lumen\Concerns;

use Monolog\Logger;
use Illuminate\Http\Request;
use Orchestra\Config\FileLoader;
use Orchestra\Config\Repository;
use Monolog\Handler\StreamHandler;
use Illuminate\Foundation\Composer;
use Monolog\Formatter\LineFormatter;
use Illuminate\Filesystem\Filesystem;
use Laravel\Lumen\Routing\UrlGenerator;

trait CoreBindings
{
    /**
     * The available container bindings and their respective load methods.
     *
     * @var array
     */
    public $availableBindings = [
        'auth'                                            => 'registerAuthBindings',
        'auth.driver'                                     => 'registerAuthBindings',
        'Illuminate\Contracts\Auth\Guard'                 => 'registerAuthBindings',
        'auth.password'                                   => 'registerAuthBindings',
        'Illuminate\Contracts\Auth\PasswordBroker'        => 'registerAuthBindings',
        'Illuminate\Auth\AuthManager'                     => 'registerAuthBindings',
        'orchestra.acl'                                   => 'registerAuthorizationBindings',
        'orchestra.platform.acl'                          => 'registerAuthorizationBindings',
        'Orchestra\Authorization\Factory'                 => 'registerAuthorizationBindings',
        'Orchestra\Authorization\Authorization'           => 'registerAuthorizationBindings',
        'Orchestra\Contracts\Authorization\Factory'       => 'registerAuthorizationBindings',
        'Orchestra\Contracts\Authorization\Authorization' => 'registerAuthorizationBindings',
        'Illuminate\Contracts\Broadcasting\Broadcaster'   => 'registerBroadcastingBindings',
        'Illuminate\Contracts\Bus\Dispatcher'             => 'registerBusBindings',
        'cache'                                           => 'registerCacheBindings',
        'Illuminate\Contracts\Cache\Factory'              => 'registerCacheBindings',
        'Illuminate\Contracts\Cache\Repository'           => 'registerCacheBindings',
        'Illuminate\Cache\CacheManager'                   => 'registerCacheBindings',
        'config'                                          => 'registerConfigBindings',
        'composer'                                        => 'registerComposerBindings',
        'cookie'                                          => 'registerCookieBindings',
        'Illuminate\Contracts\Cookie\Factory'             => 'registerCookieBindings',
        'Illuminate\Contracts\Cookie\QueueingFactory'     => 'registerCookieBindings',
        'db'                                              => 'registerDatabaseBindings',
        'Illuminate\Database\Eloquent\Factory'            => 'registerDatabaseBindings',
        'encrypter'                                       => 'registerEncrypterBindings',
        'Illuminate\Contracts\Encryption\Encrypter'       => 'registerEncrypterBindings',
        'events'                                          => 'registerEventBindings',
        'Illuminate\Contracts\Events\Dispatcher'          => 'registerEventBindings',
        'Illuminate\Contracts\Debug\ExceptionHandler'     => 'registerErrorBindings',
        'files'                                           => 'registerFilesBindings',
        'filesystem'                                      => 'registerFilesBindings',
        'Illuminate\Contracts\Filesystem\Factory'         => 'registerFilesBindings',
        'hash'                                            => 'registerHashBindings',
        'Illuminate\Contracts\Hashing\Hasher'             => 'registerHashBindings',
        'log'                                             => 'registerLogBindings',
        'Psr\Log\LoggerInterface'                         => 'registerLogBindings',
        'mailer'                                          => 'registerMailBindings',
        'Illuminate\Contracts\Mail\Mailer'                => 'registerMailBindings',
        'orchestra.memory'                                => 'registerMemoryBindings',
        'Orchestra\Memory\MemoryManager'                  => 'registerMemoryBindings',
        'orchestra.platform.memory'                       => 'registerMemoryBindings',
        'Orchestra\Memory\Provider'                       => 'registerMemoryBindings',
        'Orchestra\Contracts\Memory\Provider'             => 'registerMemoryBindings',
        'queue'                                           => 'registerQueueBindings',
        'queue.connection'                                => 'registerQueueBindings',
        'Illuminate\Contracts\Queue\Factory'              => 'registerQueueBindings',
        'Illuminate\Contracts\Queue\Queue'                => 'registerQueueBindings',
        'redis'                                           => 'registerRedisBindings',
        'request'                                         => 'registerRequestBindings',
        'Illuminate\Http\Request'                         => 'registerRequestBindings',
        'session'                                         => 'registerSessionBindings',
        'session.store'                                   => 'registerSessionBindings',
        'Illuminate\Session\SessionManager'               => 'registerSessionBindings',
        'Illuminate\Session\Store'                        => 'registerSessionBindings',
        'translator'                                      => 'registerTranslationBindings',
        'url'                                             => 'registerUrlGeneratorBindings',
        'validator'                                       => 'registerValidatorBindings',
        'view'                                            => 'registerViewBindings',
        'Illuminate\Contracts\View\Factory'               => 'registerViewBindings',
    ];

    /**
     * Register the core container aliases.
     *
     * @return void
     */
    protected function registerContainerAliases()
    {
        $this->aliases = [
            'Illuminate\Contracts\Foundation\Application'     => 'app',
            'Illuminate\Contracts\Auth\Guard'                 => 'auth.driver',
            'Illuminate\Contracts\Auth\PasswordBroker'        => 'auth.password',
            'Illuminate\Auth\AuthManager'                     => 'auth',
            'Illuminate\Contracts\Cache\Factory'              => 'cache',
            'Illuminate\Cache\CacheManager'                   => 'cache',
            'Illuminate\Contracts\Cache\Repository'           => 'cache.store',
            'Illuminate\Contracts\Config\Repository'          => 'config',
            'Illuminate\Container\Container'                  => 'app',
            'Illuminate\Contracts\Container\Container'        => 'app',
            'Illuminate\Contracts\Cookie\Factory'             => 'cookie',
            'Illuminate\Contracts\Cookie\QueueingFactory'     => 'cookie',
            'Illuminate\Contracts\Encryption\Encrypter'       => 'encrypter',
            'Illuminate\Contracts\Events\Dispatcher'          => 'events',
            'Illuminate\Contracts\Filesystem\Factory'         => 'filesystem',
            'Illuminate\Contracts\Hashing\Hasher'             => 'hash',
            'log'                                             => 'Psr\Log\LoggerInterface',
            'Illuminate\Contracts\Mail\Mailer'                => 'mailer',
            'Orchestra\Authorization\Factory'                 => 'orchestra.acl',
            'Orchestra\Contracts\Authorization\Factory'       => 'orchestra.acl',
            'Orchestra\Memory\MemoryManager'                  => 'orchestra.memory',
            'Orchestra\Authorization\Authorization'           => 'orchestra.platform.acl',
            'Orchestra\Contracts\Authorization\Authorization' => 'orchestra.platform.acl',
            'Orchestra\Memory\Provider'                       => 'orchestra.platform.memory',
            'Orchestra\Contracts\Memory\Provider'             => 'orchestra.platform.memory',
            'Illuminate\Contracts\Queue\Factory'              => 'queue',
            'Illuminate\Contracts\Queue\Queue'                => 'queue.connection',
            'Illuminate\Redis\Database'                       => 'redis',
            'Illuminate\Contracts\Redis\Database'             => 'redis',
            'request'                                         => 'Illuminate\Http\Request',
            'Illuminate\Session\SessionManager'               => 'session',
            'Illuminate\Session\Store'                        => 'session.store',
            'Illuminate\Contracts\View\Factory'               => 'view',
        ];
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerAuthBindings()
    {
        $this->singleton('auth', function () {
            return $this->loadComponent('auth', 'Orchestra\Auth\AuthServiceProvider', 'auth');
        });

        $this->singleton('auth.driver', function () {
            return $this->loadComponent('auth', 'Orchestra\Auth\AuthServiceProvider', 'auth.driver');
        });

        $this->singleton('auth.password', function () {
            return $this->loadComponent('auth', 'Orchestra\Auth\Passwords\PasswordResetServiceProvider', 'auth.password');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerAuthorizationBindings()
    {
        $this->singleton('orchestra.acl', function () {
            $this->register('Orchestra\Authorization\AuthorizationServiceProvider');

            return $this->make('orchestra.acl');
        });

        $this->singleton('orchestra.platform.acl', function () {
            $acl = $this->make('orchestra.acl')->make('orchestra');

            $acl->attach($this->make('orchestra.platform.memory'));

            return $acl;
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerBroadcastingBindings()
    {
        $this->singleton('Illuminate\Contracts\Broadcasting\Broadcaster', function () {
            $this->configure('broadcasting');

            $this->register('Illuminate\Broadcasting\BroadcastServiceProvider');

            return $this->make('Illuminate\Contracts\Broadcasting\Broadcaster');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerBusBindings()
    {
        $this->singleton('Illuminate\Contracts\Bus\Dispatcher', function () {
            $this->register('Illuminate\Bus\BusServiceProvider');

            return $this->make('Illuminate\Contracts\Bus\Dispatcher');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerCacheBindings()
    {
        $this->singleton('cache', function () {
            return $this->loadComponent('cache', 'Illuminate\Cache\CacheServiceProvider');
        });

        $this->singleton('cache.store', function () {
            return $this->loadComponent('cache', 'Illuminate\Cache\CacheServiceProvider', 'cache.store');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerConfigBindings()
    {
        $loader = new FileLoader(new Filesystem(), $this->configPath ?: $this->resourcePath('config'));

        $this->instance('config', $config = new Repository($loader, $this->environment()));
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerComposerBindings()
    {
        $this->singleton('composer', function ($app) {
            return new Composer($app->make('files'), $this->basePath());
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerCookieBindings()
    {
        $this->singleton('cookie', function () {
            return $this->loadComponent('session', 'Illuminate\Cookie\CookieServiceProvider', 'cookie');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerDatabaseBindings()
    {
        $this->singleton('db', function () {
            return $this->loadComponent(
                'database', [
                    'Illuminate\Database\DatabaseServiceProvider',
                    'Illuminate\Pagination\PaginationServiceProvider', ],
                'db'
            );
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerEncrypterBindings()
    {
        $this->singleton('encrypter', function () {
            return $this->loadComponent('app', 'Illuminate\Encryption\EncryptionServiceProvider', 'encrypter');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerEventBindings()
    {
        $this->singleton('events', function () {
            $this->register('Illuminate\Events\EventServiceProvider');

            return $this->make('events');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerErrorBindings()
    {
        if (! $this->bound('Illuminate\Contracts\Debug\ExceptionHandler')) {
            $this->singleton(
                'Illuminate\Contracts\Debug\ExceptionHandler', 'Laravel\Lumen\Exceptions\Handler'
            );
        }
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerFilesBindings()
    {
        $this->singleton('files', function () {
            return new Filesystem();
        });

        $this->singleton('filesystem', function () {
            return $this->loadComponent('filesystems', 'Illuminate\Filesystem\FilesystemServiceProvider', 'filesystem');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerHashBindings()
    {
        $this->singleton('hash', function () {
            $this->register('Illuminate\Hashing\HashServiceProvider');

            return $this->make('hash');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerLogBindings()
    {
        $this->singleton('Psr\Log\LoggerInterface', function () {
            return new Logger('lumen', [$this->getMonologHandler()]);
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerMemoryBindings()
    {
        $this->singleton('orchestra.memory', function () {
            $this->register('Orchestra\Memory\MemoryServiceProvider');

            return $this->make('orchestra.memory');
        });

        $this->singleton('orchestra.platform.memory', function () {
            return $this->make('orchestra.memory')->make();
        });
    }

    /**
     * Get the Monolog handler for the application.
     *
     * @return \Monolog\Handler\AbstractHandler
     */
    protected function getMonologHandler()
    {
        return (new StreamHandler(storage_path('logs/lumen.log'), Logger::DEBUG))
                            ->setFormatter(new LineFormatter(null, null, true, true));
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerMailBindings()
    {
        $this->singleton('mailer', function () {
            $this->configure('services');

            return $this->loadComponent('mail', 'Illuminate\Mail\MailServiceProvider', 'mailer');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerQueueBindings()
    {
        $this->singleton('queue', function () {
            return $this->loadComponent('queue', 'Illuminate\Queue\QueueServiceProvider', 'queue');
        });

        $this->singleton('queue.connection', function () {
            return $this->loadComponent('queue', 'Illuminate\Queue\QueueServiceProvider', 'queue.connection');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerRedisBindings()
    {
        $this->singleton('redis', function () {
            return $this->loadComponent('database', 'Illuminate\Redis\RedisServiceProvider', 'redis');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerRequestBindings()
    {
        $this->singleton('Illuminate\Http\Request', function () {
            return Request::capture()->setUserResolver(function () {
                return $this->make('auth')->user();
            })->setRouteResolver(function () {
                return $this->currentRoute;
            });
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerSessionBindings()
    {
        $this->singleton('session', function () {
            return $this->loadComponent('session', 'Illuminate\Session\SessionServiceProvider');
        });

        $this->singleton('session.store', function () {
            return $this->loadComponent('session', 'Illuminate\Session\SessionServiceProvider', 'session.store');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerTranslationBindings()
    {
        $this->singleton('translator', function () {
            $this->configure('app');

            $this->instance('path.lang', $this->getLanguagePath());

            $this->register('Orchestra\Translation\TranslationServiceProvider');

            return $this->make('translator');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerUrlGeneratorBindings()
    {
        $this->singleton('url', function () {
            return new UrlGenerator($this);
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerValidatorBindings()
    {
        $this->singleton('validator', function () {
            $this->register('Illuminate\Validation\ValidationServiceProvider');

            return $this->make('validator');
        });
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerViewBindings()
    {
        $this->singleton('view', function () {
            return $this->loadComponent('view', 'Orchestra\View\ViewServiceProvider');
        });
    }
}