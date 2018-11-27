<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_EQUIPCHARAC
 * @property int $ID_EQUIP
 * @property mixed $X_POSITION
 * @property mixed $TEMP_REGUL
 * @property mixed $ALPHA_TOP
 * @property mixed $ALPHA_BOTTOM
 * @property mixed $ALPHA_LEFT
 * @property mixed $ALPHA_RIGHT
 * @property mixed $ALPHA_FRONT
 * @property mixed $ALPHA_REAR
 * @property mixed $TEMP_TOP
 * @property mixed $TEMP_BOTTOM
 * @property mixed $TEMP_LEFT
 * @property mixed $TEMP_RIGHT
 * @property mixed $TEMP_FRONT
 * @property mixed $TEMP_REAR
 * @property-read EQUIPMENT $eQUIPMENT
 */
class EquipCharact extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'EQUIPCHARACT';

    /**
     * @var array
     */
    protected $fillable = ['ID_EQUIPCHARAC', 'ID_EQUIP', 'X_POSITION', 'TEMP_REGUL', 'ALPHA_TOP', 'ALPHA_BOTTOM', 'ALPHA_LEFT', 'ALPHA_RIGHT', 'ALPHA_FRONT', 'ALPHA_REAR', 'TEMP_TOP', 'TEMP_BOTTOM', 'TEMP_LEFT', 'TEMP_RIGHT', 'TEMP_FRONT', 'TEMP_REAR'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_EQUIPCHARAC';

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
