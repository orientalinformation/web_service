<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_CALC_PARAMSDEF
 * @property int $ID_USER
 * @property boolean $HORIZ_SCAN_DEF
 * @property boolean $VERT_SCAN_DEF
 * @property int $MAX_IT_NB_DEF
 * @property int $TIME_STEPS_NB_DEF
 * @property mixed $RELAX_COEFF_DEF
 * @property mixed $TIME_STEP_DEF
 * @property int $STORAGE_STEP_DEF
 * @property int $PRECISION_LOG_STEP_DEF
 * @property mixed $STOP_TOP_SURF_DEF
 * @property mixed $STOP_INT_DEF
 * @property mixed $STOP_BOTTOM_SURF_DEF
 * @property mixed $STOP_AVG_DEF
 * @property boolean $STUDY_ALPHA_TOP_FIXED_DEF
 * @property mixed $STUDY_ALPHA_TOP_DEF
 * @property boolean $STUDY_ALPHA_BOTTOM_FIXED_DEF
 * @property mixed $STUDY_ALPHA_BOTTOM_DEF
 * @property boolean $STUDY_ALPHA_LEFT_FIXED_DEF
 * @property mixed $STUDY_ALPHA_LEFT_DEF
 * @property boolean $STUDY_ALPHA_RIGHT_FIXED_DEF
 * @property mixed $STUDY_ALPHA_RIGHT_DEF
 * @property boolean $STUDY_ALPHA_FRONT_FIXED_DEF
 * @property mixed $STUDY_ALPHA_FRONT_DEF
 * @property boolean $STUDY_ALPHA_REAR_FIXED_DEF
 * @property mixed $STUDY_ALPHA_REAR_DEF
 * @property mixed $PRECISION_REQUEST_DEF
 * @property-read User $user
 */
class CalculationParametersDef extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'CALCULATION_PARAMETERS_DEF';

    /**
     * @var array
     */
    protected $fillable = ['ID_CALC_PARAMSDEF', 'ID_USER', 'HORIZ_SCAN_DEF', 'VERT_SCAN_DEF', 'MAX_IT_NB_DEF', 'TIME_STEPS_NB_DEF', 'RELAX_COEFF_DEF', 'TIME_STEP_DEF', 'STORAGE_STEP_DEF', 'PRECISION_LOG_STEP_DEF', 'STOP_TOP_SURF_DEF', 'STOP_INT_DEF', 'STOP_BOTTOM_SURF_DEF', 'STOP_AVG_DEF', 'STUDY_ALPHA_TOP_FIXED_DEF', 'STUDY_ALPHA_TOP_DEF', 'STUDY_ALPHA_BOTTOM_FIXED_DEF', 'STUDY_ALPHA_BOTTOM_DEF', 'STUDY_ALPHA_LEFT_FIXED_DEF', 'STUDY_ALPHA_LEFT_DEF', 'STUDY_ALPHA_RIGHT_FIXED_DEF', 'STUDY_ALPHA_RIGHT_DEF', 'STUDY_ALPHA_FRONT_FIXED_DEF', 'STUDY_ALPHA_FRONT_DEF', 'STUDY_ALPHA_REAR_FIXED_DEF', 'STUDY_ALPHA_REAR_DEF', 'PRECISION_REQUEST_DEF'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_CALC_PARAMSDEF';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\\Models\\User', 'ID_USER', 'ID_USER');
    }
}
