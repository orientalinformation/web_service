<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_EXH_RES
 * @property int $ID_STUDY_EQUIPMENTS
 * @property mixed $DILUTION_AIR_ENTH
 * @property mixed $MIXTURE_ENTH
 * @property mixed $CRYOGEN_ENTH_VARIATION
 * @property mixed $GAS_CRYOGEN_FLOW_RATE
 * @property mixed $DILUTION_AIR_FLOW_RATE
 * @property mixed $TOTAL_FLOW_RATE
 * @property-read STUDYEQUIPMENTS $sTUDYEQUIPMENTS
 */
class ExhRes extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'EXH_RES';

    /**
     * @var array
     */
    protected $fillable = ['ID_EXH_RES', 'ID_STUDY_EQUIPMENTS', 'DILUTION_AIR_ENTH', 'MIXTURE_ENTH', 'CRYOGEN_ENTH_VARIATION', 'GAS_CRYOGEN_FLOW_RATE', 'DILUTION_AIR_FLOW_RATE', 'TOTAL_FLOW_RATE'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_EXH_RES';

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
