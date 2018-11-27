<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_CONSUMPTIONS
 * @property int $ID_EQUIP
 * @property mixed $TEMPERATURE
 * @property mixed $CONSUMPTION_PERM
 * @property mixed $CONSUMPTION_GETCOLD
 * @property-read Equipment $equipment
 */
class Consumptions extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'CONSUMPTIONS';
    /**
     * @var array
     */
    protected $fillable = ['ID_CONSUMPTIONS', 'ID_EQUIP', 'TEMPERATURE', 'CONSUMPTION_PERM', 'CONSUMPTION_GETCOLD'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_CONSUMPTIONS';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function equipment()
    {
        return $this->belongsTo('Equipment', 'ID_EQUIP', 'ID_EQUIP');
    }
}
