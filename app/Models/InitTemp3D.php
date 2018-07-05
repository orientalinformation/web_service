<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_INIT_TEMP_3D
 * @property int $ID_PRODUCT_ELMT
 * @property mixed $MESH_POSITION
 * @property mixed $INIT_TEMP
 */
class InitTemp3D extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'init_temp_3d';

    /**
     * @var array
     */
    protected $fillable = ['ID_INIT_TEMP_3D', 'ID_PRODUCT_ELMT', 'MESH_POSITION', 'INIT_TEMP'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_INIT_TEMP_3D';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
