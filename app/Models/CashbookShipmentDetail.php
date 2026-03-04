<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CashbookShipmentDetail extends Model
{
    use HasUuids;

    protected $guarded = ['id'];

    protected $casts = [
        'date'              => 'date',
        'import_duty'       => 'decimal:2',
        'feeding'           => 'decimal:2',
        'internal_travels'  => 'decimal:2',
        'loading_charges'   => 'decimal:2',
        'accra_delivery'    => 'decimal:2',
        'port_to_warehouse' => 'decimal:2',
        'accra_to_kumasi'   => 'decimal:2',
        'kumasi_delivery'   => 'decimal:2',
        'stationaries'      => 'decimal:2',
        'port_entry'        => 'decimal:2',
        'communication'     => 'decimal:2',
        'replacement'       => 'decimal:2',
        'police'            => 'decimal:2',
        'gas'               => 'decimal:2',
        'total'             => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (CashbookShipmentDetail $detail) {
            $detail->total = round(
                (float) $detail->import_duty +
                (float) $detail->feeding +
                (float) $detail->internal_travels +
                (float) $detail->loading_charges +
                (float) $detail->accra_delivery +
                (float) $detail->port_to_warehouse +
                (float) $detail->accra_to_kumasi +
                (float) $detail->kumasi_delivery +
                (float) $detail->stationaries +
                (float) $detail->port_entry +
                (float) $detail->communication +
                (float) $detail->replacement +
                (float) $detail->police +
                (float) $detail->gas,
                2
            );
        });
    }
}
