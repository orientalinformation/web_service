<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_SHELVES
 * @property int $ID_EQUIP
 * @property mixed $SPACE
 * @property int $NB
 * @property-read EQUIPMENT $eQUIPMENT
 */
class Shelves extends Model
{
    /**
     * @var array
     */
    protected $fillable = ['ID_SHELVES', 'ID_EQUIP', 'SPACE', 'NB'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_SHELVES';

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
