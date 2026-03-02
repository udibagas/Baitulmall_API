<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Asnaf;
use App\Models\Muzaki;
use App\Models\Sedekah;
use App\Observers\AsnafObserver;
use App\Observers\MuzakiObserver;
use App\Observers\SedekahObserver;

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
        Asnaf::observe(AsnafObserver::class);
        Muzaki::observe(MuzakiObserver::class);
        Sedekah::observe(SedekahObserver::class);

        if (config('database.default') === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if (file_exists($dbPath)) {
                // \Illuminate\Support\Facades\DB::connection('sqlite')->getPdo()->exec('PRAGMA journal_mode=WAL;');
                // \Illuminate\Support\Facades\DB::connection('sqlite')->getPdo()->exec('PRAGMA synchronous=NORMAL;');
            }
        }
    }
}
