<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_FAMILY
 * @property boolean $BATCH_PROCESS
 * @property mixed $TYPE_CELL
 * @property-read Equipseries[] $equipseries
 */
class Equipfamily extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'equipfamily';

    /**
     * @var array
     */
    protected $fillable = ['ID_FAMILY', 'BATCH_PROCESS', 'TYPE_CELL'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_FAMILY';

    /**
     * Indicates if the IDs are auto-incrementing.
     * 
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function equipseries()
    {
        return $this->hasMany('Equipseries', 'ID_FAMILY', 'ID_FAMILY');
    }
}
