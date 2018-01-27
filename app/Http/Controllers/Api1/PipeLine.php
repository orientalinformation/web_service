<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use Carbon\Carbon;
use App\Models\Translation;
use App\Models\LineElmt;
use App\Models\CoolingFamily;
use App\Models\LineDefinition;


class PipeLine extends Controller
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

    public function findRefPipeline()
    {
        $mine =  LineElmt::where('ID_USER', $this->auth->user()->ID_USER)
        ->join('Translation', 'ID_PIPELINE_ELMT', '=', 'Translation.ID_TRANSLATION')
        ->where('Translation.TRANS_TYPE', 27)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->get();
        
        $others = LineElmt::where('ID_USER', '!=', $this->auth->user()->ID_USER)
        ->join('Translation', 'ID_PIPELINE_ELMT', '=', 'Translation.ID_TRANSLATION')
        ->where('Translation.TRANS_TYPE', 27)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->get();

        return compact('mine', 'others');
    }

    public function getListLineType()
    {
        $trans = Translation::where('TRANS_TYPE', 8)->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->get();

        return $trans;
    }

    public function getListEnergies()
    {
        $trans = Translation::join('cooling_family', 'ID_TRANSLATION', '=', 'cooling_family.ID_COOLING_FAMILY')
        ->where('TRANS_TYPE', 2)->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->get();
        
        return $trans;
    }

    public function newPipeLine() 
    {
        $current = Carbon::now('Asia/Ho_Chi_Minh');
        $idUserLogon = $this->auth->user()->ID_USER;
        $input = $this->request->all();

        if (!isset($input['LABEL']) || !isset($input['LINE_VERSION']) || !isset($input['LINE_COMMENT']) || !isset($input['MANUFACTURER']) 
        || !isset($input['ELT_TYPE']) || !isset($input['ID_COOLING_FAMILY']) || !isset($input['INSULATION_TYPE']) || !isset($input['ELMT_PRICE'])
        || !isset($input['ELT_SIZE']) || !isset($input['ELT_LOSSES_1']) || !isset($input['ELT_LOSSES_2']) || !isset($input['LINE_RELEASE']))
            throw new \Exception("Error Processing Request", 1);

        $name = $input['LABEL'];
        $version = $input['LINE_VERSION'];
        $comment = $input['LINE_COMMENT'];
        $manu = $input['MANUFACTURER'];
        $type = $input['ELT_TYPE'];
        $cooling = $input['ID_COOLING_FAMILY'];
        $insulation = $input['INSULATION_TYPE'];
        $price = $input['ELMT_PRICE'];
        $size = $input['ELT_SIZE'];
        $losses1 = $input['ELT_LOSSES_1'];
        $losses2 = $input['ELT_LOSSES_2'];
        $release = $input['LINE_RELEASE'];

        if ($price == '') $price = 0;
        
        if ($size == '') $size = 0;
        
        if ($losses1 == '') $losses1 = 0;

        if ($losses2 == '') $losses2 = 0; 

        if ($type != 1) {
            $losses2 = 0;

            if ($type != 2) $losses1 = 0; 
        }

        if ($comment == '') $comment =  'Created on ' . $current->toDateTimeString() . ' by '. $this->auth->user()->USERNAM ;

        $lineElmts = Translation::where('TRANS_TYPE', 27)->get();

        for ($i = 0; $i < count($lineElmts); $i++) { 
			if ($lineElmts[$i]->LABEL == $name) {
				return 0;
			}
        }

        $lineElmt = new LineElmt();
        $lineElmt->ID_USER = $idUserLogon;
        $lineElmt->LINE_VERSION = $version;
        $lineElmt->LINE_COMMENT = $comment;
        $lineElmt->MANUFACTURER = $manu;
        $lineElmt->ELT_TYPE = $type;
        $lineElmt->ID_COOLING_FAMILY = $cooling;
        $lineElmt->INSULATION_TYPE = $insulation;
        $lineElmt->ELMT_PRICE = $price;
        $lineElmt->ELT_SIZE = $size;
        $lineElmt->ELT_LOSSES_1 = $losses1;
        $lineElmt->ELT_LOSSES_2 = $losses2;
        $lineElmt->LINE_RELEASE = $release;
        $lineElmt->ELT_IMP_ID_STUDY = 0;
        $lineElmt->save();

        $idLineElmt = $lineElmt->ID_PIPELINE_ELMT;
        LineElmt::where('ID_PIPELINE_ELMT', $idLineElmt)
        ->update(['LINE_DATE' => $current->toDateTimeString()]);
        $translation = new Translation();
        $translation->TRANS_TYPE = 27;
        $translation->CODE_LANGUE = $this->auth->user()->CODE_LANGUE;
        $translation->ID_TRANSLATION = $idLineElmt;
        $translation->LABEL = $name;
        $translation->save();

        return 1;
    }

    public function deletePipeLine($idLineElmt)
    {
        $lineElmt = LineElmt::find($idLineElmt);

        if (!$idLineElmt) {
            return -1;
        } else {
            $lineDefinition = LineDefinition::where('ID_PIPELINE_ELMT', $idLineElmt)->get();
            if (count($lineDefinition) > 0) {
                $lineElmt->LINE_RELEASE = 5;
                $lineElmt->update();
            } else {
                $trans = Translation::where('ID_TRANSLATION', $idLineElmt)->where('TRANS_TYPE', 27)->delete();
                $lineElmt->delete();
            }
        }

        return 1;
    }

    public function updatePipeLine($idPipeLine)
    {
        $current = Carbon::now('Asia/Ho_Chi_Minh');
        $idUserLogon = $this->auth->user()->ID_USER;
        $input = $this->request->all();

        if (!isset($input['LABEL']) || !isset($input['LINE_VERSION']) || !isset($input['LINE_COMMENT']) || !isset($input['MANUFACTURER']) 
        || !isset($input['ELT_TYPE']) || !isset($input['ID_COOLING_FAMILY']) || !isset($input['INSULATION_TYPE']) || !isset($input['ELMT_PRICE'])
        || !isset($input['ELT_SIZE']) || !isset($input['ELT_LOSSES_1']) || !isset($input['ELT_LOSSES_2']) || !isset($input['LINE_RELEASE']))
            throw new \Exception("Error Processing Request", 1);

        $name = $input['LABEL'];
        $version = $input['LINE_VERSION'];
        $comment = $input['LINE_COMMENT'];
        $manu = $input['MANUFACTURER'];
        $type = $input['ELT_TYPE'];
        $cooling = $input['ID_COOLING_FAMILY'];
        $insulation = $input['INSULATION_TYPE'];
        $price = $input['ELMT_PRICE'];
        $size = $input['ELT_SIZE'];
        $losses1 = $input['ELT_LOSSES_1'];
        $losses2 = $input['ELT_LOSSES_2'];
        $release = $input['LINE_RELEASE'];

        if ($price == '') $price = 0;
        
        if ($size == '') $size = 0;
        
        if ($losses1 == '') $losses1 = 0;

        if ($losses2 == '') $losses2 = 0; 

        if ($type != 1) {
            $losses2 = 0;
            
            if ($type != 2) $losses1 = 0; 
        }

        if ($comment == '') $comment =  'Created on ' . $current->toDateTimeString() . ' by '. $this->auth->user()->USERNAM ;

        $lineElmt = LineElmt::find($idPipeLine);

        if (!$lineElmt) {
            return -1;
        } else {
            Translation::where('TRANS_TYPE', 27)->where('ID_TRANSLATION', $idPipeLine)->update(['LABEL' => $name]);;
            $lineElmt->LINE_VERSION = $version;
            $lineElmt->LINE_COMMENT = $comment;
            $lineElmt->MANUFACTURER = $manu;
            $lineElmt->ELT_TYPE = $type;
            $lineElmt->ID_COOLING_FAMILY = $cooling;
            $lineElmt->INSULATION_TYPE = $insulation;
            $lineElmt->ELMT_PRICE = $price;
            $lineElmt->ELT_SIZE = $size;
            $lineElmt->ELT_LOSSES_1 = $losses1;
            $lineElmt->ELT_LOSSES_2 = $losses2;
            $lineElmt->LINE_RELEASE = $release;
            $lineElmt->ELT_IMP_ID_STUDY = 0;
            $lineElmt->update();

            LineElmt::where('ID_PIPELINE_ELMT', $idPipeLine)
            ->update(['LINE_DATE' => $current->toDateTimeString()]);
        }

        return 1;
    }

    public function saveAsPipeLine($idOldLine)
    {
        $current = Carbon::now('Asia/Ho_Chi_Minh');
        $idUserLogon = $this->auth->user()->ID_USER;
        $input = $this->request->all();

        if (!isset($input['name']))
            throw new \Exception("Error Processing Request", 1);

        $name = $input['name'];
        $lineElmtOld = LineElmt::find($idOldLine);
        $comment = $lineElmtOld->LINE_COMMENT;
        $lineElmts = Translation::where('TRANS_TYPE', 27)->get();

        for ($i = 0; $i < count($lineElmts); $i++) { 
			if ($lineElmts[$i]->LABEL == $name) {
				return 0;
			}
        }
        $lineElmt = new LineElmt();
        $lineElmt->LINE_VERSION = 0;
        $lineElmt->LINE_RELEASE = 1;
        $lineElmt->LINE_COMMENT = $comment . ' Created on ' . $current->toDateTimeString() . ' by '. $this->auth->user()->USERNAM . '.'; 
        $lineElmt->MANUFACTURER = $lineElmtOld->MANUFACTURER;
        $lineElmt->ELT_TYPE = $lineElmtOld->ELT_TYPE;
        $lineElmt->ID_COOLING_FAMILY = $lineElmtOld->ID_COOLING_FAMILY;
        $lineElmt->INSULATION_TYPE = $lineElmtOld->INSULATION_TYPE;
        $lineElmt->ELMT_PRICE = $lineElmtOld->ELMT_PRICE;
        $lineElmt->ELT_SIZE = $lineElmtOld->ELT_SIZE;
        $lineElmt->ELT_LOSSES_1 = $lineElmtOld->ELT_LOSSES_1;
        $lineElmt->ELT_LOSSES_2 = $lineElmtOld->ELT_LOSSES_2;
        $lineElmt->ELT_IMP_ID_STUDY = 0;
        $lineElmt->ID_USER = $idUserLogon;
        $lineElmt->save();

        $idLineElmt = $lineElmt->ID_PIPELINE_ELMT;
        LineElmt::where('ID_PIPELINE_ELMT', $idLineElmt)
        ->update(['LINE_DATE' => $current->toDateTimeString()]);
        $translation = new Translation();
        $translation->TRANS_TYPE = 27;
        $translation->CODE_LANGUE = $this->auth->user()->CODE_LANGUE;
        $translation->ID_TRANSLATION = $idLineElmt;
        $translation->LABEL = $name;
        $translation->save();

        return 1;
    }
}