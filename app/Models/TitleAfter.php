<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TitleAfter extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'titles_after';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'code';

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'name',
    ];
}
