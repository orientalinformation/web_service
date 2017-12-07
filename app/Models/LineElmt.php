<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_PIPELINE_ELMT
 * @property int $ID_USER
 * @property int $ID_COOLING_FAMILY
 * @property mixed $LINE_VERSION
 * @property mixed $LINE_RELEASE
 * @property \Carbon\Carbon $LINE_DATE
 * @property string $LINE_COMMENT
 * @property string $MANUFACTURER
 * @property mixed $ELT_TYPE
 * @property mixed $INSULATION_TYPE
 * @property mixed $ELMT_PRICE
 * @property mixed $ELT_SIZE
 * @property mixed $ELT_LOSSES_1
 * @property mixed $ELT_LOSSES_2
 * @property int $ELT_IMP_ID_STUDY
 * @property boolean $OPEN_BY_OWNER
 * @property-read CoolingFamily $coolingFamily
 * @property-read User $user
 * @property-read LineDefinition[] $lineDefinitions
 */
class LineElmt extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'line_elmt';

    /**
     * @var array
     */
    protected $fillable = ['ID_PIPELINE_ELMT', 'ID_USER', 'ID_COOLING_FAMILY', 'LINE_VERSION', 'LINE_RELEASE', 'LINE_DATE', 'LINE_COMMENT', 'MANUFACTURER', 'ELT_TYPE', 'INSULATION_TYPE', 'ELMT_PRICE', 'ELT_SIZE', 'ELT_LOSSES_1', 'ELT_LOSSES_2', 'ELT_IMP_ID_STUDY', 'OPEN_BY_OWNER'];

    /**
     * @var array
     */
    protected $dates = ['LINE_DATE'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_PIPELINE_ELMT';

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
    public function user()
    {
        return $this->belongsTo('App\\Models\\User', 'ID_USER', 'ID_USER');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function lineDefinitions()
    {
        return $this->hasMany('App\\Models\\LineDefinition', 'ID_PIPELINE_ELMT', 'ID_PIPELINE_ELMT');
    }
}
