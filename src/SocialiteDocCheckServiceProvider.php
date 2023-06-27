<?php

namespace Antwerpes\SocialiteDocCheck;

use Illuminate\Support\ServiceProvider as BaseProvider;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\SocialiteManager;

class SocialiteDocCheckServiceProvider extends BaseProvider
{
    public function boot(): void
    {
        $this->bootSocialiteDriver();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/services.php',
            'services'
        );
    }

    protected function bootSocialiteDriver(): void
    {
        /** @var SocialiteManager $socialite */
        $socialite = $this->app->make(Factory::class);
        $socialite->extend('doccheck', function ($app) use ($socialite) {
            $config = $app['config']['services.doccheck'];

            $provider = $socialite->buildProvider(DocCheckSocialiteProvider::class, $config);
            $provider->setConfig($config);

            return $provider;
        });
    }
}
