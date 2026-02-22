<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();

$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/

$app->configure('app');
$app->configure('view');
$app->configure('cache');
$app->configure('database');
$app->configure('queue');

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    App\Http\Middleware\CorsMiddleware::class,
    App\Http\Middleware\SecurityHeadersMiddleware::class
]);

$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
    'auth.web' => App\Http\Middleware\AuthenticateWeb::class,
    'auth.jwt' => App\Http\Middleware\AuthJwt::class,
    'role' => App\Http\Middleware\CheckRole::class,
    'permission' => App\Http\Middleware\CheckPermission::class,
    'throttle' => App\Http\Middleware\ThrottleRequests::class,
    'super_admin' => App\Http\Middleware\SuperAdminMiddleware::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

// $app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);

// Register View Service Provider for Blade templates
$app->register(Illuminate\View\ViewServiceProvider::class);

// Share Request and other helpers with all views
$app->make('view')->share([
    'Request' => new class {
        public function is($path) {
            return request()->is($path);
        }

        public function __call($method, $parameters) {
            return request()->$method(...$parameters);
        }
    }
]);

// Register Cache, Database, and Queue providers
$app->register(Illuminate\Cache\CacheServiceProvider::class);
$app->register(Illuminate\Database\DatabaseServiceProvider::class);
$app->register(Illuminate\Queue\QueueServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

/*
|--------------------------------------------------------------------------
| Register Helper Functions
|--------------------------------------------------------------------------
|
| Register custom helper functions for Blade templates
|
*/

if (!function_exists('csrf_token')) {
    function csrf_token() {
        // Lumen doesn't have CSRF by default, return empty string
        return '';
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field() {
        // Lumen doesn't have CSRF by default, return empty string
        return '';
    }
}

if (!function_exists('asset')) {
    function asset($path = '') {
        // Return the public asset path
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url($path = '') {
        // Return the base URL with path
        $base = env('APP_URL', 'http://localhost');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('route')) {
    function route($name, $parameters = []) {
        // Basic route helper - just redirect to path based on name
        $routes = [
            'home' => '/',
            'campanha.criar' => '/campanha/criar',
            'campanha.importacao' => '/campanha/importacao',
            'campanha.status-importacoes' => '/campanha/status-importacoes',
            'campanha.status-fila' => '/campanha/status-fila',
            'campanha.status-ligacoes' => '/campanha/status-ligacoes',
            'campanha.gerenciar-fila' => '/campanha/gerenciar-fila',
            'dashboard.index' => '/dashboard',
            'admin.empresas' => '/admin/empresas',
            'admin.empresas.criar' => '/admin/empresas/criar',
            'admin.usuarios' => '/admin/usuarios',
        ];

        return $routes[$name] ?? '/' . $name;
    }
}

return $app;
