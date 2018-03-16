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

use App\Cryosoft\CalculateService;
use App\Cryosoft\ValueListService;
use App\Cryosoft\EquipmentsService;
use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\BrainCalculateService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use App\Models\StudyEquipment;
use App\Models\CalculationParameter;
use App\Models\CalculationParametersDef;
use App\Models\TempRecordPts;
use App\Kernel\KernelService;
use App\Models\Equipment;
use App\Models\Study;


class Calculator extends Controller 
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
	 * @var App\Cryosoft\CalculateService
	 */
	protected $cal;

	/**
	 * @var App\Cryosoft\ValueListService
	 */
	protected $value;

	/**
     * @var App\Kernel\KernelService
     */
    protected $kernel;

   	/**
     * @var App\Cryosoft\EquipmentsService
     */
    protected $equipment;

    /**
     * @var App\Cryosoft\UnitsConverterService
     */
    protected $convert;

    /**
     * @var App\Cryosoft\BrainCalculateService
     */
    protected $brainCal;

    /**
     * @var 
     */
    protected $brainMode;


	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct(Request $request, Auth $auth, CalculateService $cal, ValueListService $value, KernelService $kernel, EquipmentsService $equipment, UnitsConverterService $convert, BrainCalculateService $brainCal) 
	{
		$this->request = $request;
		$this->auth = $auth;
		$this->cal = $cal;
		$this->value = $value;
		$this->kernel = $kernel;
		$this->equipment = $equipment;
		$this->convert = $convert;
		$this->brainCal = $brainCal;
	}

	public function getOptimumCalculator() 
	{
		$input = $this->request->all();
		$idStudy = $idStudyEquipment = $ID_USER_STUDY = null;

		if (isset($input['idStudyEquipment'])) $idStudyEquipment = intval($input['idStudyEquipment']);
		if (isset($input['idStudy'])) $idStudy = intval($input['idStudy']);

		$study = Study::find($idStudy);
		if ($study) {
			$ID_USER_STUDY = $study->ID_USER;
		}

		$calMode = $this->cal->getCalculationMode($idStudy);
		$sdisableFields = $this->cal->disableFields($idStudy);
		$sdisableCalculate = $this->cal->disableCalculate($idStudy);

		$sdisableOptim = $sdisableNbOptim = $sdisableStorage = $sdisableTimeStep = $sdisablePrecision = $checkOptim = $scheckStorage = $isBrainCalculator = 0;
		$seValue1 = $seValue2 = $seValue3 = $seValue4 = $seValue5 = $seValue6 = $seValue7 = $seValue8 = 0;

		if ($idStudyEquipment != null) {
			$isBrainCalculator = 1;
		}

		if ($sdisableFields == 0) {
			$sdisableOptim = $sdisableFields;

			if ($calMode == $this->value->STUDY_OPTIMUM_MODE) {
				$sdisableNbOptim = $sdisableStorage = 1;
				$checkOptim = 1;
				$scheckStorage = 0;

				if ($this->cal->getTimeStep($idStudy) == $this->value->VALUE_N_A) {
					$sdisableTimeStep = 1;
				} else {
					$sdisableTimeStep = 0;
				}

				if ($this->cal->getPrecision($idStudy) == $this->value->VALUE_N_A) {
					$sdisablePrecision = 1;
				} else {
					$sdisablePrecision = 0;
				}
			} else if ($calMode == $this->value->STUDY_SELECTED_MODE) {
				$sdisableNbOptim = $sdisableStorage = 0;
				$checkOptim = 1;
				$scheckStorage = 0;
				$sdisableTimeStep = $sdisablePrecision = 0;
			} else {
				$sdisableNbOptim = $sdisableStorage = 1;
				$checkOptim = $scheckStorage = 0;
				$sdisableTimeStep = $sdisablePrecision = 0;
			}

		} else {
			$sdisableOptim = $sdisableNbOptim = $sdisableStorage = 1;
			$sdisableTimeStep = $sdisablePrecision = 1;
			$checkOptim = $scheckStorage = 0;
		}

		$epsilonTemp = $this->cal->getOptimErrorT();
		$epsilonEnth = $this->cal->getOptimErrorH();
		$nbOptimIter = $this->cal->getNbOptim();
		$timeStep = $this->cal->getTimeStep($idStudy);
		$precision = $this->cal->getPrecision($idStudy);
		$storagestep = $this->cal->getStorageStep();
		$hRadioOn = $this->cal->getHradioOn();
		$hRadioOff = $this->cal->getHradioOff();
		$maxIter = $this->cal->getMaxIter();
		$relaxCoef = $this->cal->getRelaxCoef();
		$vRadioOn = $this->cal->getVradioOn();
		$vRadioOff = $this->cal->getVradioOff();
		$tempPtSurf = $this->cal->getTempPtSurf();
		$tempPtIn = $this->cal->getTempPtIn();
		$tempPtBot = $this->cal->getTempPtBot();
		$tempPtAvg = $this->cal->getTempPtAvg();

		$select1 = $this->cal->getOption($idStudy, "X", "TOP");
		$seValue1 = $this->cal->getValueSelected($select1);
		
		$select2 = $this->cal->getOption($idStudy, "Y", "TOP");
		$seValue2 = $this->cal->getValueSelected($select2);

        $select3 = $this->cal->getOption($idStudy, "Z", "TOP");
		$seValue3 = $this->cal->getValueSelected($select3);

        $select4 = $this->cal->getOption($idStudy, "X", "INT");
		$seValue4 = $this->cal->getValueSelected($select4);

        $select5 = $this->cal->getOption($idStudy, "Y", "INT");
		$seValue5 = $this->cal->getValueSelected($select5);

        $select6 = $this->cal->getOption($idStudy, "Z", "INT");
		$seValue6 = $this->cal->getValueSelected($select6);

        $select7 = $this->cal->getOption($idStudy, "X", "BOT");
		$seValue7 = $this->cal->getValueSelected($select7);

        $select8 = $this->cal->getOption($idStudy, "Y", "BOT");
		$seValue8 = $this->cal->getValueSelected($select8);

        $select9 = $this->cal->getOption($idStudy, "Z", "BOT");
		$seValue9 = $this->cal->getValueSelected($select9);

		$array = [
			'sdisableFields' => $sdisableFields,
			'sdisableCalculate' => $sdisableCalculate,
			'checkOptim' => $checkOptim,
			'sdisableOptim' => $sdisableOptim,
			'sdisableNbOptim' => $sdisableNbOptim,
			'epsilonTemp' => $epsilonTemp,
			'epsilonEnth' => $epsilonEnth,
			'nbOptimIter' => $nbOptimIter,
			'sdisableTimeStep' => $sdisableTimeStep,
			'sdisablePrecision' => $sdisablePrecision,
			'sdisableStorage' => $sdisableStorage,
			'timeStep' => $timeStep,
			'precision' => $precision,
			'scheckStorage' => $scheckStorage,
			'storagestep' => $storagestep,
			'hRadioOn' => $hRadioOn,
			'hRadioOff' => $hRadioOff,
			'maxIter' => $maxIter,
			'relaxCoef' => $relaxCoef,
			'vRadioOn' => $vRadioOn,
			'vRadioOff' => $vRadioOff,
			'tempPtSurf' => $tempPtSurf,
			'tempPtIn' => $tempPtIn,
			'tempPtBot' => $tempPtBot,
			'tempPtAvg' => $tempPtAvg,
			'select1' => $select1,
			'select2' => $select2,
			'select3' => $select3,
			'select4' => $select4,
			'select5' => $select5,
			'select6' => $select6,
			'select7' => $select7,
			'select8' => $select8,
			'select9' => $select9,
			'isBrainCalculator' => $isBrainCalculator,
			'seValue1' => $seValue1,
			'seValue2' => $seValue2,
			'seValue3' => $seValue3,
			'seValue4' => $seValue4,
			'seValue5' => $seValue5,
			'seValue6' => $seValue6,
			'seValue7' => $seValue7,
			'seValue8' => $seValue8,
			'seValue9' => $seValue9,
			'ID_USER_STUDY' => $ID_USER_STUDY,
			'ID_USER_CURRENT' => $this->auth->user()->ID_USER,
		];

		return $array;
	}

	public function startCalculate()
	{
		$input = $this->request->all();
		
		$idStudy = $idStudyEquipment = null;

		if (isset($input['idStudy'])) $idStudy = intval($input['idStudy']);
		if (isset($input['idStudyEquipment'])) $idStudyEquipment = intval($input['idStudyEquipment']);

		$this->cal->resetEquipSatus($idStudy);

		$calMode = $this->cal->getCalculationMode($idStudy);

		$brainMode = $this->brainCal->getBrainMode($idStudy);

		$studyEquipments = StudyEquipment::where('ID_STUDY', $idStudy)->get();

		if (count($studyEquipments) > 0) {
			for ($i = 0; $i < count($studyEquipments); $i++) { 
				$idStudyEquipment = $studyEquipments[$i]->ID_STUDY_EQUIPMENTS;
				$calculationParameter = CalculationParameter::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->first();

				if (!$calculationParameter) {
					$calculationParameter = new CalculationParameter();

					$calculationParametersDef = CalculationParametersDef::where('ID_USER', $this->auth->user()->ID_USER)->first();

					$calculationParameter->STORAGE_STEP = $calculationParametersDef->STORAGE_STEP_DEF;
					$calculationParameter->PRECISION_LOG_STEP = $calculationParametersDef->PRECISION_LOG_STEP_DEF;
					$calculationParameter->ID_STUDY_EQUIPMENTS = $idStudyEquipment;
					$calculationParameter->save();
				}
				
				$this->saveCalculationParameters($this->request, $idStudyEquipment, $brainMode);
			}
		}

		$this->saveTempRecordPts($this->request, $idStudy);
		$this->cal->saveTempRecordPtsToReport($idStudy);

		return $this->startNumericalCalculation($idStudy);
	}

	public function startStudyCalculation($idStudy)
	{
		$conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, -1);
		$this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, 50);

		$studyEquipments = StudyEquipment::where('ID_STUDY', $idStudy)->get();
		$study = Study::find($idStudy);

		$results = [];
		
		if (count($studyEquipments) > 0) {
			for ($i = 0; $i < count($studyEquipments); $i++) { 
				$idStudyEquipment = $studyEquipments[$i]->ID_STUDY_EQUIPMENTS;
				$capability = $studyEquipments[$i]->CAPABILITIES;
				$idEquipment = $studyEquipments[$i]->ID_EQUIP;

				$equipment = Equipment::find($idEquipment);
				$error = $this->startDimMat($idStudy, $idStudyEquipment);

				if ($error != 0) {
					echo "OXException on calculate for Equipment" . $equipment->EQUIP_NAME;
				}

				if (($error == 0) && ($study->OPTION_CRYOPIPELINE == 1)) {
					array_push($results, $this->startPipeLine($idStudy, $idStudyEquipment));
				}

				if ($error == 0) {
					if ($study->OPTION_ECO == 1) {
						if ($this->equipment->getCapability($capability, 16)) {
							array_push($results, $this->startEconomic($idStudy, $idStudyEquipment));
						}
					} else {
						if ($this->equipment->getCapability($capability, 16)) {
							array_push($results, $this->startConsumptionEconomic($idStudy, $idStudyEquipment));
						}
					}
				}
			}
		}
		return $results;
	}

	public function startConsumptionEconomic($idStudy, $idStudyEquipment)
	{
		$conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment);
		return $this->kernel->getKernelObject('ConsumptionCalculator')->COCConsumptionCalculation($conf);
	}

	public function startDimMat($idStudy, $idStudyEquipment)
	{
		$conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment);
		return $this->kernel->getKernelObject('DimMatCalculator')->DMCCalculation($conf, 1);
	}

	public function startEconomic($idStudy, $idStudyEquipment)
	{
		$conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment);
		return $this->kernel->getKernelObject('EconomicCalculator')->ECEconomicCalculation($conf);
	}

	public function startPipeLine($idStudy, $idStudyEquipment)
	{
		$conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment);
		return $this->kernel->getKernelObject('PipelineCalculator')->PCPipelineCalculation($conf);
	}

	public function startNumericalCalculation($idStudy)
	{
		$studyEquipments = StudyEquipment::where('ID_STUDY', $idStudy)->get();

		// $confCleaner = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, -1);
		// $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($confCleaner, 50);
		$study = Study::find($idStudy);

		$results = [];

		if (count($studyEquipments) > 0) {
			for ($i = 0; $i < count($studyEquipments); $i++) { 
				$idStudyEquipment = $studyEquipments[$i]->ID_STUDY_EQUIPMENTS;
				$capability = $studyEquipments[$i]->CAPABILITIES;

				if ($this->equipment->getCapability($capability, 128)) {
					$conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment, 1, 1, 'c:\\temp\\brain_log'.$i.'.txt');
					$param = new \Cryosoft\stSKBRParam();

					array_push($results, $this->kernel->getKernelObject('BrainCalculator')->BRTeachCalculation($conf, $param, 10));
					//add run economic and consumption
					if ($study->OPTION_CRYOPIPELINE == 1) {
						$this->startPipeLine($idStudy, $idStudyEquipment);
					}

					$this->startEconomic($idStudy, $idStudyEquipment);
					$this->startConsumptionEconomic($idStudy, $idStudyEquipment);
				}
			}
		}
		return $results;
	}

	public function getStudyEquipmentCalculation()
	{
		$input = $this->request->all();
		$idStudy = $idStudyEquipment = $typeCalculate = $ID_USER_STUDY = null;
		$checkOptim = false;

		if (isset($input['idStudy'])) $idStudy = intval($input['idStudy']);
		if (isset($input['idStudyEquipment'])) $idStudyEquipment = intval($input['idStudyEquipment']);
		if (isset($input['checkOptim'])) $checkOptim = $input['checkOptim'];
		if (isset($input['type'])) $typeCalculate = intval($input['type']);

		$brainMode = $this->brainCal->getBrainMode($idStudy);
		$study = Study::find($idStudy);
		if ($study) {
			$ID_USER_STUDY = $study->ID_USER;
		}

		if ($checkOptim == "true") {
			$this->setBrainMode(11);
			$brainMode = 11;
		} else {
			$this->setBrainMode(12);
			$brainMode = 12;
		}

		$sdisableCalculate 	= $this->cal->disableCalculate($idStudy);
		$sdisableFields = $this->cal->disableFields($idStudy);
		
		$sdisableTS = $sdisableTR = $sdisableTOC = $sdisableOptim = $sdisableNbOptim = $sdisableStorage = 0;
		$scheckOptim = $scheckStorage = 0;

		if ($sdisableFields == 0) {
			switch($brainMode)
			{
				case $this->value->BRAIN_MODE_ESTIMATION 		: 
				case $this->value->BRAIN_MODE_ESTIMATION_OPTIM : 
					$sdisableTS = $sdisableTR = $sdisableTOC = $sdisableNbOptim = $sdisableStorage = 1;
					$scheckOptim = $scheckStorage = 0;
					break;
				case $this->value->BRAIN_MODE_OPTIMUM_CALCULATE:
				case $this->value->BRAIN_MODE_OPTIMUM_FULL 	: 
				case $this->value->BRAIN_MODE_SELECTED_FULL : 
					$sdisableTS = $sdisableTR = $sdisableNbOptim = $sdisableStorage = 0;
					$sdisableTOC = 1;
					$scheckOptim = 0;
					$scheckStorage = 1;
					break;
					
				case $this->value->BRAIN_MODE_OPTIMUM_REFINE 	: 
				case $this->value->BRAIN_MODE_SELECTED_REFINE 	: 
					$sdisableTS = $sdisableTR = $sdisableNbOptim = 0;
					$sdisableTOC = $sdisableStorage = 1;
					$scheckOptim = 1;
					$scheckStorage = 0;
					break;
				
				case $this->value->BRAIN_MODE_OPTIMUM_DHPMAX 	: 
				case $this->value->BRAIN_MODE_SELECTED_DHPMAX 	: 
					$sdisableTR = $sdisableTOC = 0;
					$sdisableTS = $sdisableNbOptim = $sdisableStorage = 1;
					$scheckOptim = 1;
					$scheckStorage = 0;
					break;
				
				default :
					$sdisableTS = $sdisableTR = $sdisableTOC = $sdisableNbOptim = $sdisableStorage = 1;
					$scheckOptim = $scheckStorage = 0;
			}		
		} else {
			$sdisableTS = $sdisableTR = $sdisableTOC = $sdisableOptim = $sdisableNbOptim = $sdisableStorage = 1;
			$scheckOptim = $scheckStorage = 0;
		}

		$lTs = $this->brainCal->getListTs($idStudyEquipment);
		$itemTs = array();
		$dwellingTimes = array();

		for ($i = 0; $i < count($lTs); $i++) { 
			$itemTs['name'] = $i;
			$itemTs['value'] = $lTs[$i];
			array_push($dwellingTimes, $itemTs);
		}

		$lTr = $this->brainCal->getListTr($idStudyEquipment);
		$itemTr = array();
		$temperatures = array();

		for($i = 0; $i < count($lTr); $i++) {
			$itemTr['name'] = $i;
			$itemTr['value'] = $lTr[$i];
			array_push($temperatures, $itemTr);
		}

		$uPercent = $this->convert->uPercent();
		$toc = $this->convert->convertCalculator($this->brainCal->getLoadingRate($idStudyEquipment, $idStudy), $uPercent['coeffA'], $uPercent['coeffB']);

		$checkOptim = ($checkOptim == "true") ? 1 : 0;
		$epsilonTemp = $this->brainCal->getOptimErrorT($brainMode, $idStudyEquipment);
		$epsilonEnth = $this->brainCal->getOptimErrorH($brainMode, $idStudyEquipment);
		$nbOptimIter = $this->brainCal->getNbOptim($brainMode, $idStudyEquipment);
		$timeStep = $this->brainCal->getTimeStep($idStudyEquipment);
		$precision = $this->brainCal->getPrecision($idStudyEquipment);
		$precisionlogstep = $this->brainCal->getPrecisionLogStep($idStudyEquipment);
		$storagestep = $this->brainCal->getStorageStep($idStudyEquipment);
		$hRadioOn = $this->brainCal->getHradioOn($idStudyEquipment);
		$vRadioOn = $this->brainCal->getVradioOn($idStudyEquipment);
		$maxIter = $this->brainCal->getMaxIter($idStudyEquipment);
		$relaxCoef = $this->brainCal->getRelaxCoef($idStudyEquipment);
		$tempPtSurf = $this->brainCal->getTempPtSurf($idStudyEquipment);
		$tempPtIn = $this->brainCal->getTempPtIn($idStudyEquipment);
		$tempPtBot = $this->brainCal->getTempPtBot($idStudyEquipment);
		$tempPtAvg = $this->brainCal->getTempPtAvg($idStudyEquipment);

		$select1 = $this->cal->getOption($idStudy, "X", "TOP");
		$seValue1 = $this->cal->getValueSelected($select1);
		
		$select2 = $this->cal->getOption($idStudy, "Y", "TOP");
		$seValue2 = $this->cal->getValueSelected($select2);

        $select3 = $this->cal->getOption($idStudy, "Z", "TOP");
		$seValue3 = $this->cal->getValueSelected($select3);

        $select4 = $this->cal->getOption($idStudy, "X", "INT");
		$seValue4 = $this->cal->getValueSelected($select4);

        $select5 = $this->cal->getOption($idStudy, "Y", "INT");
		$seValue5 = $this->cal->getValueSelected($select5);

        $select6 = $this->cal->getOption($idStudy, "Z", "INT");
		$seValue6 = $this->cal->getValueSelected($select6);

        $select7 = $this->cal->getOption($idStudy, "X", "BOT");
		$seValue7 = $this->cal->getValueSelected($select7);

        $select8 = $this->cal->getOption($idStudy, "Y", "BOT");
		$seValue8 = $this->cal->getValueSelected($select8);

        $select9 = $this->cal->getOption($idStudy, "Z", "BOT");
		$seValue9 = $this->cal->getValueSelected($select9);

		$array = [
			'dwellingTimes' => $dwellingTimes,
			'temperatures' => $temperatures,
			'toc' => $toc,
			'checkOptim' => $checkOptim,
			'epsilonTemp' => $epsilonTemp,
			'epsilonEnth' => $epsilonEnth,
			'nbOptimIter' => $nbOptimIter,
			'timeStep' => $timeStep,
			'precision' => $precision,
			'precisionlogstep' => $precisionlogstep,
			'scheckStorage' => $scheckStorage,
			'storagestep' => $storagestep,
			'hRadioOn' => $hRadioOn,
			'vRadioOn' => $vRadioOn,
			'maxIter' => $maxIter,
			'relaxCoef' => $relaxCoef,
			'tempPtSurf' => $tempPtSurf,
			'tempPtIn' => $tempPtIn,
			'tempPtBot' => $tempPtBot,
			'tempPtAvg' => $tempPtAvg,
			'select1' => $select1,
			'select2' => $select2,
			'select3' => $select3,
			'select4' => $select4,
			'select5' => $select5,
			'select6' => $select6,
			'select7' => $select7,
			'select8' => $select8,
			'select9' => $select9,
			'sdisableTOC' => $sdisableTOC,
			'typeCalculate' => $typeCalculate,
			'seValue1' => $seValue1,
			'seValue2' => $seValue2,
			'seValue3' => $seValue3,
			'seValue4' => $seValue4,
			'seValue5' => $seValue5,
			'seValue6' => $seValue6,
			'seValue7' => $seValue7,
			'seValue8' => $seValue8,
			'seValue9' => $seValue9,
			'ID_USER_STUDY' => $ID_USER_STUDY,
			'ID_USER_CURRENT' => $this->auth->user()->ID_USER,
		];

		return $array;
	}

	public function setBrainMode($brainMode) 
	{
        $this->brainMode = $brainMode;
    }

    public function  getBrainMode()
    {
    	return $this->brainMode;
    }

    public function startBrainCalculate()
    {
    	$input = $this->request->all();

    	$idStudy = $idStudyEquipment = $checkOptim = $typeCalculate = null;

		if (isset($input['idStudy'])) $idStudy = intval($input['idStudy']);
		if (isset($input['idStudyEquipment'])) $idStudyEquipment = intval($input['idStudyEquipment']);
		if (isset($input['checkOptim'])) $checkOptim = intval($input['checkOptim']);
		if (isset($input['typeCalculate'])) $typeCalculate = intval($input['typeCalculate']);
		
		$brainMode = $this->brainCal->getBrainMode($idStudy);

		if ($checkOptim == "true") {
			$this->setBrainMode(12);
			$brainMode = 12;
		} else {
			$this->setBrainMode(11);
			$brainMode = 11;
		}

 		$runType = null;
 		if ($typeCalculate == 3) {
 			$brainMode = 13;
 			$this->saveCalculationParameters($this->request, $idStudyEquipment, $brainMode);
 			$this->saveTempRecordPts($this->request, $idStudy);
 			//resetBrainStudyError(); not using
 			$runType = $this->startMaxCapacityCalculation($this->request, $idStudy, $idStudyEquipment);
 		} else {
 			$this->saveEquipmentSettings($this->request, $idStudyEquipment);
	 		$this->saveCalculationParameters($this->request, $idStudyEquipment, $brainMode);
	 		$this->saveTempRecordPts($this->request, $idStudy);

	 		if ($this->cal->isStudyHasChilds($idStudy)) {
	 			$this->cal->setChildsStudiesToRecalculate($idStudy, $idStudyEquipment);
	 		}

	 		$this->runStudyCleaner($idStudy, $idStudyEquipment, 53);

 			$runType = $this->startBrainNumericalCalculation($idStudy, $idStudyEquipment, $brainMode);

 			$study = Study::find($idStudy);
 			if ($study->OPTION_CRYOPIPELINE == 1) {
				$this->startPipeLine($idStudy, $idStudyEquipment);
			}
					
 			if ($study->OPTION_ECO == 1) {
 				$this->startEconomic($idStudy, $idStudyEquipment);
			} else {
				$this->startConsumptionEconomic($idStudy, $idStudyEquipment);
			}
 		}

    	return $runType;
    }

    public function startCalcul()
    {
    	$input = $this->request->all();

    	$idStudy = $idStudyEquipment = null;

		if (isset($input['idStudy'])) $idStudy = intval($input['idStudy']);
		if (isset($input['idStudyEquipment'])) $idStudyEquipment = intval($input['idStudyEquipment']);

		$brainMode = 1;
		$this->saveCalculationParameters($this->request, $idStudyEquipment, $brainMode);
		$this->cal->reset2DTempRecordPts($idStudy);

    	return $this->runBrainCalculator($idStudy, $idStudyEquipment, false, 0, $brainMode);
    }

    public function calculOptim()
    {
    	$input = $this->request->all();

    	$idStudy = $idStudyEquipment = null;

		if (isset($input['idStudy'])) $idStudy = intval($input['idStudy']);
		if (isset($input['idStudyEquipment'])) $idStudyEquipment = intval($input['idStudyEquipment']);

		$brainMode = 2;
		$this->saveCalculationParameters($this->request, $idStudyEquipment, $brainMode);
		$this->cal->reset2DTempRecordPts($idStudy);
    }

    public function startCalculOptim()
    {
    	$input = $this->request->all();
    	$idStudy = $idStudyEquipment = $brainOptim = null;

		if (isset($input['idStudy'])) $idStudy = intval($input['idStudy']);
		if (isset($input['idStudyEquipment'])) $idStudyEquipment = intval($input['idStudyEquipment']);

    	$BRAIN_OPTIM = $BRAIN_OPTIM_TSFIXED = $BRAIN_OPTIM_TRFIXED = $BRAIN_OPTIM_DHPFIXED = $BRAIN_OPTIM_TOPFIXED = $BRAIN_OPTIM_COSTFIXED= null;

    	if (isset($input['BRAIN_OPTIM'])) $BRAIN_OPTIM = intval($input['BRAIN_OPTIM']);
    	if (isset($input['BRAIN_OPTIM_TSFIXED'])) $BRAIN_OPTIM_TSFIXED = intval($input['BRAIN_OPTIM_TSFIXED']);
    	if (isset($input['BRAIN_OPTIM_TRFIXED'])) $BRAIN_OPTIM_TRFIXED = intval($input['BRAIN_OPTIM_TRFIXED']);

    	if ($BRAIN_OPTIM == 1) {
    		$brainOptim = $BRAIN_OPTIM_TSFIXED;
    	} else {
    		$brainOptim = $BRAIN_OPTIM_TRFIXED;
    	}
    	
    	return $this->runBrainCalculator($idStudy, $idStudyEquipment, false, 0, $brainOptim);
    }

    public function getBrainOptim()
    {
    	$input = $this->request->all();

    	$idStudyEquipment = null;

    	if (isset($input['idStudyEquipment'])) $idStudyEquipment = intval($input['idStudyEquipment']);
    	$std = $this->equipment->getStd($idStudyEquipment);
    	($std == $this->value->EQUIP_STANDARD) ? $BRAIN_OPTIM = 1 : $BRAIN_OPTIM = 0;

    	$EQUIP_STANDARD = $this->value->EQUIP_STANDARD;
    	$BRAIN_OPTIM_TSFIXED = $this->value->BRAIN_OPTIM_TSFIXED;
    	$BRAIN_OPTIM_TRFIXED = $this->value->BRAIN_OPTIM_TRFIXED;
    	$BRAIN_OPTIM_DHPFIXED = $this->value->BRAIN_OPTIM_DHPFIXED;
    	$BRAIN_OPTIM_TOPFIXED = $this->value->BRAIN_OPTIM_TOPFIXED;
    	$BRAIN_OPTIM_COSTFIXED = $this->value->BRAIN_OPTIM_COSTFIXED;

    	$array = [
    		'BRAIN_OPTIM' => $BRAIN_OPTIM,
    		'EQUIP_STANDARD' => $EQUIP_STANDARD,
    		'BRAIN_OPTIM_TSFIXED' => $BRAIN_OPTIM_TSFIXED,
    		'BRAIN_OPTIM_TRFIXED' => $BRAIN_OPTIM_TRFIXED,
    		'BRAIN_OPTIM_DHPFIXED' => $BRAIN_OPTIM_DHPFIXED,
    		'BRAIN_OPTIM_TOPFIXED' => $BRAIN_OPTIM_TOPFIXED,
    		'BRAIN_OPTIM_COSTFIXED' => $BRAIN_OPTIM_COSTFIXED
    	];
    	
    	return $array;
    }

    public function saveCalculationParameters(Request $request, $idStudyEquipment, $brainMode)
    {
    	$input = $request->all();

    	$checkOptim = $epsilonTemp = $epsilonEnth = $epsilonTemp = $epsilonEnth = $scheckStorage = $sdisableOptim = null;

		$timeStep = 1.0;
		$precision = 0.5;
		$storagestep = $relaxCoef = 0.0;
		$hRadioOn = 1;
		$vRadioOn = $tempPtSurf = $tempPtIn = $tempPtBot = $tempPtAvg = $nbOptimIter = 0;
		$maxIter = 100;

    	if (isset($input['checkOptim'])) $checkOptim = intval($input['checkOptim']);
		if (isset($input['epsilonTemp'])) $epsilonTemp = $input['epsilonTemp'];
		if (isset($input['epsilonEnth'])) $epsilonEnth = $input['epsilonEnth'];
		if (isset($input['timeStep'])) $timeStep = $input['timeStep'];
		if (isset($input['precision'])) $precision = $input['precision'];
		if (isset($input['scheckStorage'])) $scheckStorage = intval($input['scheckStorage']);
		if (isset($input['storagestep'])) $storagestep = $input['storagestep'];
		if (isset($input['hRadioOn'])) $hRadioOn = intval($input['hRadioOn']);
		if (isset($input['vRadioOn'])) $vRadioOn = intval($input['vRadioOn']);
		if (isset($input['maxIter'])) $maxIter = intval($input['maxIter']);
		if (isset($input['relaxCoef'])) $relaxCoef = $input['relaxCoef'];
		if (isset($input['tempPtSurf'])) $tempPtSurf = $input['tempPtSurf'];
		if (isset($input['tempPtIn'])) $tempPtIn = $input['tempPtIn'];
		if (isset($input['tempPtBot'])) $tempPtBot = $input['tempPtBot'];
		if (isset($input['tempPtAvg'])) $tempPtAvg = $input['tempPtAvg'];
		if (isset($input['nbOptimIter'])) $nbOptimIter = intval($input['nbOptimIter']);
		if (isset($input['sdisableOptim'])) $sdisableOptim = intval($input['sdisableOptim']);

		$calculationParameter = CalculationParameter::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->first();

		if ($checkOptim == 1) {
			$minMaxOptim = $this->brainCal->getMinMax(1130);
			if ($sdisableOptim == 1) {
				$calculationParameter->NB_OPTIM = $nbOptimIter;
			} else {
				$calculationParameter->NB_OPTIM = intval($minMaxOptim->DEFAULT_VALUE);
			}

			$calculationParameter->ERROR_T = $this->convert->unitConvert($this->value->TEMPERATURE, $epsilonTemp);
			$calculationParameter->ERROR_H = $this->convert->unitConvert($this->value->TEMPERATURE, $epsilonEnth);
		} else {
			$minMaxH = $this->brainCal->getMinMax(1131);
			$minMaxT = $this->brainCal->getMinMax(1132);
			switch ($brainMode) {
                case 1:
                    $calculationParameter->NB_OPTIM = 0;
                    $calculationParameter->ERROR_H = $minMaxH->DEFAULT_VALUE;
                    $calculationParameter->ERROR_T = $minMaxT->DEFAULT_VALUE;
                    break;
                case 2:
                	$minMaxOptim = $this->brainCal->getMinMax(1130);
                    $calculationParameter->NB_OPTIM = intval($minMaxOptim->DEFAULT_VALUE);
                    $calculationParameter->ERROR_H = $minMaxH->DEFAULT_VALUE;
                    $calculationParameter->ERROR_T = $minMaxT->DEFAULT_VALUE;
                    break;
                case 10:
                case 14:
                    $calculationParameter->NB_OPTIM = 0;
                    $calculationParameter->ERROR_H = $minMaxH->DEFAULT_VALUE;
                    $calculationParameter->ERROR_T = $minMaxT->DEFAULT_VALUE;
                    break;
                case 11:
                case 15:
                    $calculationParameter->NB_OPTIM = 0;
                    $minMaxH = $this->brainCal->getMinMax(1133);
					$minMaxT = $this->brainCal->getMinMax(1134);
                    $calculationParameter->ERROR_H = $minMaxH->DEFAULT_VALUE;
                    $calculationParameter->ERROR_T = $minMaxT->DEFAULT_VALUE;
                    break;
                case 12:
                case 16:
                	$minMaxH = $this->brainCal->getMinMax(1135);
					$minMaxT = $this->brainCal->getMinMax(1136);
                    $calculationParameter->NB_OPTIM = 0;
                    $calculationParameter->ERROR_H = $minMaxH->DEFAULT_VALUE;
                    $calculationParameter->ERROR_T = $minMaxT->DEFAULT_VALUE;
                    break;
                case 13:
                case 17:
                	$minMaxH = $this->brainCal->getMinMax(1137);
					$minMaxT = $this->brainCal->getMinMax(1138);
                    $calculationParameter->NB_OPTIM = 0;
                    $calculationParameter->ERROR_H = $minMaxH->DEFAULT_VALUE;
                    $calculationParameter->ERROR_T = $minMaxT->DEFAULT_VALUE;
            }
		}

		$calculationParameter->TIME_STEP = $this->convert->unitConvert($this->value->TIME, $timeStep, 3);
		$calculationParameter->PRECISION_REQUEST = $this->convert->unitConvert($this->value->TIME, $precision, 3);

		$studyEquipment = StudyEquipment::find($idStudyEquipment);
		if ($studyEquipment->RUN_CALCULATE != 1) {
			$studyEquipment->RUN_CALCULATE = 1;
			$studyEquipment->save();
		}

        switch ($brainMode) {
            case 1:
            case 2:
                $studyEquipment->BRAIN_SAVETODB = 1;
                $studyEquipment->save();
                break;

            case 10:
            case 11:
            case 13:
            case 15:
            case 17:
                $studyEquipment->BRAIN_SAVETODB = 0;
                $studyEquipment->save();
                break;
            case 12:
            case 14:
            case 16:
                if ($scheckStorage == 1) {
                    $studyEquipment->BRAIN_SAVETODB = 1;
                    $studyEquipment->save();
                } else {
                    $studyEquipment->BRAIN_SAVETODB = 0;
                    $studyEquipment->save();
                }
                break;
            case 3:
            case 4:
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
            default:
                $studyEquipment->BRAIN_SAVETODB = 0;
                $studyEquipment->save();
        }

        if ($studyEquipment->BRAIN_SAVETODB == 1) {
			$lfStorageStep = $this->convert->unitConvert($this->value->TIME, $storagestep, 3);
			$lfTimeStep = $this->convert->unitConvert($this->value->TIME, $timeStep, 3);
			$ldNbStorageStep = 0;

	        if ($timeStep != -1.0) {
	            $ldNbStorageStep = round($lfStorageStep / $lfTimeStep);
	        } else {
	            $ldNbStorageStep = -1;
	        }

	        $calculationParameter->STORAGE_STEP = $ldNbStorageStep;
		}

		$calculationParameter->HORIZ_SCAN = $hRadioOn;
		$calculationParameter->VERT_SCAN = $vRadioOn;
		$calculationParameter->MAX_IT_NB = $maxIter;
		$calculationParameter->RELAX_COEFF = $this->convert->convertCalculator($relaxCoef, 1.0, 0.0);
		$calculationParameter->STOP_TOP_SURF = $this->convert->unitConvert($this->value->TEMPERATURE, $tempPtSurf, 2);
		$calculationParameter->STOP_INT = $this->convert->unitConvert($this->value->TEMPERATURE, $tempPtIn, 2);
		$calculationParameter->STOP_BOTTOM_SURF = $this->convert->unitConvert($this->value->TEMPERATURE, $tempPtBot, 2);
		$calculationParameter->STOP_AVG = $this->convert->unitConvert($this->value->TEMPERATURE, $tempPtAvg, 2);
		$calculationParameter->save();
    }

    public function saveTempRecordPts(Request $request, $idStudy)
    {
    	$input = $request->all();

    	$seValue1 = $seValue2 = $seValue3 = $seValue4 = $seValue5 = $seValue6 = $seValue7 = $seValue8 = $seValue9 = 0.0;

    	if (isset($input['seValue1'])) $seValue1 = $input['seValue1'];
		if (isset($input['seValue2'])) $seValue2 = $input['seValue2'];
		if (isset($input['seValue3'])) $seValue3 = $input['seValue3'];
		if (isset($input['seValue4'])) $seValue4 = $input['seValue4'];
		if (isset($input['seValue5'])) $seValue5 = $input['seValue5'];
		if (isset($input['seValue6'])) $seValue6 = $input['seValue6'];
		if (isset($input['seValue7'])) $seValue7 = $input['seValue7'];
		if (isset($input['seValue8'])) $seValue8 = $input['seValue8'];
		if (isset($input['seValue9'])) $seValue9 = $input['seValue9'];

    	$temRecordPt = TempRecordPts::where('ID_STUDY', $idStudy)->first();

		if ($temRecordPt != null) {
			$temRecordPt->AXIS1_PT_TOP_SURF = $seValue1;
			$temRecordPt->AXIS2_PT_TOP_SURF = $seValue2;
			$temRecordPt->AXIS3_PT_TOP_SURF = $seValue3;
			$temRecordPt->AXIS1_PT_INT_PT = $seValue4;
			$temRecordPt->AXIS2_PT_INT_PT = $seValue5;
			$temRecordPt->AXIS3_PT_INT_PT = $seValue6;
			$temRecordPt->AXIS1_PT_BOT_SURF = $seValue7;
			$temRecordPt->AXIS2_PT_BOT_SURF = $seValue8;
			$temRecordPt->AXIS3_PT_BOT_SURF = $seValue9;
			$temRecordPt->CONTOUR2D_TEMP_MIN = 0.0;
			$temRecordPt->CONTOUR2D_TEMP_MAX = 0.0;
			$temRecordPt->save();
		}
    }

    public function saveEquipmentSettings(Request $request, $idStudyEquipment)
    {
    	$input = $request->all();
    	$dwellingTimes = $temperatures = [];

		if (isset($input['dwellingTimes'])) $newLTs = $input['dwellingTimes'];
		if (isset($input['temperatures'])) $newLTr = $input['temperatures'];

		$oldLTs = $this->brainCal->getListTs($idStudyEquipment);
		for ($i = 0; $i < count($oldLTs) ; $i++) { 
			if ($oldLTs[$i] != $newLTs[$i]['value']) {
				$oldLTs[$i] = $newLTs[$i]['value'];
				$this->brainCal->setTs($idStudyEquipment, doubleval($oldLTs[$i]), $i);
			}
		}

		$oldLTr = $this->brainCal->getListTr($idStudyEquipment);
		for ($i = 0; $i < count($oldLTr); $i++) { 
			if ($oldLTr[$i] != $newLTr[$i]['value']) {
				$oldLTr[$i] = $newLTr[$i]['value'];
				$this->brainCal->setTr($idStudyEquipment, doubleval($oldLTr[$i]), $i);
			}
		}
    }

    public function runStudyCleaner($idStudy, $idStudyEquipment, $number)
    {
    	$ret = 0;
		$conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment);
		$ret = $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, $number);

		if ($ret == 0 && $this->cal->isStudyHasChilds($idStudy)) {
			$this->cal->getCalculableStudyEquipments($idStudy, $idStudyEquipment);
		}

		return $ret;	
    }

    public function startBrainNumericalCalculation($idStudy, $idStudyEquipment, $brainMode)
    {
    	$ldMode = 10;
        
        switch ($brainMode) {
            case 10:
                $ldMode = 10;
                break;

            case 11:
            case 15:
                $ldMode = 11;
                break;

            case 12:
            case 14:
            case 16:
                $ldMode = 12;
                break;

            case 13:
            case 17:
                $ldMode = 13;
                break;

            default:
                $ldMode = 10;
        }

        $studyEquipment = StudyEquipment::find($idStudyEquipment);

		$results = null;

		if (count($studyEquipment) > 0) {
			$conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment, 1, 1, 'c:\\temp\\brain_log.txt');
			$param = new \Cryosoft\stSKBRParam();

			$results = $this->kernel->getKernelObject('BrainCalculator')->BRTeachCalculation($conf, $param, $ldMode);
		}
		return $results;
    }

    private function runBrainCalculator($idStudy, $idStudyEquipment, $bOptim, $brainOption, $brainMode)
    {
    	if ($this->cal->isStudyHasChilds($idStudy)) {
    		$this->cal->setChildsStudiesToRecalculate($idStudy, $idStudyEquipment);
    	}

    	$studyEquipment = StudyEquipment::find($idStudyEquipment);

    	try {
    		if (!$this->equipment->getCapability($studyEquipment->CAPABILITIES, 128)) 
    			throw new \Exception("CAN NOT RUN BRAIN CALCULATOR: MULTI TR!!", 1);

    		if ($this->equipment->getCapability($studyEquipment->CAPABILITIES, 64)) $bOptim = false;

    		if ($bOptim) {
    			if (!$this->doAnalogicBrainOptim($idStudy, $idStudyEquipment, $brainOption)) 
    				throw new \Exception("OPTIMIZATION - ANA1: PHAMCAST FAILLED !!", 1);

    			if (!$this->startExhaustGasTemp($idStudy, $idStudyEquipment))
    				throw new \Exception("OPTIMIZATION - EXHAUST GAS TEMP CALCULATION FAILED !!", 1);
    		}

    		$brainMode = 0;
        
	        if ($bOptim) {
	            switch ($brainOption) {
	                case 0:
	                case 3:
	                case 4:
	                case 5:
	                    $brainMode = 0;
	                    break;
	                case 1:
	                    $brainMode = 1;
	                    break;
	                case 2:
	                    $brainMode = 2;
	                    break;

	                default:
	                    $brainMode = 0;
	                    break;
	            }
	        } else {
	            $brainMode = 0;
	        }

	        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment);
			$param = new \Cryosoft\stSKBRParam();
			return $this->kernel->getKernelObject('BrainCalculator')->BRTeachCalculation($conf, $param, $brainMode);

    	} catch (Exception $e) {
    		return false;
    	}
    	return true;
    }

    private function doAnalogicBrainOptim($idStudy, $idStudyEquipment, $brainOption)
    {
    	$ret = true;

        switch ($brainOption) {
            case 2:
                $ret = $this->startPhamCastCalculator($idStudy, $idStudyEquipment, false);
                break;

            case 1:
                $ret = $this->startPhamCastCalculator($idStudy, $idStudyEquipment, true);
                break;

            case 3:
                $ret = false;
                break;

            case 4:
                $ret = false;
                break;

            case 5:
                $ret = false;
        }

        return $ret;
    }

    private function startPhamCastCalculator($idStudy, $idStudyEquipment, $doTr)
    {
    	$conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment);
		return $this->kernel->getKernelObject('PhamCastCalculator')->PCCCalculation($conf);
    }

    private function startExhaustGasTemp($idStudy, $idStudyEquipment)
    {
    	$conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment);
		return $this->kernel->getKernelObject('KernelToolCalculator')->KTCalculator($conf, 1);
    }

    private function startMaxCapacityCalculation(Request $request, $idStudy, $idStudyEquipment)
    {
        $input = $request->all();
    	$lfControlTemp = $lfLoadingRateMax = 0.0;
        $toc = 0;

        $studyEquipment = StudyEquipment::find($idStudyEquipment);

        if ($this->equipment->getCapability($studyEquipment->CAPABILITIES, 1)) {
			$temperatures = [];

			if (isset($input['temperatures'])) {
				$temperatures = $input['temperatures'];
				if (count($temperatures) > 0) {
					$lfControlTemp = doubleval($temperatures[0]['value']);
				}
			} else {
				$temperatures = $this->brainCal->getListTr($idStudyEquipment);
				if (count($temperatures) > 0) {
					$lfControlTemp = doubleval($temperatures[0]);
				}
			}
        }

        if (isset($input['toc'])) {
			$toc = $input['toc'];
			$lfLoadingRateMax = doubleval($toc);
		}

		$this->runStudyCleaner($idStudy, $idStudyEquipment, 54);

		$conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment);
		$param = new \Cryosoft\stSKBRParam($lfControlTemp, $lfLoadingRateMax);
		$ldMode = 13;

		return $this->kernel->getKernelObject('BrainCalculator')->BRTeachCalculation($conf, $param, $ldMode);
    }

    public function getProgressBarStudyEquipment()
    {
    	$input = $this->request->all();
    	$idStudy = null;
    	$progress = array();
    	$item = array();

    	if (isset($input['idStudy'])) $idStudy = intval($input['idStudy']);

    	$studyEquipments = StudyEquipment::where('ID_STUDY', $idStudy)->get();

    	if (count($studyEquipments) > 0) {
    		for ($i = 0; $i < count($studyEquipments); $i++) { 
	    		$item['EQUIP_NAME'] = $studyEquipments[$i]->EQUIP_NAME;
	    		$item['id'] = $i;
	    		array_push($progress, $item);
	    	}
    	}
    	
    	$array = [
    		'studyEquipments' => $progress,
    	];

    	return $array;
    }
}