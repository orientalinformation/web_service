<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_PRODCHAR_COLORS_DEF
 * @property int $ID_USER
 * @property int $ID_COLOR
 * @property int $LAYER_ORDER
 * @property-read App\Models\ColorPalette $colorPalette
 * @property-read App\Models\User $user
 */
class ProdcharColorsDef extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'prodchar_colors_def';

    /**
     * @var array
     */
    protected $fillable = ['ID_PRODCHAR_COLORS_DEF', 'ID_USER', 'ID_COLOR', 'LAYER_ORDER'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_PRODCHAR_COLORS_DEF';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function colorPalette()
    {
        return $this->belongsTo('App\\Models\\ColorPalette', 'ID_COLOR', 'ID_COLOR');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\\Models\\User', 'ID_USER', 'ID_USER');
    }
}
