<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

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
        $timezone=config('app.timezone','Asia/Karachi');
        try { if (Schema::hasTable('business_settings')) { $configured=DB::table('business_settings')->where('key','timezone')->value('value'); if ($configured && in_array($configured,timezone_identifiers_list(),true)) $timezone=$configured; } } catch (Throwable) { }
        config(['app.timezone'=>$timezone]); date_default_timezone_set($timezone);
    }
}
