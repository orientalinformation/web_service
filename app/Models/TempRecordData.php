<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence; // base trait
use Sofa\Eloquence\Mappable; // extension trait

/**
 * @property int $ID_TEMP_RECORD_DATA
 * @property int $ID_REC_POS
 * @property int $REC_AXIS_X_POS
 * @property int $REC_AXIS_Y_POS
 * @property int $REC_AXIS_Z_POS
 * @property mixed $TEMP
 * @property mixed $ENTH
 * @property boolean $TRD_BUFFER
 * @property boolean $TRD_STATE
 * @property-read RecordPosition $recordPosition
 */
class TempRecordData extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'temp_record_data';

    /**
     * @var array
     */
    protected $fillable = ['ID_TEMP_RECORD_DATA', 'ID_REC_POS', 'REC_AXIS_X_POS', 'REC_AXIS_Y_POS', 'REC_AXIS_Z_POS', 'TEMP', 'ENTH', 'TRD_BUFFER', 'TRD_STATE'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_TEMP_RECORD_DATA';

    protected $hidden = [
        'recordPosition'
    ];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    protected $maps = [
      'recordPosition' => ['ID_STUDY_EQUIPMENTS', 'RECORD_TIME']
    ];

    /**
     * @var array
     */
    protected $appends = [
        'ID_STUDY_EQUIPMENTS', 'RECORD_TIME'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function recordPosition()
    {
        return $this->belongsTo('App\\Models\\RecordPosition', 'ID_REC_POS', 'ID_REC_POS');
    }
}
