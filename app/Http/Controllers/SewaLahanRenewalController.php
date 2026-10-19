<?php

namespace App\Http\Controllers;

use App\Exports\SewaLahanRenewalExport;
use App\Http\Requests\UpdateSewaLahanRenewalRequest;
use App\Models\SewaLahanRenewal;
use App\Support\InfrastructureMetrics;
use App\Support\InfrastructureCanonicalSites;
use App\Support\InfrastructureOwnership;
use App\Support\LeaseStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SewaLahanRenewalController extends Controller
{
    /**
     * Halaman utama modul Sewa Lahan (Renewal): tabel DataTables server-side,
     * filter tahun, search granular per kolom, dan expand detail inline.
     */
    public function index(): View
    {
        $tahunList = SewaLahanRenewal::query()
            ->whereNotNull('tahun_renewal')
            ->distinct()
            ->orderByDesc('tahun_renewal')
            ->pluck('tahun_renewal');

        return view('infrastruktur.sewa-lahan.index', compact('tahunList'));
    }

    /**
     * Sumber data DataTables (server-side processing).
     * Mendukung filter tahun + search granular per kolom (LIKE dari Yajra).
     */
    public function data(Request $request): JsonResponse
    {
        $isAdmin = auth()->user()->hasRole('admin');

        $query = SewaLahanRenewal::query()->latest('id');

        $ownershipScope = (string) $request->input('ownership_scope');
        $validOwnershipScope = in_array($ownershipScope, ['Telkomsel', 'TP'], true);
        $hasOwnershipScope = $validOwnershipScope
            && ! ($request->input('filter_field') === 'ownership' && $request->input('filter_value') === $ownershipScope);

        if ($request->boolean('unique_sites')) {
            // Select the representative row within the requested year, so
            // the filtered chart and its table drill-down count the same sites.
            $selection = SewaLahanRenewal::query();
            if ($validOwnershipScope) {
                $this->applyOwnershipFilter($selection, $ownershipScope);
            }
            if ($request->filled('tahun') && $request->tahun !== 'all') {
                $selection->where('tahun_renewal', (int) $request->tahun);
            }
            $ids = InfrastructureCanonicalSites::fromRows($selection->get())->pluck('id');
            $query->whereIn('sewa_lahan_renewals.id', $ids);
        }

        // Filter tahun renewal
        if ($request->filled('tahun') && $request->tahun !== 'all') {
            $query->where('tahun_renewal', (int) $request->tahun);
        }
        $this->applyDashboardFilter($query, $request);
        if ($hasOwnershipScope) {
            $this->applyOwnershipFilter($query, $ownershipScope);
        }

        $dataTable = DataTables::of($query);

        if ($isAdmin) {
            $dataTable->addColumn(
                'aksi',
                fn (SewaLahanRenewal $s) => view('infrastruktur.sewa-lahan.partials.aksi', ['sewaLahan' => $s])->render()
            );
        }

        return $dataTable
            ->addIndexColumn()
            ->addColumn('lease_duration', fn (SewaLahanRenewal $s) => InfrastructureMetrics::leaseDurationLabel($s))
            ->addColumn('process_started_at', fn (SewaLahanRenewal $s) => InfrastructureMetrics::processStartDate($s)?->toDateString())
            ->addColumn('process_aging_days', fn (SewaLahanRenewal $s) => InfrastructureMetrics::processAgingDays($s))
            ->addColumn('performance', fn (SewaLahanRenewal $s) => InfrastructureMetrics::sitePerformance($s))
            ->rawColumns($isAdmin ? ['aksi'] : [])
            ->toJson();
    }

    private function applyDashboardFilter($query, Request $request): void
    {
        $field = (string) $request->input('filter_field');
        $value = (string) $request->input('filter_value');
        if ($field === 'tahun') {
            $value === 'Tidak Diisi'
                ? $query->whereNull('tahun_renewal')
                : $query->where('tahun_renewal', (int) $value);
        } elseif ($field === 'summary_status') {
            $query->where(function ($subQuery) use ($value): void {
                $status = "LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(source_details, '$.status')), ''))";
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
            $status = "LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(source_details, '$.status')), ''))";
            if ($value === InfrastructureMetrics::ACTIVE) {
                $query->whereRaw("{$status} REGEXP 'on[[:space:]]*air|active|operational'")
                    ->whereRaw("{$status} NOT REGEXP 'off[[:space:]]*air|non.?operational|non.?aktif|dismantle|unlock|relokasi|migrasi'");
            } elseif ($value === InfrastructureMetrics::OFF_AIR) {
                $query->whereRaw("{$status} REGEXP 'off[[:space:]]*air|non.?operational|non.?aktif|dismantle|unlock|relokasi|migrasi'");
            } elseif ($value === InfrastructureMetrics::UNKNOWN_STATUS || $value === 'Tidak Diisi') {
                $query->where(function ($subQuery): void {
                    $subQuery->where(function ($empty): void {
                        $empty->whereNull('source_details->status')->orWhereJsonContains('source_details->status', '');
                    });
                });
            } else {
                $query->whereJsonContains('source_details->status', $value);
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
                $query->where(function ($subQuery) use ($value): void {
                    if ($value === 'Tidak Diisi') {
                        $subQuery->where(function ($empty): void {
                            $empty->whereNull('source_details->vendor')->orWhereJsonContains('source_details->vendor', '');
                        })->where(function ($empty): void {
                            $empty->whereNull('source_details->tp')->orWhereJsonContains('source_details->tp', '');
                        });
                    } else {
                        $subQuery->whereJsonContains('source_details->vendor', $value)
                            ->orWhereJsonContains('source_details->tp', $value);
                    }
                });
            } elseif ($field === 'ownership') {
                $this->applyOwnershipFilter($query, $value);
            } else {
                if ($field === 'pks_status' && $value === 'Tidak Diisi') {
                    $query->where(function ($subQuery): void {
                        $subQuery->where(function ($empty): void {
                            $empty->whereNull('source_details->pks_status')->orWhereJsonContains('source_details->pks_status', '');
                        })->where(function ($empty): void {
                            $empty->whereNull('status_dokumen')->orWhere('status_dokumen', '');
                        });
                    });
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
                    } else {
                        $query->where(function ($subQuery) use ($value): void {
                            $subQuery->whereJsonContains('source_details->pks_status', $value)
                                ->orWhere('status_dokumen', $value);
                        });
                    }
                } elseif ($field === 'nop' && $value === 'Tidak Diisi') {
                    $query->where(function ($subQuery): void {
                        $subQuery->whereNull('source_details->nop')->orWhereJsonContains('source_details->nop', '');
                    });
                } else {
                    $query->whereJsonContains("source_details->{$field}", $value);
                }
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
                $location->orWhereRaw("TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(source_details, '$.{$key}')), '')) = ?", [$value]);
            }
        });
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

    private function applyOwnershipFilter($query, string $value): void
    {
        InfrastructureOwnership::applyBucketFilter($query, $value);
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
     * Form edit Sewa Lahan renewal.
     */
    public function edit(SewaLahanRenewal $sewaLahan): View
    {
        return view('infrastruktur.sewa-lahan.edit', compact('sewaLahan'));
    }

    /**
     * Simpan perubahan Sewa Lahan renewal.
     */
    public function update(UpdateSewaLahanRenewalRequest $request, SewaLahanRenewal $sewaLahan): RedirectResponse
    {
        $data = $request->validated();
        $data['update_by']  = auth()->user()->name;
        $data['tgl_update'] = now();

        $sewaLahan->update($data);

        return redirect()
            ->route('infrastruktur.sewa-lahan.index')
            ->with('success', "Sewa Lahan site {$sewaLahan->site_code} berhasil diperbarui.");
    }

    /**
     * Soft delete Sewa Lahan renewal.
     */
    public function destroy(SewaLahanRenewal $sewaLahan): RedirectResponse
    {
        $siteCode = $sewaLahan->site_code;
        $sewaLahan->delete();

        return redirect()
            ->route('infrastruktur.sewa-lahan.index')
            ->with('success', "Sewa Lahan site {$siteCode} berhasil dihapus.");
    }

    /**
     * Download Excel — seluruh data atau sesuai filter tahun aktif.
     */
    public function exportExcel(Request $request)
    {
        $tahun = $request->filled('tahun') && $request->tahun !== 'all'
            ? (int) $request->tahun
            : null;

        $filename = 'sewa_lahan_renewals' . ($tahun ? "_{$tahun}" : '_all') . '.xlsx';

        return (new SewaLahanRenewalExport($tahun))->download($filename);
    }

    /**
     * Cetak SIP — generate PDF dari data baris (template sederhana,
     * akan disempurnakan kemudian).
     */
    public function cetakSip(SewaLahanRenewal $sewaLahan)
    {
        $pdf = Pdf::loadView('infrastruktur.sewa-lahan.sip', compact('sewaLahan'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('SIP_' . $sewaLahan->site_code . '.pdf');
    }
}
