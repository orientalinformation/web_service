<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_USER
 * @property mixed $MESH_1_SIZE
 * @property mixed $MESH_2_SIZE
 * @property mixed $MESH_3_SIZE
 * @property mixed $MESH_RATIO
 * @property-read User $user
 */
class MeshParamDef extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'MESH_PARAM_DEF';

    /**
     * @var array
     */
    protected $fillable = ['ID_USER', 'MESH_1_SIZE', 'MESH_2_SIZE', 'MESH_3_SIZE', 'MESH_RATIO'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_USER';

    /**
     * Indicates if the IDs are auto-incrementing.
     * 
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\\Models\\User', 'ID_USER', 'ID_USER');
    }
}
