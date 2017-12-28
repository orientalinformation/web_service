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
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use App\Models\StudyEquipment;
use App\Models\CalculationParameter;
use App\Models\CalculationParametersDef;
use App\Models\TempRecordPts;
use App\Kernel\KernelService;


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
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct(Request $request, Auth $auth, CalculateService $cal, ValueListService $value, KernelService $kernel, EquipmentsService $equipment, UnitsConverterService $convert) 
	{
		$this->request = $request;
		$this->auth = $auth;
		$this->cal = $cal;
		$this->value = $value;
		$this->kernel = $kernel;
		$this->equipment = $equipment;
		$this->convert = $convert;
	}

	public function getOptimumCalculator() 
	{
		$input = $this->request->all();
		$idStudy = $input['idStudy'];
		$idStudyEquipment = null;

		if (isset($input['idStudyEquipment'])) {
			$idStudyEquipment = $input['idStudyEquipment'];
		}

		$calMode = $this->cal->getCalculationMode($idStudy);
		$sdisableFields = $this->cal->disableFields($idStudy);
		$sdisableCalculate = $this->cal->disableCalculate($idStudy);

		$sdisableOptim = $sdisableNbOptim = $sdisableStorage = 0;
		$sclassNbOptim = $sclassStorage = "";
		$sdisableTimeStep = $sdisablePrecision = "";
		$scheckOptim = $scheckStorage = 0;
		$isBrainCalculator = 0;

		if ($idStudyEquipment != null) {
			$isBrainCalculator = 1;
		}

		if ($sdisableFields == "") {
			$sdisableOptim = $sdisableFields;

			if ($calMode == $this->value->STUDY_OPTIMUM_MODE) {
				$sdisableNbOptim = $sdisableStorage = 1;
				$sclassNbOptim = $sclassStorage = "sous-titredisabled";
				$scheckOptim = 1;
				$scheckStorage = 0;

				if ($this->cal->getTimeStep($idStudy) == $this->value->VALUE_N_A) {
					$sdisableTimeStep = "disabled";
				} else {
					$sdisableTimeStep = "";
				}

				if ($this->cal->getPrecision($idStudy) == $this->value->VALUE_N_A) {
					$sdisablePrecision = "disabled";
				} else {
					$sdisablePrecision = "";
				}
			} else if ($calMode == $this->value->STUDY_SELECTED_MODE) {
				$sdisableNbOptim = $sdisableStorage = 0;
				$sclassNbOptim = $sclassStorage = "sous-titre";
				$scheckOptim = 1;
				$scheckStorage = 0;
				$sdisableTimeStep = $sdisablePrecision = "";
			} else {
				$sdisableNbOptim = $sdisableStorage = 1;
				$sclassNbOptim = $sclassStorage = "sous-titredisabled";
				$scheckOptim = $scheckStorage = 0;
				$sdisableTimeStep = $sdisablePrecision = "";
			}

		} else {
			$sdisableOptim = $sdisableNbOptim = $sdisableStorage = 1;
			$sdisableTimeStep = $sdisablePrecision = "disabled";
			$sclassNbOptim = $sclassStorage = "sous-titredisabled";
			$scheckOptim = $scheckStorage = 0;
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
		$select2 = $this->cal->getOption($idStudy, "Y", "TOP");
        $select3 = $this->cal->getOption($idStudy, "Z", "TOP");
        $select4 = $this->cal->getOption($idStudy, "X", "INT");
        $select5 = $this->cal->getOption($idStudy, "Y", "INT");
        $select6 = $this->cal->getOption($idStudy, "Z", "INT");
        $select7 = $this->cal->getOption($idStudy, "X", "BOT");
        $select8 = $this->cal->getOption($idStudy, "Y", "BOT");
        $select9 = $this->cal->getOption($idStudy, "Z", "BOT");

		$array = [
			'sdisableFields' => $sdisableFields,
			'sdisableCalculate' => $sdisableCalculate,
			'scheckOptim' => $scheckOptim,
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
		];

		return $array;
	}

	public function startCalculate()
	{
		$input = $this->request->all();

		$idStudy = null;
		$idStudyEquipment = null;
		$scheckOptim = null;
		$epsilonTemp = null;
		$epsilonEnth = null;
		$timeStep = -1.0;
		$precision = -1.0;
		$storagestep = 0.0;
		$hRadioOn = 1;
		$vRadioOn  = 0;
		$maxIter = 100;
		$relaxCoef = 0.0;
		$tempPtSurf = 0;
		$tempPtIn = 0;
		$tempPtBot = 0;
		$tempPtAvg = 0;
		$select1 = $select2 = $select3 = $select4 = $select5 = $select6 = $select7 = $select8 = $select9 = 0.0;

		if (isset($input['idStudy'])) $idStudy = intval($input['idStudy']);
		if (isset($input['idStudyEquipment'])) $idStudyEquipment = intval($input['idStudyEquipment']);
		if (isset($input['scheckOptim'])) $scheckOptim = intval($input['scheckOptim']);
		if (isset($input['epsilonTemp'])) $epsilonTemp = $input['epsilonTemp'];
		if (isset($input['epsilonEnth'])) $epsilonEnth = $input['epsilonEnth'];
		if (isset($input['timeStep'])) $timeStep = $input['timeStep'];
		if (isset($input['precision'])) $precision = $input['precision'];
		if (isset($input['storagestep'])) $storagestep = $input['storagestep'];
		if (isset($input['hRadioOn'])) $hRadioOn = intval($input['hRadioOn']);
		if (isset($input['vRadioOn'])) $vRadioOn = intval($input['vRadioOn']);
		if (isset($input['maxIter'])) $maxIter = intval($input['maxIter']);
		if (isset($input['relaxCoef'])) $relaxCoef = $input['relaxCoef'];
		if (isset($input['tempPtSurf'])) $tempPtSurf = $input['tempPtSurf'];
		if (isset($input['tempPtIn'])) $tempPtIn = $input['tempPtIn'];
		if (isset($input['tempPtBot'])) $tempPtBot = $input['tempPtBot'];
		if (isset($input['tempPtAvg'])) $tempPtAvg = $input['tempPtAvg'];
		if (isset($input['select1'])) $select1 = floatval($input['select1']);
		if (isset($input['select2'])) $select2 = floatval($input['select2']);
		if (isset($input['select3'])) $select3 = floatval($input['select3']);
		if (isset($input['select4'])) $select4 = floatval($input['select4']);
		if (isset($input['select5'])) $select5 = floatval($input['select5']);
		if (isset($input['select6'])) $select6 = floatval($input['select6']);
		if (isset($input['select7'])) $select7 = floatval($input['select7']);
		if (isset($input['select8'])) $select8 = floatval($input['select8']);
		if (isset($input['select9'])) $select9 = floatval($input['select9']);


		$calMode = $this->cal->getCalculationMode($idStudy);

		$studyEquipments = StudyEquipment::where('ID_STUDY', $idStudy)->get();

		if (count($studyEquipments) > 0) {
			for ($i = 0; $i < count($studyEquipments); $i++) { 
				$idStudyEquipment = $studyEquipments[$i]->ID_STUDY_EQUIPMENTS;
				$calculationParameter = CalculationParameter::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->first();

				$calculationParametersDef = CalculationParametersDef::find($this->auth->user()->ID_USER);
				$calculationParameter->STORAGE_STEP = $calculationParametersDef->STORAGE_STEP_DEF;
				$calculationParameter->PRECISION_LOG_STEP = $calculationParametersDef->PRECISION_LOG_STEP_DEF;

				if ($scheckOptim != null) {
					$calculationParameter->NB_OPTIM = $scheckOptim;
					$calculationParameter->ERROR_T = $this->convert->unitConvert($this->value->TEMPERATURE, $epsilonTemp);
					$calculationParameter->ERROR_H = $this->convert->unitConvert($this->value->TEMPERATURE, $epsilonEnth);
				}

				$calculationParameter->TIME_STEP = $this->convert->unitConvert($this->value->TIME, $timeStep, 3);
				$calculationParameter->PRECISION_REQUEST = $this->convert->unitConvert($this->value->TIME, $precision, 3);

				if ($studyEquipments[$i]->BRAIN_SAVETODB == 1) {
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
		}

		$temRecordPt = TempRecordPts::where('ID_STUDY', $idStudy)->first();

		if ($temRecordPt != null) {
			$temRecordPt->AXIS1_PT_TOP_SURF = $select1;
			$temRecordPt->AXIS2_PT_TOP_SURF = $select2;
			$temRecordPt->AXIS3_PT_TOP_SURF = $select3;
			$temRecordPt->AXIS1_PT_INT_PT = $select4;
			$temRecordPt->AXIS2_PT_INT_PT = $select5;
			$temRecordPt->AXIS3_PT_INT_PT = $select6;
			$temRecordPt->AXIS1_PT_BOT_SURF = $select7;
			$temRecordPt->AXIS2_PT_BOT_SURF = $select8;
			$temRecordPt->AXIS3_PT_BOT_SURF = $select9;
			$temRecordPt->CONTOUR2D_TEMP_MIN = 0.0;
			$temRecordPt->CONTOUR2D_TEMP_MAX = 0.0;
			$temRecordPt->save();
		}
		return $this->startNumericalCalculation($idStudy);
	}

	public function startStudyCalculation($idStudy)
	{
		$conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, -1);
		$this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, 50);

		$studyEquipments = StudyEquipment::where('ID_STUDY', $idStudy)->get();

		$results = [];
		
		if (count($studyEquipments) > 0) {
			for ($i = 0; $i < count($studyEquipments); $i++) { 
				$idStudyEquipment = $studyEquipments[$i]->ID_STUDY_EQUIPMENTS;
				$capability = $studyEquipments[$i]->CAPABILITIES;

				$error = $this->startDimMat($idStudy, $idStudyEquipment);

				if ($error != 0) {
					echo "";
				}

				if ($this->equipment->getCapability($capability, 16)) {
					array_push($results, $this->startConsumptionEconomic($idStudy, $idStudyEquipment));
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

	public function startNumericalCalculation($idStudy)
	{
		$studyEquipments = StudyEquipment::where('ID_STUDY', $idStudy)->get();
		$results = [];

		if (count($studyEquipments) > 0) {
			for ($i = 0; $i < count($studyEquipments); $i++) { 
				$idStudyEquipment = $studyEquipments[$i]->ID_STUDY_EQUIPMENTS;
				$capability = $studyEquipments[$i]->CAPABILITIES;

				if ($this->equipment->getCapability($capability, 128)) {
					$confCleaner = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, -1);
					$this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($confCleaner, 50);

					$conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idStudy, $idStudyEquipment);
					$param = new \Cryosoft\stSKBRParam();

					array_push($results, $this->kernel->getKernelObject('BrainCalculator')->BRTeachCalculation($conf, $param, 10));
				}
			}
		}
		return $results;
	}
}