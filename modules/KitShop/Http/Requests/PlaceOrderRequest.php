<?php

declare(strict_types=1);

namespace Modules\KitShop\Http\Requests;

use App\Tenancy\CurrentOrganisation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
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
        $organisationId = app(CurrentOrganisation::class)->id();

        return [
            'player_id' => ['required', 'integer', Rule::exists('players', 'id')->where('organisation_id', $organisationId)],
            'product_id' => ['required', 'integer', Rule::exists('kit_shop_products', 'id')->where('organisation_id', $organisationId)],
            'size' => ['required', 'string', 'max:8'],
            'quantity' => ['required', 'integer', 'between:1,10'],
        ];
    }
}
