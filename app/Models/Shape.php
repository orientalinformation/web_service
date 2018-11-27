<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_SHAPE
 * @property int $SHAPECODE
 * @property string $SHAPEPICT
 * @property boolean $SYM_1
 * @property boolean $SYM_2
 * @property boolean $SYM_3
 * @property boolean $AXISYM_1
 * @property boolean $AXISYM_2
 * @property boolean $AXISYM_3
 * @property-read Packing[] $packings
 * @property-read ProductElmt[] $productElmts
 */
class Shape extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'SHAPE';

    /**
     * @var array
     */
    protected $fillable = ['ID_SHAPE', 'SHAPECODE', 'SHAPEPICT', 'SYM_1', 'SYM_2', 'SYM_3', 'AXISYM_1', 'AXISYM_2', 'AXISYM_3'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_SHAPE';

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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function packings()
    {
        return $this->hasMany('App\\Models\\Packing', 'ID_SHAPE', 'ID_SHAPE');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productElmts()
    {
        return $this->hasMany('App\\Models\\ProductElmt', 'ID_SHAPE', 'ID_SHAPE');
    }
}
