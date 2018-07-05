<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id_mesh3d_info
 * @property int $id_prod
 * @property int $nb_points
 * @property int $nb_cells
 * @property int $nb_faces
 * @property int $nb_internalfaces
 * @property int $nb_boundaries
 * @property int $nb_layers
 * @property string $file_path
 */
class Mesh3DInfo extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'mesh3d_info';

    /**
     * @var array
     */
    protected $fillable = ['id_mesh3d_info', 'id_prod', 'nb_points', 'nb_cells', 'nb_faces', 'nb_internalfaces', 'nb_boundaries', 'nb_layers', 'file_path'];

    /**
     * @var string
     */
    protected $primaryKey = 'id_mesh3d_info';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

}
