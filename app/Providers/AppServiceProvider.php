<?php

namespace App\Providers;

use App\Filesystem\PublicEndpointS3Adapter;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePublicStorageUrls();
        $this->configureRateLimiting();
    }

    /**
     * Limit how often a user may post comments, answering with a validation error instead of an error page.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('comments', fn (Request $request) => Limit::perMinute(10)
            ->by((string) $request->user()?->id)
            ->response(fn () => back()->withErrors([
                'body' => 'Você enviou muitos comentários em pouco tempo. Aguarde um minuto e tente novamente.',
            ])));
    }

    /**
     * Sign temporary S3 URLs for the public endpoint when the app reaches storage through another address.
     */
    protected function configurePublicStorageUrls(): void
    {
        Storage::extend('s3', function (Application $app, array $config): Cloud {
            /** @var FilesystemManager $filesystem */
            $filesystem = $app->make('filesystem');
            $disk = $filesystem->createS3Driver($config);

            if (! $disk instanceof AwsS3V3Adapter || empty($config['public_endpoint']) || ! is_string($config['public_endpoint'])) {
                return $disk;
            }

            return PublicEndpointS3Adapter::fromDisk($disk, $config['public_endpoint']);
        });
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

        Model::preventLazyLoading(! app()->isProduction());

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
