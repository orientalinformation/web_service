<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;

class ReferenceData extends Controller
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

    public function getFamilyTranslations($lang)
    {
        $langIds = [
            'en' => 1,
            'fr' => 2,
            'es' => 3,
            'de' => 4,
            'it' => 5
        ];
        $translations = \App\Models\Translation::where('TRANS_TYPE',14)
            ->where('CODE_LANGUE',$langIds[$lang])
            ->get();
        for ($i=0; $i < $translations->count(); $i++) {
            $translations[$i]->LABEL = \mb_convert_encoding($translations[$i]->LABEL, "UTF-8");
        }
        
        return $translations;
    }
}