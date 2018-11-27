<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_PRICE
 * @property int $ID_STUDY
 * @property mixed $ENERGY
 * @property mixed $ECO_IN_CRYO1
 * @property mixed $ECO_IN_PBP1
 * @property mixed $ECO_IN_CRYO2
 * @property mixed $ECO_IN_PBP2
 * @property mixed $ECO_IN_CRYO3
 * @property mixed $ECO_IN_PBP3
 * @property mixed $ECO_IN_CRYO4
 * @property mixed $ECO_IN_MINMP
 * @property mixed $ECO_IN_MAXMP
 * @property-read Study $studies
 */
class Price extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'PRICES';
    
    /**
     * @var array
     */
    protected $fillable = ['ID_PRICE', 'ID_STUDY', 'ENERGY', 'ECO_IN_CRYO1', 'ECO_IN_PBP1', 'ECO_IN_CRYO2', 'ECO_IN_PBP2', 'ECO_IN_CRYO3', 'ECO_IN_PBP3', 'ECO_IN_CRYO4', 'ECO_IN_MINMP', 'ECO_IN_MAXMP'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_PRICE';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function study()
    {
        return $this->belongsTo('App\\Models\\Study', 'ID_STUDY', 'ID_STUDY');
    }
}
