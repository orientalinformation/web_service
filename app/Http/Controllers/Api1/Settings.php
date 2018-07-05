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
namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Cryosoft\UnitsService;
use App\Cryosoft\MinMaxService;
use App\Models\MinMax;


class Settings extends Controller
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
     * @var App\Cryosoft\UnitsService
     */
    protected $units;
    
        /**
     * @var App\Cryosoft\MinMaxService
     */
    protected $minmax;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth, UnitsService $units, MinMaxService $minmax)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->units = $units;
        $this->minmax = $minmax;
    }

    public function getMyMeshParamDef()
    {
        $meshParamDef = \App\Models\MeshParamDef::find($this->auth->user()->ID_USER);
        $meshParamDef->MESH_1_SIZE = $this->units->meshes($meshParamDef->MESH_1_SIZE, 5, 1);
        $meshParamDef->MESH_2_SIZE = $this->units->meshes($meshParamDef->MESH_2_SIZE, 5, 1);
        $meshParamDef->MESH_3_SIZE = $this->units->meshes($meshParamDef->MESH_3_SIZE, 5, 1);

        return $meshParamDef;
    }

    public function saveMyMeshParamDef()
    {
        $input = $this->request->all();
        $dimension1 = floatval($input['dim1']);
        $dimension2 = floatval($input['dim2']);
        $dimension3 = floatval($input['dim3']);

        $checkValue1 = $this->minmax->checkMinMaxValue($this->units->meshes($dimension1, 5, 0), 1);
        $checkValue2 = $this->minmax->checkMinMaxValue($this->units->meshes($dimension2, 5, 0), 1);
        $checkValue3 = $this->minmax->checkMinMaxValue($this->units->meshes($dimension3, 5, 0), 1);

        if ( !$checkValue1 ) {
            $mm = $this->minmax->getMinMaxMesh(1);
            return  [
                "Message" => "Value out of range in Dimension 1 (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        if ( !$checkValue2 ) {
            $mm = $this->minmax->getMinMaxMesh(1);
            return  [
                "Message" => "Value out of range in Dimension 2 (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        if ( !$checkValue3 ) {
            $mm = $this->minmax->getMinMaxMesh(1);
            return  [
                "Message" => "Value out of range in Dimension 3 (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $meshParamDef = \App\Models\MeshParamDef::find($this->auth->user()->ID_USER);

        if ($meshParamDef) {
            if (isset($input['dim1'])) $meshParamDef->MESH_1_SIZE = $this->units->meshes($dimension1, 5, 0);
            if (isset($input['dim2'])) $meshParamDef->MESH_2_SIZE = $this->units->meshes($dimension2, 5, 0);
            if (isset($input['dim3'])) $meshParamDef->MESH_3_SIZE = $this->units->meshes($dimension3, 5, 0);
            $meshParamDef->save();
        }

        return 1;
    }

    public function getMyTempRecordPtsDef()
    {
        $tempRecordPtsDef = \App\Models\TempRecordPtsDef::find($this->auth->user()->ID_USER);
        return $tempRecordPtsDef;
    }

    public function saveMyTempRecordPtsDef()
    {
        $input = $this->request->all();

        $axis1TopSurf = intval($input['axis1TopSurf']);
        $axis2TopSurf = intval($input['axis2TopSurf']);
        $axis3TopSurf = intval($input['axis3TopSurf']);

        $axis1IntPt = intval($input['axis1IntPt']);
        $axis2IntPt = intval($input['axis2IntPt']);
        $axis3IntPt = intval($input['axis3IntPt']);

        $axis1BotSurf = intval($input['axis1BotSurf']);
        $axis2BotSurf = intval($input['axis2BotSurf']);
        $axis3BotSurf = intval($input['axis3BotSurf']);

        $axis1PL23 = intval($input['axis1PL23']);
        $axis2PL13 = intval($input['axis2PL13']);
        $axis3PL12 = intval($input['axis3PL12']);

        $axis2Axe1 = intval($input['axis2Axe1']);
        $axis3Axe1 = intval($input['axis3Axe1']);
        $axis1Axe2 = intval($input['axis1Axe2']);
        $axis3Axe2 = intval($input['axis3Axe2']);
        $axis1Axe3 = intval($input['axis1Axe3']);
        $axis2Axe3 = intval($input['axis2Axe3']);



        $checkAxis1TopSurf = $this->minmax->checkMinMaxValue($axis1TopSurf, 1089);
        if ( !$checkAxis1TopSurf ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Top- X (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis2TopSurf = $this->minmax->checkMinMaxValue($axis2TopSurf, 1089);
        if ( !$checkAxis2TopSurf ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Top- Y (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis3TopSurf = $this->minmax->checkMinMaxValue($axis3TopSurf, 1089);
        if ( !$checkAxis3TopSurf ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Top- Z (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis1IntPt = $this->minmax->checkMinMaxValue($axis1IntPt, 1089);
        if ( !$checkAxis1IntPt ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Inside- X (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis2IntPt = $this->minmax->checkMinMaxValue($axis2IntPt, 1089);
        if ( !$checkAxis2IntPt ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Inside- Y (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis3IntPt = $this->minmax->checkMinMaxValue($axis3IntPt, 1089);
        if ( !$checkAxis3IntPt ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Inside- Z (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis1BotSurf = $this->minmax->checkMinMaxValue($axis1BotSurf, 1089);
        if ( !$checkAxis1BotSurf ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Bottom- X (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis2BotSurf = $this->minmax->checkMinMaxValue($axis2BotSurf, 1089);
        if ( !$checkAxis2BotSurf ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Bottom- Y (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis3BotSurf = $this->minmax->checkMinMaxValue($axis3BotSurf, 1089);
        if ( !$checkAxis3BotSurf ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Bottom- Z (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis3PL12 = $this->minmax->checkMinMaxValue($axis3PL12, 1089);
        if ( !$checkAxis3PL12 ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Plan 12- Y (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis2PL13 = $this->minmax->checkMinMaxValue($axis2PL13, 1089);
        if ( !$checkAxis2PL13 ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Plan 13- Z (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis1PL23 = $this->minmax->checkMinMaxValue($axis1PL23, 1089);
        if ( !$checkAxis1PL23 ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Plan 23- X (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis2Axe1 = $this->minmax->checkMinMaxValue($axis2Axe1, 1089);
        if ( !$checkAxis2Axe1 ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Axis 1- Y (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis3Axe1 = $this->minmax->checkMinMaxValue($axis3Axe1, 1089);
        if ( !$checkAxis3Axe1 ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Axis 1- Z (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis1Axe2 = $this->minmax->checkMinMaxValue($axis1Axe2, 1089);
        if ( !$checkAxis1Axe2 ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Axis 2- X (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis3Axe2 = $this->minmax->checkMinMaxValue($axis3Axe2, 1089);
        if ( !$checkAxis3Axe2 ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Axis 2- Z (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis1Axe3 = $this->minmax->checkMinMaxValue($axis1Axe3, 1089);
        if ( !$checkAxis1Axe3 ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Axis 3- X (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAxis2Axe3 = $this->minmax->checkMinMaxValue($axis2Axe3, 1089);
        if ( !$checkAxis2Axe3 ) {
            $mm = $this->minmax->getMinMaxLimitItem(1089);
            return  [
                "Message" => "Value out of range in Axis 3- Y (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $tempRecordPtsDef = \App\Models\TempRecordPtsDef::find($this->auth->user()->ID_USER);

        if ($tempRecordPtsDef != null) {

            if (isset($input['axis1TopSurf'])) $tempRecordPtsDef->AXIS1_PT_TOP_SURF_DEF = $axis1TopSurf;
            if (isset($input['axis2TopSurf'])) $tempRecordPtsDef->AXIS2_PT_TOP_SURF_DEF = $axis2TopSurf;
            if (isset($input['axis3TopSurf'])) $tempRecordPtsDef->AXIS3_PT_TOP_SURF_DEF = $axis3TopSurf;

            if (isset($input['axis1IntPt'])) $tempRecordPtsDef->AXIS1_PT_INT_PT_DEF = $axis1IntPt;
            if (isset($input['axis2IntPt'])) $tempRecordPtsDef->AXIS2_PT_INT_PT_DEF = $axis2IntPt;
            if (isset($input['axis3IntPt'])) $tempRecordPtsDef->AXIS3_PT_INT_PT_DEF = $axis3IntPt;

            if (isset($input['axis1BotSurf'])) $tempRecordPtsDef->AXIS1_PT_BOT_SURF_DEF = $axis1BotSurf;
            if (isset($input['axis2BotSurf'])) $tempRecordPtsDef->AXIS2_PT_BOT_SURF_DEF = $axis2BotSurf;
            if (isset($input['axis3BotSurf'])) $tempRecordPtsDef->AXIS3_PT_BOT_SURF_DEF = $axis3BotSurf;

            if (isset($input['axis1PL23'])) $tempRecordPtsDef->AXIS1_PL_2_3_DEF = $axis1PL23;
            if (isset($input['axis2PL13'])) $tempRecordPtsDef->AXIS2_PL_1_3_DEF = $axis2PL13;
            if (isset($input['axis3PL12'])) $tempRecordPtsDef->AXIS3_PL_1_2_DEF = $axis3PL12;

            if (isset($input['axis2Axe1'])) $tempRecordPtsDef->AXIS2_AX_1_DEF = $axis2Axe1;
            if (isset($input['axis3Axe1'])) $tempRecordPtsDef->AXIS3_AX_1_DEF = $axis3Axe1;

            if (isset($input['axis1Axe2'])) $tempRecordPtsDef->AXIS1_AX_2_DEF = $axis1Axe2;
            if (isset($input['axis3Axe2'])) $tempRecordPtsDef->AXIS3_AX_2_DEF = $axis3Axe2;

            if (isset($input['axis1Axe3'])) $tempRecordPtsDef->AXIS1_AX_3_DEF = $axis1Axe3;
            if (isset($input['axis2Axe3'])) $tempRecordPtsDef->AXIS2_AX_3_DEF = $axis2Axe3;

            $tempRecordPtsDef->save();
            
            return 1;
        }
    }

    public function getMyCalculationParametersDef()
    {
        $calculationparametersdef = \App\Models\CalculationParametersDef::find($this->auth->user()->ID_USER);
        if ($calculationparametersdef) {
            $calculationparametersdef->MAX_IT_NB_DEF = number_format((float)$calculationparametersdef->MAX_IT_NB_DEF, 2, '.', '');
            $calculationparametersdef->RELAX_COEFF_DEF = number_format((float)$calculationparametersdef->RELAX_COEFF_DEF, 2, '.', '');
            $calculationparametersdef->PRECISION_REQUEST_DEF = number_format((float)$calculationparametersdef->PRECISION_REQUEST_DEF, 2, '.', '');

            $calculationparametersdef->STOP_TOP_SURF_DEF = $this->units->prodTemperature($calculationparametersdef->STOP_TOP_SURF_DEF, 1, 1);
            $calculationparametersdef->STOP_INT_DEF = $this->units->prodTemperature($calculationparametersdef->STOP_INT_DEF, 1, 1);
            $calculationparametersdef->STOP_BOTTOM_SURF_DEF = $this->units->prodTemperature($calculationparametersdef->STOP_BOTTOM_SURF_DEF, 1, 1);
            $calculationparametersdef->STOP_AVG_DEF = $this->units->prodTemperature($calculationparametersdef->STOP_AVG_DEF, 1, 1);

            $calculationparametersdef->STUDY_ALPHA_TOP_DEF = $this->units->convectionCoeff($calculationparametersdef->STUDY_ALPHA_TOP_DEF, 2, 1);
            $calculationparametersdef->STUDY_ALPHA_BOTTOM_DEF = $this->units->convectionCoeff($calculationparametersdef->STUDY_ALPHA_BOTTOM_DEF, 2, 1);
            $calculationparametersdef->STUDY_ALPHA_LEFT_DEF = $this->units->convectionCoeff($calculationparametersdef->STUDY_ALPHA_LEFT_DEF, 2, 1);
            $calculationparametersdef->STUDY_ALPHA_RIGHT_DEF = $this->units->convectionCoeff($calculationparametersdef->STUDY_ALPHA_RIGHT_DEF, 2, 1);
            $calculationparametersdef->STUDY_ALPHA_FRONT_DEF = $this->units->convectionCoeff($calculationparametersdef->STUDY_ALPHA_FRONT_DEF, 2, 1);
            $calculationparametersdef->STUDY_ALPHA_REAR_DEF = $this->units->convectionCoeff($calculationparametersdef->STUDY_ALPHA_REAR_DEF, 2, 1);

            $calculationparametersdef->TIME_STEP_DEF = $this->units->time($calculationparametersdef->TIME_STEP_DEF, 3, 1);
        }

        return $calculationparametersdef;
    }

    public function saveMyCalculationParametersDef()
    {
        $input = $this->request->all();
        $ishorizScanDef = intval($input['ishorizScanDef']);

        $maxIter = floor($input['maxIter']);
        $relaxCoef = doubleval($input['relaxCoef']);
        $precision = doubleval($input['precision']);
        $isVertScanDef = intval($input['isVertScanDef']);
        $stopTopSurfDef = doubleval($input['stopTopSurfDef']);
        $stopIntDef = doubleval($input['stopIntDef']);
        $stopBottomSurfDef = doubleval($input['stopBottomSurfDef']);
        $stopAvgDef = doubleval($input['stopAvgDef']);
        $isStudyAlphaTopFixedDef = intval($input['isStudyAlphaTopFixedDef']);
        $isStudyAlphaBottomFixedDef = intval($input['isStudyAlphaBottomFixedDef']);
        $isStudyAlphaLeftFixedDef = intval($input['isStudyAlphaLeftFixedDef']);
        $isStudyAlphaRightFixedDef = intval($input['isStudyAlphaRightFixedDef']);
        $isStudyAlphaFrontFixedDef = intval($input['isStudyAlphaFrontFixedDef']);
        $isStudyAlphaRearFixedDef = intval($input['isStudyAlphaRearFixedDef']);
        $studyAlphaTopDef = doubleval($input['studyAlphaTopDef']);
        $studyAlphaBottomDef = doubleval($input['studyAlphaBottomDef']);
        $studyAlphaLeftDef = doubleval($input['studyAlphaLeftDef']);
        $studyAlphaRightDef = doubleval($input['studyAlphaRightDef']);
        $studyAlphaFrontDef = doubleval($input['studyAlphaFrontDef']);
        $studyAlphaRearDef = doubleval($input['studyAlphaRearDef']);
        $storageStepDef = intval($input['storageStepDef']);
        $precisionLogStepDef = intval($input['precisionLogStepDef']);
        $timeStepDef = doubleval($input['timeStepDef']);

        $checkMaxIter = $this->minmax->checkMinMaxValue($maxIter, 1010);
        if ( !$checkMaxIter ) {
            $mm = $this->minmax->getMinMaxLimitItem(1010);
            return  [
                "Message" => "Value out of range in Max of iterations (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkRelaxCoef = $this->minmax->checkMinMaxValue($relaxCoef, 1012);
        if ( !$checkRelaxCoef ) {
            $mm = $this->minmax->getMinMaxLimitItemRelaxCoef(1012, 0);
            return  [
                "Message" => "Value out of range in Coef. of relaxation (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkPrecision = $this->minmax->checkMinMaxValue($precision, 1019);
        if ( !$checkPrecision ) {
            $mm = $this->minmax->getMinMaxLimitItem(1019);
            return  [
                "Message" => "Value out of range in Precision of numerical modelling (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkTopSurfDef = $this->minmax->checkMinMaxValue($this->units->prodTemperature($stopTopSurfDef, 2, 0), 1014);
        if (!$checkTopSurfDef) {
            $mm = $this->minmax->getMinMaxProdTemperature(1014);
            return  [
                "Message" => "Value out of range in Surface (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkIntDef = $this->minmax->checkMinMaxValue($this->units->prodTemperature($stopIntDef, 2, 0), 1015);
        if (!$checkIntDef) {
            $mm = $this->minmax->getMinMaxProdTemperature(1015);
            return  [
                "Message" => "Value out of range in Internal (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkBottomSurfDef = $this->minmax->checkMinMaxValue($this->units->prodTemperature($stopBottomSurfDef, 2, 0), 1016);
        if (!$checkBottomSurfDef) {
            $mm = $this->minmax->getMinMaxProdTemperature(1016);
            return  [
                "Message" => "Value out of range in Bottom (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAvgDef = $this->minmax->checkMinMaxValue($this->units->prodTemperature($stopAvgDef, 2, 0), 1017);
        if (!$checkAvgDef) {
            $mm = $this->minmax->getMinMaxProdTemperature(1017);
            return  [
                "Message" => "Value out of range in Average (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkTimeStepDef = $this->minmax->checkMinMaxValue($this->units->time($timeStepDef, 3, 0), 1013);
        if (!$checkTimeStepDef) {
            $mm = $this->minmax->getMinMaxTime(1013);
            return  [
                "Message" => "Value out of range in Time Step (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAlphaTopDef = $this->minmax->checkMinMaxValue($this->units->convectionCoeff($studyAlphaTopDef, 2, 0), 1018);
        if (!$checkAlphaTopDef) {
            $mm = $this->minmax->getMinMaxCoeff(1018, 2);
            return  [
                "Message" => "Value out of range in Alpha top (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAlphaBottomDef = $this->minmax->checkMinMaxValue($this->units->convectionCoeff($studyAlphaBottomDef, 3, 0), 1018);
        if (!$checkAlphaBottomDef) {
            $mm = $this->minmax->getMinMaxCoeff(1018, 2);
            return  [
                "Message" => "Value out of range in Alpha bottom (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAlphaLeftDef = $this->minmax->checkMinMaxValue($this->units->convectionCoeff($studyAlphaLeftDef, 3, 0), 1018);
        if (!$checkAlphaLeftDef) {
            $mm = $this->minmax->getMinMaxCoeff(1018, 2);
            return  [
                "Message" => "Value out of range in Alpha left (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAlphaRightDef = $this->minmax->checkMinMaxValue($this->units->convectionCoeff($studyAlphaRightDef, 3, 0), 1018);
        if (!$checkAlphaRightDef) {
            $mm = $this->minmax->getMinMaxCoeff(1018, 2);
            return  [
                "Message" => "Value out of range in Alpha right (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAlphaFrontDef = $this->minmax->checkMinMaxValue($this->units->convectionCoeff($studyAlphaFrontDef, 3, 0), 1018);
        if (!$checkAlphaFrontDef) {
            $mm = $this->minmax->getMinMaxCoeff(1018, 2);
            return  [
                "Message" => "Value out of range in Alpha front (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }
        
        $checkAlphaRearDef = $this->minmax->checkMinMaxValue($this->units->convectionCoeff($studyAlphaRearDef, 3, 0), 1018);
        if (!$checkAlphaRearDef) {
            $mm = $this->minmax->getMinMaxCoeff(1018, 2);
            return  [
                "Message" => "Value out of range in Alpha rear (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $calculationparametersdef = \App\Models\CalculationParametersDef::find($this->auth->user()->ID_USER);

        if ($calculationparametersdef != null) {
            if (isset($input['ishorizScanDef'])) $calculationparametersdef->HORIZ_SCAN_DEF = $ishorizScanDef;
            if (isset($input['maxIter'])) $calculationparametersdef->MAX_IT_NB_DEF =$maxIter;

            if (isset($input['relaxCoef'])) $calculationparametersdef->RELAX_COEFF_DEF = $relaxCoef;
            if (isset($input['precision'])) $calculationparametersdef->PRECISION_REQUEST_DEF = $precision;
            if (isset($input['isVertScanDef'])) $calculationparametersdef->VERT_SCAN_DEF = $isVertScanDef;

            if (isset($input['stopTopSurfDef'])) $calculationparametersdef->STOP_TOP_SURF_DEF = $this->units->prodTemperature($stopTopSurfDef, 2, 0);
            if (isset($input['stopIntDef'])) $calculationparametersdef->STOP_INT_DEF = $this->units->prodTemperature($stopIntDef, 2, 0);
            if (isset($input['stopBottomSurfDef'])) $calculationparametersdef->STOP_BOTTOM_SURF_DEF = $this->units->prodTemperature($stopBottomSurfDef, 2, 0);
            if (isset($input['stopAvgDef'])) $calculationparametersdef->STOP_AVG_DEF = $this->units->prodTemperature($stopAvgDef, 2, 0);

            if (isset($input['isStudyAlphaTopFixedDef'])) $calculationparametersdef->STUDY_ALPHA_TOP_FIXED_DEF = $isStudyAlphaTopFixedDef;
            if (isset($input['isStudyAlphaBottomFixedDef'])) $calculationparametersdef->STUDY_ALPHA_BOTTOM_FIXED_DEF = $isStudyAlphaBottomFixedDef;
            if (isset($input['isStudyAlphaLeftFixedDef'])) $calculationparametersdef->STUDY_ALPHA_LEFT_FIXED_DEF = $isStudyAlphaLeftFixedDef;
            if (isset($input['isStudyAlphaRightFixedDef'])) $calculationparametersdef->STUDY_ALPHA_RIGHT_FIXED_DEF = $isStudyAlphaRightFixedDef;
            if (isset($input['isStudyAlphaFrontFixedDef'])) $calculationparametersdef->STUDY_ALPHA_FRONT_FIXED_DEF = $isStudyAlphaFrontFixedDef;
            if (isset($input['isStudyAlphaRearFixedDef'])) $calculationparametersdef->STUDY_ALPHA_REAR_FIXED_DEF = $isStudyAlphaRearFixedDef;

            if (isset($input['studyAlphaTopDef'])) $calculationparametersdef->STUDY_ALPHA_TOP_DEF = $this->units->convectionCoeff($studyAlphaTopDef, 2, 0);
            if (isset($input['studyAlphaBottomDef'])) $calculationparametersdef->STUDY_ALPHA_BOTTOM_DEF = $this->units->convectionCoeff($studyAlphaBottomDef, 2, 0);
            if (isset($input['studyAlphaLeftDef'])) $calculationparametersdef->STUDY_ALPHA_LEFT_DEF = $this->units->convectionCoeff($studyAlphaLeftDef, 2, 0);
            if (isset($input['studyAlphaRightDef'])) $calculationparametersdef->STUDY_ALPHA_RIGHT_DEF = $this->units->convectionCoeff($studyAlphaRightDef, 2, 0);
            if (isset($input['studyAlphaFrontDef'])) $calculationparametersdef->STUDY_ALPHA_FRONT_DEF = $this->units->convectionCoeff($studyAlphaFrontDef, 2, 0);
            if (isset($input['studyAlphaRearDef'])) $calculationparametersdef->STUDY_ALPHA_REAR_DEF = $this->units->convectionCoeff($studyAlphaRearDef, 2, 0);

            if (isset($input['storageStepDef'])) $calculationparametersdef->STORAGE_STEP_DEF = $storageStepDef;
            if (isset($input['precisionLogStepDef'])) $calculationparametersdef->PRECISION_LOG_STEP_DEF = $precisionLogStepDef;

            if (isset($input['timeStepDef'])) $calculationparametersdef->TIME_STEP_DEF = $this->units->time($timeStepDef, 3, 0);

            $calculationparametersdef->save();

            return 1;
        }
    }
}