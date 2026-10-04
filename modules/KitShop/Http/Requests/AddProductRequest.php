<?php

declare(strict_types=1);

namespace Modules\KitShop\Http\Requests;

use App\Tenancy\CurrentOrganisation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `stock` is typed as "size=quantity" pairs, e.g. "YS=10, YM=12, S=8".
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('kit_shop_products')->where('organisation_id', app(CurrentOrganisation::class)->id())],
            'price' => ['required', 'numeric', 'min:0.01', 'max:1000'],
            'stock' => ['required', 'string', 'regex:/^\s*[A-Za-z0-9]{1,8}\s*=\s*\d{1,4}\s*(,\s*[A-Za-z0-9]{1,8}\s*=\s*\d{1,4}\s*)*$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['stock.regex' => 'Write stock as size=quantity pairs, e.g. S=10, M=12.'];
    }

    public function pricePence(): int
    {
        return (int) round((float) $this->input('price') * 100);
    }

    /**
     * @return array<string, int>
     */
    public function stockBySize(): array
    {
        $stock = [];

        foreach (explode(',', (string) $this->input('stock')) as $pair) {
            [$size, $quantity] = array_map(trim(...), explode('=', $pair));
            $stock[strtoupper($size)] = (int) $quantity;
        }

        return $stock;
    }
}
