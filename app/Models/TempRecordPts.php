<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_TEMP_RECORD_PTS
 * @property int $ID_STUDY
 * @property mixed $AXIS1_PT_TOP_SURF
 * @property mixed $AXIS2_PT_TOP_SURF
 * @property mixed $AXIS3_PT_TOP_SURF
 * @property mixed $AXIS1_PT_INT_PT
 * @property mixed $AXIS2_PT_INT_PT
 * @property mixed $AXIS3_PT_INT_PT
 * @property mixed $AXIS1_PT_BOT_SURF
 * @property mixed $AXIS2_PT_BOT_SURF
 * @property mixed $AXIS3_PT_BOT_SURF
 * @property mixed $AXIS2_AX_1
 * @property mixed $AXIS3_AX_1
 * @property mixed $AXIS1_AX_2
 * @property mixed $AXIS3_AX_2
 * @property mixed $AXIS1_AX_3
 * @property mixed $AXIS2_AX_3
 * @property mixed $AXIS1_PL_2_3
 * @property mixed $AXIS2_PL_1_3
 * @property mixed $AXIS3_PL_1_2
 * @property mixed $NB_STEPS
 * @property mixed $CONTOUR2D_TEMP_MIN
 * @property mixed $CONTOUR2D_TEMP_MAX
 * @property-read Study $studies
 */
class TempRecordPts extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['ID_TEMP_RECORD_PTS', 'ID_STUDY', 'AXIS1_PT_TOP_SURF', 'AXIS2_PT_TOP_SURF', 'AXIS3_PT_TOP_SURF', 'AXIS1_PT_INT_PT', 'AXIS2_PT_INT_PT', 'AXIS3_PT_INT_PT', 'AXIS1_PT_BOT_SURF', 'AXIS2_PT_BOT_SURF', 'AXIS3_PT_BOT_SURF', 'AXIS2_AX_1', 'AXIS3_AX_1', 'AXIS1_AX_2', 'AXIS3_AX_2', 'AXIS1_AX_3', 'AXIS2_AX_3', 'AXIS1_PL_2_3', 'AXIS2_PL_1_3', 'AXIS3_PL_1_2', 'NB_STEPS', 'CONTOUR2D_TEMP_MIN', 'CONTOUR2D_TEMP_MAX'];

    protected $casts = [
        'AXIS1_PT_TOP_SURF' => 'double',
        'AXIS2_PT_TOP_SURF' => 'double',
        'AXIS3_PT_TOP_SURF' => 'double',
        'AXIS1_PT_INT_PT' => 'double',
        'AXIS2_PT_INT_PT' => 'double',
        'AXIS3_PT_INT_PT' => 'double',
        'AXIS1_PT_BOT_SURF' => 'double',
        'AXIS2_PT_BOT_SURF' => 'double',
        'AXIS3_PT_BOT_SURF' => 'double',
        'AXIS2_AX_1' => 'double',
        'AXIS3_AX_1' => 'double',
        'AXIS1_AX_2' => 'double',
        'AXIS3_AX_2' => 'double',
        'AXIS1_AX_3' => 'double',
        'AXIS2_AX_3' => 'double',
        'AXIS1_PL_2_3' => 'double',
        'AXIS2_PL_1_3' => 'double',
        'AXIS3_PL_1_2' => 'double',
        'NB_STEPS' => 'integer',
        'CONTOUR2D_TEMP_MIN' => 'double',
        'CONTOUR2D_TEMP_MAX' => 'double'
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_TEMP_RECORD_PTS';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function studies()
    {
        return $this->belongsTo('App\\Models\\Study', 'ID_STUDY', 'ID_STUDY');
    }
}
