<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\PackingElmt;
use App\Models\PackingLayer;
use Carbon\Carbon;
use App\Models\Translation;



class PackingElements extends Controller
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

    public function findPackingElements() 
    {        
        $packingElmts = \App\Models\PackingElmt::all();
        return $packingElmts;
    }

    public function findRefPackingElmt() 
    {        
        $mine =  PackingElmt::where('ID_USER', $this->auth->user()->ID_USER)
        ->join('Translation', 'ID_PACKING_ELMT', '=', 'Translation.ID_TRANSLATION')
        ->where('Translation.TRANS_TYPE', 3)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->get();
        $others = PackingElmt::where('ID_USER', '!=', $this->auth->user()->ID_USER)
        ->join('Translation', 'ID_PACKING_ELMT', '=', 'Translation.ID_TRANSLATION')
        ->where('Translation.TRANS_TYPE', 3)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->get();

        return compact('mine', 'others');
    }

    public function newPacking()
    {
        $current = Carbon::now('Asia/Ho_Chi_Minh');
        $idUserLogon = $this->auth->user()->ID_USER;
        $input = $this->request->all();
        
        if (isset($input['LABEL'])) $name = $input['LABEL'];

        if (isset($input['PACKING_VERSION'])) $version = $input['PACKING_VERSION'];

        if (isset($input['PACKINGCOND'])) $cond = $input['PACKINGCOND'];

        if (isset($input['PACKING_COMMENT'])) $comment = $input['PACKING_COMMENT'];

        if (isset($input['PACKING_RELEASE'])) $release = $input['PACKING_RELEASE'];

        if ($comment == '') $comment =  'Created on ' . $current->toDateTimeString() . ' by '. $this->auth->user()->USERNAM ;

        $packingElmts = Translation::where('TRANS_TYPE', 3)->get();
        $idPackExist = 0;

        for ($i = 0; $i < count($packingElmts); $i++) { 

			if ($packingElmts[$i]->LABEL == $name) {
                $idPackExist = $packingElmts[$i]->ID_TRANSLATION;
                $packExist = PackingElmt::find(intval($idPackExist));

                if ($packExist) {

                    if (doubleval($packExist->PACKING_VERSION) == doubleval($version)) {

                        return 0;
                    }
                }
			}
        }
        
        $packingElmt = new PackingElmt();
        $packingElmt->PACKING_VERSION = $version;
        $packingElmt->PACKINGCOND = $cond;
        $packingElmt->PACKING_COMMENT = $comment;
        $packingElmt->PACKING_RELEASE = $release;
        $packingElmt->PACK_IMP_ID_STUDY = 0;
        $packingElmt->ID_USER = $idUserLogon;
        $packingElmt->save();
        $idPackingElmt = $packingElmt->ID_PACKING_ELMT;

        PackingElmt::where('ID_PACKING_ELMT', $idPackingElmt)
        ->update(['PACKING_DATE' => $current->toDateTimeString()]);
        $translation = new Translation();
        $translation->TRANS_TYPE = 3;
        $translation->CODE_LANGUE = $this->auth->user()->CODE_LANGUE;
        $translation->ID_TRANSLATION = $idPackingElmt;
        $translation->LABEL = $name;
        $translation->save();

        return PackingElmt::where('ID_USER', $this->auth->user()->ID_USER)
        ->join('Translation', 'ID_PACKING_ELMT', '=', 'Translation.ID_TRANSLATION')
        ->where('Translation.TRANS_TYPE', 3)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->where('ID_PACKING_ELMT', $idPackingElmt)->first();
    }

    public function deletePacking($idPacking)
    {   
        $packingElmt = PackingElmt::find($idPacking);

        if (!$packingElmt) {
            return -1;
        } else {
            if ($packingElmt->OPEN_BY_OWNER) {
                $packingElmt->OPEN_BY_OWNER = 0;
                $packingElmt->update();
            }
            $packingLayers = PackingLayer::where('ID_PACKING_ELMT', $idPacking)->get();
            if (count($packingLayers) > 0) {
                $packingElmt->PACKING_RELEASE = 5;
                $packingElmt->update();
            } else {
                $trans = Translation::where('ID_TRANSLATION', $idPacking)->where('TRANS_TYPE', 3)->delete();
                $packingElmt->delete();
            }
        }

        return 1;
    }

    public function updatePacking()
    {  
        $input = $this->request->all();

        if (isset($input['ID_PACKING_ELMT'])) $idPacking = $input['ID_PACKING_ELMT'];

        if (isset($input['LABEL'])) $name = $input['LABEL'];

        if (isset($input['PACKING_VERSION'])) $version = $input['PACKING_VERSION'];

        if (isset($input['PACKINGCOND'])) $cond = $input['PACKINGCOND'];

        if (isset($input['PACKING_COMMENT'])) $comment = $input['PACKING_COMMENT'];

        if (isset($input['PACKING_RELEASE'])) $release = $input['PACKING_RELEASE'];

        $packingElmt = PackingElmt::find($idPacking);

        if (!$packingElmt) {
            return -1;
        } else {
            $packCurr = Translation::where('TRANS_TYPE', 3)->where('ID_TRANSLATION', $idPacking)->first();

            if ($packCurr) {
                if ($packCurr->LABEL != $name) {
                    $listLabelPacking = Translation::where('TRANS_TYPE', 3)->get();
                    $idPackExist = 0;

                    for ($i = 0; $i < count($listLabelPacking); $i++) { 

                        if ($listLabelPacking[$i]->LABEL == $name) {
                            $idPackExist = $listLabelPacking[$i]->ID_TRANSLATION;
                            $packExist = PackingElmt::find(intval($idPackExist));

                            if ($packExist) {

                                if (doubleval($packExist->PACKING_VERSION) == doubleval($version)) {

                                    return 0;
                                }
                            }
                        }
                    }
                }
                $packingElmtName = Translation::where('TRANS_TYPE', 3)->where('ID_TRANSLATION', $idPacking)
                ->update(['LABEL' => $name]);
                $current = Carbon::now('Asia/Ho_Chi_Minh');
                $packingElmt->PACKING_VERSION = $version;
                $packingElmt->PACKINGCOND = $cond;
                $packingElmt->PACKING_COMMENT = $comment;
                $packingElmt->PACKING_RELEASE = $release;
                $packingElmt->update();
                PackingElmt::where('ID_PACKING_ELMT', $idPacking)
                ->update(['PACKING_DATE' => $current->toDateTimeString()]);
            }

            return PackingElmt::where('ID_USER', $this->auth->user()->ID_USER)
            ->join('Translation', 'ID_PACKING_ELMT', '=', 'Translation.ID_TRANSLATION')
            ->where('Translation.TRANS_TYPE', 3)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
            ->where('ID_PACKING_ELMT', $idPacking)->first();
        }
    }

    public function saveAsPacking() 
    {
        $input = $this->request->all();

        if (isset($input['ID_PACKING_ELMT'])) $idPackingOld = $input['ID_PACKING_ELMT'];

        if (isset($input['LABEL'])) $name = $input['LABEL'];

        if (isset($input['PACKING_VERSION'])) $version = $input['PACKING_VERSION'];

        $packingOld = PackingElmt::find($idPackingOld);
        $cond = $packingOld->PACKINGCOND;
        $comment = $packingOld->PACKING_COMMENT;
        $listLabelPacking = Translation::where('TRANS_TYPE', 3)->get();
        $idPackExist = 0;

        for ($i = 0; $i < count($listLabelPacking); $i++) { 

			if ($listLabelPacking[$i]->LABEL == $name) {
                $idPackExist = $listLabelPacking[$i]->ID_TRANSLATION;
                $packExist = PackingElmt::find(intval($idPackExist));

                if ($packExist) {

                    if (doubleval($packExist->PACKING_VERSION) == doubleval($version)) {

                        return 0;
                    }
                }
			}
        }
        $current = Carbon::now('Asia/Ho_Chi_Minh');
        $idUserLogon = $this->auth->user()->ID_USER;
        $packingElmt = new PackingElmt();
        $packingElmt->PACKING_VERSION = $version;
        $packingElmt->PACKINGCOND = $cond;
        $packingElmt->PACKING_COMMENT = $comment . ' Created on ' . $current->toDateTimeString() . ' by '. $this->auth->user()->USERNAM . '.';
        $packingElmt->PACKING_RELEASE = 1;
        $packingElmt->PACK_IMP_ID_STUDY = 0;
        $packingElmt->ID_USER = $idUserLogon;
        $packingElmt->save();
        $idPackingElmt = $packingElmt->ID_PACKING_ELMT;

        PackingElmt::where('ID_PACKING_ELMT', $idPackingElmt)
        ->update(['PACKING_DATE' => $current->toDateTimeString()]);
        $translation = new Translation();
        $translation->TRANS_TYPE = 3;
        $translation->CODE_LANGUE = $this->auth->user()->CODE_LANGUE;
        $translation->ID_TRANSLATION = $idPackingElmt;
        $translation->LABEL = $name;
        $translation->save();

        return PackingElmt::where('ID_USER', $this->auth->user()->ID_USER)
            ->join('Translation', 'ID_PACKING_ELMT', '=', 'Translation.ID_TRANSLATION')
            ->where('Translation.TRANS_TYPE', 3)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
            ->where('ID_PACKING_ELMT', $idPackingElmt)->first();
    }   
}
