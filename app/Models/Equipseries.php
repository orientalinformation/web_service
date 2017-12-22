<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence; // base trait
use Sofa\Eloquence\Mappable; // extension trait

/**
 * @property int $ID_EQUIPSERIES
 * @property int $ID_FAMILY
 * @property string $SERIES_NAME
 * @property string $CONSTRUCTOR
 * @property-read Equipfamily $equipfamily
 * @property-read Equipment[] $equipment
 * @property-read TempExt[] $tempExts
 */
class Equipseries extends Model
{
    use Eloquence, Mappable;

    protected $hidden = ['equipfamily'];
    /**
     * @var array
     */
    protected $fillable = ['ID_EQUIPSERIES', 'ID_FAMILY', 'SERIES_NAME', 'CONSTRUCTOR'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_EQUIPSERIES';

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

    protected $maps = [
      'equipfamily' => ['BATCH_PROCESS']
    ];

    protected $appends = ['BATCH_PROCESS'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function equipfamily()
    {
        return $this->belongsTo('App\\Models\\Equipfamily', 'ID_FAMILY', 'ID_FAMILY');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function equipment()
    {
        return $this->hasMany('App\\Models\\Equipment', 'ID_EQUIPSERIES', 'ID_EQUIPSERIES');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tempExts()
    {
        return $this->hasMany('TempExt', 'ID_EQUIPSERIES', 'ID_EQUIPSERIES');
    }
}
