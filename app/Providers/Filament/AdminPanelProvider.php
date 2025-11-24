<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AgendaPerHariChart;
use App\Filament\Widgets\AgendaStatsOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Pages\Dashboard;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Sky,
            ])
            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\\Filament\\Pages',
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Widgets'),
                for: 'App\\Filament\\Widgets',
            )
            ->widgets([
                AgendaStatsOverview::class,
                AgendaPerHariChart::class,
            ])

            // Link-link tambahan di sidebar
            ->navigationItems([
                // Beranda utama website
                NavigationItem::make('Beranda Website')
                    ->url(url('/'), shouldOpenInNewTab: true)
                    ->icon('heroicon-o-home')
                    ->group('Halaman Publik')
                    ->sort(90),

                // Dashboard agenda publik
                NavigationItem::make('Agenda Publik')
                    ->url(url('/agenda-kegiatan'), shouldOpenInNewTab: true)
                    ->icon('heroicon-o-globe-alt')
                    ->group('Halaman Publik')
                    ->sort(100),
            ])

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
