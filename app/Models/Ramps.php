<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_RAMPS
 * @property int $ID_EQUIP
 * @property mixed $POSITION
 * @property-read EQUIPMENT $eQUIPMENT
 */
class Ramps extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['ID_RAMPS', 'ID_EQUIP', 'POSITION'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_RAMPS';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function eQUIPMENT()
    {
        return $this->belongsTo('EQUIPMENT', 'ID_EQUIP', 'ID_EQUIP');
    }
}
