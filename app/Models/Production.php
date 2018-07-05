<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_PRODUCTION
 * @property int $ID_STUDY
 * @property mixed $DAILY_PROD
 * @property mixed $DAILY_STARTUP
 * @property mixed $WEEKLY_PROD
 * @property mixed $PROD_FLOW_RATE
 * @property mixed $NB_PROD_WEEK_PER_YEAR
 * @property mixed $AMBIENT_TEMP
 * @property mixed $AMBIENT_HUM
 * @property mixed $AVG_T_DESIRED
 * @property mixed $AVG_T_INITIAL
 * @property mixed $APPROX_DWELLING_TIME
 * @property-read Study $study
 * @property-read InitialTemperature[] $initialTemperatures
 */
class Production extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'production';

    /**
     * @var array
     */
    protected $fillable = ['ID_PRODUCTION', 'ID_STUDY', 'DAILY_PROD', 'DAILY_STARTUP', 'WEEKLY_PROD',
        'PROD_FLOW_RATE', 'NB_PROD_WEEK_PER_YEAR', 'AMBIENT_TEMP', 'AMBIENT_HUM', 'AVG_T_DESIRED',
        'AVG_T_INITIAL', 'APPROX_DWELLING_TIME'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_PRODUCTION';

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
        return $this->belongsTo('App\\Models\\Study', 'ID_STUDY', 'ID_STUDY');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function initialTemperatures()
    {
        return $this->hasMany('App\\Models\\InitialTemperature', 'ID_PRODUCTION', 'ID_PRODUCTION');
    }
}
