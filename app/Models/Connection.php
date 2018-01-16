<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sofa\Eloquence\Eloquence; // base trait
use Sofa\Eloquence\Mappable; // extension trait

/**
 * @property int $ID_USER
 * @property \Carbon\Carbon $DATE_CONNECTION
 * @property \Carbon\Carbon $DATE_DISCONNECTION
 * @property mixed $TYPE_DISCONNECTION
 * @property-read User $user
 */
class Connection extends Model
{
    use Eloquence, Mappable;

    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'connection';

    /**
     * @var array
     */
    protected $fillable = ['ID_USER', 'DATE_CONNECTION', 'DATE_DISCONNECTION', 'TYPE_DISCONNECTION'];

    /**
     * @var array
     */
    protected $dates = ['DATE_CONNECTION', 'DATE_DISCONNECTION'];

    /**
     * Eloquent doesn't support composite primary keys : ID_USER, DATE_CONNECTION
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
     * @var string
     */
    public $dateFormat = 'Y-m-d H:i:s.u';

    protected $hidden = [
        'user'
    ];

    protected $maps = [
        'user' => ['USERNAM']
      ];
  
      protected $appends = ['USERNAM'];
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\\Models\\User', 'ID_USER', 'ID_USER');
    }
}
