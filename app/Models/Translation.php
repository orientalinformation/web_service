<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $CODE_LANGUE
 * @property int $ID_TRANSLATION
 * @property int $TRANS_TYPE
 * @property string $LABEL
 * @property-read Language $language
 */
class Translation extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'TRANSLATION';

    /**
     * @var array
     */
    protected $fillable = ['CODE_LANGUE', 'ID_TRANSLATION', 'TRANS_TYPE', 'LABEL'];

    /**
     * Eloquent doesn't support composite primary keys : CODE_LANGUE, ID_TRANSLATION, TRANS_TYPE
     * 
     * @var string
     */
    protected $primaryKey = ['CODE_LANGUE', 'ID_TRANSLATION', 'TRANS_TYPE'];

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
