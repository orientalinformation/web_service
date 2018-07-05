<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence; // base trait
use Sofa\Eloquence\Mappable; // extension trait

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
    use Eloquence, Mappable;
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
     * @var array
     */
    protected $hidden = [
        'colorPalette'
    ];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    protected $maps = [
       'colorPalette' => ['COLOR_NAME', 'CODE_HEXA']
    ];

    /**
     * @var array
     */
    protected $appends = [
        'COLOR_NAME', 'CODE_HEXA'
    ];

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
