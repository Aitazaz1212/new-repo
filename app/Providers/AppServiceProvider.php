<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\URL;


class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        // // Optional: Add type checking
        // if ($this->app->environment('local')) {
        //     Scramble::preventSchema(\Dedoc\Scramble\Support\Generator\Types\UnknownType::class);
        // }
    }

    public function boot()
    {
        // Force HTTPS in production
        URL::forceScheme(env('REDIRECTION_METHOD', 'https'));

        Scramble::routes(function (Route $route) {
            return Str::startsWith($route->uri, 'api/');
        });

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer'),
            );
        });
    }
}

