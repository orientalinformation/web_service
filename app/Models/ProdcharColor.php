<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_PRODCHAR_COLORS
 * @property int $ID_PROD
 * @property int $ID_COLOR
 * @property int $LAYER_ORDER
 * @property-read App\Models\Product $product
 * @property-read App\Models\ColorPalette $colorPalette
 */
class ProdcharColor extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'PRODCHAR_COLORS';

    /**
     * @var array
     */
    protected $fillable = ['ID_PRODCHAR_COLORS', 'ID_PROD', 'ID_COLOR', 'LAYER_ORDER'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_PRODCHAR_COLORS';

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
        return $this->belongsTo('App\\Models\\Product', 'ID_PROD', 'ID_PROD');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function colorPalette()
    {
        return $this->belongsTo('App\\Models\\ColorPalette', 'ID_COLOR', 'ID_COLOR');
    }
}
