<?php
/****************************************************************************
 **
 ** Copyright (C) 2017 Oriental Tran.
 ** Contact: dongtp@dfm-engineering.com
 ** Company: DFM-Engineering Vietnam
 **
 ** This file is part of the cryosoft project.
 **
 **All rights reserved.
 ****************************************************************************/
namespace App\Cryosoft;
use App\Models\LineElmt;
use App\Models\LineDefinition;
use App\Models\User;
use DB;

class LineService
{
	public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        
    }

    public function getNameComboBoxLarge($elt_type, $insideDiameter, $coolingFamily, $sort) {
        $sname = LineElmt::select('ID_PIPELINE_ELMT', 'LABEL', 'LINE_RELEASE')
                ->join('Translation', 'ID_PIPELINE_ELMT', '=', 'Translation.ID_TRANSLATION')
                ->where('Translation.TRANS_TYPE', 27)->where('ELT_TYPE', '=', $elt_type)->where('INSULATION_TYPE', $sort)
                ->where('ELT_SIZE','=',$insideDiameter)->where('ID_COOLING_FAMILY', $coolingFamily)
                ->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->orderBy('LABEL', 'ASC')->get();
        
        $result = [];
        if (!empty($sname)) {
            foreach ($sname as $row) {
            
                $result['ID_PIPELINE_ELMT'] = $row->ID_PIPELINE_ELMT;
                $result['LABEL'] = $row->LABEL;
                $result['LINE_RELEASE'] = $row->LINE_RELEASE;
            }
        } else {
            $sname = '';
            $result[] = $sname;
        }
        return $result;
    }
    
	public function getNameComboBox($elt_type,$insideDiameter, $coolingFamily, $sort) {
            $sname = LineElmt::select('ID_PIPELINE_ELMT', 'LABEL', 'LINE_RELEASE', 'ID_USER')
            ->join('Translation','ID_PIPELINE_ELMT', '=', 'Translation.ID_TRANSLATION')
            ->where('Translation.TRANS_TYPE', 27)->where('ELT_TYPE', '=', $elt_type)->where('INSULATION_TYPE', $sort)
            ->where('ELT_SIZE','=',$insideDiameter)->where('ID_COOLING_FAMILY', $coolingFamily)
            ->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->orderBy('LABEL', 'ASC')->get();
        
        return $sname;
	}

	public function getNonLine($elt_type, $insideDiameter, $coolingFamily) {
        
        $nonName = LineElmt::select('ID_PIPELINE_ELMT', 'LABEL', 'LINE_RELEASE', 'ID_USER')
            ->join('Translation', 'ID_PIPELINE_ELMT', '=', 'Translation.ID_TRANSLATION')
            ->where('Translation.TRANS_TYPE', 27)->where('ELT_TYPE', '=', $elt_type)
            ->where('ELT_SIZE',$insideDiameter)->where('ID_COOLING_FAMILY', $coolingFamily)
            ->where('INSULATION_TYPE' ,'=', 0)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
            ->orderBy('LABEL', 'ASC')->get();
        return $nonName;
    }
    
    public function getStatus($lineRelease) {
        $sname = LineElmt::select('LABEL', 'LINE_VERSION')
            ->join('Translation', 'ID_PIPELINE_ELMT', '=', 'Translation.ID_TRANSLATION')->where('ID_TRANSLATION', '=', $lineRelease)
            ->where('Translation.TRANS_TYPE', 100)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->orderBy('LABEL', 'ASC')->first();
        if (!empty($sname)) {
            return $sname->LINE_VERSION. " " .$sname->LABEL;
        } else {
            return '';
        }
    }

    public function getUserLabel($idUser) {
        $idUserLabel = User::select('USERNAM')->Where('ID_USER', $idUser)->first();
        return $idUserLabel->USERNAM;
    }
    public function getLabelByIdPipeELMT ($idPipeElmt) {
        $label = LineElmt::select('LABEL','LINE_RELEASE')
        ->join('Translation', 'ID_PIPELINE_ELMT', '=', 'Translation.ID_TRANSLATION')
        ->where('Translation.TRANS_TYPE', 27)->where('ID_PIPELINE_ELMT', $idPipeElmt)
        ->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->orderBy('LABEL', 'ASC')->first();
        return $label;
    }

    public function getdiameter($coolingFamily, $insulationType) {

        $diameter = LineElmt::distinct()->select('ELT_SIZE')
            ->where('ID_COOLING_FAMILY', $coolingFamily)->where('ELT_TYPE', '<>', 2)
            ->where('INSULATION_TYPE', '=' , $insulationType)->get();
            // ->whereRaw(DB::RAW('( [LINE_RELEASE] = 3 or [LINE_RELEASE] = 4 or [LINE_RELEASE] = 2 )'))
            // ->whereRaw(DB::RAW('( [ELT_IMP_ID_STUDY] = 0  or [ELT_IMP_ID_STUDY] =  '. $study .')'))->get();
        
        return $diameter;
    }

    public function getStorageTank($coolingFamily, $insulationType) {
        $storageTank = LineElmt::distinct()->select('ELT_SIZE')
            ->where('ID_COOLING_FAMILY', $coolingFamily)->where('ELT_TYPE', '=', 2)
            ->where('INSULATION_TYPE', '=' , $insulationType)->get();
            // ->whereRaw(DB::RAW('( [LINE_RELEASE] = 3 or [LINE_RELEASE] = 4 or [LINE_RELEASE] = 2 )'))
            // ->whereRaw(DB::RAW('( [ELT_IMP_ID_STUDY] = 0  or [ELT_IMP_ID_STUDY] =  '. $study .')'))->get();
        return $storageTank;
    }

    public function createLineDefinition($idPipeGen, $idLineELMT,  $type_elmt) {
        $this->deleteLineDefinition($idPipeGen, $type_elmt);
        $lineDef = new LineDefinition();
        $lineDef->ID_PIPE_GEN = $idPipeGen;
        $lineDef->ID_PIPELINE_ELMT = $idLineELMT;
        $lineDef->TYPE_ELMT = $type_elmt;
        $lineDef->save();
        return $lineDef;
    }

    public function deleteLineDefinition($idPipeGen, $type_elmt) {
        $delLinedef = LineDefinition::where('ID_PIPE_GEN', $idPipeGen)->where('TYPE_ELMT', $type_elmt)->delete();
        return $delLinedef;
    }

    public function getIdlineElmtformLineDef($pipeGen, $elt_type) {
        $query = LineDefinition::select('ID_PIPELINE_ELMT')->where('ID_PIPE_GEN', $pipeGen)->where('TYPE_ELMT', $elt_type)->first();
        return $query;
    }
}