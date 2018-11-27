<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_COMP
 * @property int $ID_USER
 * @property mixed $COMP_VERSION
 * @property mixed $COMP_RELEASE
 * @property \Carbon\Carbon $COMP_DATE
 * @property string $COMP_COMMENT
 * @property mixed $COND_DENS_MODE
 * @property mixed $SPECIFIC_HEAT
 * @property mixed $DENSITY
 * @property mixed $PROTID
 * @property mixed $LIPID
 * @property mixed $GLUCID
 * @property mixed $WATER
 * @property mixed $NON_FROZEN_WATER
 * @property mixed $SALT
 * @property mixed $AIR
 * @property mixed $CLASS_TYPE
 * @property int $SUB_FAMILY
 * @property mixed $FAT_TYPE
 * @property mixed $COMP_NATURE
 * @property mixed $FREEZE_TEMP
 * @property string $BLS_CODE
 * @property int $COMP_GEN_STATUS
 * @property int $COMP_IMP_ID_STUDY
 * @property boolean $OPEN_BY_OWNER
 * @property-read User $user
 * @property-read Compenth[] $compenths
 * @property-read ProductElmt[] $productElmts
 */
class Component extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'COMPONENT';

    /**
     * @var array
     */
    protected $fillable = ['ID_COMP', 'ID_USER', 'COMP_VERSION', 'COMP_RELEASE', 'COMP_DATE', 'COMP_COMMENT', 'COND_DENS_MODE', 'SPECIFIC_HEAT', 'DENSITY', 'PROTID', 'LIPID', 'GLUCID', 'WATER', 'NON_FROZEN_WATER', 'SALT', 'AIR', 'CLASS_TYPE', 'SUB_FAMILY', 'FAT_TYPE', 'COMP_NATURE', 'FREEZE_TEMP', 'BLS_CODE', 'COMP_GEN_STATUS', 'COMP_IMP_ID_STUDY', 'OPEN_BY_OWNER'];

    /**
     * @var array
     */
    protected $dates = ['COMP_DATE'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_COMP';

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
    public function compenths()
    {
        return $this->hasMany('App\\Models\\Compenth', 'ID_COMP', 'ID_COMP');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productElmts()
    {
        return $this->hasMany('App\\Models\\ProductElmt', 'ID_COMP', 'ID_COMP');
    }
}
