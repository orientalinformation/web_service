<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_PIPE_RES
 * @property int $ID_STUDY_EQUIPMENTS
 * @property mixed $EQUIVAL_LEN
 * @property mixed $FLUID_FLOW
 * @property mixed $HEAT_ENTRY
 * @property mixed $LOAD_LOSS
 * @property mixed $DIPHASIQ
 * @property-read STUDYEQUIPMENTS $sTUDYEQUIPMENTS
 */
class PipeRes extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'PIPE_RES';

    /**
     * @var array
     */
    protected $fillable = ['ID_PIPE_RES', 'ID_STUDY_EQUIPMENTS', 'EQUIVAL_LEN', 'FLUID_FLOW', 'HEAT_ENTRY', 'LOAD_LOSS', 'DIPHASIQ'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_PIPE_RES';

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
