<?php

namespace App\Http\Controllers;

use App\Exports\CombatSiteExport;
use App\Http\Requests\UpdateCombatSiteRequest;
use App\Models\CombatSite;
use App\Support\CombatSourceDetails;
use App\Support\InfrastructureCanonicalSites;
use App\Support\InfrastructureMetrics;
use App\Support\InfrastructureOwnership;
use App\Support\LeaseStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CombatSiteController extends Controller
{
    /**
     * Halaman utama modul Combat: tabel DataTables server-side + modal detail saat baris diklik.
     */
    public function index(): View
    {
        $tahunList = CombatSite::query()
            ->whereNotNull('tahun_justi_dirnet')
            ->distinct()
            ->orderByDesc('tahun_justi_dirnet')
            ->pluck('tahun_justi_dirnet');

        return view('infrastruktur.combat.index', compact('tahunList'));
    }

    /**
     * Sumber data DataTables (server-side processing).
     * Mendukung filter tahun + search per kolom.
     */
    public function data(Request $request): JsonResponse
    {
        $isAdmin = auth()->user()->hasRole('admin');

        $query = CombatSite::query()->latest('id');

        if ($request->boolean('unique_sites')) {
            $selection = CombatSite::query();
            if ($request->filled('tahun') && $request->tahun !== 'all') {
                $selection->where('tahun_justi_dirnet', (int) $request->tahun);
            }
            $ids = InfrastructureCanonicalSites::fromRows($selection->get())->pluck('id');
            $query->whereIn('combat_sites.id', $ids);
        }

        // Filter tahun justi dirnet
        if ($request->filled('tahun') && $request->tahun !== 'all') {
            $query->where('tahun_justi_dirnet', (int) $request->tahun);
        }
        $this->applyDashboardFilter($query, $request);

        $dataTable = DataTables::of($query);

        if ($isAdmin) {
            $dataTable->addColumn(
                'aksi',
                fn (CombatSite $c) => view('infrastruktur.combat.partials.aksi', ['combat' => $c])->render()
            );
            $dataTable->addColumn('edit_url', fn (CombatSite $c) => route('infrastruktur.combat.edit', $c));
        }

        return $dataTable
            ->addIndexColumn()
            ->addColumn('lease_duration', fn (CombatSite $c) => InfrastructureMetrics::leaseDurationLabel($c))
            ->addColumn('pks_status_label', fn (CombatSite $c) => InfrastructureMetrics::pksStatusLabel($c))
            ->addColumn('process_started_at', fn (CombatSite $c) => InfrastructureMetrics::processStartDate($c)?->toDateString())
            ->addColumn('process_aging_days', fn (CombatSite $c) => InfrastructureMetrics::processAgingDays($c))
            ->addColumn('performance', fn (CombatSite $c) => InfrastructureMetrics::sitePerformance($c))
            ->addColumn('next_action_label', fn (CombatSite $c) => InfrastructureMetrics::nextActionLabel($c))
            ->addColumn('priority_label', fn (CombatSite $c) => InfrastructureMetrics::priorityLabel($c))
            ->addColumn('detail_url', fn (CombatSite $c) => route('infrastruktur.combat.show', $c))
            ->editColumn('source_details', fn (CombatSite $c) => CombatSourceDetails::flattened($c->source_details))
            ->editColumn('status_dokumen', fn (CombatSite $c) => Str::limit((string) $c->status_dokumen, 50))
            ->rawColumns($isAdmin ? ['aksi'] : [])
            ->toJson();
    }

    /** Data popup untuk satu baris Combat, termasuk riwayat tahun dari site yang sama. */
    public function show(CombatSite $combat): JsonResponse
    {
        $site = $combat->toArray();
        $site['detail_url'] = route('infrastruktur.combat.show', $combat);
        $site['source_details'] = CombatSourceDetails::flattened($combat->source_details);
        if (str_ends_with((string) $combat->status_dokumen, '…')) {
            $site['status_dokumen'] = $site['source_details']['status_dokumen'] ?? $combat->status_dokumen;
        }
        $site['lease_duration'] = InfrastructureMetrics::leaseDurationLabel($combat);
        $site['pks_status_label'] = InfrastructureMetrics::pksStatusLabel($combat);
        $site['process_started_at'] = InfrastructureMetrics::processStartDate($combat)?->toDateString();
        $site['process_aging_days'] = InfrastructureMetrics::processAgingDays($combat);
        $site['performance'] = InfrastructureMetrics::sitePerformance($combat);
        if (auth()->user()->hasRole('admin')) {
            $site['edit_url'] = route('infrastruktur.combat.edit', $combat);
        }

        $history = CombatSite::query()
            ->where('site_code', $combat->site_code)
            ->orderByDesc('tahun_justi_dirnet')
            ->orderByDesc('id')
            ->get(['id', 'site_code', 'site_name', 'tahun_justi_dirnet', 'status_dokumen', 'status_perpanjangan', 'source_details'])
            ->map(function (CombatSite $row): array {
                $details = CombatSourceDetails::flattened($row->source_details);

                return [
                    'id' => $row->id,
                    'site_name' => $row->site_name,
                    'year' => $row->tahun_justi_dirnet,
                    'status_dokumen' => str_ends_with((string) $row->status_dokumen, '…')
                        ? ($details['status_dokumen'] ?? $row->status_dokumen)
                        : $row->status_dokumen,
                    'status_perpanjangan' => $row->status_perpanjangan,
                    'status_justifikasi' => $details['status_justifikasi'] ?? $details['justifikasi'] ?? null,
                ];
            });

        return response()->json(['site' => $site, 'history' => $history]);
    }

    private function applyDashboardFilter($query, Request $request): void
    {
        $field = (string) $request->input('filter_field');
        $value = (string) $request->input('filter_value');
        if ($field === 'tahun') {
            $value === 'Tidak Diisi'
                ? $query->whereNull('tahun_justi_dirnet')
                : $query->where('tahun_justi_dirnet', (int) $value);
        } elseif ($field === 'summary_status') {
            $query->where(function ($subQuery) use ($value): void {
                $status = 'LOWER('.$this->sourceValueExpression('status').')';
                if ($value === 'active') {
                    $subQuery->whereRaw("{$status} REGEXP 'on[[:space:]]*air|active|operational'")
                        ->whereRaw("{$status} NOT REGEXP 'off[[:space:]]*air|non.?operational|non.?aktif|dismantle|unlock|relokasi|migrasi'");
                } elseif ($value === 'contract') {
                    $status = "LOWER(CONCAT(COALESCE(status_dokumen, ''), ' ', COALESCE(status_perpanjangan, '')))";
                    $subQuery->whereRaw("{$status} REGEXP 'nego|pending|belum|proses|legal|perpanjang|finalisasi'");
                } elseif ($value === 'risk') {
                    $status = "LOWER(CONCAT(COALESCE(status_dokumen, ''), ' ', COALESCE(status_perpanjangan, '')))";
                    $subQuery->whereRaw("{$status} REGEXP 'nego|pending|belum|proses|legal|perpanjang|finalisasi'")
                        ->orWhere(function ($withoutPks): void {
                            $withoutPks->where(function ($empty): void { $empty->whereNull('no_pks_baru')->orWhere('no_pks_baru', ''); })
                                ->where(function ($empty): void { $empty->whereNull('no_pks_lama')->orWhere('no_pks_lama', ''); });
                        });
                } elseif ($value === 'off_air') {
                    $subQuery->whereRaw("{$status} REGEXP 'off[[:space:]]*air|non.?operational|non.?aktif|dismantle|unlock|relokasi|migrasi'");
                } elseif ($value === 'without_pks') {
                    $subQuery->whereRaw("NULLIF(TRIM(no_pks_baru), '') IS NULL")
                        ->whereRaw("NULLIF(TRIM(no_pks_lama), '') IS NULL");
                }
            });
        } elseif ($field === 'status_perpanjangan') {
            $value === 'Tidak Diisi'
                ? $query->where(function ($subQuery): void { $subQuery->whereNull('status_perpanjangan')->orWhere('status_perpanjangan', ''); })
                : $query->where('status_perpanjangan', $value);
        } elseif ($field === 'status_masa_sewa') {
            $this->applyLeaseStatusFilter($query, $value);
        } elseif ($field === 'status') {
            $status = 'LOWER('.$this->sourceValueExpression('status').')';
            if ($value === InfrastructureMetrics::ACTIVE) {
                $query->whereRaw("{$status} REGEXP 'on[[:space:]]*air|active|operational'")
                    ->whereRaw("{$status} NOT REGEXP 'off[[:space:]]*air|non.?operational|non.?aktif|dismantle|unlock|relokasi|migrasi'");
            } elseif ($value === InfrastructureMetrics::OFF_AIR) {
                $query->whereRaw("{$status} REGEXP 'off[[:space:]]*air|non.?operational|non.?aktif|dismantle|unlock|relokasi|migrasi'");
            } elseif ($value === InfrastructureMetrics::UNKNOWN_STATUS || $value === 'Tidak Diisi') {
                $query->whereRaw("{$status} = ''");
            } else {
                $query->whereRaw("{$status} = ?", [mb_strtolower(trim($value), 'UTF-8')]);
            }
        } elseif ($field === 'status_dokumen') {
            $value === 'Tidak Diisi'
                ? $query->where(function ($subQuery): void { $subQuery->whereNull('status_dokumen')->orWhere('status_dokumen', ''); })
                : $query->where('status_dokumen', $value);
        } elseif ($field === 'site_code') {
            $query->where('site_code', $value);
        } elseif ($field === 'pipeline') {
            $this->applyPipelineFilter($query, $value);
        } elseif ($field === 'geography') {
            $this->applyGeographyFilter($query, $value);
        } elseif ($field === 'priority') {
            $this->applyPriorityFilter($query);
        } elseif ($field === 'lease_alert') {
            $this->applyLeaseAlertFilter($query);
        } elseif ($field === 'lease_window') {
            $endDate = 'COALESCE(end_date_baru, end_date_lama)';
            $query->whereRaw("{$endDate} BETWEEN ? AND ?", [today()->toDateString(), today()->addDays(180)->toDateString()]);
        } elseif ($field !== '' && $value !== '' && in_array($field, ['pks_status', 'nop', 'vendor', 'ownership'], true)) {
            if ($field === 'vendor') {
                $vendor = $this->sourceValueExpression('vendor', 'tp');
                $value === 'Tidak Diisi'
                    ? $query->whereRaw("{$vendor} = ''")
                    : $query->whereRaw('LOWER('.$vendor.') = ?', [mb_strtolower(trim($value), 'UTF-8')]);
            } elseif ($field === 'ownership') {
                InfrastructureOwnership::applyBucketFilter($query, $value);
            } elseif ($field === 'pks_status') {
                if ($value === 'Ada PKS') {
                    $query->where(function ($subQuery): void {
                        $subQuery->where(function ($filled): void {
                            $filled->whereNotNull('no_pks_baru')->where('no_pks_baru', '<>', '');
                        })->orWhere(function ($filled): void {
                            $filled->whereNotNull('no_pks_lama')->where('no_pks_lama', '<>', '');
                        });
                    });
                } elseif ($value === 'Tanpa PKS') {
                    $query->where(function ($empty): void { $empty->whereNull('no_pks_baru')->orWhere('no_pks_baru', ''); })
                        ->where(function ($empty): void { $empty->whereNull('no_pks_lama')->orWhere('no_pks_lama', ''); });
                } elseif ($value === 'Tidak Diisi') {
                    $pksStatus = $this->sourceValueExpression('pks_status');
                    $query->whereRaw("{$pksStatus} = ''")
                        ->where(function ($empty): void {
                            $empty->whereNull('status_dokumen')->orWhere('status_dokumen', '');
                        });
                } else {
                    $query->where(function ($subQuery) use ($value): void {
                        $pksStatus = $this->sourceValueExpression('pks_status');
                        $subQuery->whereRaw('LOWER('.$pksStatus.') = ?', [mb_strtolower(trim($value), 'UTF-8')])
                            ->orWhere('status_dokumen', $value);
                    });
                }
            } elseif ($field === 'nop') {
                $nop = $this->normalizedNopExpression();
                if ($value === 'Tidak Diisi') {
                    $query->whereRaw("{$nop} = ''");
                } else {
                    $needle = preg_replace('/^NOP[\s-]*/i', '', $value) ?? $value;
                    $query->whereRaw("{$nop} = ?", [mb_strtoupper(trim($needle), 'UTF-8')]);
                }
            } else {
                $query->whereJsonContains("source_details->{$field}", $value);
            }
        }
    }

    private function applyPipelineFilter($query, string $value): void
    {
        $stage = "LOWER(COALESCE(NULLIF(status_perpanjangan, ''), NULLIF(status_dokumen, ''), ''))";

        match ($value) {
            'Paid' => $query->whereRaw("{$stage} REGEXP 'paid|bayar'"),
            'Drop' => $query->whereRaw("{$stage} REGEXP 'drop|dismantle|relokasi'"),
            'Negosiasi' => $query->whereRaw("{$stage} REGEXP 'negos'"),
            'BAK' => $query->whereRaw("{$stage} REGEXP 'bak'"),
            'Pending PKS' => $query->whereRaw("{$stage} REGEXP 'pending.*pks|pks.*pending'"),
            'PKS' => $query->whereRaw("{$stage} REGEXP 'pks|legal'")
                ->whereRaw("{$stage} NOT REGEXP 'pending.*pks|pks.*pending'"),
            'Budget' => $query->whereRaw("{$stage} REGEXP 'budget'"),
            'Finance' => $query->whereRaw("{$stage} REGEXP 'financ'"),
            'Tidak Diisi' => $query->whereRaw("{$stage} = ''"),
            default => $query->whereRaw("{$stage} <> ''")
                ->whereRaw("{$stage} NOT REGEXP 'paid|bayar|drop|dismantle|relokasi|negos|bak|pks|legal|budget|financ'"),
        };
    }

    private function applyGeographyFilter($query, string $value): void
    {
        $query->where(function ($location) use ($value): void {
            foreach (['kabupaten', 'city', 'kota', 'area', 'wilayah'] as $key) {
                $location->orWhereRaw('LOWER('.$this->sourceValueExpression($key).') = ?', [mb_strtolower(trim($value), 'UTF-8')]);
            }
        });
    }

    /**
     * Existing Combat snapshots retain the original DATABASE and
     * DATABASE_REVENUE sheets under JSON keys.  Resolve those alongside the
     * legacy flat snapshot so a chart filter always sees the same field as
     * the dashboard.
     */
    private function sourceValueExpression(string ...$keys): string
    {
        $expressions = [];

        foreach ($keys as $key) {
            if (! preg_match('/^[a-z0-9_]+$/', $key)) {
                throw new \InvalidArgumentException('Invalid Combat source field.');
            }

            foreach (["$.{$key}", "$.database.{$key}", "$.database_revenue.{$key}"] as $path) {
                $expressions[] = "NULLIF(NULLIF(NULLIF(TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(source_details, '{$path}')), '')), ''), 'null'), '-')";
            }
        }

        return 'COALESCE('.implode(', ', $expressions).", '')";
    }

    private function normalizedNopExpression(): string
    {
        $nop = $this->sourceValueExpression('nop');

        return "UPPER(TRIM(REGEXP_REPLACE({$nop}, '^NOP[[:space:]-]*', '')))";
    }

    private function applyPriorityFilter($query): void
    {
        $attention = "LOWER(CONCAT(COALESCE(status_dokumen, ''), ' ', COALESCE(status_perpanjangan, '')))";
        $endDate = 'COALESCE(end_date_baru, end_date_lama)';

        $query->where(function ($priority) use ($attention, $endDate): void {
            $priority->whereRaw("{$attention} REGEXP 'nego|pending|belum|proses|legal|perpanjang|finalisasi'")
                ->orWhere(function ($withoutPks): void {
                    $withoutPks->where(function ($empty): void { $empty->whereNull('no_pks_baru')->orWhere('no_pks_baru', ''); })
                        ->where(function ($empty): void { $empty->whereNull('no_pks_lama')->orWhere('no_pks_lama', ''); });
                })
                ->orWhereNull('end_date_baru')->whereNull('end_date_lama')
                ->orWhereRaw("{$endDate} <= ?", [today()->addDays(180)->toDateString()]);
        });
    }

    private function applyLeaseAlertFilter($query): void
    {
        $endDate = 'COALESCE(end_date_baru, end_date_lama)';

        $query->where(function ($alert) use ($endDate): void {
            $alert->where(function ($missing): void {
                $missing->whereNull('end_date_baru')->whereNull('end_date_lama');
            })->orWhereRaw("{$endDate} <= ?", [today()->addDays(180)->toDateString()]);
        });
    }

    private function applyLeaseStatusFilter($query, string $value): void
    {
        $endDate = 'COALESCE(end_date_baru, end_date_lama)';
        $today = today()->toDateString();

        match ($value) {
            LeaseStatus::NO_END_DATE => $query->whereNull('end_date_baru')->whereNull('end_date_lama'),
            LeaseStatus::EXPIRED => $query->whereRaw("{$endDate} < ?", [$today]),
            LeaseStatus::WITHIN_90_DAYS => $query->whereRaw("{$endDate} BETWEEN ? AND ?", [$today, today()->addDays(90)->toDateString()]),
            LeaseStatus::WITHIN_180_DAYS => $query->whereRaw("{$endDate} BETWEEN ? AND ?", [today()->addDays(91)->toDateString(), today()->addDays(180)->toDateString()]),
            LeaseStatus::SAFE => $query->whereRaw("{$endDate} > ?", [today()->addDays(180)->toDateString()]),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Form edit Combat site.
     */
    public function edit(Request $request, CombatSite $combat): View
    {
        if ($request->boolean('modal')) {
            return view('infrastruktur.combat.partials.edit-modal', compact('combat'));
        }

        return view('infrastruktur.combat.edit', compact('combat'));
    }

    /**
     * Simpan perubahan Combat site.
     */
    public function update(UpdateCombatSiteRequest $request, CombatSite $combat): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $data['update_by'] = auth()->user()->name;
        $data['tanggal']   = now();

        $combat->update($data);

        if ($request->expectsJson()) {
            return response()->json(['message' => "Combat site {$combat->site_code} berhasil diperbarui."]);
        }

        return redirect()
            ->route('infrastruktur.combat.index')
            ->with('success', "Combat site {$combat->site_code} berhasil diperbarui.");
    }

    /**
     * Soft delete Combat site.
     */
    public function destroy(CombatSite $combat): RedirectResponse
    {
        $siteCode = $combat->site_code;
        $combat->delete();

        return redirect()
            ->route('infrastruktur.combat.index')
            ->with('success', "Combat site {$siteCode} berhasil dihapus.");
    }

    /**
     * Download Excel — filter sesuai tahun aktif.
     */
    public function exportExcel(Request $request)
    {
        $tahun = $request->filled('tahun') && $request->tahun !== 'all'
            ? (int) $request->tahun
            : null;

        $filename = 'combat_sites' . ($tahun ? "_{$tahun}" : '_all') . '.xlsx';

        return (new CombatSiteExport($tahun))->download($filename);
    }
}
