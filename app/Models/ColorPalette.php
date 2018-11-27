<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_COLOR
 * @property int $COLOR_ORDER
 * @property string $COLOR_NAME
 * @property string $CODE_HEXA
 * @property string $COLOR_TEXT
 * @property-read App\Models\ProdCharColor[] $ProdCharColors
 * @property-read App\Models\ProdCharColorsDef[] $ProdCharColorsDefs
 */
class ColorPalette extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'COLOR_PALETTE';

    /**
     * @var array
     */
    protected $fillable = ['ID_COLOR', 'COLOR_ORDER', 'COLOR_NAME', 'CODE_HEXA', 'COLOR_TEXT'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_COLOR';

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
    public function ProdCharColors()
    {
        return $this->hasMany('App\\Models\\ProdCharColor', 'ID_COLOR', 'ID_COLOR');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ProdCharColorsDefs()
    {
        return $this->hasMany('App\\Models\\ProdCharColorsDef', 'ID_COLOR', 'ID_COLOR');
    }
}
