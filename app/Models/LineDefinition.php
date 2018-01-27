<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $ID_LINE_DEFINITION
 * @property int $ID_PIPE_GEN
 * @property int $ID_PIPELINE_ELMT
 * @property mixed $TYPE_ELMT
 * @property-read LineElmt $lineElmt
 * @property-read PipeGen $pipeGen
 */
class LineDefinition extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'line_definition';

    /**
     * @var array
     */
    protected $fillable = ['ID_LINE_DEFINITION', 'ID_PIPE_GEN', 'ID_PIPELINE_ELMT', 'TYPE_ELMT'];

    /**
     * @var string
     */
    protected $primaryKey = 'ID_LINE_DEFINITION';

    /**
     * Indicates if the model should be timestamped.
     * 
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lineElmt()
    {
        return $this->belongsTo('LineElmt', 'ID_PIPELINE_ELMT', 'ID_PIPELINE_ELMT');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pipeGen()
    {
        return $this->belongsTo('PipeGen', 'ID_PIPE_GEN', 'ID_PIPE_GEN');
    }
}
