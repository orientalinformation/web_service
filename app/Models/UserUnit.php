<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_USER
 * @property int $ID_UNIT
 * @property-read LN2USER $lN2USER
 * @property-read UNIT $uNIT
 */
class UserUnit extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'user_unit';

    /**
     * @var array
     */
    protected $fillable = ['ID_USER', 'ID_UNIT'];

    /**
     * Eloquent doesn't support composite primary keys : ID_USER, ID_UNIT
     * 
     * @var string
     */
    protected $primaryKey = 'ID_USER';

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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lN2USER()
    {
        return $this->belongsTo('LN2USER', 'ID_USER', 'ID_USER');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uNIT()
    {
        return $this->belongsTo('UNIT', 'ID_UNIT', 'ID_UNIT');
    }
}
