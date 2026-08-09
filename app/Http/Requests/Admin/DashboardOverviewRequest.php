<?php

namespace App\Http\Requests\Admin;

use App\Support\AdminPermissions;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardOverviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(AdminPermissions::permission('dashboard', 'view'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'range' => ['nullable', 'string', Rule::in(['7d', '30d', '90d', '12m', 'custom'])],
            'start_date' => ['nullable', 'date', 'required_if:range,custom', 'before_or_equal:end_date'],
            'end_date' => ['nullable', 'date', 'required_if:range,custom', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array{range: string, start_date: string|null, end_date: string|null}
     */
    public function filters(): array
    {
        $validated = $this->validated();
        $range = $validated['range'] ?? '30d';
        $startDate = $validated['start_date'] ?? null;
        $endDate = $validated['end_date'] ?? null;

        return [
            'range' => is_scalar($range) ? (string) $range : '30d',
            'start_date' => is_scalar($startDate) ? (string) $startDate : null,
            'end_date' => is_scalar($endDate) ? (string) $endDate : null,
        ];
    }
}
