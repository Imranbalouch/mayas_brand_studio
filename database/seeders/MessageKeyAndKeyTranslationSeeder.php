<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Language;
use App\Models\Message_key;
use App\Models\Message_key_translation;
use Illuminate\Support\Str;
use Carbon\Carbon;


class MessageKeyAndKeyTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $status = '1';
        $now = Carbon::now();
        $english = Language::where('code', 'us')->first();
        $arabic = Language::where('code', 'sa')->first();

        
        $insertedId = Message_key::insertGetId([
            'uuid' => Str::uuid(),
            'key' => 'already_subscribe',
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);

        $translation_english = 'You have already subscribed';
        $translation_arabic  = 'لقد اشتركت بالفعل';

        Message_key_translation::create([
            'uuid' => Str::uuid(),
            'key_id' => $insertedId,
            'language_id' => $english->id,
            'translation' => $translation_english,
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);

        Message_key_translation::create([
            'uuid' => Str::uuid(),
            'key_id' => $insertedId,
            'language_id' => $arabic->id,
            'translation' => $translation_arabic,
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);

    }
}
