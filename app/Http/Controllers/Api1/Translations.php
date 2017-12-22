<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;

class Translations extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    //

    public function getComponentTranslations($lang)
    {
        $langIds = [
            'en' => 1,
            'fr' => 2,
            'es' => 3,
            'de' => 4,
            'it' => 5
        ];
        $translations = \App\Models\Translation::where('TRANS_TYPE',1)
            ->where('CODE_LANGUE',$langIds[$lang])
            ->get();
        
        // @TODO: Use mutator or other more efficient way to decode the languages
        for ($i=0; $i < $translations->count(); $i++) {
            $translations[$i]->LABEL = \mb_convert_encoding($translations[$i]->LABEL, "UTF-8", "ISO-8859-1");
        }

        return $translations;
    }

    public function getPackingTranslations($lang)
    {
        $langIds = [
            'en' => 1,
            'fr' => 2,
            'es' => 3,
            'de' => 4,
            'it' => 5
        ];
        $translations = \App\Models\Translation::where('TRANS_TYPE',3)
            ->where('CODE_LANGUE',$langIds[$lang])
            ->get();
        
        // @TODO: Use mutator or other more efficient way to decode the languages
        for ($i=0; $i < $translations->count(); $i++) {
            $translations[$i]->LABEL = \mb_convert_encoding($translations[$i]->LABEL, "UTF-8");
        }

        return $translations;
    }
}
