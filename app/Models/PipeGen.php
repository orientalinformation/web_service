<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_PIPE_GEN
 * @property int $ID_STUDY_EQUIPMENTS
 * @property mixed $INSULLINE_LENGHT
 * @property mixed $NOINSULLINE_LENGHT
 * @property int $ELBOWS
 * @property int $TEES
 * @property int $INSUL_VALVES
 * @property int $NOINSUL_VALVES
 * @property boolean $MATHIGHER
 * @property mixed $HEIGHT
 * @property int $FLUID
 * @property mixed $PRESSURE
 * @property mixed $GAS_TEMP
 * @property-read StudyEquipments $studyEquipments
 * @property-read LineDefinition[] $lineDefinitions
 */
class PipeGen extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'pipe_gen';

    /**
     * @var array
     */
    protected $fillable = ['ID_PIPE_GEN', 'ID_STUDY_EQUIPMENTS', 'INSULLINE_LENGHT', 'NOINSULLINE_LENGHT', 'ELBOWS', 'TEES', 'INSUL_VALVES', 'NOINSUL_VALVES', 'MATHIGHER', 'HEIGHT', 'FLUID', 'PRESSURE', 'GAS_TEMP'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_PIPE_GEN';

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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lineDefinitions()
    {
        return $this->hasMany('LineDefinition', 'ID_PIPE_GEN', 'ID_PIPE_GEN');
    }
}
