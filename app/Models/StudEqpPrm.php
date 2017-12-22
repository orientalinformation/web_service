<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_STUD_EQP_PRM
 * @property int $ID_STUDY_EQUIPMENTS
 * @property mixed $VALUE_TYPE
 * @property mixed $VALUE
 * @property-read StudyEquipments $studyEquipments
 */
class StudEqpPrm extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'stud_eqp_prm';

    /**
     * @var array
     */
    protected $fillable = ['ID_STUD_EQP_PRM', 'ID_STUDY_EQUIPMENTS', 'VALUE_TYPE', 'VALUE'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_STUD_EQP_PRM';

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
        return $this->belongsTo('StudyEquipments', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }
}
