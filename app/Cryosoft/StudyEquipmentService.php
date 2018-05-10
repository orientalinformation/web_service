<?php

namespace App\Cryosoft;

use App\Models\StudyEquipment;
use App\Models\LayoutGeneration;
use App\Models\StudEqpPrm;
use App\Models\LayoutResults;
use App\Models\DimaResults;
use App\Models\RecordPosition;
use App\Models\Product;
use App\Models\Production;
use App\Models\TempRecordData;
use App\Models\InitialTemperature;
use App\Models\ProductElement;
use App\Models\PrecalcLdgRatePrm;
use App\Models\EquipGeneration;
use App\Models\TempExt;
use com\oxymel\ofcconveyer\Crate;
use com\oxymel\ofcconveyer\ConveyerTemplate;
use com\oxymel\ofcconveyer\ConveyerBelt;
use com\oxymel\ofcconveyer\SVGGenerator;


class StudyEquipmentService
{
    const IRREGULAR_MESH = 0;
    const REGULAR_MESH = 1;

    const MAILLAGE_MODE_REGULAR = 0;
    const MAILLAGE_MODE_IRREGULAR = 1;

    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->value = $app['App\\Cryosoft\\ValueListService'];
        $this->convert = $app['App\\Cryosoft\\UnitsConverterService'];
        $this->kernel = $app['App\\Kernel\\KernelService'];
        $this->equip = $app['App\\Cryosoft\\EquipmentsService'];
        $this->brain = $app['App\\Cryosoft\\BrainCalculateService'];
        $this->cal = $app['App\\Cryosoft\\CalculateService'];
    }

    public function calculateEquipmentParams(StudyEquipment &$sEquip) 
    {
        // runLayoutCalculator(sEquip, username, password);
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $sEquip->ID_STUDY, $sEquip->ID_STUDY_EQUIPMENTS, 1, 1, 'c:\\temp\\layout-trace.txt');
        $lcRunResult = $this->kernel->getKernelObject('LayoutCalculator')->LCCalculation($conf, 1);

        $lcTSRunResult = -1;

        if (($sEquip->equipment->CAPABILITIES & CAP_VARIABLE_TS != 0) && ($sEquip->equipment->CAPABILITIES & CAP_TS_FROM_TOC != 0)) {
            $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $sEquip->ID_STUDY, $sEquip->ID_STUDY_EQUIPMENTS, 1, 1, 'c:\\temp\\layout-ts-trace.txt');
            $lcTSRunResult = $this->kernel->getKernelObject('LayoutCalculator')->LCCalculation($conf, 2);
        }

        $doTR = false;

        if (($sEquip->equipment->CAPABILITIES & CAP_VARIABLE_TR != 0)
            && ($sEquip->equipment->CAPABILITIES & CAP_TR_FROM_TS != 0)
            && ($sEquip->equipment->CAPABILITIES & CAP_PHAMCAST_ENABLE != 0)) {
            $doTR = true;
            $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $sEquip->ID_STUDY, $sEquip->ID_STUDY_EQUIPMENTS);
            $lcRunResult = $this->kernel->getKernelObject('PhamCastCalculator')->PCCCalculation($conf, !$doTR);
        }

        if (!$doTR
            && ($sEquip->equipment->CAPABILITIES & CAP_VARIABLE_TS != 0)
            && ($sEquip->equipment->CAPABILITIES & CAP_TS_FROM_TR != 0)
            && ($sEquip->equipment->CAPABILITIES & CAP_PHAMCAST_ENABLE != 0)) {
            $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $sEquip->ID_STUDY, $sEquip->ID_STUDY_EQUIPMENTS);
            $lcRunResult = $this->kernel->getKernelObject('PhamCastCalculator')->PCCCalculation($conf, !$doTR);
        }

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $sEquip->ID_STUDY, $sEquip->ID_STUDY_EQUIPMENTS);
        $lcRunResult = $this->kernel->getKernelObject('KernelToolCalculator')->KTCalculator($conf, 1);

        $sEquip->fresh();

        return 1;
    }

    /**
     * @return \App\Models\LayoutGeneration
     */
    public function getStudyEquipmentLayoutGen(StudyEquipment &$sEquip) 
    {
        $layoutGen = LayoutGeneration::where('ID_STUDY_EQUIPMENTS', $sEquip->ID_STUDY_EQUIPMENTS)->first();
        if (!$layoutGen) {
            $layoutGen = new LayoutGeneration();
            $layoutGen->ID_STUDY_EQUIPMENTS = $sEquip->ID_STUDY_EQUIPMENTS;
            $layoutGen->PROD_POSITION = 0;

            $equipWithSpecificSize = ($sEquip->STDEQP_WIDTH != NO_SPECIFIC_SIZE) && ($sEquip->STDEQP_LENGTH != NO_SPECIFIC_SIZE);

            if ($equipWithSpecificSize) {
                $layoutGen->SHELVES_TYPE = SHELVES_USERDEFINED;
                $layoutGen->SHELVES_LENGTH = $sEquip->STDEQP_LENGTH;
                $layoutGen->SHELVES_WIDTH = $sEquip->STDEQP_WIDTH;
            } else if ($sEquip->equipment->BATCH_PROCESS) {
            // default is now euronorme
                $layoutGen->SHELVES_TYPE = SHELVES_EURONORME;
                $layoutGen->SHELVES_LENGTH = SHELVES_EURO_LENGTH;
                $layoutGen->SHELVES_WIDTH = SHELVES_EURO_WIDTH;
            } else {
                $layoutGen->SHELVES_TYPE = SHELVES_USERDEFINED;
                $layoutGen->SHELVES_LENGTH = $sEquip->equipment->EQP_LENGTH;
                $layoutGen->SHELVES_WIDTH = $sEquip->equipment->EQP_WIDTH;
            }
            $layoutGen->LENGTH_INTERVAL = INTERVAL_UNDEFINED;
            $layoutGen->WIDTH_INTERVAL = INTERVAL_UNDEFINED;

            $layoutGen->save();
        } else {
            $layoutGen->LENGTH_INTERVAL = $this->convert->prodDimension(doubleval($layoutGen->LENGTH_INTERVAL));
            $layoutGen->WIDTH_INTERVAL = $this->convert->prodDimension(doubleval($layoutGen->WIDTH_INTERVAL));
        }
        return $layoutGen;
    }

    public function getDisplayStudyEquipment(StudyEquipment &$studyEquipment)
    {
        /** @var StudyEquipment $studyEquipment */
        $equip = [
            'ID_STUDY_EQUIPMENTS' => $studyEquipment->ID_STUDY_EQUIPMENTS,
            'EQUIP_NAME' => $studyEquipment->EQUIP_NAME,
            'ID_EQUIP' => $studyEquipment->ID_EQUIP,
            'EQP_LENGTH' => $studyEquipment->EQP_LENGTH,
            'EQP_WIDTH' => $studyEquipment->EQP_WIDTH,
            'EQUIP_VERSION' => $studyEquipment->EQUIP_VERSION,
            'layoutGen' => null,
        ];

        $layoutGen = $this->getStudyEquipmentLayoutGen($studyEquipment);

        $equip['ORIENTATION'] = $layoutGen->PROD_POSITION;
        $equip['displayName'] = 'EQUIP_NAME_NOT_FOUND';
        $equip['layoutGen'] = $layoutGen;
        $layoutResults = LayoutResults::where('ID_STUDY_EQUIPMENTS', $studyEquipment->ID_STUDY_EQUIPMENTS)->first();
        if ($layoutResults) {
            $layoutResults->QUANTITY_PER_BATCH = $this->convert->mass($layoutResults->QUANTITY_PER_BATCH);
            $layoutResults->LOADING_RATE = $this->convert->toc($layoutResults->LOADING_RATE);
            $layoutResults->LEFT_RIGHT_INTERVAL = $this->convert->prodDimension($layoutResults->LEFT_RIGHT_INTERVAL);
            $layoutResults->NUMBER_PER_M = $this->convert->none($layoutResults->NUMBER_PER_M);
            $layoutResults->NUMBER_IN_WIDTH = $this->convert->none($layoutResults->NUMBER_IN_WIDTH);
        }
        
        $equip['layoutResults'] = $layoutResults;

            // determine study equipment name
        if ($studyEquipment->equipment->STD
            && !($studyEquipment->equipment->CAPABILITIES & CAP_DISPLAY_DB_NAME != 0)
            && !($studyEquipment->equipment->CAPABILITIES & CAP_EQUIP_SPECIFIC_SIZE != 0)) {
            $equip['displayName'] = $equip['EQUIP_NAME'] . " - "
                . number_format($studyEquipment->equipment->EQP_LENGTH + ($studyEquipment->NB_MODUL * $studyEquipment->equipment->MODUL_LENGTH), 2)
                . "x" . number_format($studyEquipment->equipment->EQP_WIDTH, 2) . " (v" . ($studyEquipment->EQUIP_VERSION) . ")"
                . ($studyEquipment->EQUIP_RELEASE == 3 ? ' / Active' : ''); // @TODO: translate
        } else if (($studyEquipment->equipment->CAPABILITIES & CAP_EQUIP_SPECIFIC_SIZE != 0)
            && ($studyEquipment->equipment->STDEQP_LENGTH != NO_SPECIFIC_SIZE)
            && ($studyEquipment->equipment->STDEQP_WIDTH != NO_SPECIFIC_SIZE)) {
            $equip['displayName'] = $equip['EQUIP_NAME']
                . " (v" . ($studyEquipment->EQUIP_VERSION) . ")"
                . ($studyEquipment->EQUIP_RELEASE == 3 ? ' / Active' : ''); // @TODO: translate
        } else {
            $equip['displayName'] = $equip['EQUIP_NAME']
                . ($studyEquipment->EQUIP_RELEASE == 3 ? ' / Active' : ''); // @TODO: translate
        }

        $equip['tr'] = $this->loadEquipmentData($studyEquipment, REGULATION_TEMP);
        $equip['ts'] = $this->loadEquipmentData($studyEquipment, DWELLING_TIME);
        $equip['vc'] = $this->loadEquipmentData($studyEquipment, CONVECTION_SPEED);
        $equip['dh'] = $this->loadEquipmentData($studyEquipment, ENTHALPY_VAR);
        $equip['TExt'] = (count($this->loadEquipmentData($studyEquipment, EXHAUST_TEMP)) > 0) ? $this->loadEquipmentData($studyEquipment, EXHAUST_TEMP)[0] : '';

        $equip['top_or_QperBatch'] = $this->topOrQperBatch($studyEquipment);
        return $equip;
    }

    public function findStudyEquipmentsByStudy(&$study)
    {
        $studyEquipments = StudyEquipment::where('ID_STUDY', $study->ID_STUDY)->with('equipment')->get();
        $returnStudyEquipments = [];

        foreach ($studyEquipments as $studyEquipment) {
            $equip = $this->getDisplayStudyEquipment($studyEquipment);
            array_push($returnStudyEquipments, $equip);
        }

        return $returnStudyEquipments;
    }

    /**
     * @param StudyEquipment $studyEquip
     * @param int $dataType
     */
    public function loadEquipmentData(StudyEquipment &$studyEquip, int $dataType)
    {
        $num_fields = 0;
        switch ($dataType) {
            case CONVECTION_SPEED:
                // unit_ident = UnitsConverter . CONV_SPEED_UNIT;
                $num_fields = $studyEquip->equipment->NB_VC;
                break;
            case DWELLING_TIME:
                // unit_ident = UnitsConverter . TIME_UNIT;
                $num_fields = $studyEquip->equipment->NB_TS;
                break;
            case REGULATION_TEMP:
                // unit_ident = UnitsConverter . CONTROL_TEMP_UNIT;
                $num_fields = $studyEquip->equipment->NB_TR;
                break;
            case ENTHALPY_VAR:
                // unit_ident = UnitsConverter . ENTHALPY_UNIT;
                $num_fields = $studyEquip->equipment->NB_TR;
                break;
            case EXHAUST_TEMP:
                // unit_ident = UnitsConverter . CONTROL_TEMP_UNIT;
                $num_fields = 1;
                break;
        }
        $studyEquipParams = StudEqpPrm::where('ID_STUDY_EQUIPMENTS', $studyEquip->ID_STUDY_EQUIPMENTS)
            ->where('VALUE_TYPE', '>=', $dataType)->where('VALUE_TYPE', '<', $dataType + 100)->pluck('VALUE')->toArray();
        
        $result = array_map('doubleval', $studyEquipParams);

        $data = [];
        foreach ($result as $row) {
            switch ($dataType) {
                case CONVECTION_SPEED:
                    $data[] = $this->convert->convectionSpeed($row);
                    break;

                case DWELLING_TIME:
                    $data[] = $this->convert->time($row);
                    break;
                
                case REGULATION_TEMP:
                case EXHAUST_TEMP:
                    $data[] = $this->convert->controlTemperature($row);
                    break;

                case ENTHALPY_VAR:
                    $data[] = $this->convert->enthalpy($row);
                    break;
            }
        }

        return $data;
    }

    public function topOrQperBatch(StudyEquipment &$se)
    {
        /** @var App\Models\LayoutResults $lr */
        $lr = $se->layoutResults->first();
        $returnStr = "";
        if ($se->equipment->BATCH_PROCESS == 1) {
            $returnStr = ((!$lr) || !($se->equipment->CAPABILITIES & CAP_LAYOUT_ENABLE != 0)) ?
                "" :
                $this->convert->mass($lr->QUANTITY_PER_BATCH) .
                " " . $this->convert->massSymbol() .
                "/batch"; // @TODO: translate
        } else {
            $returnStr = ((!$lr) || !($se->equipment->CAPABILITIES & CAP_LAYOUT_ENABLE != 0)) ?
                "" : $this->convert->toc($lr->LOADING_RATE) . " %";
        }

        // if ((lg . getWidthInterval() != $this->value->INTERVAL_UNDEFINED)
        //     || (lg . getLengthInterval() != $this->value->INTERVAL_UNDEFINED)) {
        //     String simg = "<br><img src=\"/cryosoft/jspPages/img/icon_info.gif\" alt=\"\" border=\"0\">";
        //     out . println(simg);
        // }
        return $returnStr;
    }

    public function isAnalogicResults(StudyEquipment &$se) 
    {
        $results = DimaResults::where('ID_STUDY_EQUIPMENTS',$se->ID_STUDY_EQUIPMENTS)->get();

        return count($results)>0;
    }

    public function setInitialTempFromAnalogicalResults(StudyEquipments &$sequip, $shape, Product &$product, Production &$production)
    {
        $offset = [0, 0, 0];
        $bret = true;
        $dimaResults = null;
        $lfTemp = 0.0;

        try {		
            $dimaResults = DimaResults::where('ID_STUDY_EQUIPMENTS', $sequip->ID_STUDY_EQUIPMENTS)->orderBy('SETPOINT','desc')->get();
            
            $nIndex = 0;
            foreach ($dimaResults as $dimaResult) {
                if ($nIndex > $this->value->TR_INDEX) break;
                $lfTemp = $dimaResult->DIMA_TFP;
                $nIndex++;
            }
            
            $this->saveInitialTemperature($shape, $offset, $lfTemp, $product, $production);
        } catch (\Exception $e) {
            throw new Exception("Error while writing initial temp from analogical results");
        }

        return $bret;
    }

    // add new setInitialTempFromAnalogicalResults1 5 parameter by oriental
    public function setInitialTempFromAnalogicalResults1(StudyEquipments &$sequip, $shape, $offset, Product &$product, Production &$production)
    {
        $bret = true;
        $dimaResults = null;
        $lfTemp = 0.0;
        
        try {       
            $dimaResults = DimaResults::where('ID_STUDY_EQUIPMENTS', $sequip->ID_STUDY_EQUIPMENTS)->orderBy('SETPOINT','desc')->get();
                
            $nIndex = 0;
            foreach ($dimaResults as $dimaResult) {
                if ($nIndex > $this->value->TR_INDEX) break;
                $lfTemp = $dimaResult->DIMA_TFP;
                $nIndex++;
            }
            
            $this->saveInitialTemperature($shape, $offset, $lfTemp, $product, $production);
        } catch (\Exception $e) {
            throw new Exception("Error while writing initial temp from analogical results");
        }

        return $bret;
    }
    

    public function setInitialTempFromSimpleNumericalResults(StudyEquipment &$sequip, $shape, Product &$product, Production &$production)
    {
        $offset = [0, 0, 0];
        $bret = false;
        $dimaResults = null;

        $lfTemp = 0.0;
        
        // Increase value to show still alive
        // cryoRun . nextCRRStatus(true);

        try {		
            //get TFP from Dima Results
            // UnnamedObjectQuery query = new UnnamedObjectQuery(DimaResults . class,"WHERE ID_STUDY_EQUIPMENTS = ?"
            //     + " AND DIMA_TYPE = ?" ,"II")
            // ;
            // query . addParameter(sequip . getIdStudyEquipments());
            // query . addParameter(ValuesList . DIMA_TYPE_DHP_CHOSEN);
            $dimaResults = DimaResults::where('ID_STUDY_EQUIPMENTS', $sequip->ID_STUDY_EQUIPMENTS)
                ->where('DIMA_TYPE', $this->value->DIMA_TYPE_DHP_CHOSEN)->first();

            // TODO: Check if dima result exists before create child study

            // Increase value to show still alive
            // cryoRun . nextCRRStatus(true);

            $lfTemp = $dimaResults->DIMA_TFP;
            
            // save initial temperature
            $this->saveInitialTemperature($shape, $offset, $lfTemp, $product, $production);
        } catch (Exception $e) {
            // LOG . error("Error while writing initial temp from analogical results", e);
            throw new Exception("Error while writing initial temp from analogical results");
        }

        return $bret;
    }

    // add new setInitialTempFromSimpleNumericalResults1 5 paramester add by oriental
    public function setInitialTempFromSimpleNumericalResults1(StudyEquipment &$sequip, $shape, $offset, Product &$product, Production &$production)
    {
        $bret = false;
        $dimaResults = null;

        $lfTemp = 0.0;

        try {       
            $dimaResults = DimaResults::where('ID_STUDY_EQUIPMENTS', $sequip->ID_STUDY_EQUIPMENTS)
                ->where('DIMA_TYPE', $this->value->DIMA_TYPE_DHP_CHOSEN)->first();

            $lfTemp = $dimaResults->DIMA_TFP;
            
            // save initial temperature
            $this->saveInitialTemperature($shape, $offset, $lfTemp, $product, $production);
        } catch (Exception $e) {
            throw new Exception("Error while writing initial temp from analogical results");
        }

        return $bret;
    }

    public function setInitialTempFromNumericalResults (StudyEquipment &$sequip, $shape, Product &$product, Production &$production)
    {
        $offset = [0,0,0];
        $bret = true;
        $counter = 0;
        $NB_TEMP_FOR_NEXTSTATUS = 25;
        
        try {
            $recPos = RecordPosition::where('ID_STUDY_EQUIPMENTS', $sequip->ID_STUDY_EQUIPMENTS)
                ->orderBy('RECORD_TIME', 'DESC')->first();
            if ($recPos) {
                // get temp record data
                $tempRecordData = TempRecordData::where([
                    ['ID_REC_POS', $recPos->ID_REC_POS],
                    ['REC_AXIS_Z_POS', '0']
                ])->orderBy('REC_AXIS_X_POS')->orderBy('REC_AXIS_Y_POS')->get();

                if ($tempRecordData) {
                    $orientation = $sequip->layoutGenerations()->first()->PROD_POSITION;
                    $NbNodesZ = 0;
                    switch ($shape) {
                        case $this->value->SLAB:
                        case $this->value->CYLINDER_STANDING:
                        case $this->value->CYLINDER_LAYING:
                        case $this->value->SPHERE:
                        case $this->value->CYLINDER_CONCENTRIC_STANDING:
                        case $this->value->CYLINDER_CONCENTRIC_LAYING:
                            $NbNodesZ = 1;
                            break;
                        case $this->value->PARALLELEPIPED_STANDING:
                        case $this->value->PARALLELEPIPED_BREADED:
                            if ($orientation == $this->value->POSITION_PARALLEL) {
                                $NbNodesZ = $product->meshGenerations()->first()->MESH_1_NB;
                            } else {
                                $NbNodesZ = $product->meshGenerations()->first()->MESH_3_NB;
                                
                            }
                            break;
                        case $this->value->PARALLELEPIPED_LAYING:
                            $NbNodesZ = $product->meshGenerations()->first()->MESH_3_NB;
                            break;
                    }
    
                    // Increase value to show still alive
                    $listTemp = [];

                    foreach ($tempRecordData as $trd) {
                        $initTemp = new InitialTemperature();
                        $initTemp->ID_PRODUCTION  = $production->ID_PRODUCTION;
                        $initTemp->INITIAL_T = $trd->TEMP;
                        
                        // propagation temperature axe Z
                        for ($i = 0; $i < $NbNodesZ; $i++) {
                            switch ($shape) {
                                case $this->value->SLAB:
                                    $initTemp->MESH_1_ORDER =  $i;
                                    $initTemp->MESH_2_ORDER = ($trd->REC_AXIS_Y_POS +$offset[1]);
                                    $initTemp->MESH_3_ORDER =  $trd->REC_AXIS_X_POS;
                                    break;
                                case $this->value->PARALLELEPIPED_STANDING:
                                case $this->value->PARALLELEPIPED_BREADED:
                                    if ($orientation == $this->value->POSITION_PARALLEL) {
                                        $initTemp->MESH_1_ORDER = ($i + $offset[0]);
                                        $initTemp->MESH_2_ORDER = ($trd->REC_AXIS_Y_POS + $offset[1]);
                                        $initTemp->MESH_3_ORDER = ($trd->REC_AXIS_X_POS + $offset[2]);
                                    } else {
                                        $initTemp->MESH_1_ORDER = ($trd->REC_AXIS_X_POS + $offset[0]);
                                        $initTemp->MESH_2_ORDER = ($trd->REC_AXIS_Y_POS + $offset[1]);
                                        $initTemp->MESH_3_ORDER = ($i +$offset[2]);
                                    }
                                    break;
                                case $this->value->PARALLELEPIPED_LAYING:
                                    $initTemp->MESH_1_ORDER =  $trd->REC_AXIS_Y_POS;
                                    $initTemp->MESH_2_ORDER = ($trd->REC_AXIS_X_POS + $offset[1]);
                                    $initTemp->MESH_3_ORDER =  $i;
                                    break;
                                case $this->value->CYLINDER_STANDING:
                                case $this->value->CYLINDER_CONCENTRIC_LAYING:
                                    $initTemp->MESH_1_ORDER =  $trd->REC_AXIS_X_POS;
                                    $initTemp->MESH_2_ORDER = ($trd->REC_AXIS_Y_POS + $offset[1]);
                                    $initTemp->MESH_3_ORDER =  $i;
                                    break;

                                case $this->value->CYLINDER_LAYING:
                                case $this->value->CYLINDER_CONCENTRIC_STANDING:
                                    $initTemp->MESH_1_ORDER =  $trd->REC_AXIS_Y_POS;
                                    $initTemp->MESH_2_ORDER = ($trd->REC_AXIS_X_POS + $offset[1]);
                                    $initTemp->MESH_3_ORDER =  $i;
                                    break;

                                case $this->value->SPHERE:
                                    $initTemp->MESH_1_ORDER =  $trd->REC_AXIS_X_POS;
                                    $initTemp->MESH_2_ORDER = ($trd->REC_AXIS_Y_POS + $offset[1]);
                                    $initTemp->MESH_3_ORDER =  $i;
                                    break;
                            }
                            
                            //create initial temperature
                            // $initTemp->save();
                            array_push($listTemp, $initTemp->toArray());
                        } // end for
                        
                    } // end foreach tempRecordData

                    $slices = array_chunk($listTemp, 100);
                    foreach ($slices as $slice) {
                        InitialTemperature::insert($slice);
                    }

                    // update production to set avg initial temp
                    $production->AVG_T_INITIAL = $sequip->AVERAGE_PRODUCT_TEMP;
                    $production->save();
                    
                    // Increase value to show still alive
                } else {
                    $bret = false;
                }
            } else {
                $bret = false;
            }
        } catch (\Exception $e) {
            throw new \Exception("Error while writing initial temp from numerical results");
        }

        return $bret;
    }

    // new setInitialTempFromNumericalResults 5 parameter add by oriental
    public function setInitialTempFromNumericalResults1 (StudyEquipment &$sequip, $shape, $offset, Product &$product, Production &$production)
    {
        $bret = true;
        $counter = 0;

        try {
            $recPos = RecordPosition::where('ID_STUDY_EQUIPMENTS', $sequip->ID_STUDY_EQUIPMENTS)
                ->orderBy('RECORD_TIME', 'DESC')->first();
            if ($recPos) {
                // get temp record data
                $tempRecordData = TempRecordData::where([
                    ['ID_REC_POS', $recPos->ID_REC_POS],
                    ['REC_AXIS_Z_POS', '0']
                ])->orderBy('REC_AXIS_X_POS')->orderBy('REC_AXIS_Y_POS')->get();

                if ($tempRecordData) {
                    $orientation = $sequip->layoutGenerations()->first()->PROD_POSITION;
                    $NbNodesZ = 0;
                    switch ($shape) {
                        case $this->value->SLAB:
                        case $this->value->CYLINDER_STANDING:
                        case $this->value->CYLINDER_LAYING:
                        case $this->value->SPHERE:
                        case $this->value->CYLINDER_CONCENTRIC_STANDING:
                        case $this->value->CYLINDER_CONCENTRIC_LAYING:
                            $NbNodesZ = 1;
                            break;
                        case $this->value->PARALLELEPIPED_STANDING:
                        case $this->value->PARALLELEPIPED_BREADED:
                            if ($orientation == $this->value->POSITION_PARALLEL) {
                                $NbNodesZ = $product->meshGenerations()->first()->MESH_1_NB;
                            } else {
                                $NbNodesZ = $product->meshGenerations()->first()->MESH_3_NB;
                                
                            }
                            break;
                        case $this->value->PARALLELEPIPED_LAYING:
                            $NbNodesZ = $product->meshGenerations()->first()->MESH_3_NB;
                            break;
                    }
    
                    // Increase value to show still alive
                    $listTemp = [];

                    foreach ($tempRecordData as $trd) {
                        $initTemp = new InitialTemperature();
                        $initTemp->ID_PRODUCTION  = $production->ID_PRODUCTION;
                        $initTemp->INITIAL_T = $trd->TEMP;
                        
                        // propagation temperature axe Z
                        for ($i = 0; $i < $NbNodesZ; $i++) {
                            switch ($shape) {
                                case $this->value->SLAB:
                                    $initTemp->MESH_1_ORDER =  $i;
                                    $initTemp->MESH_2_ORDER = ($trd->REC_AXIS_Y_POS +$offset[1]);
                                    $initTemp->MESH_3_ORDER =  $trd->REC_AXIS_X_POS;
                                    break;
                                case $this->value->PARALLELEPIPED_STANDING:
                                case $this->value->PARALLELEPIPED_BREADED:
                                    if ($orientation == $this->value->POSITION_PARALLEL) {
                                        $initTemp->MESH_1_ORDER = ($i + $offset[0]);
                                        $initTemp->MESH_2_ORDER = ($trd->REC_AXIS_Y_POS + $offset[1]);
                                        $initTemp->MESH_3_ORDER = ($trd->REC_AXIS_X_POS + $offset[2]);
                                    } else {
                                        $initTemp->MESH_1_ORDER = ($trd->REC_AXIS_X_POS + $offset[0]);
                                        $initTemp->MESH_2_ORDER = ($trd->REC_AXIS_Y_POS + $offset[1]);
                                        $initTemp->MESH_3_ORDER = ($i +$offset[2]);
                                    }
                                    break;
                                case $this->value->PARALLELEPIPED_LAYING:
                                    $initTemp->MESH_1_ORDER =  $trd->REC_AXIS_Y_POS;
                                    $initTemp->MESH_2_ORDER = ($trd->REC_AXIS_X_POS + $offset[1]);
                                    $initTemp->MESH_3_ORDER =  $i;
                                    break;
                                case $this->value->CYLINDER_STANDING:
                                case $this->value->CYLINDER_CONCENTRIC_LAYING:
                                    $initTemp->MESH_1_ORDER =  $trd->REC_AXIS_X_POS;
                                    $initTemp->MESH_2_ORDER = ($trd->REC_AXIS_Y_POS + $offset[1]);
                                    $initTemp->MESH_3_ORDER =  $i;
                                    break;

                                case $this->value->CYLINDER_LAYING:
                                case $this->value->CYLINDER_CONCENTRIC_STANDING:
                                    $initTemp->MESH_1_ORDER =  $trd->REC_AXIS_Y_POS;
                                    $initTemp->MESH_2_ORDER = ($trd->REC_AXIS_X_POS + $offset[1]);
                                    $initTemp->MESH_3_ORDER =  $i;
                                    break;

                                case $this->value->SPHERE:
                                    $initTemp->MESH_1_ORDER =  $trd->REC_AXIS_X_POS;
                                    $initTemp->MESH_2_ORDER = ($trd->REC_AXIS_Y_POS + $offset[1]);
                                    $initTemp->MESH_3_ORDER =  $i;
                                    break;
                            }
                            
                            //create initial temperature
                            // $initTemp->save();
                            array_push($listTemp, $initTemp->toArray());
                        } // end for
                        
                    } // end foreach tempRecordData

                    $slices = array_chunk($listTemp, 100);
                    foreach ($slices as $slice) {
                        InitialTemperature::insert($slice);
                    }

                    // update production to set avg initial temp
                    $production->AVG_T_INITIAL = $sequip->AVERAGE_PRODUCT_TEMP;
                    $production->save();
                    
                    // Increase value to show still alive
                } else {
                    $bret = false;
                }
            } else {
                $bret = false;
            }
        } catch (\Exception $e) {
            throw new \Exception("Error while writing initial temp from numerical results");
        }

        return $bret;
    }

    private function saveInitialTemperature (/*int*/ $shape, array $offset, /*double*/ $lfTemp, Product &$product, Production &$production)
    {
        // // V4 : use oxymel connection to update
        // Transaction tx = dbmgr . getTransaction();
        // // V4 : use standard connection to insert data
        // Connection connection = null;

        // InitialTemperature initTemp = null;
        // long counter = 0;
        $initTemp = null;
        $counter = 0;
        
        // Increase value to show still alive
        // cryoRun . nextCRRStatus(true);

        try {
            // V4: use standard connection to insert data
            // connection = CryosoftDB . getDatasource() . getConnection();
            
            // dispatch this temp
            $nbNode1 = $product->meshGenerations->first()->MESH_1_NB;
            $nbNode2 = $product->meshGenerations->first()->MESH_2_NB;
            $nbNode3 = $product->meshGenerations->first()->MESH_3_NB;

            // short i, j, k;
            $i = $j = $k = 0;
            switch ($shape) {
                case $this->value->SLAB:
                case $this->value->SPHERE:
                    $nbNode1 = $nbNode3 = 1;
                    break;
                case $this->value->CYLINDER_STANDING:
                case $this->value->CYLINDER_CONCENTRIC_LAYING:
                case $this->value->CYLINDER_LAYING:
                case $this->value->CYLINDER_CONCENTRIC_STANDING:
                    $nbNode3 = 1;
                    break;
                case $this->value->PARALLELEPIPED_STANDING:
                case $this->value->PARALLELEPIPED_BREADED:
                case $this->value->PARALLELEPIPED_LAYING:
                    break;
            }

            $listTemp = [];

            for ($i = 0; $i < $nbNode1; $i ++) {
                for ($j = 0; $j < $nbNode2; $j ++) {
                    for ($k = 0; $k < $nbNode3; $k ++) {
                        $initTemp = new InitialTemperature();
                        $initTemp->ID_PRODUCTION = $production->ID_PRODUCTION;
                        $initTemp->INITIAL_T = $lfTemp;
                        $initTemp->MESH_1_ORDER = (($i + $offset[0]));
                        $initTemp->MESH_2_ORDER = (($j + $offset[1]));
                        $initTemp->MESH_3_ORDER = (($k + $offset[2]));
                        array_push($listTemp, $initTemp->toArray());
                        // CryosoftDB . create($initTemp, connection);
                        // $initTemp->save();

                        // if ((++counter % NB_TEMP_FOR_NEXTSTATUS) == 0) {
                        //     // Increase value to show still alive
                        //     cryoRun . nextCRRStatus(true);
                        // }
                    }
                }
            }
            
            $slices = array_chunk($listTemp, 100);
            foreach ($slices as $slice) {
                InitialTemperature::insert($slice);
            }
            
            // Increase value to show still alive
            // cryoRun . nextCRRStatus(true);
            
            // update production to set avg initial temp
            $production->AVG_T_INITIAL = $lfTemp;
            $production->save();
            
            // Increase value to show still alive
            // cryoRun . nextCRRStatus(true);
        } catch (Exception $e) {
            // LOG . error("Error while writing initial temp from analogical results", e);
            throw new Exception("Error while writing initial temp from analogical results");
        }
    }

    public function generateLayoutPreview(StudyEquipment &$sequip) {
        $study = $sequip->study;

        $idRatePrm = $study->ID_PRECALC_LDG_RATE_PRM;
        $intervalW = $intervalL = 0;

        if ($idRatePrm == 0 || !$idRatePrm) {
            $intervalW = $intervalL = 0;
        } else {
            $precalcLdgRatePrm = PrecalcLdgRatePrm::find($idRatePrm);

            if ($precalcLdgRatePrm) {
                $intervalW = $precalcLdgRatePrm->W_INTERVAL;
                $intervalL = $precalcLdgRatePrm->L_INTERVAL;
            }
        }


        $base64img = '';
        // Create an image with the specified dimensions
        // $image = imageCreate(300, 200);
 
        // // Create a color (this first call to imageColorAllocate
        // //  also automatically sets the image background color)
        // $colorRed = imageColorAllocate($image, 255, 0, 0);
        // // Create another color
        // $colorYellow = imageColorAllocate($image, 255, 255, 0);
        
        // // Draw a rectangle
        // imageFilledRectangle($image, 50, 50, 250, 150, $colorYellow);
        
        // // Set type of image and send the output
        // ob_start();
        // imagejpeg($image, null, 100);
        // // Release memory
        // imageDestroy($image);
        // $base64img = base64_encode( ob_get_clean() );

        $cb = null;
        $equip = $sequip->equipment;
        $lfEquipLength = 0.0; // double
        $lfEquipWidth = 0.0; // double
        $layoutGeneration = $sequip->layoutGenerations->first();
        $layoutRes = $sequip->layoutResults->first();
        $prodShape = $sequip->study->products->first()->productElmts->first()->ID_SHAPE;

        $widthInterVal = ($layoutGeneration->WIDTH_INTERVAL < 0) ? doubleval($intervalW) : $layoutGeneration->WIDTH_INTERVAL;
        $lengthInterVal = ($layoutGeneration->LENGTH_INTERVAL < 0) ? doubleval($intervalL) : $layoutGeneration->LENGTH_INTERVAL;

        if ($sequip->BATCH_PROCESS) {
            $lfEquipLength = $layoutGeneration->SHELVES_LENGTH;
            $lfEquipWidth = $layoutGeneration->SHELVES_WIDTH;
            
            $cb = Crate::constructor__I_D_D_S(
                ConveyerTemplate::$M,
                $this->convert->convertToDouble($this->convert->shelvesWidthSVG($lfEquipLength)),
                $this->convert->convertToDouble($this->convert->shelvesWidthSVG($lfEquipWidth)),
                $prodShape
            );
            $cb->setCoordinateLegend($this->convert->carpetWidthSymbol());
        } else {
            $equipWithSpecificSize = ($sequip->STDEQP_WIDTH != $this->value->NO_SPECIFIC_SIZE)
                && ($sequip->STDEQP_LENGTH != $this->value->NO_SPECIFIC_SIZE);

            $lfEquipLength = 1.0;
            if ($equipWithSpecificSize) {
                $lfEquipWidth = $sequip->STDEQP_WIDTH;
            } else {
                $lfEquipWidth = $equip->EQP_WIDTH;
            }
            
            $cb = ConveyerBelt::constructor__D_D_S_String(
                $this->convert->convertToDouble($this->convert->carpetWidth($lfEquipLength)),
                $this->convert->convertToDouble($this->convert->carpetWidth($lfEquipWidth)),
                $prodShape,
                $this->convert->carpetWidthSymbol()
            );
            // var_dump($cb); die('har');
        }

        // We find back the product length and width from other values
        $plength = 0.0; // double
        $pwidth = 0.0; // double
 
        $pwidth = ($lfEquipWidth - 2 * $layoutRes->LEFT_RIGHT_INTERVAL
            - $widthInterVal * ($layoutRes->NUMBER_IN_WIDTH - 1)) / $layoutRes->NUMBER_IN_WIDTH;

        $plength = $lfEquipLength / $layoutRes->NUMBER_PER_M - $lengthInterVal;

        if ($sequip->BATCH_PROCESS) {
            //convert
            $pwidth = $this->convert->convertToDouble($this->convert->shelvesWidthSVG($pwidth));
            $plength = $this->convert->convertToDouble($this->convert->shelvesWidthSVG($plength));
        } else {
            // convert
            $pwidth = $this->convert->convertToDouble($this->convert->carpetWidthSVG($pwidth));
            $plength = $this->convert->convertToDouble($this->convert->carpetWidthSVG($plength));
        }
        
        // Since we have computed the width and length back from the values
        // nb_in_width and nb_in_one_meter, we must consider we're parallel.
        $cb->setParallelePlacement(true);
        $cb->setProduct($plength, $pwidth, $prodShape);
        $numM = intval($layoutRes->NUMBER_PER_M);
        $hmargin = 0;
        $lengthInter = 0;
        $widthInter = 0;
        $borderInter = 0;
        if ($sequip->BATCH_PROCESS) {
            $hmargin = ($this->convert->convertToDouble($this->convert->shelvesWidthSVG($lfEquipLength))
                - $numM * ($plength + $this->convert->convertToDouble($this->convert->shelvesWidthSVG($lengthInterVal)))) / 2;
            
            // convert
            $lengthInter = $this->convert->convertToDouble($this->convert->shelvesWidthSVG($lengthInterVal));
            $widthInter = $this->convert->convertToDouble($this->convert->shelvesWidthSVG($widthInterVal));
            $borderInter = $this->convert->convertToDouble($this->convert->shelvesWidthSVG($layoutRes->LEFT_RIGHT_INTERVAL));
        } else {
            $hmargin = ($this->convert->convertToDouble($this->convert->carpetWidthSVG($lfEquipLength))
                - $numM * ($plength + $this->convert->convertToDouble($this->convert->carpetWidthSVG($lengthInterVal)))) / 2;
            // convert
            $lengthInter = $this->convert->convertToDouble($this->convert->carpetWidthSVG($lengthInterVal));
            $widthInter = $this->convert->convertToDouble($this->convert->carpetWidthSVG($widthInterVal));
            $borderInter = $this->convert->convertToDouble($this->convert->carpetWidthSVG($layoutRes->LEFT_RIGHT_INTERVAL));
        }

        $cb->setNbElements( intval ($layoutRes->NUMBER_PER_M), intval ($layoutRes->NUMBER_IN_WIDTH));
        $cb->setEdgeInterval($hmargin, $borderInter);
        $cb->setProductsInterval($lengthInter, $widthInter);

        $svg = '';

        try {
            $svg = $cb->getSVGImage_I_I(800, 800);
        } catch (Exception $e) {
            throw new Exception("Unable to generate SVG image");
        }

        // file_put_contents('/home/thaolt/test.svg', $svg);

        $image = new \Imagick();
        $image->readImageBlob($svg);
        $image->setImageFormat("jpeg");
        $image->resizeImage(800, 800, \imagick::FILTER_LANCZOS, 1, true);
        $public_path = rtrim(app()->basePath("public/"), '/');
        $nameImgLayout = $sequip->study->ID_STUDY.'-'.$sequip->study->STUDY_NAME.'-StdeqpLayout-'.$sequip->ID_STUDY_EQUIPMENTS.'.jpg';
        if (!is_dir($public_path . "/reports/" . $sequip->study->USERNAM)) {
            mkdir($public_path . "/reports/" . $sequip->study->USERNAM, 0777, true);
        } 
        $image->writeImage($public_path . "/reports/" . $sequip->study->USERNAM ."/". $nameImgLayout);
        $base64img = base64_encode($image);

        $image->destroy();
        
        return $base64img;
    }

    public function loadExhaustGasTemperature(&$studyEquipment)
    {
        if ($studyEquipment->STD == 1) {
            $idEquipSeries = $studyEquipment->ID_EQUIPSERIES;
        } else {
            $idEquipSeries = 0;

            $equipGenerations = EquipGeneration::where('ID_EQUIP', $studyEquipment->ID_EQUIP)->get();
            if (count($equipGenerations) > 0) {
                foreach ($equipGenerations as $equipGeneration) {
                    $equipment = Equipment::where('ID_EQUIP', $equipGeneration->ID_ORIG_EQUIP1)->first();
                    if ($equipment) {
                        if ($equipment->STD == 1) {
                            $idEquipSeries = $equipment->ID_EQUIPSERIES;
                        }
                    }
                }
            }
        }

        $tempExts = [];

        if ($idEquipSeries != 0) {
            $tempExts = TempExt::where('ID_EQUIPSERIES', $idEquipSeries)->get(); 
        }

        return $tempExts;
    }

    public function loadAlphaCoef(&$studyEquipment)
    {
        $alpha = new \SplFixedArray(6);
        $calcParams = $studyEquipment->calculationParameters->first();

        if ($calcParams) {
            if ($calcParams->STUDY_ALPHA_TOP_FIXED == true) {
                $alpha[0] = $this->convert->convectionCoeff($calcParams->STUDY_ALPHA_TOP);
            } else {
                $alpha[0] = $this->convert->convectionCoeff(0.00);
            }

            if ($calcParams->STUDY_ALPHA_BOTTOM_FIXED == true) {
                $alpha[1] = $calcParams->STUDY_ALPHA_BOTTOM;
            } else {
                $alpha[1] = $this->convert->convectionCoeff(0.00);
            }

            if ($calcParams->STUDY_ALPHA_LEFT_FIXED == true) {
                $alpha[2] = $calcParams->STUDY_ALPHA_LEFT;
            } else {
                $alpha[2] = $this->convert->convectionCoeff(0.00);
            }

            if ($calcParams->STUDY_ALPHA_RIGHT_FIXED == true) {
                $alpha[3] = $calcParams->STUDY_ALPHA_RIGHT;
            } else {
                $alpha[3] = $this->convert->convectionCoeff(0.00);
            }

            if ($calcParams->STUDY_ALPHA_FRONT_FIXED == true) {
                $alpha[4] = $calcParams->STUDY_ALPHA_FRONT;
            } else {
                $alpha[4] = $this->convert->convectionCoeff(0.00);
            }

            if ($calcParams->STUDY_ALPHA_REAR_FIXED == true) {
                $alpha[5] = $calcParams->STUDY_ALPHA_REAR;
            } else {
                $alpha[5] = $this->convert->convectionCoeff(0.00);
            }
        }

        return $alpha;
    }

    public function updateEquipmentData(&$studyEquipment)
    {
        if ($this->equip->getCapability($studyEquipment->CAPABILITIES, 1) && !empty($studyEquipment->tr)) {
            $this->cleanSpecificEqpPrm($studyEquipment->ID_STUDY_EQUIPMENTS, 300);
            $i = 0;
            foreach ($studyEquipment->tr as $tr) {
                $studEqpPrm = new StudEqpPrm();
                $studEqpPrm->ID_STUDY_EQUIPMENTS = $studyEquipment->ID_STUDY_EQUIPMENTS;
                $studEqpPrm->VALUE_TYPE = 300 + $i;
                $studEqpPrm->VALUE = doubleval($this->convert->controlTemperature($tr, ['save' => true]));
                $studEqpPrm->save();
                $i++;
            }
        }

        if (!empty($studyEquipment->ts)) {
            $this->cleanSpecificEqpPrm($studyEquipment->ID_STUDY_EQUIPMENTS, 200);
            $i = 0;
            foreach ($studyEquipment->ts as $ts) {
                $studEqpPrm = new StudEqpPrm();
                $studEqpPrm->ID_STUDY_EQUIPMENTS = $studyEquipment->ID_STUDY_EQUIPMENTS;
                $studEqpPrm->VALUE_TYPE = 200 + $i;
                $studEqpPrm->VALUE = doubleval($this->convert->time($ts, ['save' => true]));
                $studEqpPrm->save();
                $i++;
            }
        }

        if ($this->equip->getCapability($studyEquipment->CAPABILITIES, 4) && !empty($studyEquipment->vc)) {
            $this->cleanSpecificEqpPrm($studyEquipment->ID_STUDY_EQUIPMENTS, 300);
            $i = 0;
            foreach ($studyEquipment->vc as $vc) {
                $studEqpPrm = new StudEqpPrm();
                $studEqpPrm->ID_STUDY_EQUIPMENTS = $studyEquipment->ID_STUDY_EQUIPMENTS;
                $studEqpPrm->VALUE_TYPE = 100 + $i;
                $studEqpPrm->VALUE = doubleval($this->convert->convectionSpeed($vc, ['save' => true]));
                $studEqpPrm->save();
                $i++;
            }
        }

        if (!empty($studyEquipment->dh)) {
            $this->cleanSpecificEqpPrm($studyEquipment->ID_STUDY_EQUIPMENTS, 400);
            $i = 0;
            foreach ($studyEquipment->dh as $dh) {
                $studEqpPrm = new StudEqpPrm();
                $studEqpPrm->ID_STUDY_EQUIPMENTS = $studyEquipment->ID_STUDY_EQUIPMENTS;
                $studEqpPrm->VALUE_TYPE = 400 + $i;
                $studEqpPrm->VALUE = doubleval($dh);
                $studEqpPrm->save();
                $i++;
            }
        }

        if ($this->equip->getCapability($studyEquipment->CAPABILITIES, 512) && !empty($studyEquipment->tExt)) {
            $this->cleanSpecificEqpPrm($studyEquipment->ID_STUDY_EQUIPMENTS, 500);
            $studEqpPrm = new StudEqpPrm();
            $studEqpPrm->ID_STUDY_EQUIPMENTS = $studyEquipment->ID_STUDY_EQUIPMENTS;
            $studEqpPrm->VALUE_TYPE = 500;
            $studEqpPrm->VALUE = doubleval($this->convert->exhaustTemperature($studyEquipment->tExt, ['save' => true]));
            $studEqpPrm->save();
        }

        //data value
        $calculationParameters = $studyEquipment->calculationParameters->first();
        //post value
        $calculationParameter = $studyEquipment->calculation_parameter;
        //update value
        $calculationParameters->STUDY_ALPHA_TOP_FIXED = ($calculationParameter->STUDY_ALPHA_TOP_FIXED) ? 1 : 0;
        $calculationParameters->STUDY_ALPHA_BOTTOM_FIXED = ($calculationParameter->STUDY_ALPHA_BOTTOM_FIXED) ? 1 : 0;
        $calculationParameters->STUDY_ALPHA_LEFT_FIXED = ($calculationParameter->STUDY_ALPHA_LEFT_FIXED) ? 1 : 0;
        $calculationParameters->STUDY_ALPHA_RIGHT_FIXED = ($calculationParameter->STUDY_ALPHA_RIGHT_FIXED) ? 1 : 0;
        $calculationParameters->STUDY_ALPHA_FRONT_FIXED = ($calculationParameter->STUDY_ALPHA_FRONT_FIXED) ? 1 : 0;
        $calculationParameters->STUDY_ALPHA_REAR_FIXED = ($calculationParameter->STUDY_ALPHA_REAR_FIXED) ? 1 : 0;
        $calculationParameters->STUDY_ALPHA_TOP = ($calculationParameter->STUDY_ALPHA_TOP_FIXED) ? doubleval($this->convert->convectionCoeff($calculationParameter->STUDY_ALPHA_TOP, ['save' => true])) : '0.0';
        $calculationParameters->STUDY_ALPHA_BOTTOM = ($calculationParameter->STUDY_ALPHA_BOTTOM_FIXED) ? doubleval($this->convert->convectionCoeff($calculationParameter->STUDY_ALPHA_BOTTOM, ['save' => true])) : '0.0';
        $calculationParameters->STUDY_ALPHA_LEFT = ($calculationParameter->STUDY_ALPHA_LEFT_FIXED) ? doubleval($this->convert->convectionCoeff($calculationParameter->STUDY_ALPHA_LEFT, ['save' => true])) : '0.0';
        $calculationParameters->STUDY_ALPHA_RIGHT = ($calculationParameter->STUDY_ALPHA_RIGHT_FIXED) ? doubleval($this->convert->convectionCoeff($calculationParameter->STUDY_ALPHA_RIGHT, ['save' => true])) : '0.0';
        $calculationParameters->STUDY_ALPHA_FRONT = ($calculationParameter->STUDY_ALPHA_FRONT_FIXED) ? doubleval($this->convert->convectionCoeff($calculationParameter->STUDY_ALPHA_FRONT, ['save' => true])) : '0.0';
        $calculationParameters->STUDY_ALPHA_REAR = ($calculationParameter->STUDY_ALPHA_REAR_FIXED) ? doubleval($this->convert->convectionCoeff($calculationParameter->STUDY_ALPHA_REAR, ['save' => true])) : '0.0';
        $calculationParameters->save();
    }

    public function cleanSpecificEqpPrm($idStudyEquipment, $valueType) {
        $studEqpPrm = StudEqpPrm::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->where('VALUE_TYPE', '>=', $valueType)->where('VALUE_TYPE', '<', $valueType + 100)->delete();
    }

    public function runStudyCleaner($idStudy, $idStudyEquipment, $number)
    {
        $ret = 0;
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment);
        $ret = $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, $number);

        if ($ret == 0 && $this->cal->isStudyHasChilds($idStudy)) {
            $this->cal->setChildsStudiesToRecalculate($idStudy, $idStudyEquipment);
        }

        return $ret;    
    }

    public function runSizingCalculator($idStudy, $idStudyEquipment)
    {
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment);
        return $this->kernel->getKernelObject('DimMatCalculator')->DMCCalculation($conf, 2);
    }

    public function applyStudyCleaner($idStudy, $idStudyEquipment, $number)
    {
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment);
        return $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, $number);
    }

    public function afterStudyCleaner($idStudy, $idStudyEquipment, $mode, $bNewText = false, $bNewTr = false, $bNewTs = false, $bNewVc = false)
    {
        $bRecalcPhamCast = false;
        $bRecalcEco = false;
        $bRecalcExhaust = false;
        $bRecalcTS = false;
        $bRecalcTOC = false;

        switch ($mode) {
            case 43:
                $bRecalcTOC = $bRecalcTS = $bRecalcPhamCast = $bRecalcExhaust = $bRecalcEco = false;
                break;

            case 41:
            case 42:
                $bRecalcTOC = $bRecalcTS = $true;
                $bRecalcPhamCast = $bRecalcExhaust = $bRecalcEco = false;
                break;

            case 48:
                $bRecalcTOC = true;
                $bRecalcTS = true;
                $bRecalcPhamCast = $bRecalcExhaust = true;
                $bRecalcEco = false;
                break;
            case 45:
                $bRecalcTOC = $bRecalcTS = $bRecalcPhamCast = $bRecalcExhaust = false;
                $bRecalcEco = true;
                break;
            case 44:
            case 46:
            case 47:
            default:
                $bRecalcTOC = $bRecalcTS = $bRecalcPhamCast = $bRecalcExhaust = true;
                $bRecalcEco = false;
                break;

            $this->recalculateEquipment($idStudy, $idStudyEquipment, $bRecalcTOC, $bRecalcTS, $bRecalcPhamCast, $bRecalcExhaust, $bRecalcEco);
        }
    }


    public function recalculateEquipment($idStudy, $idStudyEquipment, $bRecalcTOC, $bRecalcTS, $bRecalcPhamCast, $bRecalcExhaust, $bRecalcEco)
    {
        $bExPhamCast = false;
        $bExExhaust = false;
        $bExTOC = false;
        $bExTS = false;
        $bExEco = false;

        $studyEquipments = StudyEquipment::where('ID_STUDY', $idStudy)->get();
        if (count($studyEquipments) > 0) {
            foreach ($studyEquipments as $studyEquipment) {
                $capability = $studyEquipment->CAPABILITIES;
                if (($idStudyEquipment == -1) || ($studyEquipment->ID_STUDY_EQUIPMENTS == $idStudyEquipment)) {
                    if ($bRecalcTOC) {
                        try {
                            // dbdata.getEquipmentLayout(sequip);
                            $this->runLayoutCalculator($idStudy, $studyEquipment->ID_STUDY_EQUIPMENTS);
                        } catch (Exception $e) {
                            $bExTOC = true;
                        }
                    }

                    if (($bRecalcTS) && (!$bExTOC)) {

                        if ($this->equip->getCapability($capability, 2) && $this->equip->getCapability($capability, 131072)) {
                            try {
                                // dbdata.getEquipmentLayout(sequip);
                                $this->runTSCalculator($idStudy, $studyEquipment->ID_STUDY_EQUIPMENTS);
                            } catch (OXException $e) {
                                $bExTS = true;
                            }
                        }
                    }

                    $doTR = false;
                    if (($bRecalcPhamCast) && (!$bExTOC) && (!$bExTS)) {
                        if (($this->equip->getCapability($capability, 1)) && ($this->equip->getCapability($capability, 524288)) && ($this->equip->getCapability($capability, 8))) {
                            $doTR = true;

                            $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $studyEquipment->ID_STUDY, $studyEquipment->ID_STUDY_EQUIPMENTS);
                            $this->kernel->getKernelObject('PhamCastCalculator')->PCCCalculation($conf, !$doTR);
                        }
                    }

                    if (($bRecalcTS) && ($bRecalcPhamCast) && (!$bExTOC) && (!$bExTS) && (!$bExPhamCast)) {

                        if ((!$doTR) && ($this->equip->getCapability($capability, 2)) && ($this->equip->getCapability($capability, 262144)) && ($this->equip->getCapability($capability, 8))) {

                            $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $studyEquipment->ID_STUDY, $studyEquipment->ID_STUDY_EQUIPMENTS);
                            $this->kernel->getKernelObject('PhamCastCalculator')->PCCCalculation($conf, !$doTR);
                        }
                    }

                    if (($bRecalcExhaust) && (!$bExTOC) && (!$bExTS) && (!$bExPhamCast)) {
                        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $studyEquipment->ID_STUDY, $studyEquipment->ID_STUDY_EQUIPMENTS);
                        $this->kernel->getKernelObject('KernelToolCalculator')->KTCalculator($conf, 1);
                    }

                    if (($bRecalcEco) && (!$bExTOC) && (!$bExTS) && (!$bExPhamCast)) {
                        try {
                            $this->runEcoCalculator($studyEquipment);
                        } catch (Exception $e) {
                            $bExEco = true;
                        }
                    }
                }
            }
        }
    }

    public function runLayoutCalculator($idStudy, $idStudyEquipment)
    {
        $idStudyEquipment = $studyEquipment->ID_STUDY_EQUIPMENTS;
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment, 1, 1, 'c:\\temp\\layout-trace.txt');
        $this->kernel->getKernelObject('LayoutCalculator')->LCCalculation($conf, 1);

        
    }

    public function runTSCalculator($idStudy, $idStudyEquipment)
    {
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment, 1, 1, 'c:\\temp\\layout-ts-trace.txt');
        $this->kernel->getKernelObject('LayoutCalculator')->LCCalculation($conf, 2);
    }

    public function runEcoCalculator(StudyEquipment &$studyEquipment)
    {
        if (count($studyEquipment->economicResults) > 0) {
            $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $studyEquipment->ID_STUDY, $studyEquipment->ID_STUDY_EQUIPMENTS);
            $this->kernel->getKernelObject('KernelToolCalculator')->ECCalculator($conf, 1);
        }
    }

}