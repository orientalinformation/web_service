<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_REC_POS
 * @property int $ID_STUDY_EQUIPMENTS
 * @property mixed $RECORD_TIME
 * @property mixed $AVERAGE_TEMP
 * @property mixed $AVERAGE_ENTH_VAR
 * @property mixed $ENTHALPY_VAR
 * @property boolean $RECORD_BUFFER
 * @property boolean $RECORD_STATE
 * @property-read StudyEquipments $studyEquipments
 * @property-read TempRecordData[] $tempRecordDatas
 */
class RecordPosition extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'record_position';

    /**
     * @var array
     */
    protected $fillable = ['ID_REC_POS', 'ID_STUDY_EQUIPMENTS', 'RECORD_TIME', 'AVERAGE_TEMP', 'AVERAGE_ENTH_VAR', 'ENTHALPY_VAR', 'RECORD_BUFFER', 'RECORD_STATE'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_REC_POS';

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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tempRecordDatas()
    {
        return $this->hasMany('App\\Models\\TempRecordData', 'ID_REC_POS', 'ID_REC_POS');
    }
}
