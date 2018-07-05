<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_PACKING_LAYER
 * @property int $ID_PACKING
 * @property int $ID_PACKING_ELMT
 * @property mixed $THICKNESS
 * @property mixed $PACKING_SIDE_NUMBER
 * @property mixed $PACKING_LAYER_ORDER
 * @property-read Packing $packing
 * @property-read PackingElmt $packingElmt
 */
class PackingLayer extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'packing_layer';

    /**
     * @var array
     */
    protected $fillable = ['ID_PACKING_LAYER', 'ID_PACKING', 'ID_PACKING_ELMT', 'THICKNESS', 'PACKING_SIDE_NUMBER', 'PACKING_LAYER_ORDER'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_PACKING_LAYER';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function packing()
    {
        return $this->belongsTo('App\\Models\\Packing', 'ID_PACKING', 'ID_PACKING');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function packingElmt()
    {
        return $this->belongsTo('App\\Models\\PackingElmt', 'ID_PACKING_ELMT', 'ID_PACKING_ELMT');
    }
}
