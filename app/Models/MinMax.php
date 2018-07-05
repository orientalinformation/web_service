<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_MIN_MAX
 * @property int $LIMIT_ITEM
 * @property mixed $LIMIT_MAX
 * @property mixed $LIMIT_MIN
 * @property mixed $DEFAULT_VALUE
 * @property string $COMMENT
 */
class MinMax extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'min_max';

    /**
     * @var array
     */
    protected $fillable = ['ID_MIN_MAX', 'LIMIT_ITEM', 'LIMIT_MAX', 'LIMIT_MIN', 'DEFAULT_VALUE', 'COMMENT'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_MIN_MAX';

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
