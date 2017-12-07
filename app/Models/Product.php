<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_PROD
 * @property int $ID_STUDY
 * @property int $ID_MESH_GENERATION
 * @property string $PRODNAME
 * @property boolean $PROD_ISO
 * @property mixed $PROD_WEIGHT
 * @property mixed $PROD_REALWEIGHT
 * @property mixed $PROD_VOLUME
 * @property-read Study $studies
 * @property-read MeshGeneration[] $meshGenerations
 * @property-read ProdcharColors[] $prodcharColors
 * @property-read ProductElmt[] $productElmts
 */
class Product extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'product';

    /**
     * @var array
     */
    protected $fillable = ['ID_PROD', 'ID_STUDY', 'ID_MESH_GENERATION', 'PRODNAME', 'PROD_ISO', 'PROD_WEIGHT', 'PROD_REALWEIGHT', 'PROD_VOLUME'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_PROD';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

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
    public function meshGenerations()
    {
        return $this->hasMany('App\\Models\\MeshGeneration', 'ID_PROD', 'ID_PROD');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prodcharColors()
    {
        return $this->hasMany('App\\Models\\ProdcharColors', 'ID_PROD', 'ID_PROD');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productElmts()
    {
        return $this->hasMany('App\\Models\\ProductElmt', 'ID_PROD', 'ID_PROD');
    }
}
