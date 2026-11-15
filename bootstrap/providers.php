<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\BladeServiceProvider::class,
    App\Providers\Filament\ManagerPanelProvider::class,
    App\Providers\HelpersServiceProvider::class,
    App\Providers\HtmlMacroServiceProvider::class,
    App\Providers\ViewServiceProvider::class,
    Nitmedia\Wkhtml2pdf\L5Wkhtml2pdfServiceProvider::class,
    Bugsnag\BugsnagLaravel\BugsnagServiceProvider::class,
];
