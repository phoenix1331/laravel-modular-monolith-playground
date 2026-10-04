<?php

declare(strict_types=1);

namespace Modules\KitShop\Models;

use App\Audit\Auditable;
use App\Audit\AuditAction;
use App\Audit\AuditObserver;
use App\Models\Player;
use App\Tenancy\BelongsToOrganisation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organisation_id
 * @property int $player_id
 * @property int $product_id
 * @property string $size
 * @property int $quantity
 * @property int $total_pence
 * @property CarbonImmutable|null $fulfilled_at
 * @property int|null $ordered_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Player $player
 * @property-read Product $product
 */
#[Table('kit_shop_orders')]
#[Fillable(['player_id', 'product_id', 'size', 'quantity', 'total_pence', 'ordered_by'])]
#[ObservedBy(AuditObserver::class)]
class Order extends Model implements Auditable
{
    use BelongsToOrganisation;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['fulfilled_at' => 'immutable_datetime', 'quantity' => 'integer', 'total_pence' => 'integer'];
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function auditType(): string
    {
        return 'kit-shop.order';
    }

    public function auditActions(): array
    {
        return [AuditAction::Create, AuditAction::Update];
    }
}
