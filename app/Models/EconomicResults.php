<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_ECONOMIC_RESULTS
 * @property int $ID_STUDY_EQUIPMENTS
 * @property mixed $FLUID_CONSUMPTION_PRODUCT
 * @property mixed $FLUID_CONSUMPTION_MAT_PERM
 * @property mixed $FLUID_CONSUMPTION_MAT_GETCOLD
 * @property mixed $FLUID_CONSUMPTION_LINE_PERM
 * @property mixed $FLUID_CONSUMPTION_LINE_GETCOLD
 * @property mixed $FLUID_CONSUMPTION_TANK
 * @property mixed $FLUID_CONSUMPTION_TOTAL
 * @property mixed $FLUID_CONSUMPTION_PER_KG
 * @property mixed $FLUID_CONSUMPTION_DAY
 * @property mixed $FLUID_CONSUMPTION_MONTH
 * @property mixed $FLUID_CONSUMPTION_YEAR
 * @property mixed $FLUID_CONSUMPTION_HOUR
 * @property mixed $FLUID_CONSUMPTION_WEEK
 * @property mixed $COST_PRODUCT
 * @property mixed $COST_MAT_PERM
 * @property mixed $COST_MAT_GETCOLD
 * @property mixed $COST_LINE_PERM
 * @property mixed $COST_LINE_GETCOLD
 * @property mixed $COST_TANK
 * @property mixed $COST_TOTAL
 * @property mixed $COST_KG
 * @property mixed $COST_DAY
 * @property mixed $COST_MONTH
 * @property mixed $COST_YEAR
 * @property mixed $COST_HOUR
 * @property mixed $COST_WEEK
 * @property mixed $PERCENT_PRODUCT
 * @property mixed $PERCENT_EQUIPMENT_PERM
 * @property mixed $PERCENT_EQUIPMENT_DOWN
 * @property mixed $PERCENT_LINE
 * @property-read StudyEquipments $studyEquipments
 */
class EconomicResults extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['ID_ECONOMIC_RESULTS', 'ID_STUDY_EQUIPMENTS', 'FLUID_CONSUMPTION_PRODUCT', 'FLUID_CONSUMPTION_MAT_PERM', 'FLUID_CONSUMPTION_MAT_GETCOLD', 'FLUID_CONSUMPTION_LINE_PERM', 'FLUID_CONSUMPTION_LINE_GETCOLD', 'FLUID_CONSUMPTION_TANK', 'FLUID_CONSUMPTION_TOTAL', 'FLUID_CONSUMPTION_PER_KG', 'FLUID_CONSUMPTION_DAY', 'FLUID_CONSUMPTION_MONTH', 'FLUID_CONSUMPTION_YEAR', 'FLUID_CONSUMPTION_HOUR', 'FLUID_CONSUMPTION_WEEK', 'COST_PRODUCT', 'COST_MAT_PERM', 'COST_MAT_GETCOLD', 'COST_LINE_PERM', 'COST_LINE_GETCOLD', 'COST_TANK', 'COST_TOTAL', 'COST_KG', 'COST_DAY', 'COST_MONTH', 'COST_YEAR', 'COST_HOUR', 'COST_WEEK', 'PERCENT_PRODUCT', 'PERCENT_EQUIPMENT_PERM', 'PERCENT_EQUIPMENT_DOWN', 'PERCENT_LINE'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_ECONOMIC_RESULTS';

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
