<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_STUD_EQUIPPROFILE
 * @property int $ID_STUDY_EQUIPMENTS
 * @property mixed $EP_X_POSITION
 * @property mixed $EP_TEMP_REGUL
 * @property mixed $EP_ALPHA_TOP
 * @property mixed $EP_ALPHA_BOTTOM
 * @property mixed $EP_ALPHA_LEFT
 * @property mixed $EP_ALPHA_RIGHT
 * @property mixed $EP_ALPHA_FRONT
 * @property mixed $EP_ALPHA_REAR
 * @property mixed $EP_TEMP_TOP
 * @property mixed $EP_TEMP_BOTTOM
 * @property mixed $EP_TEMP_LEFT
 * @property mixed $EP_TEMP_RIGHT
 * @property mixed $EP_TEMP_FRONT
 * @property mixed $EP_TEMP_REAR
 * @property-read StudyEquipment $StudyEquipment
 */
class StudEquipprofile extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'STUD_EQUIPPROFILE';

    /**
     * @var array
     */
    protected $fillable = ['ID_STUD_EQUIPPROFILE', 'ID_STUDY_EQUIPMENTS', 'EP_X_POSITION', 'EP_TEMP_REGUL', 'EP_ALPHA_TOP', 'EP_ALPHA_BOTTOM', 'EP_ALPHA_LEFT', 'EP_ALPHA_RIGHT', 'EP_ALPHA_FRONT', 'EP_ALPHA_REAR', 'EP_TEMP_TOP', 'EP_TEMP_BOTTOM', 'EP_TEMP_LEFT', 'EP_TEMP_RIGHT', 'EP_TEMP_FRONT', 'EP_TEMP_REAR'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_STUD_EQUIPPROFILE';

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
