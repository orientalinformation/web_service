<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_STUDY_RESULTS
 * @property int $ID_STUDY
 * @property int $BEST_EQUIPMENT
 * @property mixed $TOTAL_DWELLINGTIME
 * @property-read Study $studies
 */
class StudyResult extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['ID_STUDY_RESULTS', 'ID_STUDY', 'BEST_EQUIPMENT', 'TOTAL_DWELLINGTIME'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_STUDY_RESULTS';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function studies()
    {
        return $this->belongsTo('App\\Models\\Study', 'ID_STUDY', 'ID_STUDY');
    }
}
