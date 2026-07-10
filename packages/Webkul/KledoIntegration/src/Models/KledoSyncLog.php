<?php

namespace Webkul\KledoIntegration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Sales\Models\Order;

class KledoSyncLog extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'kledo_sync_logs';

    /**
     * Mass-assignable attributes.
     *
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'status',
        'response_body',
    ];

    /**
     * The order this log entry belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
