<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_CALC_PARAMS
 * @property int $ID_STUDY_EQUIPMENTS
 * @property boolean $HORIZ_SCAN
 * @property boolean $VERT_SCAN
 * @property int $MAX_IT_NB
 * @property int $TIME_STEPS_NB
 * @property mixed $RELAX_COEFF
 * @property mixed $TIME_STEP
 * @property int $STORAGE_STEP
 * @property int $PRECISION_LOG_STEP
 * @property mixed $STOP_TOP_SURF
 * @property mixed $STOP_INT
 * @property mixed $STOP_BOTTOM_SURF
 * @property mixed $STOP_AVG
 * @property boolean $STUDY_ALPHA_TOP_FIXED
 * @property mixed $STUDY_ALPHA_TOP
 * @property boolean $STUDY_ALPHA_BOTTOM_FIXED
 * @property mixed $STUDY_ALPHA_BOTTOM
 * @property boolean $STUDY_ALPHA_LEFT_FIXED
 * @property mixed $STUDY_ALPHA_LEFT
 * @property boolean $STUDY_ALPHA_RIGHT_FIXED
 * @property mixed $STUDY_ALPHA_RIGHT
 * @property boolean $STUDY_ALPHA_FRONT_FIXED
 * @property mixed $STUDY_ALPHA_FRONT
 * @property boolean $STUDY_ALPHA_REAR_FIXED
 * @property mixed $STUDY_ALPHA_REAR
 * @property mixed $PRECISION_REQUEST
 * @property int $NB_OPTIM
 * @property mixed $ERROR_T
 * @property mixed $ERROR_H
 * @property-read StudyEquipment $studyEquipment
 */
class CalculationParameter extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['ID_CALC_PARAMS', 'ID_STUDY_EQUIPMENTS', 'HORIZ_SCAN', 'VERT_SCAN', 'MAX_IT_NB', 'TIME_STEPS_NB', 'RELAX_COEFF', 'TIME_STEP', 'STORAGE_STEP', 'PRECISION_LOG_STEP', 'STOP_TOP_SURF', 'STOP_INT', 'STOP_BOTTOM_SURF', 'STOP_AVG', 'STUDY_ALPHA_TOP_FIXED', 'STUDY_ALPHA_TOP', 'STUDY_ALPHA_BOTTOM_FIXED', 'STUDY_ALPHA_BOTTOM', 'STUDY_ALPHA_LEFT_FIXED', 'STUDY_ALPHA_LEFT', 'STUDY_ALPHA_RIGHT_FIXED', 'STUDY_ALPHA_RIGHT', 'STUDY_ALPHA_FRONT_FIXED', 'STUDY_ALPHA_FRONT', 'STUDY_ALPHA_REAR_FIXED', 'STUDY_ALPHA_REAR', 'PRECISION_REQUEST', 'NB_OPTIM', 'ERROR_T', 'ERROR_H'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_CALC_PARAMS';


    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function studyEquipment()
    {
        return $this->belongsTo('App\\Models\\StudyEquipment', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }
}
