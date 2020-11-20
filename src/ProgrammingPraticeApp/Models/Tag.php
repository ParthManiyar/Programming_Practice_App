<?php

namespace ProgrammingPraticeApp\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @property integer        id
 * @property string         name
 * @property \Carbon\Carbon created_at
 * @property \Carbon\Carbon update_at
 */
class Tag extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];


    /********************
     *  Relationships
     ********************/

    public function problems()
    {
        return $this->belongsToMany(Problem::class);
    }

   
}