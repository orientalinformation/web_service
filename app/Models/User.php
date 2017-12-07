<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;

use Sofa\Eloquence\Eloquence; // base trait
use Sofa\Eloquence\Mappable; // extension trait

/**
 * @property int $ID_USER
 * @property int $ID_MONETARY_CURRENCY
 * @property int $CODE_LANGUE
 * @property int $ID_CALC_PARAMSDEF
 * @property int $ID_TEMP_RECORD_PTS_DEF
 * @property string $USERNAM
 * @property string $USERPASS
 * @property mixed $USERPRIO
 * @property int $TRACE_LEVEL
 * @property int $USER_ENERGY
 * @property string $USER_CONSTRUCTOR
 * @property int $USER_FAMILY
 * @property int $USER_ORIGINE
 * @property int $USER_PROCESS
 * @property int $USER_MODEL
 * @property string $USERMAIL
 * @property-read CalculationParametersDef[] $calculationParametersDefs
 * @property-read Component[] $components
 * @property-read Connection[] $connections
 * @property-read Equipment[] $equipment
 * @property-read LineElmt[] $lineElmts
 * @property-read MeshParamDef $meshParamDef
 * @property-read PackingElmt[] $packingElmts
 * @property-read ProdcharColorsDef[] $prodcharColorsDefs
 * @property-read Study[] $studies
 * @property-read TempRecordPtsDef[] $tempRecordPtsDefs
 * @property-read Unit[] $units
 */
class User extends Model implements JWTSubject, AuthenticatableContract, AuthorizableContract
{

    use Authenticatable, Authorizable,
        Eloquence, Mappable;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'ln2user';

    /**
     * @var array
     */
    protected $fillable = ['ID_USER', 'ID_MONETARY_CURRENCY', 'CODE_LANGUE', 'ID_CALC_PARAMSDEF', 
        'ID_TEMP_RECORD_PTS_DEF', 'USERNAM', 'USERPRIO', 'TRACE_LEVEL', 'USER_ENERGY', 'USER_CONSTRUCTOR', 
        'USER_FAMILY', 'USER_ORIGINE', 'USER_PROCESS', 'USER_MODEL', 'USERMAIL'];

   
    /**
     * @var array
     */
    protected $hidden = [
        'USERPASS',
    ];

    /**
     * @var array
     */
    protected $maps = [
      'username' => 'USERNAM',
      'password' => 'USERPASS',
      'email' => 'USERMAIL',
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_USER';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function calculationParametersDefs()
    {
        return $this->hasMany('App\\Models\\CalculationParametersDef', 'ID_USER', 'ID_USER');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function components()
    {
        return $this->hasMany('App\\Models\\Component', 'ID_USER', 'ID_USER');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function connections()
    {
        return $this->hasMany('App\\Models\\Connection', 'ID_USER', 'ID_USER');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function equipment()
    {
        return $this->hasMany('App\\Models\\Equipment', 'ID_USER', 'ID_USER');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lineElmts()
    {
        return $this->hasMany('App\\Models\\LineElmt', 'ID_USER', 'ID_USER');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function meshParamDef()
    {
        return $this->hasOne('App\\Models\\MeshParamDef', 'ID_USER', 'ID_USER');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function packingElmts()
    {
        return $this->hasMany('App\\Models\\PackingElmt', 'ID_USER', 'ID_USER');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prodcharColorsDefs()
    {
        return $this->hasMany('App\\Models\\ProdcharColorsDef', 'ID_USER', 'ID_USER');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function studies()
    {
        return $this->hasMany('App\\Models\\Study', 'ID_USER', 'ID_USER');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tempRecordPtsDefs()
    {
        return $this->hasMany('App\\Models\\TempRecordPtsDef', 'ID_USER', 'ID_USER');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function units()
    {
        return $this->belongsToMany('App\\Models\\Unit', 'user_unit', 'ID_USER', 'ID_UNIT');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
