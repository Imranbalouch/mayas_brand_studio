<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailTemplate extends Model
{

    protected $table = 'email_templates';
    
    protected $fillable = [
        'uuid',
        'template_slug',
        'template_title',
        'subject',
        'form_name',
        'description',
        'body',
        'send_as_plaintext',
        'disabled',
        'type'
    ];

    protected $casts = [
        'send_as_plaintext' => 'boolean',
        'disabled' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->firstOrFail();
    }

    // Replace merge fields in the template
    public function replaceMergeFields($mergeData)
    {
        $subject = $this->subject;
        $body = $this->body;

        foreach ($mergeData as $key => $value) {
            $placeholder = '{' . $key . '}';
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body
        ];
    }
}