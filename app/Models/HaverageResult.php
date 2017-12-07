<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_HAVERAGE_RESULTS
 * @property int $ID_STUDY
 * @property mixed $AVG_TEMP
 * @property mixed $ENTHALPY
 * @property mixed $CONDUCTIVITY
 * @property-read Study $studies
 */
class HaverageResult extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['ID_HAVERAGE_RESULTS', 'ID_STUDY', 'AVG_TEMP', 'ENTHALPY', 'CONDUCTIVITY'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_HAVERAGE_RESULTS';

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
