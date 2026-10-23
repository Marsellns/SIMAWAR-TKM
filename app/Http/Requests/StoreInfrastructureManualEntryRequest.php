<?php

namespace App\Http\Requests;

use App\Support\InfrastructureManualEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInfrastructureManualEntryRequest extends FormRequest
{
    protected $errorBag = 'infrastructureManual';

    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('site_code'))) {
            $this->merge(['site_code' => strtoupper(trim($this->input('site_code')))]);
        }
    }

    public function rules(): array
    {
        $dataset = (string) $this->route('dataset');
        abort_unless(isset(InfrastructureManualEntry::DATASETS[$dataset]), 404);
        $rules = match ($dataset) {
            'sewa-lahan' => (new UpdateSewaLahanRenewalRequest)->rules(),
            'combat' => (new UpdateCombatSiteRequest)->rules(),
            'bapss' => [
                'site_code' => ['required', 'string', 'max:30'], 'site_name' => ['nullable', 'string', 'max:255'],
                'tgl_bapss' => ['nullable', 'date_format:Y-m-d'], 'tgl_dismantle' => ['nullable', 'date_format:Y-m-d'],
                'remark' => ['nullable', 'string', 'max:500'],
                'pdf_bapss_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
                'pdf_ba_dismantle_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            ],
            default => $this->fieldRules(InfrastructureManualEntry::fields($dataset)),
        };
        foreach ($rules as $key => &$fieldRules) {
            if (in_array('numeric', $fieldRules, true)) {
                $fieldRules[] = 'max:9999999999999999.99';
                $fieldRules[] = 'decimal:0,2';
                $fieldRules[] = 'regex:/^\d{1,16}(\.\d{1,2})?$/';
            }
            if ($key === 'keterangan') {
                $fieldRules[] = 'max:10000';
            }
            // An end date can be known even when its start date is unknown.
            $fieldRules = array_values(array_filter($fieldRules, fn ($rule) => ! is_string($rule) || ! str_starts_with($rule, 'after_or_equal:') || $this->filled(substr($rule, 15))));
        }
        unset($fieldRules);
        foreach (['end_date' => 'start_date', 'termin_end' => 'termin_start', 'tanggal_berakhir' => 'tanggal_mulai'] as $end => $start) {
            if (isset($rules[$end]) && $this->filled($start)) {
                $rules[$end][] = 'after_or_equal:'.$start;
            }
        }
        if (in_array($dataset, ['sewa-lahan', 'combat'], true)) {
            $fields = InfrastructureManualEntry::sourceFields($dataset);
            $rules['source_details'] = ['nullable', 'array:'.implode(',', array_keys($fields))];
            $rules += $this->fieldRules($fields, 'source_details.');
            $rules['source_details.latitude'][] = 'required_with:source_details.longitude';
            $rules['source_details.longitude'][] = 'required_with:source_details.latitude';
            $rules['performance_year'] = ['nullable', 'integer', 'between:2000,2100'];
            $rules['performance'] = ['nullable', 'array:'.implode(',', array_keys(InfrastructureManualEntry::MONTHS))];
            foreach (InfrastructureManualEntry::MONTHS as $month => $label) {
                $rules['performance.'.$month] = ['nullable', 'array:revenue,cost'];
                foreach (['revenue', 'cost'] as $metric) {
                    $rules["performance.{$month}.{$metric}"] = ['nullable', 'numeric', 'min:0', 'max:9999999999999999.99', 'decimal:0,2', 'regex:/^\d{1,16}(\.\d{1,2})?$/'];
                }
            }
        }

        return $rules;
    }

    private function fieldRules(array $fields, string $prefix = ''): array
    {
        $rules = [];
        foreach ($fields as $key => [$label, $type, $length]) {
            $rules[$prefix.$key] = match ($type) {
                'date' => ['nullable', 'date_format:Y-m-d'],
                'number' => ['nullable', 'numeric', 'min:0'],
                'coordinate' => ['nullable', 'numeric', $key === 'latitude' ? 'between:-90,90' : 'between:-180,180'],
                'integer' => ['nullable', 'integer', 'min:0', 'max:100000'],
                'year' => ['nullable', 'integer', 'between:2000,2100'],
                'checkbox' => ['nullable', 'boolean'],
                'ownership' => ['nullable', Rule::in(['Telkomsel', 'TP'])],
                default => [$key === 'site_code' ? 'required' : 'nullable', 'string', 'max:'.$length],
            };
        }

        return $rules;
    }

    protected function getRedirectUrl(): string
    {
        return route('infrastruktur.'.$this->route('dataset').'.index');
    }
}
