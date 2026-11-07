<?php

return [
    // Only these report pages mount the shared map. Operational routes stay intact.
    'pages' => [
        'dashboard' => ['module' => 'pnl', 'title' => 'Dashboard Utama'],
        'pnl.index' => ['module' => 'pnl', 'title' => 'Profit & Loss'],
        'data-potensi.dashboard' => ['module' => 'feasibility', 'title' => 'Feasibility Site'],
        'infrastruktur.index' => ['module' => 'infrastructure', 'title' => 'Infrastruktur'],
        'infrastruktur.sewa-lahan.index' => ['module' => 'infrastructure', 'scope' => 'sewa', 'title' => 'Sewa Lahan'],
        'infrastruktur.combat.index' => ['module' => 'infrastructure', 'scope' => 'combat', 'title' => 'Combat'],
        'infrastruktur.recurring-ipas.index' => ['module' => 'recurring', 'scope' => 'ant', 'title' => 'Recurring ANT & IPAS'],
        'infrastruktur.recurring-tagihan-ipas.index' => ['module' => 'recurring', 'scope' => 'tagihan', 'title' => 'Tagihan IPAS'],
        'electricity.dashboard' => ['module' => 'electricity', 'title' => 'Electricity'],
        'electricity.centralized.listrik-pln.index' => ['module' => 'electricity', 'scope' => 'centralized', 'title' => 'Listrik PLN'],
        'electricity.inbuilding.listrik-inbuilding.index' => ['module' => 'electricity', 'scope' => 'inbuilding', 'title' => 'Listrik Inbuilding'],
        'equipment-relocation.index' => ['module' => 'equipment', 'title' => 'Equipment Relocation'],
    ],
    // Use a company/self-hosted Photon instance for bulk address lookup.
    // No external address requests are made unless an operator runs --geocode.
    'geocoding_url' => env('SITE_GEOCODING_URL'),
    'geocoding_token' => env('SITE_GEOCODING_TOKEN'),
    'geocoding_delay_ms' => (int) env('SITE_GEOCODING_DELAY_MS', 1100),
];
