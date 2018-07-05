<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\Translation;
use App\Models\Language;
use App\Models\User;
use DB;

class Translations extends Controller
{
    /**
     * @var Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var Illuminate\Contracts\Auth\Factory
     */
    protected $auth;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth)
    {
        $this->request = $request;
        $this->auth = $auth;
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
        $translations = Translation::where('TRANS_TYPE',1)
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
        $translations = Translation::where('TRANS_TYPE',3)
            ->where('CODE_LANGUE',$langIds[$lang])
            ->get();
        
        // @TODO: Use mutator or other more efficient way to decode the languages
        // for ($i=0; $i < $translations->count(); $i++) {
        //     $translations[$i]->LABEL = \mb_convert_encoding($translations[$i]->LABEL, "UTF-8");
        // }

        return $translations;
    }

    public function getDefaultLanguage() {
        $userIdLang = User::Select('CODE_LANGUE')->Where('USERNAM', '=' ,'KERNEL')->first();
        $langName = Translation::where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->where('TRANS_TYPE', 9)->get();
        $referenceLangs = Translation::where('CODE_LANGUE', $userIdLang->CODE_LANGUE)
        ->orderBy('TRANS_TYPE', 'ASC')->orderBy('LABEL', 'ASC')->get();
        $translationLangs = Translation::where('CODE_LANGUE', $userIdLang->CODE_LANGUE)
        ->orderBy('TRANS_TYPE', 'ASC')->orderBy('LABEL', 'ASC')->get();
        return compact("referenceLangs", "translationLangs", "langName"); 
    }

    public function changeLabels() {
        $inputs = $this->request->all();
        foreach ($inputs as $input) {
            $langID = $input['CODE_LANGUE'];
            $id_trans = $input['ID_TRANSLATION'];
            $trans_type = $input['TRANS_TYPE'];
            $label = $input['LABEL'];
            DB::table('Translation')->where('CODE_LANGUE', $langID)->where('ID_TRANSLATION', $id_trans)
            ->where('TRANS_TYPE', $trans_type)->update(['LABEL' => $label]);
        }
    }

    public function filterTrans() {
        $input = $this->request->all();
        $id = $input['id'];
        $idtrans = $input['idtrans'];
        $referenceLangs = Translation::where('CODE_LANGUE', $id)->orderBy('TRANS_TYPE', 'ASC')->orderBy('LABEL', 'ASC')->get();
        if (count($referenceLangs) > 0) {
            foreach ($referenceLangs as $referenceLang) {
                $translations = Translation::where('ID_TRANSLATION', $referenceLang->ID_TRANSLATION)
                ->where('TRANS_TYPE', $referenceLang->TRANS_TYPE)->where('CODE_LANGUE', $idtrans)->orderBy('TRANS_TYPE', 'ASC')->orderBy('LABEL', 'ASC')->first();
                if ($translations) {
                    $translation[] = $translations;
                } else {
                    $translation[] = $referenceLang;
                }
            }
            return compact("translation", "referenceLangs");
        }
    }
}
