<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_TEMP_EXT
 * @property int $ID_EQUIPSERIES
 * @property mixed $TR
 * @property mixed $T_EXT
 * @property-read EQUIPSERIES $eQUIPSERIES
 */
class TempExt extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'temp_ext';

    /**
     * @var array
     */
    protected $fillable = ['ID_TEMP_EXT', 'ID_EQUIPSERIES', 'TR', 'T_EXT'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_TEMP_EXT';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function equipseries()
    {
        return $this->belongsTo('App\\Models\\Equipseries', 'ID_EQUIPSERIES', 'ID_EQUIPSERIES');
    }
}
