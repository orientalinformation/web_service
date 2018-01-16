<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_LAYOUT_GENERATION
 * @property int $ID_STUDY_EQUIPMENTS
 * @property mixed $WIDTH_INTERVAL
 * @property mixed $LENGTH_INTERVAL
 * @property boolean $PROD_POSITION
 * @property mixed $SHELVES_WIDTH
 * @property mixed $SHELVES_LENGTH
 * @property mixed $NB_SHELVES_PERSO
 * @property mixed $SHELVES_TYPE
 * @property-read StudyEquipments $studyEquipments
 */
class LayoutGeneration extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'layout_generation';

    /**
     * @var array
     */
    protected $fillable = ['ID_LAYOUT_GENERATION', 'ID_STUDY_EQUIPMENTS', 'WIDTH_INTERVAL', 'LENGTH_INTERVAL', 'PROD_POSITION', 'SHELVES_WIDTH', 'SHELVES_LENGTH', 'NB_SHELVES_PERSO', 'SHELVES_TYPE'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_LAYOUT_GENERATION';

    /**
     * @var array
     */
    protected $casts = [
        'PROD_POSITION' => 'integer',
    ];

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
        return $this->belongsTo('App\\Models\\StudyEquipments', 'ID_STUDY_EQUIPMENTS', 'ID_STUDY_EQUIPMENTS');
    }
}
