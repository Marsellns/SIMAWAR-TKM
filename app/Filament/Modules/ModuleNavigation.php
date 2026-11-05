<?php

namespace App\Filament\Modules;

use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

class ModuleNavigation
{
    /** Build fresh items so children and active state do not leak between requests. */
    public static function groups(): array
    {
        $modules = ReportModuleRegistry::navigation();
        if ($role = auth()->user()?->dashboardViewerRole()) {
            $modules = array_filter($modules, fn (array $module): bool => $role->canReadReport($module[2]));
        }
        $items = [];
        $groups = [];
        foreach ($modules as $sort => $module) {
            $key = $module[0].'::'.$module[1];
            $items[$key] = NavigationItem::make($module[1])
                ->icon($module[4])
                ->url(fn () => route($module[2], $module[5] ?? []))
                ->isActiveWhen(fn () => request()->routeIs($module[3])
                    && collect($module[5] ?? [])->every(fn ($value, $key) => request()->query($key) === $value))
                ->sort($sort);
        }
        foreach ($modules as $module) {
            $item = $items[$module[0].'::'.$module[1]];
            if ($parent = $module[6] ?? null) {
                $parentItem = $items[$module[0].'::'.$parent];
                $parentItem->childItems([...$parentItem->getChildItems(), $item]);
            } else {
                $groups[$module[0]][] = $item;
            }
        }

        $dashboards = ReportModuleRegistry::dashboardGroups();

        return collect($groups)->map(function ($items, $label) use ($dashboards) {
            $group = NavigationGroup::make($label)->items($items);
            $images = [
                'Dashboard' => 'dashboard',
                'Profit & Loss' => 'profit-loss',
                'Infrastruktur Management' => 'infrastructure',
                'Electricity Monitoring' => 'electricity',
                'PO Monitoring' => 'purchase-order',
                'Equipment' => 'equipment',
                'Feasibility Site' => 'feasibility',
            ];
            $attributes = [
                'data-simaster-module-image' => asset('assets/modules/'.($images[$label] ?? 'dashboard').'.svg'),
                'data-simaster-module-url' => $items[0]->getUrl(),
            ];
            if ($dashboardRoute = $dashboards[$label] ?? null) {
                $attributes = array_merge($attributes, [
                    'data-simaster-dashboard-url' => route($dashboardRoute),
                    'data-simaster-dashboard-current' => request()->routeIs($dashboardRoute) ? 'true' : 'false',
                ]);
            }
            $group->extraSidebarAttributes($attributes);

            return $group;
        })->values()->all();
    }
}
