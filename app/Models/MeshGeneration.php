<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_MESH_GENERATION
 * @property int $ID_PROD
 * @property boolean $MESH_1_FIXED
 * @property boolean $MESH_2_FIXED
 * @property boolean $MESH_3_FIXED
 * @property mixed $MESH_1_MODE
 * @property mixed $MESH_2_MODE
 * @property mixed $MESH_3_MODE
 * @property int $MESH_1_NB
 * @property int $MESH_2_NB
 * @property int $MESH_3_NB
 * @property mixed $MESH_1_SIZE
 * @property mixed $MESH_2_SIZE
 * @property mixed $MESH_3_SIZE
 * @property mixed $MESH_1_INT
 * @property mixed $MESH_2_INT
 * @property mixed $MESH_3_INT
 * @property mixed $MESH_1_RATIO
 * @property mixed $MESH_2_RATIO
 * @property mixed $MESH_3_RATIO
 * @property int $BEST_1_NB
 * @property int $BEST_2_NB
 * @property int $BEST_3_NB
 * @property-read Product $product
 */
class MeshGeneration extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'mesh_generation';

    /**
     * @var array
     */
    protected $fillable = ['ID_MESH_GENERATION', 'ID_PROD', 'MESH_1_FIXED', 'MESH_2_FIXED', 'MESH_3_FIXED', 'MESH_1_MODE', 'MESH_2_MODE', 'MESH_3_MODE', 'MESH_1_NB', 'MESH_2_NB', 'MESH_3_NB', 'MESH_1_SIZE', 'MESH_2_SIZE', 'MESH_3_SIZE', 'MESH_1_INT', 'MESH_2_INT', 'MESH_3_INT', 'MESH_1_RATIO', 'MESH_2_RATIO', 'MESH_3_RATIO', 'BEST_1_NB', 'BEST_2_NB', 'BEST_3_NB'];

    protected $casts = [
        'MESH_1_SIZE' => 'double',
        'MESH_2_SIZE' => 'double',
        'MESH_3_SIZE' => 'double',
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_MESH_GENERATION';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo('\\App\\Models\\Product', 'ID_PROD', 'ID_PROD');
    }
}
