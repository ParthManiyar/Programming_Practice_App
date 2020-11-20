<?php

namespace ProgrammingPraticeApp\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * @property integer        id
 * @property string         title
 * @property string         problemcode
 * @property integer        submission
 * @property \Carbon\Carbon created_at
 * @property \Carbon\Carbon update_at
 */
class Problem extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title','problemcode','submission','author'
    ];


    /********************
     *  Relationships
     ********************/

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

   
}