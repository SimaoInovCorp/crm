<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use OpenAI\Contracts\ClientContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerOpenAiClient();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Override the default openai-php/laravel binding to supply an explicit
     * CA-bundle path so cURL works correctly in all contexts (web + CLI/Tinker)
     * on Windows with Laravel Herd, where the default Guzzle client omits it.
     *
     * The openai-php/laravel ServiceProvider is a DeferrableProvider, which means
     * Laravel fires it on first resolution — overriding any pre-registered binding.
     * We force it to run eagerly here, then immediately replace the binding.
     */
    protected function registerOpenAiClient(): void
    {
        // 1. Force the deferred package provider to run now so Laravel removes it
        //    from the deferred-services map. Without this, it fires on first resolve
        //    and overrides our singleton below.
        if (!$this->app->providerIsLoaded(\OpenAI\Laravel\ServiceProvider::class)) {
            $this->app->register(\OpenAI\Laravel\ServiceProvider::class);
        }

        // 2. Override the binding with our custom client configuration.
        $this->app->singleton(ClientContract::class, function () {
            $apiKey = config('openai.api_key');
            $organization = config('openai.organization');
            $project = config('openai.project');
            $baseUri = config('openai.base_uri');

            // Allow disabling SSL peer verification via OPENAI_VERIFY_SSL=false.
            // Useful on Windows dev machines with SSL-inspection proxies whose CA
            // is in the Windows certificate store but not in a file-based bundle.
            $verify = config('openai.verify_ssl', true);

            $httpClient = new GuzzleClient([
                'timeout' => config('openai.request_timeout', 30),
                'verify'  => $verify,
            ]);

            $factory = \OpenAI::factory()
                ->withApiKey($apiKey)
                ->withOrganization($organization)
                ->withHttpClient($httpClient);

            if (is_string($project)) {
                $factory->withProject($project);
            }

            if (is_string($baseUri)) {
                $factory->withBaseUri($baseUri);
            }

            return $factory->make();
        });

        $this->app->alias(ClientContract::class, 'openai');
        $this->app->alias(ClientContract::class, \OpenAI\Client::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

