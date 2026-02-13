<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactUs extends Model
{
    use HasFactory;

    protected $table = 'contact_us';

    public function dynamicForm()
    {
        return $this->belongsTo(DynamicForm::class, 'form_id','uuid');
    }
}
