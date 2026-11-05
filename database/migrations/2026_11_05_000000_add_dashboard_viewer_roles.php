<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['viewer_electricity', 'viewer_po_monitoring', 'viewer_equipment_relocation', 'viewer_profit_loss'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function down(): void
    {
        // Keep role assignments so a rollback cannot turn scoped accounts into unrestricted accounts.
    }
};
