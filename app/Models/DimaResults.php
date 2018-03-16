<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_DIMA_RESULTS
 * @property int $ID_STUDY_EQUIPMENTS
 * @property mixed $SETPOINT
 * @property int $DIMA_STATUS
 * @property mixed $DIMA_TS
 * @property mixed $DIMA_TFP
 * @property mixed $DIMA_VEP
 * @property mixed $DIMA_VC
 * @property mixed $DIMA_TYPE
 * @property mixed $DIMA_PRECIS
 * @property mixed $CRYOCONSPROD
 * @property mixed $HOURLYOUTPUTMAX
 * @property mixed $CONSUM
 * @property mixed $USERATE
 * @property mixed $CONSUMMAX
 * @property mixed $USERATEMAX
 * @property-read StudyEquipments $studyEquipments
 */
class DimaResults extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['ID_DIMA_RESULTS', 'ID_STUDY_EQUIPMENTS', 'SETPOINT', 'DIMA_STATUS', 'DIMA_TS', 'DIMA_TFP', 'DIMA_VEP', 'DIMA_VC', 'DIMA_TYPE', 'DIMA_PRECIS', 'CRYOCONSPROD', 'HOURLYOUTPUTMAX', 'CONSUM', 'USERATE', 'CONSUMMAX', 'USERATEMAX'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_DIMA_RESULTS';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function studyEquipments()
    {
        return $this->belongsTo('App\\Models\\StudyEquipment', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }
}
