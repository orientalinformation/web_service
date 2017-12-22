<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_MONETARY_CURRENCY
 * @property string $MONEY_TEXT
 * @property string $MONEY_SYMB
 */
class MonetaryCurrency extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'monetary_currency';

    /**
     * @var array
     */
    protected $fillable = ['ID_MONETARY_CURRENCY', 'MONEY_TEXT', 'MONEY_SYMB'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_MONETARY_CURRENCY';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
