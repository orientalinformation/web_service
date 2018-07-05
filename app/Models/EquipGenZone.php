<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_EQUIP_GEN_ZONE
 * @property int $ID_EQUIPGENERATION
 * @property int $ZONE_NUMBER
 * @property mixed $TEMP_SENSOR
 * @property mixed $TOP_ADIABAT
 * @property mixed $BOTTOM_ADIABAT
 * @property mixed $LEFT_ADIABAT
 * @property mixed $RIGHT_ADIABAT
 * @property mixed $FRONT_ADIABAT
 * @property mixed $REAR_ADIABAT
 * @property mixed $TOP_CHANGE
 * @property mixed $TOP_PRM1
 * @property mixed $TOP_PRM2
 * @property mixed $TOP_PRM3
 * @property mixed $BOTTOM_CHANGE
 * @property mixed $BOTTOM_PRM1
 * @property mixed $BOTTOM_PRM2
 * @property mixed $BOTTOM_PRM3
 * @property mixed $LEFT_CHANGE
 * @property mixed $LEFT_PRM1
 * @property mixed $LEFT_PRM2
 * @property mixed $LEFT_PRM3
 * @property mixed $RIGHT_CHANGE
 * @property mixed $RIGHT_PRM1
 * @property mixed $RIGHT_PRM2
 * @property mixed $RIGHT_PRM3
 * @property mixed $FRONT_CHANGE
 * @property mixed $FRONT_PRM1
 * @property mixed $FRONT_PRM2
 * @property mixed $FRONT_PRM3
 * @property mixed $REAR_CHANGE
 * @property mixed $REAR_PRM1
 * @property mixed $REAR_PRM2
 * @property mixed $REAR_PRM3
 * @property-read EQUIPGENERATION $eQUIPGENERATION
 */
class EquipGenZone extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'equip_gen_zone';

    /**
     * @var array
     */
    protected $fillable = ['ID_EQUIP_GEN_ZONE', 'ID_EQUIPGENERATION', 'ZONE_NUMBER', 'TEMP_SENSOR', 'TOP_ADIABAT', 'BOTTOM_ADIABAT', 'LEFT_ADIABAT', 'RIGHT_ADIABAT', 'FRONT_ADIABAT', 'REAR_ADIABAT', 'TOP_CHANGE', 'TOP_PRM1', 'TOP_PRM2', 'TOP_PRM3', 'BOTTOM_CHANGE', 'BOTTOM_PRM1', 'BOTTOM_PRM2', 'BOTTOM_PRM3', 'LEFT_CHANGE', 'LEFT_PRM1', 'LEFT_PRM2', 'LEFT_PRM3', 'RIGHT_CHANGE', 'RIGHT_PRM1', 'RIGHT_PRM2', 'RIGHT_PRM3', 'FRONT_CHANGE', 'FRONT_PRM1', 'FRONT_PRM2', 'FRONT_PRM3', 'REAR_CHANGE', 'REAR_PRM1', 'REAR_PRM2', 'REAR_PRM3'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_EQUIP_GEN_ZONE';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function eQUIPGENERATION()
    {
        return $this->belongsTo('EQUIPGENERATION', 'ID_EQUIPGENERATION', 'ID_EQUIPGENERATION');
    }
}
