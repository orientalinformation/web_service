<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence; // base trait
use Sofa\Eloquence\Mappable; // extension trait

/**
 * @property int $ID_MESH_POSITION
 * @property int $ID_PRODUCT_ELMT
 * @property mixed $MESH_AXIS
 * @property mixed $MESH_ORDER
 * @property mixed $MESH_AXIS_POS
 * @property-read ProductElmt $productElmt
 */
class MeshPosition extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'MESH_POSITION';

     protected $hidden = [
        'productElmt'
    ];

    /**
     * @var array
     */
    protected $fillable = ['ID_MESH_POSITION', 'ID_PRODUCT_ELMT', 'MESH_AXIS', 'MESH_ORDER', 'MESH_AXIS_POS'];

    protected $casts = [
        'MESH_AXIS_POS' => 'double'
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_MESH_POSITION';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    protected $maps = [
      'productElmt' => ['ID_STUDY']
    ];

    protected $appends = ['ID_STUDY'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function productElmt()
    {
        return $this->belongsTo('App\\Models\\ProductElmt', 'ID_PRODUCT_ELMT', 'ID_PRODUCT_ELMT');
    }
}
