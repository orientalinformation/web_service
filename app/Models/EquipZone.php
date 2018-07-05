<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_EQUIP_ZONE
 * @property int $ID_EQUIP
 * @property int $EQUIP_ZONE_NUMBER
 * @property mixed $EQUIP_ZONE_LENGTH
 * @property string $EQUIP_ZONE_NAME
 * @property-read EQUIPMENT $eQUIPMENT
 */
class EquipZone extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'equip_zone';

    /**
     * @var array
     */
    protected $fillable = ['ID_EQUIP_ZONE', 'ID_EQUIP', 'EQUIP_ZONE_NUMBER', 'EQUIP_ZONE_LENGTH', 'EQUIP_ZONE_NAME'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_EQUIP_ZONE';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function eQUIPMENT()
    {
        return $this->belongsTo('EQUIPMENT', 'ID_EQUIP', 'ID_EQUIP');
    }
}
