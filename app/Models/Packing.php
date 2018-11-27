<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_PACKING
 * @property int $ID_SHAPE
 * @property int $ID_STUDY
 * @property string $NOMEMBMAT
 * @property-read Shape $shape
 * @property-read Study $studies
 * @property-read PackingLayer[] $packingLayers
 */
class Packing extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'PACKING';

    /**
     * @var array
     */
    protected $fillable = ['ID_PACKING', 'ID_SHAPE', 'ID_STUDY', 'NOMEMBMAT'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_PACKING';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shape()
    {
        return $this->belongsTo('App\\Models\\Shape', 'ID_SHAPE', 'ID_SHAPE');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function studies()
    {
        return $this->belongsTo('App\\Models\\Study', 'ID_STUDY', 'ID_STUDY');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function packingLayers()
    {
        return $this->hasMany('App\\Models\\PackingLayer', 'ID_PACKING', 'ID_PACKING');
    }
}
