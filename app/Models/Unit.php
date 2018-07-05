<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_UNIT
 * @property int $TYPE_UNIT
 * @property string $SYMBOL
 * @property mixed $COEFF_A
 * @property mixed $COEFF_B
 * @property-read User[] $users
 */
class Unit extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'unit';

    /**
     * @var array
     */
    protected $fillable = ['ID_UNIT', 'TYPE_UNIT', 'SYMBOL', 'COEFF_A', 'COEFF_B'];

    protected $casts = [
        'COEFF_A' => 'double',
        'COEFF_B' => 'double',
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_UNIT';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany('App\\Models\\User', 'user_unit', 'ID_UNIT', 'ID_USER');
    }
}
