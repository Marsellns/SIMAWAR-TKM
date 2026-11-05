<?php

namespace App\Filament\Pages;

use App\Support\OperationalDatasets;
use App\Support\ReportModules;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\Route;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

/** Custom Filament page for the existing SQL-backed operational reports. */
class OperationalReport extends Page
{
    protected string $view = 'filament.pages.operational-report';

    #[Locked]
    public string $sourceRoute = '';

    #[Locked]
    public array $sourceParameters = [];

    #[Locked]
    public array $sourceQuery = [];

    private ?string $initialHtml = null;

    public function mount(): void
    {
        abort_unless($this->acceptsSourceRoute(request()->route(), request()->query()), 404);
        $this->sourceRoute = request()->route()->getName();
        $this->sourceParameters = array_map(
            fn ($value) => $value instanceof Model ? $value->getRouteKey() : $value,
            request()->route()->parameters(),
        );
        $this->sourceQuery = request()->query();
        $this->initialHtml = request()->attributes->get('simaster.report_html');
    }

    /** The shared renderer also accepts pre-refactor Livewire snapshots. */
    protected function acceptsSourceRoute(?Route $route, array $query): bool
    {
        return ReportModules::isReportRoute($route);
    }

    public function getHeading(): string
    {
        return '';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        if ($this->sourceRoute === 'operations.create') {
            $definition = OperationalDatasets::DEFINITIONS[$this->sourceParameters['dataset'] ?? ''] ?? null;
            if ($definition) {
                return 'Tambah Data '.$definition[1];
            }
        }
        $module = collect(ReportModules::navigation())
            ->filter(fn ($module) => str($this->sourceRoute)->is($module[3])
                && collect($module[5] ?? [])->every(fn ($value, $key) => ($this->sourceQuery[$key] ?? null) === $value))
            ->sortByDesc(fn ($module) => ($this->sourceRoute === $module[2] ? 1000 : 0)
                + strlen($module[3]) + count($module[5] ?? []) * 100)
            ->first();

        $dashboardLabel = array_search($this->sourceRoute, ReportModules::dashboardGroups(), true);

        return $module[1] ?? ($dashboardLabel ?: (str($this->sourceRoute)->is('notifications.*') ? 'Notifikasi' : 'Laporan SIMAWAR'));
    }

    protected function getViewData(): array
    {
        return ['reportHtml' => new HtmlString($this->initialHtml ?? $this->renderSource())];
    }

    private function renderSource(): string
    {
        $router = app('router');
        $source = $router->getRoutes()->getByName($this->sourceRoute);
        abort_unless($source, 404);
        $user = auth()->user();
        abort_unless($user?->canAccessPanel(Filament::getPanel('report')), 403);
        abort_unless($user->dashboardViewerRole()?->canReadReport($this->sourceRoute) ?? true, 403);

        // Locked route identifiers only select an existing server-side controller.
        // Persistent middleware rechecks its original auth / role requirements.
        $request = Request::create(route($this->sourceRoute, $this->sourceParameters), 'GET', $this->sourceQuery);
        $route = (clone $source)->bind($request);
        abort_unless($this->acceptsSourceRoute($route, $this->sourceQuery), 404);
        $request->setLaravelSession(request()->session());
        $request->setUserResolver(fn () => auth()->user());
        $request->setRouteResolver(fn () => $route);
        $request->attributes->set('simaster.report_fragment', true);
        $original = app('request');

        try {
            app()->instance('request', $request);
            $router->substituteBindings($route);
            $router->substituteImplicitBindings($route);
            [$controller, $method] = explode('@', $route->getActionName(), 2);
            $view = app(ControllerDispatcher::class)->dispatch($route, app($controller), $method);
            abort_unless($view instanceof View, 404);

            return $view->render();
        } finally {
            app()->instance('request', $original);
        }
    }
}
