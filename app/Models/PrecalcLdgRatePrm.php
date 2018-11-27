<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_PRECALC_LDG_RATE_PRM
 * @property int $ID_STUDY
 * @property mixed $L_INTERVAL
 * @property mixed $W_INTERVAL
 * @property mixed $PRECALC_LDG_TR
 * @property mixed $APPROX_LDG_RATE
 * @property-read Study $study
 */
class PrecalcLdgRatePrm extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'PRECALC_LDG_RATE_PRM';

    /**
     * @var array
     */
    protected $fillable = ['ID_PRECALC_LDG_RATE_PRM', 'ID_STUDY', 'L_INTERVAL', 'W_INTERVAL', 'PRECALC_LDG_TR', 'APPROX_LDG_RATE'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_PRECALC_LDG_RATE_PRM';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function study()
    {
        return $this->belongsTo('\\App\\Models\\Study', 'ID_STUDY', 'ID_STUDY');
    }
}
