<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence; // base trait
use Sofa\Eloquence\Mappable; // extension trait


/**
 * @property int $ID_STUDY
 * @property int $ID_TEMP_RECORD_PTS
 * @property int $ID_PRODUCTION
 * @property int $ID_PACKING
 * @property int $ID_STUDY_RESULTS
 * @property int $ID_PROD
 * @property int $ID_USER
 * @property int $ID_PRICE
 * @property int $ID_HAVERAGE_RESULTS
 * @property int $ID_REPORT
 * @property int $ID_PRECALC_LDG_RATE_PRM
 * @property int $CALCULATION_MODE
 * @property int $CALCULATION_STATUS
 * @property string $STUDY_NAME
 * @property string $CUSTOMER
 * @property string $COMMENT_TXT
 * @property boolean $OPTION_CRYOPIPELINE
 * @property boolean $OPTION_EXHAUSTPIPELINE
 * @property boolean $OPTION_ECO
 * @property boolean $CHAINING_CONTROLS
 * @property boolean $CHAINING_ADD_COMP_ENABLE
 * @property boolean $CHAINING_NODE_DECIM_ENABLE
 * @property boolean $TO_RECALCULATE
 * @property int $PARENT_ID
 * @property int $PARENT_STUD_EQP_ID
 * @property boolean $HAS_CHILD
 * @property boolean $OPEN_BY_OWNER
 * @property-read User $user
 * @property-read HaverageResult[] $haverageResults
 * @property-read Packing[] $packings
 * @property-read PrecalcLdgRate[] $precalcLdgRates
 * @property-read PrecalcLdgRatePrm[] $precalcLdgRatePrms
 * @property-read Price[] $prices
 * @property-read Product[] $products
 * @property-read Production[] $productions
 * @property-read Report[] $reports
 * @property-read StudyEquipment[] $studyEquipments
 * @property-read StudyResult[] $studyResults
 * @property-read TempRecordPts[] $tempRecordPts
 */
class Study extends Model
{
     use Eloquence, Mappable;

    /**
     * @var array
     */
    protected $fillable = ['ID_STUDY', 'ID_TEMP_RECORD_PTS', 'ID_PRODUCTION', 'ID_PACKING', 
    'ID_STUDY_RESULTS', 'ID_PROD', 'ID_USER', 'ID_PRICE', 'ID_HAVERAGE_RESULTS', 'ID_REPORT', 
    'ID_PRECALC_LDG_RATE_PRM', 'CALCULATION_MODE', 'CALCULATION_STATUS', 'STUDY_NAME', 'CUSTOMER', 
    'COMMENT_TXT', 'OPTION_CRYOPIPELINE', 'OPTION_EXHAUSTPIPELINE', 'OPTION_ECO', 'CHAINING_CONTROLS', 
    'CHAINING_ADD_COMP_ENABLE', 'CHAINING_NODE_DECIM_ENABLE', 'TO_RECALCULATE', 'PARENT_ID', 
    'PARENT_STUD_EQP_ID', 'HAS_CHILD', 'OPEN_BY_OWNER'];

    protected $casts = [
        'OPTION_CRYOPIPELINE' => 'integer',
        'OPTION_EXHAUSTPIPELINE' => 'integer',
        'OPTION_ECO' => 'integer',
        'CHAINING_ADD_COMP_ENABLE' => 'integer',
        'CHAINING_CONTROLS' => 'integer',
        'CALCULATION_MODE' => 'integer',
        'CALCULATION_STATUS' => 'integer',
        'HAS_CHILD' => 'integer',
        'ID_HAVERAGE_RESULTS' => 'integer',
        'ID_PACKING' => 'integer',
        'ID_PRECALC_LDG_RATE_PRM' => 'integer',
        'ID_PRICE' => 'integer',
        'ID_PROD' => 'integer',
        'ID_PRODUCTION' => 'integer',
        'ID_REPORT' => 'integer',
        'ID_STUDY' => 'integer',
        'ID_STUDY_RESULTS' => 'integer',
        'ID_TEMP_RECORD_PTS' => 'integer',
        'ID_USER' => 'integer',
        'OPEN_BY_OWNER' => 'integer',
        'PARENT_ID' => 'integer',
        'PARENT_STUD_EQP_ID' => 'integer',
        'TO_RECALCULATE' => 'integer',
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_STUDY';

    protected $hidden = [
        'user'
    ];

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

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
    public function haverageResults()
    {
        return $this->hasMany('App\\Models\\HaverageResults', 'ID_STUDY', 'ID_STUDY');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function packings()
    {
        return $this->hasMany('App\\Models\\Packing', 'ID_STUDY', 'ID_STUDY');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function precalcLdgRates()
    {
        return $this->hasMany('App\\Models\\PrecalcLdgRate', 'ID_STUDY', 'ID_STUDY');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function precalcLdgRatePrms()
    {
        return $this->hasMany('App\\Models\\PrecalcLdgRatePrm', 'ID_STUDY', 'ID_STUDY');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prices()
    {
        return $this->hasMany('App\\Models\\Prices', 'ID_STUDY', 'ID_STUDY');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany('App\\Models\\Product', 'ID_STUDY', 'ID_STUDY');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productions()
    {
        return $this->hasMany('App\\Models\\Production', 'ID_STUDY', 'ID_STUDY');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reports()
    {
        return $this->hasMany('App\\Models\\Report', 'ID_STUDY', 'ID_STUDY');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function studyEquipments()
    {
        return $this->hasMany('App\\Models\\StudyEquipment', 'ID_STUDY', 'ID_STUDY');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function studyResults()
    {
        return $this->hasMany('App\\Models\\StudyResults', 'ID_STUDY', 'ID_STUDY');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tempRecordPts()
    {
        return $this->hasMany('App\\Models\\TempRecordPts', 'ID_STUDY', 'ID_STUDY');
    }
}
