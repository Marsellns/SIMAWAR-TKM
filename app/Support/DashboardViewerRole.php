<?php

namespace App\Support;

use Illuminate\Support\Str;

enum DashboardViewerRole: string
{
    case Electricity = 'viewer_electricity';
    case PoMonitoring = 'viewer_po_monitoring';
    case EquipmentRelocation = 'viewer_equipment_relocation';
    case ProfitLoss = 'viewer_profit_loss';

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }

    private function label(): string
    {
        return match ($this) {
            self::Electricity => 'Viewer Electricity',
            self::PoMonitoring => 'Viewer PO Monitoring',
            self::EquipmentRelocation => 'Viewer Equipment Relocation',
            self::ProfitLoss => 'Viewer Profit & Loss',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Electricity => 'electricity.dashboard',
            self::PoMonitoring => 'po-monitoring.dashboard',
            self::EquipmentRelocation => 'equipment-relocation.index',
            self::ProfitLoss => 'pnl.index',
        };
    }

    public function canReadReport(?string $route): bool
    {
        if ($route === $this->dashboardRoute()) {
            return true;
        }

        return match ($this) {
            self::Electricity => Str::is([
                'electricity.dashboard.data', 'electricity.payment-detail',
                'electricity.*.index', 'electricity.*.data', 'electricity.*.show',
                'electricity.*.export-excel', 'electricity.*.chart-data',
                'electricity.centralized.listrik-pln.grafik', 'electricity.centralized.listrik-pln.grafik-data',
            ], $route),
            self::PoMonitoring => in_array($route, [
                'po-monitoring.details', 'po-monitoring.document-circulation.index', 'po-monitoring.document-circulation.show',
                'po-hq.index', 'po-hq.data', 'po-hq.show',
                'po-varcost.index', 'po-varcost.data', 'po-varcost.export-excel',
                'presales.index', 'presales.show', 'presales.file',
            ], true),
            self::EquipmentRelocation => in_array($route, [
                'equipment-relocation.inventory-data', 'equipment-relocation.relocation-data',
                'equipment-relocation.export-excel', 'rru.index',
            ], true),
            self::ProfitLoss => in_array($route, [
                'pnl.data', 'pnl.highlights', 'pnl.analytics', 'pnl.export-excel', 'pnl.site-history',
            ], true),
        };
    }
}
