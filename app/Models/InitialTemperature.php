<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_INITIAL_TEMP
 * @property int $ID_PRODUCTION
 * @property mixed $MESH_1_ORDER
 * @property mixed $MESH_2_ORDER
 * @property mixed $MESH_3_ORDER
 * @property mixed $INITIAL_T
 * @property-read Production $production
 */
class InitialTemperature extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'INITIAL_TEMPERATURE';

    /**
     * @var array
     */
    protected $fillable = ['ID_INITIAL_TEMP', 'ID_PRODUCTION', 'MESH_1_ORDER', 'MESH_2_ORDER', 'MESH_3_ORDER', 'INITIAL_T'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_INITIAL_TEMP';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function production()
    {
        return $this->belongsTo('App\\Models\\Production', 'ID_PRODUCTION', 'ID_PRODUCTION');
    }
}
