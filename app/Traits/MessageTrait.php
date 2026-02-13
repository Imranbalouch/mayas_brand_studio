<?php

// app/Traits/MessageTrait.php
namespace App\Traits;

use App\Models\Language;
use App\Models\Message_key;
use App\Models\Message_key_translation;

trait MessageTrait
{

    public function get_message($key)
    {
        
        $check_language = Language::where('is_default', '1')->first();

        if(!$check_language)
        {
            Language::where('code', 'us')->update(['is_default' => 1]);
        }


        $check_admin_language = Language::where('is_admin_default', '1')->first();

        if(!$check_admin_language)
        {
            Language::where('code', 'us')->update(['is_admin_default' => 1]);
        }


        $get_language = Language::where('is_default', '1')->first();
        $get_key = Message_key::where('key', $key)->first();

        if($get_key){

            $get_translation = Message_key_translation::where('key_id', $get_key->id)->where('language_id', $get_language->id)->first();
            
            // if($get_translation){

            //     return $get_translation->translation;

            // }else{

                $get_translation = Message_key_translation::where('key_id', $get_key->id)->where('language_id', '1')->first();
                return $get_translation->translation;

            //}

        }else{
            return "Invalid Message key";
        }

    }

}



?>