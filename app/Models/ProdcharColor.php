<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_PRODCHAR_COLORS
 * @property int $ID_PROD
 * @property int $ID_COLOR
 * @property int $LAYER_ORDER
 * @property-read PRODUCT $pRODUCT
 * @property-read COLORPALETTE $cOLORPALETTE
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
    public function pRODUCT()
    {
        return $this->belongsTo('PRODUCT', 'ID_PROD', 'ID_PROD');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cOLORPALETTE()
    {
        return $this->belongsTo('COLORPALETTE', 'ID_COLOR', 'ID_COLOR');
    }
}
