<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $CODE_LANGUE
 * @property string $LANG_NAME
 * @property-read ErrorTxt[] $errorTxts
 * @property-read Translation[] $translations
 */
class Language extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'language';

    /**
     * @var array
     */
    protected $fillable = ['CODE_LANGUE', 'LANG_NAME'];

    /**
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function errorTxts()
    {
        return $this->hasMany('App\\Models\\ErrorTxt', 'CODE_LANGUE', 'CODE_LANGUE');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        return $this->hasMany('App\\Models\\Translation', 'CODE_LANGUE', 'CODE_LANGUE');
    }
}
