<?php

namespace App\Providers;

use App\Models\Organiser;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('*', function ($view) {
            $view->with('viewClubData', config('clubs'));
            // $view->with('organisers', Organiser::all());
            $view->with('organisers', []);

            if (request()->path() == 'globalmanclub') {
                $view->with('bgColorClass', 'bg-dark');
                $view->with('bgColorNeonClass', 'bg-neon');
                $view->with('textColorClass', 'text-neon');
            }
        });
    }
}
