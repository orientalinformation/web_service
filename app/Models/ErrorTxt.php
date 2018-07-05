<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $CODE_LANGUE
 * @property int $ERR_CODE
 * @property int $ERR_COMP
 * @property string $ERR_TXT
 * @property-read Language $language
 */
class ErrorTxt extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'error_txt';

    /**
     * @var array
     */
    protected $fillable = ['CODE_LANGUE', 'ERR_CODE', 'ERR_COMP', 'ERR_TXT'];

    /**
     * Eloquent doesn't support composite primary keys : CODE_LANGUE, ERR_CODE, ERR_COMP
     * 
     * @var string
     */
    protected $primaryKey = 'CODE_LANGUE';

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
    public function language()
    {
        return $this->belongsTo('App\\Models\\Language', 'CODE_LANGUE', 'CODE_LANGUE');
    }
}
