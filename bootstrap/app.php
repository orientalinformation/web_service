<?php

require_once __DIR__.'/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__.'/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

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
    realpath(__DIR__.'/../')
);

$app->withFacades();

$app->withEloquent();

// $app->withAliases([
//     'Tymon\JWTAuth\Facades\JWTAuth' => 'JWTAuth',
//     'Tymon\JWTAuth\Facades\JWTFactory' => 'JWTFactory'
// ]);

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

$app->singleton('filesystem', function ($app) {
    return $app->loadComponent(
        'filesystems',
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        'filesystem'
    );
});

$app->configure('cors');

$app->configure('filesystems');

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
    'Nord\Lumen\Cors\CorsMiddleware',
]);

$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
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

class_alias('Illuminate\Support\Facades\Config', 'Config');

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);


$app->register(Sofa\Eloquence\BaseServiceProvider::class);
$app->register(Nord\Lumen\Cors\CorsServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);

$app->register(\App\Providers\KernelServiceProvider::class);
$app->register(\App\Providers\CryosoftServiceProvider::class);
$app->register(Elibyy\TCPDF\ServiceProvider::class);

class_alias('\Elibyy\TCPDF\Facades\TCPDF', 'PDF');

$app->register(Plank\Mediable\MediableServiceProvider::class);
class_alias(Plank\Mediable\MediaUploaderFacade::class, 'MediaUploader');

if ($app->environment('local')) {
    $app->register(Krlove\EloquentModelGenerator\Provider\GeneratorServiceProvider::class);
    $app->register(Flipbox\LumenGenerator\LumenGeneratorServiceProvider::class);

}

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
    $router->POST('/api/v1/reports/{id}/downLoadPDF', 'Api1\\Reports@downLoadPDF');
    $router->POST('/api/v1/reports/{id}/downLoadHtmlToPDF', 'Api1\\Reports@downLoadHtmlToPDF');
    $router->GET('/api/v1/reports/{id}/processingReport', 'Api1\\Reports@processingReport');
    require dirname(__DIR__).'/routes/auth.php';
    require dirname(__DIR__).'/routes/translations.php';
});

$app->router->group([
    'middleware' => 'auth',
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require dirname(__DIR__).'/routes/api.php';
});

return $app;
