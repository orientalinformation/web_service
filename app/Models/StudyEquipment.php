<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence; // base trait
use Sofa\Eloquence\Mappable; // extension trait

/**
 * @property int $ID_STUDY_EQUIPMENTS
 * @property int $ID_STUDY
 * @property int $ID_EQUIP
 * @property int $ID_EXH_GEN
 * @property int $ID_EXH_RES
 * @property int $ID_PIPE_GEN
 * @property int $ID_PIPE_RES
 * @property int $ID_ECONOMIC_RESULTS
 * @property int $ID_STUD_EQUIPPROFILE
 * @property int $ID_LAYOUT_GENERATION
 * @property int $ID_LAYOUT_RESULTS
 * @property int $ID_CALC_PARAMS
 * @property mixed $LINE_ORDER
 * @property mixed $STDEQP_LENGTH
 * @property mixed $STDEQP_WIDTH
 * @property mixed $EQP_INST
 * @property mixed $AVERAGE_PRODUCT_TEMP
 * @property mixed $AVERAGE_PRODUCT_ENTHALPY
 * @property mixed $ENTHALPY_VARIATION
 * @property mixed $PRECIS
 * @property mixed $NB_MODUL
 * @property boolean $STACKING_WARNING
 * @property boolean $ENABLE_CONS_PIE
 * @property int $EQUIP_STATUS
 * @property boolean $RUN_CALCULATE
 * @property boolean $BRAIN_SAVETODB
 * @property int $BRAIN_TYPE
 * @property-read Equipment $equipment
 * @property-read Studies $studies
 * @property-read CalculationParameters[] $calculationParameters
 * @property-read DimaResults[] $dimaResults
 * @property-read EcoResEqp[] $ecoResEqps
 * @property-read EconomicResults[] $economicResults
 * @property-read ExhGen[] $exhGens
 * @property-read ExhRes[] $exhRes
 * @property-read LayoutGeneration[] $layoutGenerations
 * @property-read LayoutResults[] $layoutResults
 * @property-read PipeGen[] $pipeGens
 * @property-read PipeRes[] $pipeRes
 * @property-read RecordPosition[] $recordPositions
 * @property-read StudEqpPrm[] $studEqpPrms
 * @property-read StudEquipprofile[] $studEquipprofiles
 */
class StudyEquipment extends Model
{
    use Eloquence, Mappable;

    /**
     * @var array
     */
    protected $fillable = ['ID_STUDY_EQUIPMENTS', 'ID_STUDY', 'ID_EQUIP', 'ID_EXH_GEN', 'ID_EXH_RES', 'ID_PIPE_GEN', 'ID_PIPE_RES', 'ID_ECONOMIC_RESULTS', 'ID_STUD_EQUIPPROFILE', 'ID_LAYOUT_GENERATION', 'ID_LAYOUT_RESULTS', 'ID_CALC_PARAMS', 'LINE_ORDER', 'STDEQP_LENGTH', 'STDEQP_WIDTH', 'EQP_INST', 'AVERAGE_PRODUCT_TEMP', 'AVERAGE_PRODUCT_ENTHALPY', 'ENTHALPY_VARIATION', 'PRECIS', 'NB_MODUL', 'STACKING_WARNING', 'ENABLE_CONS_PIE', 'EQUIP_STATUS', 'RUN_CALCULATE', 'BRAIN_SAVETODB', 'BRAIN_TYPE'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_STUDY_EQUIPMENTS';

    protected $hidden = [
        'equipment'
    ];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    protected $maps = [
      'equipment' => ['ID_COOLING_FAMILY', 'EQUIP_NAME', 'CAPABILITIES', 'MODUL_LENGTH', 'EQP_LENGTH', 'EQP_WIDTH', 'EQUIP_VERSION', 'STDEQP_LENGTH', 'STDEQP_WIDTH', 'STD', 'ITEM_TR', 'NB_TR', 'SERIES_NAME', 'BATCH_PROCESS']
    ];

    protected $appends = ['ID_COOLING_FAMILY', 'EQUIP_NAME', 'CAPABILITIES', 'MODUL_LENGTH', 'EQP_LENGTH', 'EQP_WIDTH', 'EQUIP_VERSION', 'STDEQP_LENGTH', 'STDEQP_WIDTH', 'STD', 'ITEM_TR', 'NB_TR', 'SERIES_NAME', 'BATCH_PROCESS'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function equipment()
    {
        return $this->belongsTo('App\\Models\\Equipment', 'ID_EQUIP', 'ID_EQUIP');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function studies()
    {
        return $this->belongsTo('App\\Models\\Study', 'ID_STUDY', 'ID_STUDY');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function calculationParameters()
    {
        return $this->hasMany('App\\Models\\CalculationParameter', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dimaResults()
    {
        return $this->hasMany('App\\Models\\DimaResults', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ecoResEqps()
    {
        return $this->hasMany('EcoResEqp', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function economicResults()
    {
        return $this->hasMany('App\\Models\\EconomicResults', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function exhGens()
    {
        return $this->hasMany('ExhGen', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function exhRes()
    {
        return $this->hasMany('ExhRes', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function layoutGenerations()
    {
        return $this->hasMany('App\\Models\\LayoutGeneration', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function layoutResults()
    {
        return $this->hasMany('App\\Models\\LayoutResults', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pipeGens()
    {
        return $this->hasMany('PipeGen', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pipeRes()
    {
        return $this->hasMany('PipeRes', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recordPositions()
    {
        return $this->hasMany('RecordPosition', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function studEqpPrms()
    {
        return $this->hasMany('App\\Models\\StudEqpPrm', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function studEquipprofiles()
    {
        return $this->hasMany('StudEquipprofile', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }
}
