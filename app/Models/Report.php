<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Plank\Mediable\Mediable;

/**
 * @property int $ID_REPORT
 * @property int $ID_STUDY
 * @property mixed $REP_CUSTOMER
 * @property mixed $PROD_LIST
 * @property mixed $PROD_TEMP
 * @property mixed $PROD_3D
 * @property mixed $PACKING
 * @property mixed $EQUIP_LIST
 * @property mixed $EQUIP_PARAM
 * @property mixed $PIPELINE
 * @property mixed $ASSES_TERMAL
 * @property mixed $ASSES_CONSUMP
 * @property mixed $ASSES_ECO
 * @property mixed $ASSES_TR
 * @property mixed $ASSES_TR_MIN
 * @property mixed $ASSES_TR_MAX
 * @property mixed $SIZING_TR
 * @property mixed $SIZING_TR_MIN
 * @property mixed $SIZING_TR_MAX
 * @property mixed $SIZING_VALUES
 * @property mixed $SIZING_GRAPHE
 * @property mixed $SIZING_TEMP_G
 * @property mixed $SIZING_TEMP_V
 * @property mixed $SIZING_TEMP_SAMPLE
 * @property mixed $AXE1_X
 * @property mixed $AXE1_Y
 * @property mixed $AXE2_X
 * @property mixed $AXE2_Z
 * @property mixed $AXE3_Y
 * @property mixed $AXE3_Z
 * @property mixed $ISOCHRONE_G
 * @property mixed $ISOCHRONE_V
 * @property mixed $ISOCHRONE_SAMPLE
 * @property mixed $POINT1_X
 * @property mixed $POINT1_Y
 * @property mixed $POINT1_Z
 * @property mixed $POINT2_X
 * @property mixed $POINT2_Y
 * @property mixed $POINT2_Z
 * @property mixed $POINT3_X
 * @property mixed $POINT3_Y
 * @property mixed $POINT3_Z
 * @property mixed $ISOVALUE_G
 * @property mixed $ISOVALUE_V
 * @property mixed $ISOVALUE_SAMPLE
 * @property mixed $PLAN_X
 * @property mixed $PLAN_Y
 * @property mixed $PLAN_Z
 * @property mixed $CONTOUR2D_G
 * @property mixed $CONTOUR2D_SAMPLE
 * @property mixed $CONTOUR2D_TEMP_STEP
 * @property mixed $ENTHALPY_V
 * @property mixed $ENTHALPY_G
 * @property mixed $ENTHALPY_SAMPLE
 * @property string $DEST_SURNAME
 * @property string $DEST_NAME
 * @property string $DEST_FUNCTION
 * @property string $DEST_COORD
 * @property string $PHOTO_PATH
 * @property string $CUSTOMER_LOGO
 * @property mixed $CONS_SPECIFIC
 * @property mixed $CONS_OVERALL
 * @property mixed $CONS_TOTAL
 * @property mixed $CONS_HOUR
 * @property mixed $CONS_DAY
 * @property mixed $CONS_WEEK
 * @property mixed $CONS_MONTH
 * @property mixed $CONS_YEAR
 * @property mixed $CONS_EQUIP
 * @property mixed $CONS_PIPE
 * @property mixed $CONS_TANK
 * @property mixed $CONTOUR2D_OUTLINE_TIME
 * @property string $REPORT_COMMENT
 * @property string $WRITER_SURNAME
 * @property string $WRITER_NAME
 * @property string $WRITER_FUNCTION
 * @property string $WRITER_COORD
 * @property boolean $REP_CONS_PIE
 * @property mixed $CONTOUR2D_TEMP_MIN
 * @property mixed $CONTOUR2D_TEMP_MAX
 * @property-read Study $studies
 */
class Report extends Model
{
    use Mediable;
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'report';

    /**
     * @var array
     */
    protected $fillable = ['ID_REPORT', 'ID_STUDY', 'REP_CUSTOMER', 'PROD_LIST', 'PROD_TEMP', 'PROD_3D', 'PACKING', 'EQUIP_LIST', 'EQUIP_PARAM', 'PIPELINE', 'ASSES_TERMAL', 'ASSES_CONSUMP', 'ASSES_ECO', 'ASSES_TR', 'ASSES_TR_MIN', 'ASSES_TR_MAX', 'SIZING_TR', 'SIZING_TR_MIN', 'SIZING_TR_MAX', 'SIZING_VALUES', 'SIZING_GRAPHE', 'SIZING_TEMP_G', 'SIZING_TEMP_V', 'SIZING_TEMP_SAMPLE', 'AXE1_X', 'AXE1_Y', 'AXE2_X', 'AXE2_Z', 'AXE3_Y', 'AXE3_Z', 'ISOCHRONE_G', 'ISOCHRONE_V', 'ISOCHRONE_SAMPLE', 'POINT1_X', 'POINT1_Y', 'POINT1_Z', 'POINT2_X', 'POINT2_Y', 'POINT2_Z', 'POINT3_X', 'POINT3_Y', 'POINT3_Z', 'ISOVALUE_G', 'ISOVALUE_V', 'ISOVALUE_SAMPLE', 'PLAN_X', 'PLAN_Y', 'PLAN_Z', 'CONTOUR2D_G', 'CONTOUR2D_SAMPLE', 'CONTOUR2D_TEMP_STEP', 'ENTHALPY_V', 'ENTHALPY_G', 'ENTHALPY_SAMPLE', 'DEST_SURNAME', 'DEST_NAME', 'DEST_FUNCTION', 'DEST_COORD', 'PHOTO_PATH', 'CUSTOMER_LOGO', 'CONS_SPECIFIC', 'CONS_OVERALL', 'CONS_TOTAL', 'CONS_HOUR', 'CONS_DAY', 'CONS_WEEK', 'CONS_MONTH', 'CONS_YEAR', 'CONS_EQUIP', 'CONS_PIPE', 'CONS_TANK', 'CONTOUR2D_OUTLINE_TIME', 'REPORT_COMMENT', 'WRITER_SURNAME', 'WRITER_NAME', 'WRITER_FUNCTION', 'WRITER_COORD', 'REP_CONS_PIE', 'CONTOUR2D_TEMP_MIN', 'CONTOUR2D_TEMP_MAX'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_REPORT';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    protected $casts = [
        'POINT1_X' => 'double',
        'POINT1_Y' => 'double',
        'POINT1_Z' => 'double',
        'POINT2_X' => 'double',
        'POINT2_Y' => 'double',
        'POINT2_Z' => 'double',
        'POINT3_X' => 'double',
        'POINT3_Y' => 'double',
        'POINT3_Z' => 'double',
        'AXE1_X' => 'double',
        'AXE1_Y' => 'double',
        'AXE2_X' => 'double',
        'AXE2_Z' => 'double',
        'AXE3_Y' => 'double',
        'AXE3_Z' => 'double',
        'PLAN_X' => 'double',
        'PLAN_Y' => 'double',
        'PLAN_Z' => 'double',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function studies()
    {
        return $this->belongsTo('App\\Models\\Study', 'ID_STUDY', 'ID_STUDY');
    }
}
