<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence; // base trait
use Sofa\Eloquence\Mappable; // extension trait

/**
 * @property int $ID_PACKING_ELMT
 * @property int $ID_USER
 * @property mixed $PACKING_VERSION
 * @property mixed $PACKING_RELEASE
 * @property \Carbon\Carbon $PACKING_DATE
 * @property string $PACKING_COMMENT
 * @property mixed $PACKINGCOND
 * @property int $PACK_IMP_ID_STUDY
 * @property boolean $OPEN_BY_OWNER
 * @property-read User $user
 * @property-read PackingLayer[] $packingLayers
 */
class PackingElmt extends Model
{
    use Eloquence, Mappable;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'packing_elmt';

    /**
     * @var array
     */
    protected $fillable = ['ID_PACKING_ELMT', 'ID_USER', 'PACKING_VERSION', 'PACKING_RELEASE', 'PACKING_DATE', 'PACKING_COMMENT', 'PACKINGCOND', 'PACK_IMP_ID_STUDY', 'OPEN_BY_OWNER'];

    /**
     * @var array
     */
    protected $dates = ['PACKING_DATE'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_PACKING_ELMT';
    /**
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;
    
    protected $hidden = [
        'user'
    ];

    protected $maps = [
        'user' => ['USERNAM']
      ];
  
      protected $appends = ['USERNAM'];
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\\Models\\User', 'ID_USER', 'ID_USER');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function packingLayers()
    {
        return $this->hasMany('App\\Models\\PackingLayer', 'ID_PACKING_ELMT', 'ID_PACKING_ELMT');
    }
}
