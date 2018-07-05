<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_EQUIPGENERATION
 * @property int $ID_EQUIP
 * @property int $ID_ORIG_EQUIP1
 * @property int $ID_ORIG_EQUIP2
 * @property mixed $AVG_PRODINTEMP
 * @property mixed $TEMP_SETPOINT
 * @property mixed $DWELLING_TIME
 * @property mixed $MOVING_CHANGE
 * @property mixed $MOVING_POS
 * @property mixed $ROTATE
 * @property mixed $POS_CHANGE
 * @property mixed $NEW_POS
 * @property int $EQP_GEN_STATUS
 * @property mixed $EQP_GEN_LOADRATE
 * @property-read EQUIPMENT $eQUIPMENT
 */
class EquipGeneration extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'equip_generation';

    /**
     * @var array
     */
    protected $fillable = ['ID_EQUIPGENERATION', 'ID_EQUIP', 'ID_ORIG_EQUIP1', 'ID_ORIG_EQUIP2', 'AVG_PRODINTEMP', 'TEMP_SETPOINT', 'DWELLING_TIME', 'MOVING_CHANGE', 'MOVING_POS', 'ROTATE', 'POS_CHANGE', 'NEW_POS', 'EQP_GEN_STATUS', 'EQP_GEN_LOADRATE'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_EQUIPGENERATION';

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
        return $this->belongsTo('App\\Models\\Equipment', 'ID_EQUIP', 'ID_EQUIP');
    }
}
