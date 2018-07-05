<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_COOLING_FAMILY
 */
class CoolingFamily extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'cooling_family';

    /**
     * @var array
     */
    protected $fillable = ['ID_COOLING_FAMILY'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_COOLING_FAMILY';

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

}
