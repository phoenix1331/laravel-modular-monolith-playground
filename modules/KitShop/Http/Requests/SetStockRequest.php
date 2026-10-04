<?php

declare(strict_types=1);

namespace Modules\KitShop\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SetStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'size' => ['required', 'string', 'max:8'],
            'quantity' => ['required', 'integer', 'between:0,9999'],
        ];
    }
}
