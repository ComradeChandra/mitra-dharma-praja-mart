<?php

namespace App\Providers;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        // Di server yang memakai https: semua tautan dan aset dibuat https.
        // Banyak hosting memasang HTTPS lewat proxy, sehingga aplikasi mengira
        // dirinya diakses lewat http; tanpa ini CSS/JS dimuat lewat http dan
        // diblokir browser, dan halamannya tampil berantakan.
        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Lihat 'trusted_proxies' di config/app.php.
        if ($proxy = config('app.trusted_proxies')) {
            TrustProxies::at($proxy === '*' ? '*' : array_map('trim', explode(',', $proxy)));
        }
    }
}
