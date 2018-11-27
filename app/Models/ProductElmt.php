<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use Sofa\Eloquence\Eloquence; // base trait
use Sofa\Eloquence\Mappable; // extension trait

/**
 * @property int $ID_PRODUCT_ELMT
 * @property int $ID_PROD
 * @property int $ID_SHAPE
 * @property int $ID_COMP
 * @property string $PROD_ELMT_NAME
 * @property mixed $SHAPE_PARAM1
 * @property mixed $SHAPE_PARAM2
 * @property mixed $SHAPE_PARAM3
 * @property mixed $PROD_DEHYD
 * @property mixed $PROD_DEHYD_COST
 * @property mixed $SHAPE_POS1
 * @property mixed $SHAPE_POS2
 * @property mixed $SHAPE_POS3
 * @property boolean $PROD_ELMT_ISO
 * @property mixed $ORIGINAL_THICK
 * @property boolean $NODE_DECIM
 * @property int $INSERT_LINE_ORDER
 * @property mixed $PROD_ELMT_WEIGHT
 * @property mixed $PROD_ELMT_REALWEIGHT
 * @property-read Component $component
 * @property-read Product $product
 * @property-read Shape $shape
 * @property-read MeshPosition[] $meshPositions
 */
class ProductElmt extends Model
{
    use Eloquence, Mappable;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'PRODUCT_ELMT';

    protected $hidden = [
        'product','shape'
    ];

    /**
     * @var array
     */
    protected $fillable = ['ID_PRODUCT_ELMT', 'ID_PROD', 'ID_SHAPE', 'ID_COMP', 'PROD_ELMT_NAME', 'SHAPE_PARAM1', 'SHAPE_PARAM2', 'SHAPE_PARAM3', 'PROD_DEHYD', 'PROD_DEHYD_COST', 'SHAPE_POS1', 'SHAPE_POS2', 'SHAPE_POS3', 'PROD_ELMT_ISO', 'ORIGINAL_THICK', 'NODE_DECIM', 'INSERT_LINE_ORDER', 'PROD_ELMT_WEIGHT', 'PROD_ELMT_REALWEIGHT'];

    protected $casts = [
        'SHAPE_PARAM2' => 'double',
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_PRODUCT_ELMT';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    protected $maps = [
      'product' => ['ID_STUDY', 'ID_PRODUCTION'],
      'shape' => ['SHAPECODE']
    ];

    protected $appends = ['ID_STUDY', 'ID_PRODUCTION', 'SHAPECODE'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function component()
    {
        return $this->belongsTo('\\App\\Models\\Component', 'ID_COMP', 'ID_COMP');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo('\\App\\Models\\Product', 'ID_PROD', 'ID_PROD');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shape()
    {
        return $this->belongsTo('\\App\\Models\\Shape', 'ID_SHAPE', 'ID_SHAPE');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function meshPositions()
    {
        return $this->hasMany('\\App\\Models\\MeshPosition', 'ID_PRODUCT_ELMT', 'ID_PRODUCT_ELMT');
    }
}
