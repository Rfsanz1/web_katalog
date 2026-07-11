<?php

namespace Webkul\KledoIntegration\Models;

use Illuminate\Database\Eloquent\Model;

class KledoPaymentMapping extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'kledo_payment_mappings';

    /**
     * Mass-assignable attributes.
     *
     * @var array<string>
     */
    protected $fillable = [
        'payment_method_code',
        'finance_account_id',
    ];
}
