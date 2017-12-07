<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_EQUIP
 * @property int $ID_EQUIPSERIES
 * @property int $ID_COOLING_FAMILY
 * @property int $ID_USER
 * @property int $ID_EQUIPGENERATION
 * @property string $EQUIP_NAME
 * @property mixed $EQUIP_VERSION
 * @property mixed $EQUIP_RELEASE
 * @property \Carbon\Carbon $EQUIP_DATE
 * @property string $EQUIP_COMMENT
 * @property string $EQUIPPICT
 * @property boolean $STD
 * @property mixed $EQP_LENGTH
 * @property mixed $EQP_WIDTH
 * @property mixed $EQP_HEIGHT
 * @property mixed $MODUL_LENGTH
 * @property mixed $NB_MAX_MODUL
 * @property mixed $NB_TR
 * @property mixed $NB_TS
 * @property mixed $NB_VC
 * @property mixed $BUYING_COST
 * @property mixed $RENTAL_COST
 * @property mixed $INSTALL_COST
 * @property mixed $MAX_FLOW_RATE
 * @property mixed $MAX_NOZZLES_BY_RAMP
 * @property mixed $MAX_RAMPS
 * @property int $NUMBER_OF_ZONES
 * @property mixed $TMP_REGUL_MIN
 * @property int $CAPABILITIES
 * @property int $ITEM_TR
 * @property int $ITEM_TS
 * @property int $ITEM_VC
 * @property int $ITEM_PRECIS
 * @property int $ITEM_TIMESTEP
 * @property mixed $DLL_IDX
 * @property mixed $FATHER_DLL_IDX
 * @property int $EQP_IMP_ID_STUDY
 * @property boolean $OPEN_BY_OWNER
 * @property-read CoolingFamily $coolingFamily
 * @property-read Equipseries $equipseries
 * @property-read User $user
 * @property-read Consumptions[] $consumptions
 * @property-read EquipGeneration[] $equipGenerations
 * @property-read EquipZone[] $equipZones
 * @property-read Equipcharact[] $equipcharacts
 * @property-read PrecalcLdgRate[] $precalcLdgRates
 * @property-read Ramps[] $ramps
 * @property-read Shelves[] $shelves
 * @property-read StudyEquipments[] $studyEquipments
 */
class Equipment extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['ID_EQUIP', 'ID_EQUIPSERIES', 'ID_COOLING_FAMILY', 'ID_USER', 
        'ID_EQUIPGENERATION', 'EQUIP_NAME', 'EQUIP_VERSION', 'EQUIP_RELEASE', 'EQUIP_DATE', 
        'EQUIP_COMMENT', 'EQUIPPICT', 'STD', 'EQP_LENGTH', 'EQP_WIDTH', 'EQP_HEIGHT', 
        'MODUL_LENGTH', 'NB_MAX_MODUL', 'NB_TR', 'NB_TS', 'NB_VC', 'BUYING_COST', 
        'RENTAL_COST', 'INSTALL_COST', 'MAX_FLOW_RATE', 'MAX_NOZZLES_BY_RAMP', 'MAX_RAMPS', 
        'NUMBER_OF_ZONES', 'TMP_REGUL_MIN', 'CAPABILITIES', 'ITEM_TR', 'ITEM_TS', 'ITEM_VC',
        'ITEM_PRECIS', 'ITEM_TIMESTEP', 'EQP_IMP_ID_STUDY', 'OPEN_BY_OWNER'
        // ,'DLL_IDX', 'FATHER_DLL_IDX'
        ];
    
    

    /**
     * @var array
     */
    protected $hidden = ['DLL_IDX', 'FATHER_DLL_IDX'];

    /**
     * @var array
     */
    protected $dates = ['EQUIP_DATE'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_EQUIP';

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
    public function coolingFamily()
    {
        return $this->belongsTo('App\\Models\\CoolingFamily', 'ID_COOLING_FAMILY', 'ID_COOLING_FAMILY');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function equipseries()
    {
        return $this->belongsTo('App\\Models\\Equipseries', 'ID_EQUIPSERIES', 'ID_EQUIPSERIES');
    }

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
    public function consumptions()
    {
        return $this->hasMany('App\\Models\\Consumptions', 'ID_EQUIP', 'ID_EQUIP');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function equipGenerations()
    {
        return $this->hasMany('App\\Models\\EquipGeneration', 'ID_EQUIP', 'ID_EQUIP');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function equipZones()
    {
        return $this->hasMany('App\\Models\\EquipZone', 'ID_EQUIP', 'ID_EQUIP');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function equipcharacts()
    {
        return $this->hasMany('App\\Models\\Equipcharact', 'ID_EQUIP', 'ID_EQUIP');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function precalcLdgRates()
    {
        return $this->hasMany('App\\Models\\PrecalcLdgRate', 'ID_EQUIP', 'ID_EQUIP');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ramps()
    {
        return $this->hasMany('App\\Models\\Ramps', 'ID_EQUIP', 'ID_EQUIP');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function shelves()
    {
        return $this->hasMany('App\\Models\\Shelves', 'ID_EQUIP', 'ID_EQUIP');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function studyEquipments()
    {
        return $this->hasMany('App\\Models\\StudyEquipments', 'ID_EQUIP', 'ID_EQUIP');
    }
}
