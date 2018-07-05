<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_TEMP_RECORD_PTS_DEF
 * @property int $ID_USER
 * @property mixed $AXIS1_PT_TOP_SURF_DEF
 * @property mixed $AXIS2_PT_TOP_SURF_DEF
 * @property mixed $AXIS3_PT_TOP_SURF_DEF
 * @property mixed $AXIS1_PT_INT_PT_DEF
 * @property mixed $AXIS2_PT_INT_PT_DEF
 * @property mixed $AXIS3_PT_INT_PT_DEF
 * @property mixed $AXIS1_PT_BOT_SURF_DEF
 * @property mixed $AXIS2_PT_BOT_SURF_DEF
 * @property mixed $AXIS3_PT_BOT_SURF_DEF
 * @property mixed $AXIS2_AX_1_DEF
 * @property mixed $AXIS3_AX_1_DEF
 * @property mixed $AXIS1_AX_2_DEF
 * @property mixed $AXIS3_AX_2_DEF
 * @property mixed $AXIS1_AX_3_DEF
 * @property mixed $AXIS2_AX_3_DEF
 * @property mixed $AXIS1_PL_2_3_DEF
 * @property mixed $AXIS2_PL_1_3_DEF
 * @property mixed $AXIS3_PL_1_2_DEF
 * @property mixed $NB_STEPS_DEF
 * @property mixed $CONTOUR2D_TEMP_MIN_DEF
 * @property mixed $CONTOUR2D_TEMP_MAX_DEF
 * @property-read User $user
 */
class TempRecordPtsDef extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'temp_record_pts_def';

    /**
     * @var array
     */
    protected $fillable = ['ID_TEMP_RECORD_PTS_DEF', 'ID_USER', 'AXIS1_PT_TOP_SURF_DEF', 'AXIS2_PT_TOP_SURF_DEF', 'AXIS3_PT_TOP_SURF_DEF', 'AXIS1_PT_INT_PT_DEF', 'AXIS2_PT_INT_PT_DEF', 'AXIS3_PT_INT_PT_DEF', 'AXIS1_PT_BOT_SURF_DEF', 'AXIS2_PT_BOT_SURF_DEF', 'AXIS3_PT_BOT_SURF_DEF', 'AXIS2_AX_1_DEF', 'AXIS3_AX_1_DEF', 'AXIS1_AX_2_DEF', 'AXIS3_AX_2_DEF', 'AXIS1_AX_3_DEF', 'AXIS2_AX_3_DEF', 'AXIS1_PL_2_3_DEF', 'AXIS2_PL_1_3_DEF', 'AXIS3_PL_1_2_DEF', 'NB_STEPS_DEF', 'CONTOUR2D_TEMP_MIN_DEF', 'CONTOUR2D_TEMP_MAX_DEF'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_TEMP_RECORD_PTS_DEF';

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
