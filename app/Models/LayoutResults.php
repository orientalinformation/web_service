<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_LAYOUT_RESULTS
 * @property int $ID_STUDY_EQUIPMENTS
 * @property mixed $NUMBER_PER_M
 * @property int $NUMBER_IN_WIDTH
 * @property mixed $LEFT_RIGHT_INTERVAL
 * @property mixed $LOADING_RATE
 * @property mixed $QUANTITY_PER_BATCH
 * @property mixed $LOADING_RATE_MAX
 * @property mixed $QUANTITY_PER_BATCH_MAX
 * @property mixed $NB_SHELVES
 * @property mixed $NB_SHELVES_MAX
 * @property-read StudyEquipments $studyEquipments
 */
class LayoutResults extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'LAYOUT_RESULTS';
    
    /**
     * @var array
     */
    protected $fillable = ['ID_LAYOUT_RESULTS', 'ID_STUDY_EQUIPMENTS', 'NUMBER_PER_M', 'NUMBER_IN_WIDTH', 'LEFT_RIGHT_INTERVAL', 'LOADING_RATE', 'QUANTITY_PER_BATCH', 'LOADING_RATE_MAX', 'QUANTITY_PER_BATCH_MAX', 'NB_SHELVES', 'NB_SHELVES_MAX'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_LAYOUT_RESULTS';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    protected $casts = [
        'LEFT_RIGHT_INTERVAL' => 'double',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function studyEquipments()
    {
        return $this->belongsTo('App\\Models\\StudyEquipments', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }
}
