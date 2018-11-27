<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_COMPENTH
 * @property int $ID_COMP
 * @property mixed $COMPTEMP
 * @property mixed $COMPENTH
 * @property mixed $COMPCOND
 * @property mixed $COMPDENS
 * @property-read COMPONENT $cOMPONENT
 */
class Compenth extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'COMPENTH';

    /**
     * @var array
     */
    protected $fillable = ['ID_COMPENTH', 'ID_COMP', 'COMPTEMP', 'COMPENTH', 'COMPCOND', 'COMPDENS'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_COMPENTH';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cOMPONENT()
    {
        return $this->belongsTo('COMPONENT', 'ID_COMP', 'ID_COMP');
    }
}
