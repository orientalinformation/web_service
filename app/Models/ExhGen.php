<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_EXH_GEN
 * @property int $ID_STUDY_EQUIPMENTS
 * @property mixed $DILUTION_AIR_TEMP
 * @property mixed $DILUTION_AIR_HUMIDITY
 * @property mixed $MIXTURE_TEMP_DESIRED
 * @property mixed $HEATING_POWER
 * @property-read STUDYEQUIPMENTS $sTUDYEQUIPMENTS
 */
class ExhGen extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'EXH_GEN';

    /**
     * @var array
     */
    protected $fillable = ['ID_EXH_GEN', 'ID_STUDY_EQUIPMENTS', 'DILUTION_AIR_TEMP', 'DILUTION_AIR_HUMIDITY', 'MIXTURE_TEMP_DESIRED', 'HEATING_POWER'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_EXH_GEN';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sTUDYEQUIPMENTS()
    {
        return $this->belongsTo('STUDYEQUIPMENTS', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }
}
