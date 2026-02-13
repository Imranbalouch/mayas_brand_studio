<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageType extends Model
{
    use HasFactory;

    protected $table = 'page_types';

    protected $fillable = [
        'uuid',
        'name',
        'page_type',
    ];
}
