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

use Illuminate\Support\Facades\DB;
use App\Models\Study;
use App\Models\StudyEquipment;
use App\Models\Production;
use App\Models\Product;
use App\Models\DimaResults;
use App\Models\EconomicResults;
use App\Models\StudEqpPrm;
use App\Models\MinMax;
use App\Models\StudEquipprofile;
use App\Models\RecordPosition;
use App\Models\TempRecordPts;
use App\Models\TempRecordData;
use App\Models\MeshPosition;
use App\Models\ProductElmt;
use App\Models\CalculationParameter;
use App\Models\Translation;
use App\Models\InitTemp3D;
use App\Models\InitialTemperature;

use App\Cryosoft\ValueListService;
use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\EquipmentsService;
use App\Cryosoft\DimaResultsService;
use App\Cryosoft\EconomicResultsService;
use App\Cryosoft\StudyService;
use App\Cryosoft\OutputService;
use App\Models\LayoutGeneration;
use App\Models\PackingElmt;

class ReportService
{
    /**
	 * @var Illuminate\Contracts\Auth\Factory
	 */
	protected $auth;

	/**
	 * @var App\Cryosoft\ValueListService
	 */
	protected $value;

	/**
	 * @var App\Cryosoft\UnitsConverterService
	 */
	protected $unit;

	/**
	 * @var App\Cryosoft\EquipmentsService;
	 */
    protected $equip;

	/**
	 * @var App\Cryosoft\DimaResultsService;
	 */
    protected $dima;

	/**
	 * @var App\Cryosoft\EconomicResultsService;
	 */
    protected $eco;

	/**
	 * @var App\Cryosoft\StudyService;
	 */
    protected $stu;

	/**
	 * @var App\Cryosoft\OutputService;
	 */
    protected $output;
    
	public function __construct(\Laravel\Lumen\Application $app) 
    {
		$this->app = $app;
		$this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
		$this->value = $app['App\\Cryosoft\\ValueListService'];
		$this->unit = $app['App\\Cryosoft\\UnitsConverterService'];
		$this->equip = $app['App\\Cryosoft\\EquipmentsService'];
		$this->dima = $app['App\\Cryosoft\\DimaResultsService'];
		$this->eco = $app['App\\Cryosoft\\EconomicResultsService'];
		$this->stu = $app['App\\Cryosoft\\StudyService'];
        $this->output = $app['App\\Cryosoft\\OutputService'];
        $this->product = $app['App\\Cryosoft\\ProductService'];
        $this->productElmts = $app['App\\Cryosoft\\ProductElementsService'];
        $this->plotFolder = $this->output->base_path('scripts');
        $this->pasTemp = -1.0;
        $this->plotFolder3D = $this->output->public_path('3d');
	}

    public function getParentIdChaining($idStudy, $arrStudyId = [])
    {
        $study = Study::find($idStudy);
        if ($study) {
            $arrStudyId[] = $idStudy;
            $arrStudyId = $this->getParentIdChaining($study->PARENT_ID, $arrStudyId);
        }

        return array_reverse($arrStudyId);
    }

    public function getChainingStudy($idStudy)
    {
        $study = Study::find($idStudy);
        if ($study->PARENT_ID != 0) {
            $arrStudyId = $this->getParentIdChaining($study->PARENT_ID);

            $studyEquipments = StudyEquipment::whereIn("ID_STUDY", $arrStudyId)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();

            $lfcoef = $this->unit->unitConvert($this->value->MASS_PER_UNIT, 1.0);

            $result = array();

            foreach ($studyEquipments as $row) {
                $capabilitie = $row->CAPABILITIES;
                $equipStatus = $row->EQUIP_STATUS;
                $brainType = $row->BRAIN_TYPE;
                $stuName = $row->study->STUDY_NAME;
                $calculWarning = "";

                $item["id"] = $idStudyEquipment = $row->ID_STUDY_EQUIPMENTS;

                $sSpecificSize = "";
                if ($this->equip->getCapability($capabilitie , 2097152)) {
                    $sSpecificSize = $this->equip->getSpecificEquipSize($idStudyEquipment);
                }
                $item["specificSize"] = $sSpecificSize;   
                
                $item["equipName"] = $this->equip->getResultsEquipName($idStudyEquipment);
                $calculate = "";
                $tr = $ts = $vc = $vep = $tfp = $dhp = $conso= $conso_warning = $toc = $precision = "";

                $item["runBrainPopup"] = false;
                if ($this->equip->getCapability($capabilitie, 128)) {
                    $item["runBrainPopup"] = true;
                }

                if (!($this->equip->getCapability($capabilitie, 128))) {
                    $tr = $ts = $vc = $vep = $tfp = $dhp = $conso= $conso_warning = $toc = $precision = "";
                    $calculate = "disabled";
                } else if (($equipStatus != 0) && ($equipStatus != 1) && ($equipStatus != 100000)) {
                    $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $conso_warning = $toc = $precision = "****";
                    $calculate = "disabled";
                } else if ($equipStatus == 10000) {
                    $tr = $ts = $vc = $vep = $tfp = $dhp = $conso= $conso_warning = $toc = $precision = "";
                    $calculate = "disabled";
                } else {
                    $dimaResult = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("DIMA_TYPE", 1)->first();
                    if ($dimaResult == null) {
                        $tr = $ts = $vc = $vep = $tfp = $dhp = $conso= $conso_warning = $toc = $precision = "";
                    } else {
                        switch ($brainType) {
                            case 0:
                                $calculate = true;
                                break;

                            case 1:
                            case 2:
                            case 3:
                                $calculate = false;
                                break;

                            case 4:
                                $calculate = false;
                                break;

                            default:
                                $calculate = "";
                                break;
                        }

                        if ($this->dima->getCalculationWarning($dimaResult->DIMA_STATUS) != 0) {
                            $calculWarning = $this->dima->getCalculationWarning($dimaResult->DIMA_STATUS);
                        }

                        $tr = $this->unit->controlTemperature($dimaResult->SETPOINT);
                        $ts = $this->unit->time($dimaResult->DIMA_TS);
                        $vc = $this->unit->convectionSpeed($dimaResult->DIMA_VC);
                        $vep = $this->unit->enthalpy($dimaResult->DIMA_VEP);
                        $tfp = $this->unit->prodTemperature($dimaResult->DIMA_TFP);

                        if ($this->equip->getCapability($capabilitie, 256)) {
                            $consumption = $dimaResult->CONSUM / $lfcoef;
                            $idCoolingFamily = $row->ID_COOLING_FAMILY;

                            $valueStr = $this->unit->consumption($consumption, $idCoolingFamily, 1);

                            $calculationStatus = $this->dima->getCalculationStatus($dimaResult->DIMA_STATUS);

                            $consumptionCell = $this->dima->consumptionCell($lfcoef, $calculationStatus, $valueStr);
                            $conso = $consumptionCell["value"];
                            $conso_warning = $consumptionCell["warning"];

                        } else {
                            $conso = "****";
                        }

                        if ($this->equip->getCapability($capabilitie, 32)) {
                            $dhp = $this->unit->productFlow($dimaResult->HOURLYOUTPUTMAX);

                            $batch = $row->BATCH_PROCESS;
                            if ($batch) {
                                $massConvert = $this->unit->mass($dimaResult->USERATE);
                                $massSymbol = $this->unit->massSymbol();
                                $toc = $massConvert . " " . $massSymbol . "/batch";
                            } else {
                                $toc = $this->unit->toc($dimaResult->USERATE) . "%";
                            }
                        } else {
                            $dhp = $toc = "****";
                        }

                        if ($dimaResult->DIMA_PRECIS < 50.0) {
                            $precision = $this->unit->precision($dimaResult->DIMA_PRECIS);
                        } else {
                            $precision = "!!!!";
                        }

                    }
                }

                $item["tr"] = $tr;
                $item["ts"] = $ts;
                $item["vc"] = $vc;
                $item["vep"] = $vep;
                $item["tfp"] = $tfp;
                $item["dhp"] = $dhp;
                $item["conso"] = $conso;
                $item["conso_warning"] = $conso_warning;
                $item["toc"] = $toc;
                $item["precision"] = $precision;
                $item["stuName"] = $stuName;
                $item["proInfoStudy"] = $this->getProInfoStudy($row->ID_STUDY);

                $result[] = $item;
            }


            return $result;
        } 
    }

    public function getOptimumHeadBalance($idStudy)
    {
        $idUser = $this->auth->user()->ID_USER;
        $study = Study::find($idStudy);
        $calculationMode = $study->CALCULATION_MODE;
        $studyEquipments = StudyEquipment::where("ID_STUDY", $idStudy)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();     

        $lfcoef = $this->unit->unitConvert($this->value->MASS_PER_UNIT, 1.0);

        $result = array();

        foreach ($studyEquipments as $row) {
            $capabilitie = $row->CAPABILITIES;
            $equipStatus = $row->EQUIP_STATUS;
            $brainType = $row->BRAIN_TYPE;
            $stuName = $row->study->STUDY_NAME;
            $calculWarning = "";

            $item["id"] = $idStudyEquipment = $row->ID_STUDY_EQUIPMENTS;

            $sSpecificSize = "";
            if ($this->equip->getCapability($capabilitie , 2097152)) {
                $sSpecificSize = $this->equip->getSpecificEquipSize($idStudyEquipment);
            }
            $item["specificSize"] = $sSpecificSize;   
            
            $item["equipName"] = $this->equip->getResultsEquipName($idStudyEquipment);
            $calculate = "";
            $tr = $ts = $vc = $vep = $tfp = $dhp = $conso= $conso_warning = $toc = $precision = "";

            $item["runBrainPopup"] = false;
            if ($this->equip->getCapability($capabilitie, 128)) {
                $item["runBrainPopup"] = true;
            }

            if (!($this->equip->getCapability($capabilitie, 128))) {
                $tr = $ts = $vc = $vep = $tfp = $dhp = $conso= $conso_warning = $toc = $precision = "";
                $calculate = "disabled";
            } else if (($equipStatus != 0) && ($equipStatus != 1) && ($equipStatus != 100000)) {
                $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $conso_warning = $toc = $precision = "****";
                $calculate = "disabled";
            } else if ($equipStatus == 10000) {
                $tr = $ts = $vc = $vep = $tfp = $dhp = $conso= $conso_warning = $toc = $precision = "";
                $calculate = "disabled";
            } else {
                $dimaResult = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("DIMA_TYPE", 1)->first();
                if ($dimaResult == null) {
                    $tr = $ts = $vc = $vep = $tfp = $dhp = $conso= $conso_warning = $toc = $precision = "";
                } else {
                    switch ($brainType) {
                        case 0:
                            $calculate = true;
                            break;

                        case 1:
                        case 2:
                        case 3:
                            $calculate = false;
                            break;

                        case 4:
                            $calculate = false;
                            break;

                        default:
                            $calculate = "";
                            break;
                    }

                    if ($this->dima->getCalculationWarning($dimaResult->DIMA_STATUS) != 0) {
                        $calculWarning = $this->dima->getCalculationWarning($dimaResult->DIMA_STATUS);
                    }

                    $tr = $this->unit->controlTemperature($dimaResult->SETPOINT);
                    $ts = $this->unit->time($dimaResult->DIMA_TS);
                    $vc = $this->unit->convectionSpeed($dimaResult->DIMA_VC);
                    $vep = $this->unit->enthalpy($dimaResult->DIMA_VEP);
                    $tfp = $this->unit->prodTemperature($dimaResult->DIMA_TFP);

                    if ($this->equip->getCapability($capabilitie, 256)) {
                        $consumption = $dimaResult->CONSUM / $lfcoef;
                        $idCoolingFamily = $row->ID_COOLING_FAMILY;

                        $valueStr = $this->unit->consumption($consumption, $idCoolingFamily, 1);

                        $calculationStatus = $this->dima->getCalculationStatus($dimaResult->DIMA_STATUS);

                        $consumptionCell = $this->dima->consumptionCell($lfcoef, $calculationStatus, $valueStr);
                        $conso = $consumptionCell["value"];
                        $conso_warning = $consumptionCell["warning"];

                    } else {
                        $conso = "****";
                    }

                    if ($this->equip->getCapability($capabilitie, 32)) {
                        $dhp = $this->unit->productFlow($dimaResult->HOURLYOUTPUTMAX);

                        $batch = $row->BATCH_PROCESS;
                        if ($batch) {
                            $massConvert = $this->unit->mass($dimaResult->USERATE);
                            $massSymbol = $this->unit->massSymbol();
                            $toc = $massConvert . " " . $massSymbol . "/batch";
                        } else {
                            $toc = $this->unit->toc($dimaResult->USERATE) . "%";
                        }
                    } else {
                        $dhp = $toc = "****";
                    }

                    if ($dimaResult->DIMA_PRECIS < 50.0) {
                        $precision = $this->unit->precision($dimaResult->DIMA_PRECIS);
                    } else {
                        $precision = "!!!!";
                    }

                }
            }

            $item["calculWarning"] = $calculWarning;
            $item["calculate"] = $calculate;
            $item["tr"] = $tr;
            $item["ts"] = $ts;
            $item["vc"] = $vc;
            $item["vep"] = $vep;
            $item["tfp"] = $tfp;
            $item["dhp"] = $dhp;
            $item["conso"] = $conso;
            $item["conso_warning"] = $conso_warning;
            $item["toc"] = $toc;
            $item["precision"] = $precision;
            $item["stuName"] = $stuName;

            $result[] = $item;
        }


        return $result;
    }

    public function getOptimumHeadBalanceMax($idStudy)
    {
        $idUser = $this->auth->user()->ID_USER;
        $study = Study::find($idStudy);

        $calculationMode = $study->CALCULATION_MODE;

        //get study equipment
        $studyEquipments = StudyEquipment::where("ID_STUDY", $idStudy)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();

        $lfcoef = $this->unit->unitConvert($this->value->MASS_PER_UNIT, 1.0);

        $result = array();
        // return $studyEquipments;

        foreach ($studyEquipments as $row) {
            $capabilitie = $row->CAPABILITIES;
            $equipStatus = $row->EQUIP_STATUS;
            $brainType = $row->BRAIN_TYPE;
            $ldWarning = 0;
            $item["id"] = $idStudyEquipment = $row->ID_STUDY_EQUIPMENTS;
            $sSpecificSize = "";
            if ($this->equip->getCapability($capabilitie , 2097152)) {
                $sSpecificSize = $this->equip->getSpecificEquipSize($idStudyEquipment);
            }
            $item["specificSize"] = $sSpecificSize;
            $item["equipName"] = $this->equip->getResultsEquipName($idStudyEquipment);

            $dimaResult = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("DIMA_TYPE", 16)->first();

            if (!($this->equip->getCapability($capabilitie, 128))) {
                $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $conso_warning = $toc = $precision = "****";
            } else if (!$dimaResult) {
                $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $conso_warning = $toc = $precision = "";
            } else {
                $ldError = $this->dima->getCalculationWarning($dimaResult->DIMA_STATUS);
                $ldWarning = 0;
                if (($ldError == 282) || ($ldError == 283) || ($ldError == 284) || ($ldError == 285) || ($ldError == 286)) {
                    $ldWarning = $ldError;
                    $ldError = 0;
                }

                if ($ldError != 0) {
                    $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $conso_warning = $toc = $precision = "****";
                } else {
                    $tr = $this->unit->controlTemperature($dimaResult->SETPOINT);
                    $ts = $this->unit->time($dimaResult->DIMA_TS);
                    $vc = $this->unit->convectionSpeed($dimaResult->DIMA_VC);
                    $vep = $this->unit->enthalpy($dimaResult->DIMA_VEP);
                    $tfp = $this->unit->prodTemperature($dimaResult->DIMA_TFP);

                    if ($this->equip->getCapability($capabilitie, 256)) {
                        $consumption = $dimaResult->CONSUM / $lfcoef;
                        $idCoolingFamily = $row->ID_COOLING_FAMILY;

                        $valueStr = $this->unit->consumption($consumption, $idCoolingFamily, 1);

                        $calculationStatus = $this->dima->getCalculationStatus($dimaResult->DIMA_STATUS);

                        $consumptionCell = $this->dima->consumptionCell($lfcoef, $calculationStatus, $valueStr);
                        $conso = $consumptionCell["value"];
                        $conso_warning = $consumptionCell["warning"];

                    } else {
                        $conso = "****";
                        $conso_warning = "";
                    }

                    if ($this->equip->getCapability($capabilitie, 32)) {
                        $dhp = $this->unit->productFlow($dimaResult->HOURLYOUTPUTMAX);

                        $batch = $row->BATCH_PROCESS;
                        if ($batch) {
                            $massConvert = $this->unit->mass($dimaResult->USERATE);
                            $massSymbol = $this->unit->massSymbol();
                            $toc = $massConvert . " " . $massSymbol . "/batch";
                        } else {
                            $toc = $this->unit->toc($dimaResult->USERATE) . "%";
                        }
                    } else {
                        $dhp = $toc = "****";
                    }

                    if ($dimaResult->DIMA_PRECIS < 50.0) {
                        $precision = $this->unit->precision($dimaResult->DIMA_PRECIS);
                    } else {
                        $precision = "!!!!";
                    }
                }
            }

            $item["calculWarning"] = $ldWarning;
            $item["tr"] = $tr;
            $item["ts"] = $ts;
            $item["vc"] = $vc;
            $item["vep"] = $vep;
            $item["tfp"] = $tfp;
            $item["dhp"] = $dhp;
            $item["conso"] = $conso;
            $item["conso_warning"] = $conso_warning;
            $item["toc"] = $toc;
            $item["precision"] = $precision;

            $result[] = $item;
        }

        return $result;
    }

    public function getEstimationHeadBalance($idStudy, $trSelect)
    {
        // $idStudy = $this->request->input('idStudy');
        $idUser = $this->auth->user()->ID_USER;

        $study = Study::find($idStudy);

        $calculationMode = $study->CALCULATION_MODE;

        //get study equipment
        $studyEquipments = StudyEquipment::where("ID_STUDY", $idStudy)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();

        $lfcoef = $this->unit->unitConvert($this->value->MASS_PER_UNIT, 1.0);

        $result = array();

        if (count($studyEquipments) > 0) {
            foreach ($studyEquipments as $row) {
                $capabilitie = $row->CAPABILITIES;
                $equipStatus = $row->EQUIP_STATUS;
                $brainType = $row->BRAIN_TYPE;
                $idCoolingFamily = $row->ID_COOLING_FAMILY;
                $calculWarning = "";
                $item["id"] = $idStudyEquipment = $row->ID_STUDY_EQUIPMENTS;
                $item["specificSize"] = $this->equip->getSpecificEquipSize($idStudyEquipment);
                $item["equipName"] = $this->equip->getResultsEquipName($idStudyEquipment);

                $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $toc = $tocMax = $consoMax = $precision = "";

                $studEqpPrm = StudEqpPrm::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("VALUE_TYPE", 300)->first();
                if (!empty($studEqpPrm)) {
                    $lfTr = $studEqpPrm->VALUE;

                    if ($trSelect == 2) {
                        $lfTr += -10.0;
                    } else if ($trSelect == 0) {
                        $lfTr += 10.0;
                    }

                    $itemTr = $row->ITEM_TR;
                    $minMax = MinMax::where("LIMIT_ITEM", $itemTr)->first();
                    if (!($this->equip->getCapability($capabilitie, 16)) || !($this->equip->getCapability($capabilitie, 1)) && (($trSelect == 0) || ($trSelect == 2))) {
                        $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $toc = $tocMax = $consoMax = $precision = "---";
                    } else if ($lfTr < $minMax->LIMIT_MIN || $lfTr > $minMax->LIMIT_MAX) {
                        $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $toc = $tocMax = $consoMax = $precision = "****";
                    } else if ($equipStatus != 0 && $equipStatus != 1 && $equipStatus != 100000) {
                        $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $toc = $tocMax = $consoMax = $precision = "****";
                    } else if ($equipStatus == 100000) {
                        $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $toc = $tocMax = $consoMax = $precision = "";
                    } else {
                        $dimaResults = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->orderBy("SETPOINT", "DESC")->get();
                        if (count($dimaResults) > 0) {
                            $dimaR = $dimaResults[$trSelect];

                            if (!empty($dimaR)) {
                                $tr = $this->unit->controlTemperature($dimaR->SETPOINT);
                                $ts = $this->unit->time($dimaR->DIMA_TS);

                                if ($brainType != 0 && $trSelect == 1) {
                                    $tfp = $this->unit->prodTemperature($row->AVERAGE_PRODUCT_TEMP);
                                    $vep = $this->unit->enthalpy($row->AVERAGE_PRODUCT_ENTHALPY);

                                    if ($row->PRECIS < 50.0) {
                                        $precision = $this->unit->precision($row->PRECIS);
                                    } else {
                                        $precision = "!!!!";
                                    }
                                } else {
                                    $vep = $this->unit->enthalpy($dimaR->DIMA_VEP);
                                    $tfp = $this->unit->prodTemperature($dimaR->DIMA_TFP);
                                    $precision = "&nbsp;";
                                }

                                if ($this->equip->getCapability($capabilitie, 256)) {
                                    if ($lfcoef != 0.0) {
                                        if ($this->dima->isConsoToDisplay($dimaR->DIMA_STATUS) == 0) {
                                            $conso = "****";
                                        } else {
                                            $conso = $this->unit->consumption($dimaR->CONSUM, $idCoolingFamily, 1);
                                        }
                                        $consoMax = $this->unit->consumption($dimaR->CONSUMMAX / $lfcoef, $idCoolingFamily, 1);
                                    } else {
                                        $conso = $consoMax = "****";
                                    }

                                }

                                if ($this->equip->getCapability($capabilitie, 32)) {
                                    $batch = $row->BATCH_PROCESS;
                                    if ($this->dima->isConsoToDisplay($dimaR->DIMA_STATUS) == 0) {
                                        $toc = "****";
                                    }
                                    if ($batch) {
                                        $toc = $this->unit->mass($dimaR->USERATE) . " " . $this->unit->massSymbol() . "/batch";
                                    } else {
                                        $toc = $this->unit->toc($dimaR->USERATE) . "%";
                                    }

                                    if ($batch) {
                                        $tocMax = $this->unit->mass($dimaR->USERATEMAX) . " " . $this->unit->massSymbol() . "/batch";
                                    } else {
                                        $tocMax = $this->unit->toc($dimaR->USERATEMAX) . "%";
                                    }

                                    $dhp = $this->unit->productFlow($dimaR->HOURLYOUTPUTMAX);
                                } else {
                                    $toc = $tocMax = $dhp = "---";
                                }
                            } else {
                                $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $toc = $tocMax = $consoMax = $precision = "****";
                            }
                        }
                    }
                }
                

                $item["tr"] = $tr;
                $item["ts"] = $ts;
                $item["vc"] = $vc;
                $item["vep"] = $vep;
                $item["tfp"] = $tfp;
                $item["dhp"] = $dhp;
                $item["conso"] = $conso;
                $item["toc"] = $toc;
                $item["consoMax"] = $consoMax;
                $item["tocMax"] = $tocMax;
                $item["precision"] = $precision;

                $result[] = $item;
            }
        }
        


        return $result;
    }

    public function getAnalyticalConsumption($idStudy)
    {
        $study = Study::find($idStudy);

        $lfcoef = $this->unit->unitConvert($this->value->MASS_PER_UNIT, 1.0);

        $calculationMode = $study->CALCULATION_MODE;

        $studyEquipments = StudyEquipment::where("ID_STUDY", $idStudy)->where("BRAIN_TYPE", ">=", 0)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();

        $result = array();

        foreach ($studyEquipments as $row) {
            $capabilitie = $row->CAPABILITIES;
            $equipStatus = $row->EQUIP_STATUS;
            $brainType = $row->BRAIN_TYPE;
            $idCoolingFamily = $row->ID_COOLING_FAMILY;
            $idStudyEquipment = $row->ID_STUDY_EQUIPMENTS;

            if ($this->equip->getCapability($capabilitie, 256)) {
                $equipName = $this->equip->getSpecificEquipName($idStudyEquipment);
                $economicResult = EconomicResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->first();

                if ($calculationMode == 1) {
                    $studEqpPrm = StudEqpPrm::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("VALUE_TYPE", 300)->first();
                    $lfSetpoint = 0.0;
                    if (!empty($studEqpPrm)) {
                        $lfSetpoint = $studEqpPrm->VALUE;
                    }

                    $dimaR = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("SETPOINT", $lfSetpoint)->first();
                } else {
                    $dimaR = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("DIMA_TYPE", 1)->first();
                }

                if ($economicResult) {
                    if ($dimaR) {
                        $dimaStatus = $this->dima->getCalculationStatus($dimaR->DIMA_STATUS);
                        $equipStatus = $row->EQUIP_STATUS;
                    } else {
                        $dimaStatus = 1;
                        $equipStatus = 0;
                    }

                    $consoToDisplay = $this->eco->isConsoToDisplay($dimaStatus, $equipStatus);
                    if (!$consoToDisplay) {
                        $tc = "****";
                        $kgProduct = "****";
                        $product = "****";
                        $day = "****";
                        $week = "****";
                        $hour = "****";
                        $month = "****";
                        $year = "****";
                        $eqptPerm = "****";
                        $eqptCold = "****";
                        $lineCold = "****";
                        $linePerm = "****";
                        $tank = "****";
                        $percentProduct = 0;
                        $percentEquipmentPerm = 0;
                        $percentEquipmentDown = 0;
                        $percentLine = 0;
                    } else {
                        $tc = $this->unit->consumption($economicResult->FLUID_CONSUMPTION_TOTAL / $lfcoef, $idCoolingFamily, 1);
                        if ($lfcoef != 0.0) {
                            $kgProduct = $this->unit->consumption($economicResult->FLUID_CONSUMPTION_PER_KG / $lfcoef, $idCoolingFamily, 1);
                            $product = $this->unit->consumption($economicResult->FLUID_CONSUMPTION_PRODUCT / $lfcoef, $idCoolingFamily, 1);
                        } else {
                            $kgProduct = $product = "****";
                        }

                        $day = $this->unit->consumption($economicResult->FLUID_CONSUMPTION_DAY, $idCoolingFamily, 1, 0);
                        $week = $this->unit->consumption($economicResult->FLUID_CONSUMPTION_WEEK, $idCoolingFamily, 1, 0);
                        $hour = $this->unit->consumption($economicResult->FLUID_CONSUMPTION_HOUR, $idCoolingFamily, 1);
                        $month = $this->unit->consumption($economicResult->FLUID_CONSUMPTION_MONTH, $idCoolingFamily, 1, 0);
                        
                        $year = $this->unit->consumption($economicResult->FLUID_CONSUMPTION_YEAR, $idCoolingFamily, 1, 0);
                        $eqptCold = $this->unit->consumption($economicResult->FLUID_CONSUMPTION_MAT_GETCOLD, $idCoolingFamily, 3);
                        $eqptPerm = $this->unit->consumption($economicResult->FLUID_CONSUMPTION_MAT_PERM, $idCoolingFamily, 2);
                        $lineCold = $this->unit->consumption($economicResult->FLUID_CONSUMPTION_LINE_GETCOLD, $idCoolingFamily, 3);
                        $linePerm = $this->unit->consumption($economicResult->FLUID_CONSUMPTION_LINE_PERM, $idCoolingFamily, 2);
                        $tank = $this->unit->consumption($economicResult->FLUID_CONSUMPTION_TANK, $idCoolingFamily, 1);

                        $percentProduct = round($economicResult->PERCENT_PRODUCT * 100);
                        $percentEquipmentPerm = round($economicResult->PERCENT_EQUIPMENT_PERM * 100);
                        $percentEquipmentDown = round($economicResult->PERCENT_EQUIPMENT_DOWN * 100);
                        $percentLine = round($economicResult->PERCENT_LINE * 100);
                    }

                    $item["id"] = $idStudyEquipment;
                    $item["equipName"] = $equipName;

                    $item["tc"] = $tc;
                    $item["kgProduct"] = $kgProduct;
                    $item["product"] = $product;
                    $item["day"] = $day;
                    $item["week"] = $week;
                    $item["hour"] = $hour;
                    $item["month"] = $month;
                    $item["year"] = $year;
                    $item["eqptPerm"] = $eqptPerm;
                    $item["eqptCold"] = $eqptCold;
                    $item["lineCold"] = $lineCold;
                    $item["linePerm"] = $linePerm;
                    $item["tank"] = $tank;
                    $item["percentProduct"] = $percentProduct;
                    $item["percentEquipmentPerm"] = $percentEquipmentPerm;
                    $item["percentEquipmentDown"] = $percentEquipmentDown;
                    $item["percentLine"] = $percentLine;
                    $item['ENABLE_CONS_PIE'] = $row->ENABLE_CONS_PIE;

                    $result[] = $item;
                }
            }

        }
        return $result;
    }

    public function drawConsumptionPie($idStudyEquipment)
    {
        $input = $this->request->all();
        $studyEquipment = StudyEquipment::findOrFail($idStudyEquipment);
        $study = $studyEquipment->study;
        $idStudy = $study->ID_STUDY;

        $percentProduct = $input['percentProduct'];
        $percentEquipmentPerm = $input['percentEquipmentPerm'];
        $percentEquipmentDown = $input['percentEquipmentDown'];
        $percentLine = $input['percentLine'];
        $percentProductLabel = $input['percentProductLabel'];
        $percentEquipmentPermLabel = $input['percentEquipmentPermLabel'];
        $percentEquipmentDownLabel = $input['percentEquipmentDownLabel'];
        $percentLineLabel = $input['percentLineLabel'];

        $f = fopen("/tmp/consumptionPie.inp", "w");
        fputs($f, 'name percent' . "\n");
        fputs($f, '"'. $percentProductLabel .'" '. $percentProduct .'' . "\n");
        fputs($f, '"'. $percentEquipmentPermLabel .'" '. $percentEquipmentPerm .'' . "\n");
        fputs($f, '"'. $percentEquipmentDownLabel .'" '. $percentEquipmentDown .'' . "\n");
        if ($percentLine > 0) {
            fputs($f, '"'. $percentLineLabel .'" '. $percentLine .'' . "\n");
        }
        
        fclose($f);

        $folder = $this->output->public_path('consumption');

        $userName = $study->USERNAM;
        if (!is_dir($folder)) {
            mkdir($folder, 0777);
        }

        if (!is_dir($folder . '/' . $userName)) {
            mkdir($folder . '/' . $userName, 0777);
        }

        if (!is_dir($folder . '/' . $userName . '/' . $idStudy)) {
            mkdir($folder . '/' . $userName . '/' . $idStudy, 0777);
        }

        $outPutFolder = $folder . '/' . $userName . '/' . $idStudy;
        $outPutFileName = $idStudyEquipment;
        
        system('gnuplot -c '. $this->plotFolder .'/consumptions.plot "/tmp/consumptionPie.inp" "'. $outPutFolder . '" "'. $outPutFileName .'" ');

        $image = getenv('APP_URL') . 'consumption/' . $userName . '/' . $idStudy . '/' . $idStudyEquipment . '.png?time=' . time();

        return $image;
    }

    public function getAnalyticalEconomic($idStudy)
    {
        $study = Study::find($idStudy);

        $lfcoef = $this->unit->unitConvert($this->value->MASS_PER_UNIT, 1.0);

        $calculationMode = $study->CALCULATION_MODE;

        $studyEquipments = StudyEquipment::where("ID_STUDY", $idStudy)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();

        $result = array();

        foreach ($studyEquipments as $row) {
            $capabilitie = $row->CAPABILITIES;
            $equipStatus = $row->EQUIP_STATUS;
            $brainType = $row->BRAIN_TYPE;
            $idCoolingFamily = $row->ID_COOLING_FAMILY;
            $idStudyEquipment = $row->ID_STUDY_EQUIPMENTS;

            if ($this->equip->getCapability($capabilitie, 256)) {
                $equipName = $this->equip->getSpecificEquipName($idStudyEquipment);
                $economicResult = EconomicResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->first();

                $studEqpPrm = StudEqpPrm::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("VALUE_TYPE", 300)->first();
                $lfSetpoint = 0.0;
                if (!empty($studEqpPrm)) {
                    $lfSetpoint = $studEqpPrm->VALUE;
                }

                $dimaR = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("SETPOINT", $lfSetpoint)->first();

                if ($economicResult != null) {
                    if ($dimaR != null) {
                        $dimaStatus = $this->dima->getCalculationStatus($dimaR->DIMA_STATUS);
                        $equipStatus = $row->EQUIP_STATUS;
                    } else {
                        $dimaStatus = 1;
                        $equipStatus = 0;
                    }

                    $consoToDisplay = $this->eco->isConsoToDisplay($dimaStatus, $equipStatus);
                    if (!$consoToDisplay) {
                        $tc = "****";
                        $kgProduct = "****";
                        $product = "****";
                        $day = "****";
                        $week = "****";
                        $hour = "****";
                        $month = "****";
                        $year = "****";
                        $eqptPerm = "****";
                        $eqptCold = "****";
                        $lineCold = "****";
                        $linePerm = "****";
                        $tank = "****";
                    } else {
                        $tc = $this->unit->monetary($economicResult->COST_TOTAL);
                        if ($lfcoef != 0.0) {
                            $kgProduct = $this->unit->monetary($economicResult->COST_KG / $lfcoef);
                            $product = $this->unit->monetary($economicResult->COST_PRODUCT / $lfcoef);
                        } else {
                            $kgProduct = $product = "****";
                        }

                        $day = $this->unit->monetary($economicResult->COST_DAY, 0);
                        $week = $this->unit->monetary($economicResult->COST_WEEK, 0);
                        $hour = $this->unit->monetary($economicResult->COST_HOUR);
                        $month = $this->unit->monetary($economicResult->COST_MONTH, 0);
                        $year = $this->unit->monetary($economicResult->COST_YEAR, 0);
                        $eqptCold = $this->unit->monetary($economicResult->COST_MAT_GETCOLD);
                        $eqptPerm = $this->unit->monetary($economicResult->COST_MAT_PERM);
                        $lineCold = $this->unit->monetary($economicResult->COST_LINE_GETCOLD);
                        $linePerm = $this->unit->monetary($economicResult->COST_LINE_PERM);
                        $tank = $this->unit->monetary($economicResult->COST_TANK);
                    }

                    $item["id"] = $idStudyEquipment;
                    $item["equipName"] = $equipName;
                    $item["tc"] = $tc;
                    $item["kgProduct"] = $kgProduct;
                    $item["product"] = $product;
                    $item["day"] = $day;
                    $item["week"] = $week;
                    $item["hour"] = $hour;
                    $item["month"] = $month;
                    $item["year"] = $year;
                    $item["eqptPerm"] = $eqptPerm;
                    $item["eqptCold"] = $eqptCold;
                    $item["lineCold"] = $lineCold;
                    $item["linePerm"] = $linePerm;
                    $item["tank"] = $tank;

                    $result[] = $item;
                }
            }


        }

        return $result;
    }
    public function getProInfoStudy($idStudy)
    {
        $production = Production::select("PROD_FLOW_RATE", "AVG_T_INITIAL")->where("ID_STUDY", $idStudy)->first();
        $product = Product::select("PROD_REALWEIGHT")->where("ID_STUDY", $idStudy)->first();

        $prodFlowRate = $this->unit->productFlow($production->PROD_FLOW_RATE);
        $avgTInitial = $this->unit->prodTemperature($production->AVG_T_INITIAL);
        $prodElmtRealweight = $this->unit->mass($product->PROD_REALWEIGHT);

        return compact("prodFlowRate", "prodElmtRealweight", "avgTInitial");
    }

    public function heatExchange($nbSample, $idStudy, $idStudyEquipment) 
    {
        // $idStudyEquipment = StudyEquipment::where('ID_STUDY', $idStudy)->get();
        $listRecordPos = RecordPosition::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->orderBy("RECORD_TIME", "ASC")->get();
        $curve = array();
        $result = array();
        foreach ($listRecordPos as $row) {

            $item["x"] = $this->unit->time($row->RECORD_TIME);
            $item["y"] = $this->unit->enthalpy($row->AVERAGE_ENTH_VAR);

            $curve[] = $item;
        }

        $nbRecord = count($listRecordPos);
        $equipName = $this->equip->getResultsEquipName($idStudyEquipment);

        $lfTS = $listRecordPos[$nbRecord - 1]->RECORD_TIME;
        $lfStep = $listRecordPos[1]->RECORD_TIME - $listRecordPos[0]->RECORD_TIME;
        $lEchantillon = $this->output->calculateEchantillon($nbSample, $nbRecord, $lfTS, $lfStep);
        foreach ($lEchantillon as $row) {

            $recordPos = $listRecordPos[$row];

            $itemResult["x"] = $this->unit->time($recordPos->RECORD_TIME);
            $itemResult["y"] = $this->unit->enthalpy($recordPos->AVERAGE_ENTH_VAR);

            $result[] = $itemResult;
        }

        $f = fopen("/tmp/heatExchange.inp", "w");
        fputs($f, '"X" "Y"' . "\n");
        foreach ($curve as $row) {
            fputs($f, (double) $row['x'] . ' ' . (double) $row['y'] . "\n");
        }
        fclose($f);

        $study = Study::find($idStudy);
        $userName = $study->USERNAM;
        $heatExchangeFolder = $this->output->public_path('heatExchange');

        if (!is_dir($heatExchangeFolder)) {
            mkdir($heatExchangeFolder, 0777);
        }
        if (!is_dir($heatExchangeFolder . '/' . $userName)) {
            mkdir($heatExchangeFolder . '/' . $userName, 0777);
        }

        system('gnuplot -c '. $this->plotFolder . '/heatExchange.plot "('. $this->unit->timeSymbol() .')" "('. $this->unit->enthalpySymbol() .')" "'. $heatExchangeFolder . '/' . $userName .'" '. $idStudyEquipment .' "Enthapy" "/tmp/heatExchange.inp"');

        return compact("result", "equipName", "idStudyEquipment");
    }

    public function productSection($nbSample, $axeTempRecordData, $idStudy, $idStudyEquipment, $selectedAxe)
    {
        $productElmt = ProductElmt::where('ID_STUDY', $idStudy)->first();
        $shape = $productElmt->SHAPECODE;
        $layoutGen = LayoutGeneration::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->first();
        $orientation = $layoutGen->PROD_POSITION;

        $study = Study::find($idStudy);
        $userName = $study->USERNAM;
        $productSectionFolder = $this->output->public_path('productSection');
        $fileName = $idStudyEquipment . '-' . $selectedAxe;

        if (!is_dir($productSectionFolder)) {
            mkdir($productSectionFolder, 0777);
        }

        if (!is_dir($productSectionFolder . '/' . $userName)) {
            mkdir($productSectionFolder . '/' . $userName, 0777);
        }

        $equipName = $this->equip->getResultsEquipName($idStudyEquipment);
        $timeSymbol = $this->unit->timeSymbol();
        $temperatureSymbol = $this->unit->temperatureSymbol();
        $prodchartDimensionSymbol = $this->unit->prodchartDimensionSymbol();
        
        $resultLabel = [];
        $resultTemperature = [];

        $axeTemp = [];
        switch ($selectedAxe) {
            case 1:
                if ($shape <= 9) {
                    array_push($axeTemp, $this->unit->prodchartDimension($axeTempRecordData[0][1]));
                    array_push($axeTemp, $this->unit->prodchartDimension($axeTempRecordData[0][2]));
                } else {
                    array_push($axeTemp, $axeTempRecordData[0][1]);
                    array_push($axeTemp, $axeTempRecordData[0][2]);
                }
                break;

            case 2:
                if ($shape <= 9) {
                    array_push($axeTemp, $this->unit->prodchartDimension($axeTempRecordData[1][0]));
                    array_push($axeTemp, $this->unit->prodchartDimension($axeTempRecordData[1][2]));
                } else {
                    array_push($axeTemp, $axeTempRecordData[1][0]);
                    array_push($axeTemp, $axeTempRecordData[1][2]);
                }
                break;

            case 3:
                if ($shape <= 9) {
                    array_push($axeTemp, $this->unit->prodchartDimension($axeTempRecordData[2][0]));
                    array_push($axeTemp, $this->unit->prodchartDimension($axeTempRecordData[2][1]));
                } else {
                    array_push($axeTemp, $axeTempRecordData[2][0]);
                    array_push($axeTemp, $axeTempRecordData[2][1]);
                }
                break;
        }

        if ($shape < 10) {
            $listRecordPos = RecordPosition::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->orderBy("RECORD_TIME", "ASC")->get();

            $nbRecord = count($listRecordPos);

            $lfTS = $listRecordPos[$nbRecord - 1]->RECORD_TIME;
            $lfStep = $listRecordPos[1]->RECORD_TIME - $listRecordPos[0]->RECORD_TIME;
            $lEchantillon = $this->output->calculateEchantillon($nbSample, $nbRecord, $lfTS, $lfStep);
            $dataChart = [];

            foreach ($lEchantillon as $row) {
                $recordPos = $listRecordPos[$row];

                $itemResult["x"] = $this->unit->time($recordPos->RECORD_TIME);
                $tempRecordData = $this->output->getTempRecordData($recordPos->ID_REC_POS, $idStudy, $axeTempRecordData, $selectedAxe - 1, $shape, $orientation);

                $item = [];
                $recAxis = [];
                $mesAxis = [];
                $itemDataChart = [];
                
                if (count($tempRecordData) > 0) {
                    foreach ($tempRecordData as $row) {
                        $item[] = $this->unit->prodTemperature($row->TEMP);

                        switch ($selectedAxe) {
                            case 1:
                                if (($shape == 1) || ($shape == 2 && $orientation == 1) && ($shape == 9 && $orientation == 1)) {
                                    $recAxisValue = $row->REC_AXIS_Z_POS;
                                } else if ($shape == 3 || $shape == 5 || $shape == 7) {
                                    $recAxisValue = $row->REC_AXIS_Y_POS;
                                } else {
                                    $recAxisValue = $row->REC_AXIS_X_POS;
                                }

                                switch ($shape) {
                                    case 1:
                                    case 6:
                                        $meshAxisValue = "";
                                        break;

                                    case 2:
                                    case 9:
                                        if ($orientation == 1) {
                                            $meshAxisValue = $this->output->getAxisForPosition2($idStudy, $row->REC_AXIS_Z_POS, $selectedAxe);
                                        } else {
                                            $meshAxisValue = $this->output->getAxisForPosition2($idStudy, $row->REC_AXIS_X_POS, $selectedAxe);
                                        }
                                        break;

                                    case 3:
                                    case 5:
                                    case 7:
                                        $meshAxisValue = $this->output->getAxisForPosition2($idStudy, $row->REC_AXIS_Y_POS, $selectedAxe);
                                        break;

                                    case 4:
                                    case 8:
                                        $meshAxisValue = $this->output->getAxisForPosition2($idStudy, $row->REC_AXIS_X_POS, $selectedAxe);
                                        break;
                                }

                                break;

                            case 2:
                                if ($shape == 1 || $shape == 2 || $shape == 9 || $shape == 4 || $shape == 8 || $shape == 6) {
                                    $recAxisValue = $row->REC_AXIS_Y_POS;
                                } else {
                                    $recAxisValue = $row->REC_AXIS_X_POS;
                                }

                                switch ($shape) {
                                    case 1:
                                    case 2:
                                    case 4:
                                    case 6:
                                    case 8:
                                    case 9:
                                        $meshAxisValue = $this->output->getAxisForPosition2($idStudy, $row->REC_AXIS_Y_POS, $selectedAxe);
                                        break;

                                    case 3:
                                    case 5:
                                    case 7:
                                        $meshAxisValue = $this->output->getAxisForPosition2($idStudy, $row->REC_AXIS_X_POS, $selectedAxe);
                                        break;

                                }

                                break;

                            case 3:
                                if (($shape == 3) || ($shape == 2 && $orientation == 1) && ($shape == 9)) {
                                    $recAxisValue = $row->REC_AXIS_X_POS;
                                } else {
                                    $recAxisValue = $row->REC_AXIS_Z_POS;
                                }

                                if (($orientation == 0) && ($shape == 2 || $shape == 9)) {
                                    $meshAxisValue = $this->output->getAxisForPosition2($idStudy, $row->REC_AXIS_Z_POS, $selectedAxe);
                                } else if ($shape == 3) {
                                    $meshAxisValue = $this->output->getAxisForPosition2($idStudy, $row->REC_AXIS_Z_POS, $selectedAxe);
                                } else {
                                    $meshAxisValue = $this->output->getAxisForPosition2($idStudy, $row->REC_AXIS_X_POS, $selectedAxe);
                                }


                                break;
                        }
                        
                        $recAxis[] = $recAxisValue;
                        $mesAxis[] = $meshAxisValue;
                        $meshPoints = MeshPosition::select('MESH_AXIS_POS')->where('ID_STUDY', $idStudy)->where('MESH_AXIS', $selectedAxe)->orderBy('MESH_AXIS_POS')->first();
                        $itemDataChart[] = [
                            "x" => $this->unit->prodTemperature($row->TEMP),
                            "y" => $meshAxisValue
                        ];
                    }
                }

                $dataChart[] = $itemDataChart;

                $resultLabel[] = $itemResult["x"];
                $resultTemperature[] = $item;
            }

            $resultValue = [];

            foreach ($resultTemperature as $row) {
                foreach ($row as $key => $value) {
                    $resultValue[$key][] = $value;
                }
            }
            
            $f = fopen("/tmp/productSection.inp", "w");

            $dataLabel = '';
            fputs($f, '"X" ');
            foreach ($resultLabel as $row) {
                $dataLabel .= '"Temperature T' . $row . '(' . $this->unit->timeSymbol() . ')' . '"' . ' ';
            } 

            fputs($f, $dataLabel);
            fputs($f, "\n");

            $i = 0;
            foreach ($resultValue as $key => $row) {
                $dataValue = '';
                $dataValue = $mesAxis[$key] . ' ';
                foreach ($row as $value) {
                    $dataValue .= $value . ' ';
                }
                fputs($f, $dataValue);
                fputs($f, "\n");
                $i++;
            }
            fclose($f);
            $inpFile = "/tmp/productSection.inp";
        } else {
            $prodFolder = 'Prod_' . $study->ID_PROD;
            $stdeqpFolder = 'Equipment' . $idStudyEquipment;
            switch ($selectedAxe) {
                case 1:
                    $inpFileName = 'productSection_X.inp';
                    break;

                case 2:
                    $inpFileName = 'productSection_Y.inp';
                    break;

                case 3:
                    $inpFileName = 'productSection_Z.inp';
                    break;
            }

            // $inpFile = $this->plotFolder3D . '/MeshBuilder3D/' . $prodFolder . '/' . $stdeqpFolder . '/' . $inpFileName;
            
            $data = file_get_contents($this->plotFolder3D . '/MeshBuilder3D/' . $prodFolder . '/' . $stdeqpFolder . '/' . $inpFileName);

            $dataArr = explode("\n", $data);
            $labelArr = preg_replace("/\s+/u", " ", $dataArr[0]);
            $labelArr = explode(' ', trim($labelArr));
            foreach ($labelArr as $key => $label) {
                if (!preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $labelArr[$key])) {
                    unset($labelArr[$key]);
                }
            }

            $labelArr = array_values($labelArr);
            $labelArr = array_map(
                function($str) {
                    return str_replace('(s)"', '', $str);
                },
                $labelArr
            );

            $nbRecord = count($labelArr);
            $lfTS = $labelArr[$nbRecord - 1];
            $lfStep = $labelArr[1] - $labelArr[0];
            $lEchantillon = $this->output->calculateEchantillon($nbSample, $nbRecord, $lfTS, $lfStep);
            foreach ($lEchantillon as $row) {
                $resultLabel[] = $labelArr[$row];
            }
            // $resultLabel = $labelArr;
            
            unset($dataArr[0]);
            $listRecordPos = $dataArr;
            $listRecordPos = array_values($listRecordPos);
            $listRecordPos = array_filter($listRecordPos);
            $dataChart = [];
            $recAxis = [];
            $mesAxis = [];           
            $resultValue = [];
            foreach ($listRecordPos as $key => $value) {
                if (isset($listRecordPos[$key])) {
                    $recordPos = trim($listRecordPos[$key]);
                    $recordPos = preg_replace("/\s+/u", " ", $recordPos);
                    $recordPos = explode(' ', $recordPos);
                    $recordPos = array_filter($recordPos);

                    $mesAxis[] = $recordPos[0];
                    unset($recordPos[0]);
                    $recordPosValue = $recordPos;
                    $recordPosValue = array_values($recordPosValue);
                    foreach ($lEchantillon as $row) {
                        if (isset($recordPosValue[$row])) {
                            $resultValue[$key][] = $recordPosValue[$row];
                        }
                    }
                    // $resultValue[$key] = $recordPosValue;
                }
            }

            $f = fopen("/tmp/productSection.inp", "w");

            $dataLabel = '';
            fputs($f, '"X" ');
            foreach ($resultLabel as $row) {
                $dataLabel .= '"Temperature T' . $row . '(' . $this->unit->timeSymbol() . ')' . '"' . ' ';
            } 

            fputs($f, $dataLabel);
            fputs($f, "\n");

            $i = 0;
            foreach ($resultValue as $key => $row) {
                $dataValue = '';
                $dataValue = $mesAxis[$key] . ' ';
                foreach ($row as $value) {
                    $dataValue .= $value . ' ';
                }
                fputs($f, $dataValue);
                fputs($f, "\n");
                $i++;
            }
            fclose($f);
            $inpFile = "/tmp/productSection.inp";
        }
        
        system('gnuplot -c '. $this->plotFolder .'/productSection.plot "('. $this->unit->temperatureSymbol() .')" "('. $this->unit->prodchartDimensionSymbol() .')" "'. $productSectionFolder . '/' . $userName .'" "'. $fileName .'" '. $inpFile .'');

        $result["recAxis"] = $recAxis;
        $result["mesAxis"] = $mesAxis;
        $result["resultValue"] = $resultValue;

        return compact("equipName", "axeTemp", "dataChart", "resultLabel",
         "result", "selectedAxe", "timeSymbol", "temperatureSymbol", "prodchartDimensionSymbol",
         "idStudyEquipment");
    }

    public function timeBased($nbSample, $axisTemp, $idStudy, $idStudyEquipment)
    {
        $study = Study::find($idStudy);
        $userName = $study->USERNAM;
        $productElmt = ProductElmt::where('ID_STUDY', $idStudy)->first();
        $shape = $productElmt->SHAPECODE;
        $equipName = $this->equip->getResultsEquipName($idStudyEquipment);
        $timeSymbol = $this->unit->timeSymbol();
        $temperatureSymbol = $this->unit->temperatureSymbol();
        $timeBasedFolder = $this->output->public_path('timeBased');
        if (!is_dir($timeBasedFolder)) {
            mkdir($timeBasedFolder, 0777);
        }
        if (!is_dir($timeBasedFolder . '/' . $userName)) {
            mkdir($timeBasedFolder . '/' . $userName, 0777);
        }

        if ($shape < 10) {
            $listRecordPos = RecordPosition::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->orderBy('RECORD_TIME')->get();
            $result = array();
            $label = array();
            $curve = array();
            $axisValue = $this->output->getRightPosition($idStudy, $idStudyEquipment);

            if (count($listRecordPos) > 0) {
                foreach ($listRecordPos as $row) {
                    $termRecordDataTop = $this->output->getTemperaturePosition($row->ID_REC_POS, (int) $axisValue['axis1TopPos'], (int) $axisValue['axis2TopPos']);
                    $termRecordDataInt = $this->output->getTemperaturePosition($row->ID_REC_POS, (int) $axisValue['axis1IntPos'], (int) $axisValue['axis2IntPos']);
                    $termRecordDataBot = $this->output->getTemperaturePosition($row->ID_REC_POS, (int) $axisValue['axis1BotPos'], (int) $axisValue['axis2BotPos']);

                    $itemCurveTop["x"] = $this->unit->time($row->RECORD_TIME);
                    $itemCurveTop["y"] = $this->unit->prodTemperature($termRecordDataTop->TEMP);

                    $itemCurveInt["x"] = $this->unit->time($row->RECORD_TIME);
                    $itemCurveInt["y"] = $this->unit->prodTemperature($termRecordDataInt->TEMP);

                    $itemCurveBotom["x"] = $this->unit->time($row->RECORD_TIME);
                    $itemCurveBotom["y"] = $this->unit->prodTemperature($termRecordDataBot->TEMP);

                    $itemCurveAverage["x"] = $this->unit->time($row->RECORD_TIME);
                    $itemCurveAverage["y"] = $this->unit->prodTemperature($row->AVERAGE_TEMP);

                    $curve["top"][] = $itemCurveTop;
                    $curve["int"][] = $itemCurveInt;
                    $curve["bot"][] = $itemCurveBotom;
                    $curve["average"][] = $itemCurveAverage;
                }
                
                $nbRecord = count($listRecordPos);

                $lfTS = $listRecordPos[$nbRecord - 1]->RECORD_TIME;
                $lfStep = $listRecordPos[1]->RECORD_TIME - $listRecordPos[0]->RECORD_TIME;

                $lEchantillon = $this->output->calculateEchantillon($nbSample, $nbRecord, $lfTS, $lfStep);
                $item = [];
                foreach ($lEchantillon as $row) {
                    $recordPos = $listRecordPos[$row];
                    $item["points"] = $this->unit->time($recordPos->RECORD_TIME);

                    //top
                    $termRecordDataTop = $this->output->getTemperaturePosition($recordPos->ID_REC_POS, (int) $axisValue['axis1TopPos'], (int) $axisValue['axis2TopPos']);
                    $item["top"] =  $this->unit->prodTemperature($termRecordDataTop->TEMP);
                    
                    //int
                    $termRecordDataInt = $this->output->getTemperaturePosition($recordPos->ID_REC_POS, (int) $axisValue['axis1IntPos'], (int) $axisValue['axis2IntPos']);
                    $item["int"] = $this->unit->prodTemperature($termRecordDataInt->TEMP);

                    //bot
                    $termRecordDataBot = $this->output->getTemperaturePosition($recordPos->ID_REC_POS, (int) $axisValue['axis1BotPos'], (int) $axisValue['axis2BotPos']);
                    $item["bot"] = $this->unit->prodTemperature($termRecordDataBot->TEMP);

                    $item["average"] = $this->unit->prodTemperature($recordPos->AVERAGE_TEMP);
                    $result[] = $item; 
                }
                
                $label["top"] = $this->unit->meshesUnit($axisTemp['POINT1_X']) . "," . $this->unit->meshesUnit($axisTemp['POINT1_Y']) . "," . $this->unit->meshesUnit($axisTemp['POINT1_Z']);

                $label["int"] = $this->unit->meshesUnit($axisTemp['POINT2_X']) . "," . $this->unit->meshesUnit($axisTemp['POINT2_Y']) . "," . $this->unit->meshesUnit($axisTemp['POINT2_Z']);

                $label["bot"] = $this->unit->meshesUnit($axisTemp['POINT3_X']) . "," . $this->unit->meshesUnit($axisTemp['POINT3_Y']) . "," . $this->unit->meshesUnit($axisTemp['POINT3_Z']);
            }
            
            $inpFile = '/tmp/timeBased.inp';
            $f = fopen("/tmp/timeBased.inp", "w");

            $dataLabel = '';
            fputs($f, '"X" ');
            fputs($f, '"Top('. $label['top'] .')" ');
            fputs($f, '"Internal('. $label['int'] .')" ');
            fputs($f, '"Bottom('. $label['bot'] .')" ');
            fputs($f, '"Average temperature"'. "\n");

            foreach ($curve['top'] as $key => $row) {
                fputs($f, (double) $row['x'] . ' ' . (double) $row['y'] . ' ' . (double) $curve['int'][$key]['y'] . ' ' . (double) $curve['bot'][$key]['y'] . ' ' . (double) $curve['average'][$key]['y'] . "\n");
            } 
            fclose($f);
            
        } else {
            $prodFolder = 'Prod_' . $study->ID_PROD;
            $stdeqpFolder = 'Equipment' . $idStudyEquipment;
            $inpFile = $this->plotFolder3D . '/MeshBuilder3D/' . $prodFolder . '/' . $stdeqpFolder . '/timeBase.inp';
            $data = file_get_contents($this->plotFolder3D . '/MeshBuilder3D/' . $prodFolder . '/' . $stdeqpFolder . '/timeBase.inp');
            $dataArr = explode("\n", $data);
            $labelArr = explode(' ', trim($dataArr[0]));
            //get value in text within parenthesis
            preg_match('#\((.*?)\)#', $labelArr[1], $topLabelMatch);
            preg_match('#\((.*?)\)#', $labelArr[2], $intLabelMatch);
            preg_match('#\((.*?)\)#', $labelArr[3], $botLabelMatch);
            $label = [
                "top" => $topLabelMatch[1], 
                "int" => $intLabelMatch[1], 
                "bot" => $botLabelMatch[1]
            ];

            unset($dataArr[0]);
            $listRecordPos = $dataArr;
            $listRecordPos = array_values($listRecordPos);
            $listRecordPos = array_filter($listRecordPos);

            $nbRecord = count($listRecordPos);

            $recordPosLast = trim($listRecordPos[$nbRecord - 1]);
            $recordPosLast = preg_replace("/\s+/u", " ", $recordPosLast);
            $recordPosLast = explode(' ', $recordPosLast);
            $recordPosLast = array_filter($recordPosLast);
            $lfTS = $recordPosLast[0];

            $recordPosFirst = trim($listRecordPos[0]);
            $recordPosFirst = preg_replace("/\s+/u", " ", $recordPosFirst);
            $recordPosFirst = explode(' ', $recordPosFirst);
            $recordPosFirst = array_filter($recordPosFirst);

            $recordPosSecond = trim($listRecordPos[1]);
            $recordPosSecond = preg_replace("/\s+/u", " ", $recordPosSecond);
            $recordPosSecond = explode(' ', $recordPosSecond);
            $recordPosSecond = array_filter($recordPosSecond);

            $lfStep = $recordPosSecond[0] - $recordPosFirst[0];
            $lEchantillon = $this->output->calculateEchantillon($nbSample, $nbRecord, $lfTS, $lfStep);
            
            $dataRecord = [];
            foreach ($lEchantillon as $row) {
                $recordPos = trim($listRecordPos[$row]);
                $recordPos = preg_replace("/\s+/u", " ", $recordPos);
                $recordPos = explode(' ', $recordPos);
                $recordPos = array_filter($recordPos);
                
                $i = 0;
                $item = [];
                foreach ($recordPos as $record) {
                    $item["points"] = $recordPos[0];
                    $item["top"] =  $recordPos[1];
                    $item["int"] =  $recordPos[2];
                    $item["bot"] =  $recordPos[3];
                    $item["average"] =  $recordPos[4];
                    $i++;
                }

                $result[] = $item;
            }

            $curve = [];
        }
        
        system('gnuplot -c '. $this->plotFolder .'/timeBased.plot "('. $this->unit->timeSymbol() .')" "('. $this->unit->temperatureSymbol() .')" "'. $timeBasedFolder . '/' . $userName .'" "'. $idStudyEquipment .'" '. $inpFile .'');

        return compact("label", "result", "timeSymbol", "temperatureSymbol", "equipName", "idStudyEquipment");
    }

    public function getSymbol($idStudy)
    {
        $productFlowSymbol = $this->unit->productFlowSymbol();
        $massSymbol = $this->unit->massSymbol();
        $temperatureSymbol = $this->unit->temperatureSymbol();
        $timeSymbol = $this->unit->timeSymbol();
        $perUnitOfMassSymbol = $this->unit->perUnitOfMassSymbol();
        $enthalpySymbol = $this->unit->enthalpySymbol();
        $monetarySymbol = $this->unit->monetarySymbol();
        $equipDimensionSymbol = $this->unit->equipDimensionSymbol();
        $convectionSpeedSymbol = $this->unit->convectionSpeedSymbol();
        $convectionCoeffSymbol = $this->unit->convectionCoeffSymbol();
        $timePositionSymbol = $this->unit->timePositionSymbol();
        $prodchartDimensionSymbol = $this->unit->prodchartDimensionSymbol();
        $prodDimensionSymbol = $this->unit->prodDimensionSymbol();
        $meshesSymbol = $this->unit->meshesSymbol();
        $packingThicknessSymbol = $this->unit->packingThicknessSymbol();
        $shelvesWidthSymbol = $this->unit->shelvesWidthSymbol();
        $percentSymbol = "%";
        $consumSymbol = $this->unit->consumptionSymbol($this->equip->initEnergyDef($idStudy), 1);
        $consumMaintienSymbol = $this->unit->consumptionSymbol($this->equip->initEnergyDef($idStudy), 2);
        $mefSymbol = $this->unit->consumptionSymbol($this->equip->initEnergyDef($idStudy), 3);
        $pressureSymbol = $this->unit->pressureSymbol();
        $materialRiseSymbol = $this->unit->materialRiseSymbol();
        $lineDimensionSymbol = $this->unit->lineDimensionSymbol();
        $ret = compact("productFlowSymbol", "massSymbol", "temperatureSymbol", 
        "percentSymbol", "timeSymbol", "perUnitOfMassSymbol", "enthalpySymbol", 
        "monetarySymbol", "equipDimensionSymbol", "convectionSpeedSymbol", "convectionCoeffSymbol", 
        "timePositionSymbol", "prodDimensionSymbol", "meshesSymbol", "packingThicknessSymbol", 
        "shelvesWidthSymbol", "prodchartDimensionSymbol", "consumSymbol", 
        "consumMaintienSymbol", "mefSymbol", "pressureSymbol", "materialRiseSymbol", "lineDimensionSymbol");

        return $ret;
    }

    public function productchart2D($idStudy, $idStudyEquipment, $selectedPlan)
    {
        $dimension = 'Dimenstions';
        $productElmt = ProductElmt::where('ID_STUDY', $idStudy)->first();
        $shape = $productElmt->SHAPECODE;
        $layoutGen = LayoutGeneration::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->first();
        $orientation = $layoutGen->PROD_POSITION;
        $equipName = $this->equip->getResultsEquipName($idStudyEquipment);
        $axisName = $this->output->getAxisName($shape, $orientation, $selectedPlan);
        $heatmapFolder = $this->output->public_path('heatmap');
        $study = Study::find($idStudy);
        $userName = $study->USERNAM;
        if (!is_dir($heatmapFolder)) {
            mkdir($heatmapFolder, 0777);
        }
        
        if (!is_dir($heatmapFolder . '/' . $userName)) {
            mkdir($heatmapFolder . '/' . $userName, 0777);
        }

        if (!is_dir($heatmapFolder . '/' . $userName . '/' . $idStudyEquipment)) {
            mkdir($heatmapFolder . '/' . $userName . '/' . $idStudyEquipment, 0777);
        }

        if (!is_dir($heatmapFolder . '/' . $userName . '/' . $idStudyEquipment)) {
            mkdir($heatmapFolder . '/' . $userName . '/' . $idStudyEquipment, 0777);
        }
        //get minMax
        $minMax = [
            'minTempStep' => 0,
            'maxTempStep' => -1,
            'minTemperature' => 0,
            'maxTemperature' => -1,
        ];

        $mmStep = MinMax::where('LIMIT_ITEM', $this->value->MINMAX_REPORT_TEMP_STEP)->first();
        $mmBounds = MinMax::where('LIMIT_ITEM', $this->value->MINMAX_REPORT_TEMP_BOUNDS)->first();

        if (!empty($mmStep) && !empty($mmBounds)) {
            $minMax = [
                'minTempStep' => (int) $mmStep->LIMIT_MIN,
                'maxTempStep' => (int) $mmStep->LIMIT_MAX,
                'minTemperature' => (int) $mmBounds->LIMIT_MIN,
                'maxTemperature' => (int) $mmBounds->LIMIT_MAX,
            ];
        }

        // get TimeInterval
        $recordPosition = RecordPosition::select('RECORD_TIME')->where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->orderBy("RECORD_TIME", "ASC")->get();
        $lfDwellingTime = $recordPosition[count($recordPosition) - 1]->RECORD_TIME;

        $calculationParameter = CalculationParameter::select('STORAGE_STEP', 'TIME_STEP')->where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->first();
        $lfStep = $calculationParameter->STORAGE_STEP * $calculationParameter->TIME_STEP;
        if (count($recordPosition) < 10) {
            $lftimeInterval = $lfStep;
        } else {
            $lftimeInterval = $lfDwellingTime / 9.0;
            $lftimeInterval = round($lftimeInterval / $lfStep) * $lfStep;
        }

        $lftimeInterval = $this->unit->none(round($lftimeInterval * 100.0) / 100.0);

        $selPoints = $this->output->getSelectedMeshPoints($idStudy);
        if (empty($selPoints)) {
            $selPoints = $this->output->getMeshSelectionDef();
        }

        $axeTempRecordData = [];
        $planTempRecordData = [];
        if (!empty($selPoints)) {
            $axeTempRecordData = [
                [-1.0, $selPoints[9], $selPoints[10]],
                [$selPoints[11], -1.0, $selPoints[12]],
                [$selPoints[13], $selPoints[14], -1.0]
            ];
            $planTempRecordData = [
                [$selPoints[15], 0.0, 0.0],
                [0.0, $selPoints[16], 0.0],
                [0.0, 0.0, $selPoints[17]]
            ];
        }

        $valueRecAxis = [];
        if (!empty($planTempRecordData)) {
            $valueRecAxis = [
                "x" => $this->unit->prodchartDimension($planTempRecordData[0][0]),
                "y" => $this->unit->prodchartDimension($planTempRecordData[1][1]),
                "z" => $this->unit->prodchartDimension($planTempRecordData[2][2])
            ];
        }

        //contour data
        $pasTemp = -1.0;
        $tempInterval = [0.0, 0.0];
        if ($shape < 10) {
            $chartTempInterval = $this->output->init2DContourTempInterval($idStudyEquipment, $lfDwellingTime, $tempInterval, $pasTemp);
            
            if (!file_exists($heatmapFolder . '/' . $userName . '/' . $idStudyEquipment . '/' . $contourFileName . '.png')) { 
                $dataContour = $this->output->getGrideByPlan($idStudy, $idStudyEquipment, $lfDwellingTime, $chartTempInterval[0], $chartTempInterval[1], $planTempRecordData, $selectedPlan - 1, $shape, $orientation);
                $plotFile = 'contour.plot';
                $inpFile = "/tmp/contour.inp";
                $f = fopen("/tmp/contour.inp", "w");

                foreach ($dataContour as $datum) {
                    fputs($f, (double) $datum['X'] . ' ' . (double) $datum['Y'] . ' ' .  (double) $datum['Z'] . "\n" );
                }
                fclose($f);
            }

            $contourFileName = $lfDwellingTime . '-' . $chartTempInterval[0] . '-' . $chartTempInterval[1] . '-' . $chartTempInterval[2];
        } else {
            $prodFolder = 'Prod_' . $study->ID_PROD;
            $stdeqpFolder = 'Equipment' . $idStudyEquipment;
            
            $lastRecordTime = (int) $recordPosition[count($recordPosition) - 1]->RECORD_TIME;
            switch ($selectedPlan) {
                case 1:
                    $inpFileName  = 'contour_X_' . $lastRecordTime . '.inp';
                    $timeStepFileName = 'contourParam_X.inp';
                    break;
                
                case 2:
                    $inpFileName  = 'contour_Y_' . $lastRecordTime . '.inp';
                    $timeStepFileName = 'contourParam_Y.inp';
                    break;

                case 3:
                    $inpFileName  = 'contour_Z_' . $lastRecordTime . '.inp';
                    $timeStepFileName = 'contourParam_Z.inp';
                    break;
            }

            $inpFile = $this->plotFolder3D . '/MeshBuilder3D/' . $prodFolder . '/' . $stdeqpFolder . '/' . $inpFileName;

            $dataTimeStepArr = explode("\n", $dataTimeStep);
            $dataTimeStepArr = array_filter($dataTimeStepArr);
            $lastTimeStep = $dataTimeStepArr[count($dataTimeStepArr) - 1];
            $lastTimeStepArr = explode(';', $lastTimeStep);
            $lastTimeStepArrMin = explode('=', $lastTimeStepArr[1]);
            $lastTimeStepArrMax = explode('=', $lastTimeStepArr[2]);
            $lastTimeStepArrStep = explode('=', $lastTimeStepArr[3]);
            $chartTempInterval = [(int) $lastTimeStepArrMin[1], (int) $lastTimeStepArrMax[1], (int) $lastTimeStepArrStep[1]];

            switch ($shape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case CYLINDER_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case SPHERE_3D:
                    $plotFile = 'contourSphere.plot';
                    break;

                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                    $plotFile = 'contourOval.plot';
                    break;

                default:
                    $plotFile = 'contour3Dshape.plot';
                    break;
            }

            $contourFileName = $lfDwellingTime . '-' . $chartTempInterval[0] . '-' . $chartTempInterval[1] . '-' . $chartTempInterval[2] . '-' . $selectedPlan;
        }
        
        system('gnuplot -c '. $this->plotFolder .'/' . $plotFile . ' "'. $dimension .' '. $axisName[0] .'" "'. $dimension .' '. $axisName[1] .'" "'. $this->unit->prodchartDimensionSymbol() .'" '. $chartTempInterval[0] .' '. $chartTempInterval[1] .' '. $chartTempInterval[2] .' "'. $heatmapFolder . '/' . $userName . '/' . $idStudyEquipment .'" "'. $contourFileName .'" '. $inpFile .'');
        $imageContour[] = 'http://'.$_SERVER['HTTP_HOST'] . '/heatmap/' . $userName . '/' . $idStudyEquipment . '/' . $contourFileName . '.png';

        return compact("chartTempInterval", "lfDwellingTime", "lftimeInterval", "equipName", "idStudyEquipment");
        
    }

    public function productChart2DStatic($idStudy, $idStudyEquipment, $selectedPlan, $pasTemp, $temperatureMin, $temperatureMax)
    {
        set_time_limit(1000);
        $equipName = $this->equip->getResultsEquipName($idStudyEquipment);

        // get TimeInterva
        $recordPosition = RecordPosition::select('RECORD_TIME')->where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->orderBy("RECORD_TIME", "ASC")->get();
        $lfDwellingTime = $recordPosition[count($recordPosition) - 1]->RECORD_TIME;
        $dimension = 'Dimenstions';

        $heatmapFolder = $this->output->public_path('heatmap');
        $study = Study::find($idStudy);
        $userName = $study->USERNAM;
        if (!is_dir($heatmapFolder)) {
            mkdir($heatmapFolder, 0777);
        }

        if (!is_dir($heatmapFolder . '/' . $userName)) {
            mkdir($heatmapFolder . '/' . $userName, 0777);
        }

        if (!is_dir($heatmapFolder . '/' . $userName . '/' . $idStudyEquipment)) {
            mkdir($heatmapFolder . '/' . $userName . '/' . $idStudyEquipment, 0777);
        }

        $productElmt = ProductElmt::where('ID_STUDY', $idStudy)->first();
        $shape = $productElmt->SHAPECODE;
        $layoutGen = LayoutGeneration::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->first();
        $orientation = $layoutGen->PROD_POSITION;

        $calculationParameter = CalculationParameter::select('STORAGE_STEP', 'TIME_STEP')->where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->first();

        $lfStep = $calculationParameter->STORAGE_STEP * $calculationParameter->TIME_STEP;
        if (count($recordPosition) < 10) {
            $lftimeInterval = $lfStep;

        } else {
            $lftimeInterval = $lfDwellingTime / 9.0;
            $lftimeInterval = round($lftimeInterval / $lfStep) * $lfStep;
        }

        $lftimeInterval = $this->unit->none(round($lftimeInterval * 100.0) / 100.0);

        $selPoints = $this->output->getSelectedMeshPoints($idStudy);
        if (empty($selPoints)) {
            $selPoints = $this->output->getMeshSelectionDef();
        }

        $axeTempRecordData = [];
        $planTempRecordData = [];
        if (!empty($selPoints)) {
            $axeTempRecordData = [
                [-1.0, $selPoints[9], $selPoints[10]],
                [$selPoints[11], -1.0, $selPoints[12]],
                [$selPoints[13], $selPoints[14], -1.0]
            ];
            $planTempRecordData = [
                [$selPoints[15], 0.0, 0.0],
                [0.0, $selPoints[16], 0.0],
                [0.0, 0.0, $selPoints[17]]
            ];
        }

        $axisName = $this->output->getAxisName($shape, $orientation, $selectedPlan);

        if ($shape < 10) {
            //contour data
            $tempInterval = [$temperatureMin, $temperatureMax];

            $chartTempInterval = $this->output->init2DContourTempInterval($idStudyEquipment, $lfDwellingTime, $tempInterval, $pasTemp);
            
            $dataContour = $this->output->getGrideByPlan($idStudy, $idStudyEquipment, $lfDwellingTime, $chartTempInterval[0], $chartTempInterval[1], $planTempRecordData, $selectedPlan - 1, $shape, $orientation);
            $plotFile = 'contour.plot';
            $inpFile = "/tmp/contour.inp";
            $f = fopen("/tmp/contour.inp", "w");
            foreach ($dataContour as $datum) {
                fputs($f, (double) $datum['X'] . ' ' . (double) $datum['Y'] . ' ' .  (double) $datum['Z'] . "\n" );
            }
            fclose($f);

            $contourFileName = $lfDwellingTime . '-' . $chartTempInterval[0] . '-' . $chartTempInterval[1] . '-' . $chartTempInterval[2];
        } else {
            $prodFolder = 'Prod_' . $study->ID_PROD;
            $stdeqpFolder = 'Equipment' . $idStudyEquipment;
            
            $lastRecordTime = (int) $recordPosition[count($recordPosition) - 1]->RECORD_TIME;
            switch ($selectedPlan) {
                case 1:
                    $inpFileName  = 'contour_X_' . $lastRecordTime . '.inp';
                    $timeStepFileName = 'contourParam_X.inp';
                    break;
                
                case 2:
                    $inpFileName  = 'contour_Y_' . $lastRecordTime . '.inp';
                    $timeStepFileName = 'contourParam_Y.inp';
                    break;

                case 3:
                    $inpFileName  = 'contour_Z_' . $lastRecordTime . '.inp';
                    $timeStepFileName = 'contourParam_Z.inp';
                    break;
            }

            $inpFile = $this->plotFolder3D . '/MeshBuilder3D/' . $prodFolder . '/' . $stdeqpFolder . '/' . $inpFileName;
               
            $data = file_get_contents($this->plotFolder3D . '/MeshBuilder3D/' . $prodFolder . '/' . $stdeqpFolder . '/' . $inpFileName);
            $dataFiles = explode("\n", $data);
            $dataFiles = array_filter($dataFiles);

            $dataTimeStep = file_get_contents($this->plotFolder3D . '/MeshBuilder3D/' . $prodFolder . '/' . $stdeqpFolder . '/' . $timeStepFileName);

            $dataTimeStepArr = explode("\n", $dataTimeStep);
            $dataTimeStepArr = array_filter($dataTimeStepArr);
            foreach ($dataTimeStepArr as $dataTime) {
                $dataTimeArr = explode(';', $dataTime);
                $dataTimeArrElmFirst = explode('=', $dataTimeArr[0]);
                if (trim($dataTimeArrElmFirst[1]) == $lfDwellingTime) {
                    $dataTimeArrMin = explode('=', $dataTimeArr[1]);
                    $dataTimeArrMax = explode('=', $dataTimeArr[2]);
                    $dataTimeArrStep = explode('=', $dataTimeArr[3]);
                    $chartTempInterval = [(int) $dataTimeArrMin[1], (int) $dataTimeArrMax[1], (int) $dataTimeArrStep[1]];
                }
            }

            switch ($shape) {
                case CYLINDER_STANDING_3D:
                case CYLINDER_LAYING_3D:
                case CYLINDER_CONCENTRIC_STANDING_3D:
                case CYLINDER_CONCENTRIC_LAYING_3D:
                case SPHERE_3D:
                    $plotFile = 'contourSphere.plot';
                    break;

                case OVAL_STANDING_3D:
                case OVAL_LAYING_3D:
                    $plotFile = 'contourOval.plot';
                    break;

                default:
                    $plotFile = 'contour3Dshape.plot';
                    break;
            }

            $contourFileName = $lfDwellingTime . '-' . $chartTempInterval[0] . '-' . $chartTempInterval[1] . '-' . $chartTempInterval[2] . '-' . $selectedPlan;
        }

        system('gnuplot -c '. $this->plotFolder .'/' . $plotFile . ' "'. $dimension .' '. $axisName[0] .'" "'. $dimension .' '. $axisName[1] .'" "'. $this->unit->prodchartDimensionSymbol() .'" '. $chartTempInterval[0] .' '. $chartTempInterval[1] .' '. $chartTempInterval[2] .' "'. $heatmapFolder . '/' . $userName . '/' . $idStudyEquipment .'" "'. $contourFileName .'" '. $inpFile .'');
        
        $dataFile = getenv('APP_URL') . '/heatmap/' . $userName . '/' . $idStudyEquipment . '/data.json';
        $imageContour[] = getenv('APP_URL') . '/heatmap/' . $userName . '/' . $idStudyEquipment . '/' . $contourFileName . '.png';

        return compact("chartTempInterval", "lfDwellingTime", "lftimeInterval", "equipName", "idStudyEquipment", "selectedPlan");
    }
    
    public function getStudyPackingLayers($id)
    {
        $packing = \App\Models\Packing::where('ID_STUDY', $id)->first();
        $packingLayers = null;

        if ($packing != null) {
            $packingLayers = \App\Models\PackingLayer::where('ID_PACKING', $packing->ID_PACKING)->get();
            
                for ($i = 0; $i < count($packingLayers); $i++) { 
                    $value = $this->unit->unitConvert(16, $packingLayers[$i]->THICKNESS);
                    $packingLayers[$i]->THICKNESS = $value;
                }
                
                $packingLayerData = [];
                $count = 0;
                foreach ($packingLayers as $key => $pk) {
                    $pkrelease[] = $pk->packingElmt->PACKING_RELEASE;
                    $version = $pk->packingElmt->PACKING_VERSION;
                    $name = \App\Models\PackingLayer::select('LABEL')->join('TRANSLATION', 'ID_PACKING_ELMT', '=', 'TRANSLATION.ID_TRANSLATION')
                    ->where('ID_PACKING_LAYER', $pk['ID_PACKING_LAYER'])
                    ->where('TRANS_TYPE', 3)->where('ID_TRANSLATION', $pk['ID_PACKING_ELMT'])
                    ->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->orderBy('LABEL', 'DESC')->first();
                    $status = Translation::select('LABEL')->where('TRANS_TYPE', 100)->whereIn('ID_TRANSLATION', $pkrelease)
                    ->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->orderBy('LABEL', 'ASC')->first();
                    $label = $name->LABEL . "-" . $version  . "(". $status->LABEL .")";
                    $packingLayers[$key]['LABEL'] = $label;
                    
                    $count++;
                } 
                
                foreach ($packingLayers as $pk) {
                    $packingLayerData[$pk['PACKING_SIDE_NUMBER']][] = $pk;
                }
            return compact('packing', 'packingLayerData', 'count');
        }
    }

    public function sizingOptimumResult($idStudy)
    {
        $study = Study::find($idStudy);
        $calculationMode = $study->CALCULATION_MODE;

        //get study equipment
        $studyEquipments = StudyEquipment::where("ID_STUDY", $idStudy)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();
        $production = Production::where("ID_STUDY", $idStudy)->first();

        $productFlowRate = (double) $production->PROD_FLOW_RATE;

        $lfcoef = $this->unit->unitConvert($this->value->MASS_PER_UNIT, 1.0);
        $result = array();
        $selectedEquipment =  array();
        $availableEquipment = array(); 
        $dataGrapChart = array();

        //get result
        foreach ($studyEquipments as $row) {
            $capabilitie = $row->CAPABILITIES;
            $equipStatus = $row->EQUIP_STATUS;
            $brainType = $row->BRAIN_TYPE;
            $idCoolingFamily = $row->ID_COOLING_FAMILY;
            $item["id"] = $idStudyEquipment = $row->ID_STUDY_EQUIPMENTS;
            $item["equipName"] = $equipName = $this->equip->getSpecificEquipName($idStudyEquipment);

            $tr = $ts = $vc = $dhp = $conso = $conso_warning = $toc = $trMax = $tsMax = $vcMax = $dhpMax = $consoMax = $consomax_warning = $tocMax = "";

            if (!($this->equip->getCapability($capabilitie , 128))){
                $tr = $ts = $vc = $dhp = $conso = $conso_warning = $toc = $trMax = $tsMax = $vcMax = $dhpMax = $consoMax = $consomax_warning = $tocMax = "";
            } else if ($equipStatus == 100000) {
                $tr = $ts = $vc = $dhp = $conso = $conso_warning = $toc = $trMax = $tsMax = $vcMax = $dhpMax = $consoMax = $consomax_warning = $tocMax = "";
            } else {
                if (($equipStatus != 0) && ($equipStatus != 1) && ($equipStatus != 100000)) {
                    $tr = $ts = $vc = $dhp = $conso = $toc = "****";
                } else {
                    $dimaResult = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("DIMA_TYPE", 1)->first();
                    if ($dimaResult == null) {
                        $tr = $ts = $vc = $dhp = $conso = $toc = "";
                    } else {  
                        $tr = $this->unit->controlTemperature($dimaResult->SETPOINT);
                        $ts = $this->unit->timeUnit($dimaResult->DIMA_TS);
                        $vc = $this->unit->convectionSpeed($dimaResult->DIMA_VC);

                        if ($this->equip->getCapability($capabilitie, 128)) {
                            $consumption = $dimaResult->CONSUM / $lfcoef;
                            $valueStr = $this->unit->consumption($consumption, $idCoolingFamily, 1);
                            $calculationStatus = $this->dima->getCalculationStatus($dimaResult->DIMA_STATUS);
                            $consumptionCell = $this->dima->consumptionCell($lfcoef, $calculationStatus, $valueStr);
                            $conso = $consumptionCell["value"];
                            $conso_warning = $consumptionCell["warning"];
                        } else {
                            $conso = "****";
                            $conso_warning = "";
                        }

                        if ($this->equip->getCapability($capabilitie, 32)) {
                            $dhp = $this->unit->productFlow($dimaResult->HOURLYOUTPUTMAX);

                            $batch = $row->BATCH_PROCESS;
                            if ($batch) {
                                $toc = $this->unit->mass($dimaResult->USERATE) . " " . $this->unit->massSymbol() . "/batch"; 
                            } else {
                                $toc = $this->unit->toc($dimaResult->USERATE) . " %";
                            }
                        } else {
                            $toc = $dhp = "****";
                        }   
                    }

                    // max result
                    $dimaResultMax = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("DIMA_TYPE", 16)->first();
                    if ($dimaResultMax == null) {
                        $trMax = $tsMax = $vcMax = $dhpMax = $consoMax = $tocMax = "";
                    } else {
                        $ldError = 0;
                        $ldError = $this->dima->getCalculationWarning($dimaResultMax->DIMA_STATUS);
                        if (($ldError == 282) || ($ldError == 283) || ($ldError == 284) || ($ldError == 285) || ($ldError == 286)) {
                            $ldError = 0;
                        }

                        if( $ldError != 0) {
                            $trMax = $tsMax = $vcMax = $dhpMax = $consoMax = $tocMax = "****";
                        } else {
                            $trMax = $this->unit->controlTemperature($dimaResultMax->SETPOINT);
                            $tsMax = $this->unit->timeUnit($dimaResultMax->DIMA_TS);
                            $vcMax = $this->unit->convectionSpeed($dimaResultMax->DIMA_VC);

                            if ($this->equip->getCapability($capabilitie, 128)) {
                                $consumption = $dimaResultMax->CONSUM / $lfcoef;
                                $valueStr = $this->unit->consumption($consumption, $idCoolingFamily, 1);
                                $calculationStatus = $this->dima->getCalculationStatus($dimaResultMax->DIMA_STATUS);
                                $consumptionCellMax = $this->dima->consumptionCell($lfcoef, $calculationStatus, $valueStr);
                                $consoMax = $consumptionCellMax["value"];
                                $consomax_warning = $consumptionCellMax["warning"];
                            } else {
                                $consoMax = "****";
                                $consomax_warning = "";
                            }

                            if ($this->equip->getCapability($capabilitie, 32)) {
                                $dhpMax = $this->unit->productFlow($dimaResultMax->HOURLYOUTPUTMAX);

                                $batch = $row->BATCH_PROCESS;
                                if ($batch) {
                                    $tocMax = $this->unit->mass($dimaResultMax->USERATE) . " " . $this->unit->massSymbol() . "/batch"; 
                                } else {
                                    $tocMax = $this->unit->toc($dimaResultMax->USERATE) . " %";
                                }
                            } else {
                                $tocMax = $dhpMax = "****";
                            }
                        }
                    }
                }
            }
            
            $item["tr"] = $tr;
            $item["ts"] = $ts;
            $item["vc"] = $vc;
            $item["dhp"] = $dhp;
            $item["conso"] = $conso;
            $item["conso_warning"] = $conso_warning;
            $item["toc"] = $toc;
            $item["trMax"] = $trMax;
            $item["tsMax"] = $tsMax;
            $item["vcMax"] = $vcMax;
            $item["dhpMax"] = $dhpMax;
            $item["consoMax"] = $consoMax;
            $item["consomax_warning"] = $consomax_warning;
            $item["tocMax"] = $tocMax;

            $result[] = $item;
        }

        //get grap data
        $i = 0;
        foreach ($studyEquipments as $row) {
            $capabilitie = $row->CAPABILITIES;
            $equipStatus = $row->EQUIP_STATUS;
            $brainType = $row->BRAIN_TYPE;
            $idCoolingFamily = $row->ID_COOLING_FAMILY;
            $itemGrap["id"] = $idStudyEquipment = $row->ID_STUDY_EQUIPMENTS;
            $itemGrap["equipName"] = $equipName = $this->equip->getSpecificEquipName($idStudyEquipment);

            $dhp = $conso = $dhpMax = $consoMax = "";

            if (($this->equip->getCapability($capabilitie , 128) && ($brainType != 0) && ($equipStatus == 1))) {
                $dimaResult = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("DIMA_TYPE", 1)->first();
                if ($dimaResult != null) {
                    if ($this->equip->getCapability($capabilitie , 256)){
                        if ($this->dima->isConsoToDisplay($dimaResult->DIMA_STATUS)) {
                            if ($lfcoef != 0.0) {
                                $conso = $this->unit->consumption($dimaResult->CONSUM / $lfcoef, $idCoolingFamily, 1);
                            } else {
                                $conso = $this->unit->consumption(0.0, $idCoolingFamily, 1);
                            }
                        } 
                    }

                    if ($this->equip->getCapability($capabilitie , 32)) {
                        $dhp = $this->unit->productFlow($dimaResult->HOURLYOUTPUTMAX);
                    }
                } 

                $dimaResultMax = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("DIMA_TYPE", 16)->first();
                if ($dimaResultMax != null) {
                    if ($this->equip->getCapability($capabilitie , 256)){
                        if ($this->dima->isConsoToDisplay($dimaResultMax->DIMA_STATUS)) {
                            if ($lfcoef != 0.0) {
                                $consoMax = $this->unit->consumption($dimaResultMax->CONSUM / $lfcoef, $idCoolingFamily, 1);
                            } else {
                                $consoMax = $this->unit->consumption(0.0, $idCoolingFamily, 1);;
                            }
                        } 
                    }

                    if ($this->equip->getCapability($capabilitie , 32)) {
                        $dhpMax = $this->unit->productFlow($dimaResultMax->HOURLYOUTPUTMAX);
                    } else {
                        $dhpMax = $this->unit->consumption(0.0, $idCoolingFamily, 1);;
                    }
                } else {
                    $dhpMax = $consoMax = $this->unit->consumption(0.0, $idCoolingFamily, 1);
                }

                if ($dimaResult != null || $dimaResultMax != null) {
                    $itemGrap["dhp"] = (double) $dhp;
                    $itemGrap["conso"] = (double) $conso;
                    $itemGrap["dhpMax"] = (double) $dhpMax;
                    $itemGrap["consoMax"] = (double) $consoMax;

                    if ($i < 4) {
                        $selectedEquipment[] = $itemGrap;
                    } else {
                        $availableEquipment[] = $itemGrap;
                    }
                }

                $i++;
            }
        }

        $imageSizing = '';

        if (!empty($selectedEquipment)) {
            $f = fopen("/tmp/sizing.inp", "w");
            fputs($f, '"Equip Name" "Product flowrate" "Maximum product flowrate" "Cryogen consumption (product + equipment heat losses)" "Maximum cryogen consumption (product + equipment heat losses)"' . "\n");
            foreach ($selectedEquipment as $row) {
                $itemChart["id"] = $row["id"];
                $itemChart["equipName"] = $row["equipName"];
                $dhpChart = $row["dhp"];
                $consoChart = $row["conso"];
                $dhpMaxChart = $row["dhpMax"];
                $consoMaxChart = $row["consoMax"];

                if (($dhpChart == null || $dhpChart == "****") || $dhpChart == "") {
                    $itemChart["dhp"] = 0.0;
                } else {
                    $itemChart["dhp"] = $dhpChart;
                }

                if (($consoChart == null || $consoChart == "****") || $consoChart == "") {
                    $itemChart["conso"] = 0.0;
                } else {
                    $itemChart["conso"] = $consoChart;
                }

                if (($dhpMaxChart == null || $dhpMaxChart == "****") || $dhpMaxChart == "") {
                    $itemChart["dhpMax"] = 0.0;
                } else {
                    $itemChart["dhpMax"] = $dhpMaxChart;
                }

                if (($consoMaxChart == null || $consoMaxChart == "****") || $consoMaxChart == "") {
                    $itemChart["consoMax"] = 0.0;
                } else {
                    $itemChart["consoMax"] = $consoMaxChart;
                }

            
                $dataGrapChart[] =  $itemChart;   

                fputs($f, '"'. trim($itemChart["equipName"]) .'"' . ' '. (double) $itemChart["dhp"] .' '. (double) $itemChart["dhpMax"] .' '. (double) $itemChart["conso"] .' '. (double) $itemChart["consoMax"] . "\n"); 
            }
            fclose($f);

            $sizingFolder = $this->output->public_path('sizing');

            $userName = $study->USERNAM;
            if (!is_dir($sizingFolder)) {
                mkdir($sizingFolder, 0777);
            }

            if (!is_dir($sizingFolder . '/' . $userName)) {
                mkdir($sizingFolder . '/' . $userName, 0777);
            }

            if (!is_dir($sizingFolder . '/' . $userName . '/' . $idStudy)) {
                mkdir($sizingFolder . '/' . $userName . '/' . $idStudy, 0777);
            }
            
            system('gnuplot -c '. $this->plotFolder .'/sizing.plot "Flowrate '. $this->unit->productFlowSymbol() .'" "Conso '. $this->unit->consumptionSymbol($this->equip->initEnergyDef($idStudy), 1) .'/'. $this->unit->perUnitOfMassSymbol() .'" "'. $sizingFolder . '/' . $userName . '/' . $idStudy . '" '. $idStudy .' '. $productFlowRate .' "Custom Flowrate" "/tmp/sizing.inp"');

            $imageSizing = getenv('APP_URL') . '/sizing/' . $userName . '/' . $idStudy . '/' . $idStudy . '.png?time=' . time();
        }

        return compact("result", "selectedEquipment", "availableEquipment", "dataGrapChart", "productFlowRate", "imageSizing");
    }


    public function sizingEstimationResult($idStudy, $trSelect = 1) 
    {
        $study = Study::find($idStudy);
        $production = Production::where("ID_STUDY", $idStudy)->first();
        $productFlowRate = (double) $production->PROD_FLOW_RATE;

        $studyEquipments = StudyEquipment::where("ID_STUDY", $idStudy)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();
        $lfcoef = $this->unit->unitConvert($this->value->MASS_PER_UNIT, 1.0);
        $result = array();
        $dataGraphChart = array();

        //get result
        foreach ($studyEquipments as $row) {
            $capabilitie = $row->CAPABILITIES;
            $equipStatus = $row->EQUIP_STATUS;
            $brainType = $row->BRAIN_TYPE;
            $idCoolingFamily = $row->ID_COOLING_FAMILY;
            $item["id"] = $idStudyEquipment = $row->ID_STUDY_EQUIPMENTS;
            $item["equipName"] = $equipName = $this->equip->getSpecificEquipName($idStudyEquipment);

            $dimaResults = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->orderBy("SETPOINT", "DESC")->get();

            $viewEquip = false;
            $optionTr = "";
            $addEquipment = false;
            $tr = $dhp = $conso = $conso_warning = $toc = $dhpMax = $consoMax = $consomax_warning = $tocMax = "";

            if ($row->NB_TR <= 1 && count($dimaResults) > 0) { 
                $dimaR = $dimaResults[$trSelect];
                if (( (!($this->equip->getCapability($capabilitie, 16))) || (!($this->equip->getCapability($capabilitie, 1))) ) && ($trSelect == 0 || $trSelect == 2)) {
                    $tr = "---";
                    $viewEquip = false;
                    $optionTr = "disabled";
                    $dhp = $conso = $conso_warning = $toc = $dhpMax = $consoMax = $consomax_warning = $tocMax = "---";
                } else {
                    if ($this->equip->isValidTemperature($idStudyEquipment, $trSelect) && $dimaR != null) {
                        $tr = $this->unit->controlTemperature($dimaR->SETPOINT);
                        $viewEquip = true;

                        if ($this->equip->getCapability($capabilitie, 256)) {
                            if ($lfcoef != 0.0) {
                                if ($this->dima->isConsoToDisplay($dimaR->DIMA_STATUS) == 0) {
                                    $conso = "****";
                                } else {
                                    $consumption = $dimaR->CONSUM / $lfcoef;
                                    $conso = $this->unit->consumption($consumption, $idCoolingFamily, 1);
                                }
                                $consumptionMax = $dimaR->CONSUMMAX / $lfcoef;
                                $consoMax = $this->unit->consumption($consumptionMax, $idCoolingFamily, 1);
                            } else {
                                $conso = $consoMax = "****";
                            }
                        } else {
                            $conso = $consoMax = "---";
                        }

                        if ($this->equip->getCapability($capabilitie, 8192)) {
                            $batch = $row->BATCH_PROCESS;
                            $calculationStatus = $this->dima->getCalculationStatus($dimaR->DIMA_STATUS);

                            if ($calculationStatus != 1) {
                                $toc = $dhp = "****";
                            } else {
                                if ($batch) {
                                    $massConvert = $this->unit->mass($dimaR->USERATE);
                                    $massSymbol = $this->unit->massSymbol();
                                    $toc = $massConvert . $massSymbol . "/batch";
                                } else {
                                    $toc = $this->unit->toc($dimaR->USERATE) . "%";
                                }
                                $dhp = $this->unit->productFlow($production->PROD_FLOW_RATE);
                            }

                            if ($batch) {
                                $tocMax = $this->unit->mass($dimaR->USERATEMAX) . $this->unit->massSymbol() . "/batch";
                            } else {
                                $tocMax = $this->unit->toc($dimaR->USERATEMAX) . "%";
                            }

                            $dhpMax = $this->unit->productFlow($dimaR->HOURLYOUTPUTMAX);
                            
                        } else {
                            $toc = $tocMax = "---";
                            $dhp = $dhpMax = "---";
                        }
                    } else {
                        $viewEquip = false;
                        $optionTr = "disabled";
                        $dhp = $conso = $toc = "****";
                        $dhpMax  = $consoMax = $tocMax = "****";
                    }
                }
            } else {
                $viewEquip = false; 
                $optionTr = "disabled";
                $tr = $dhp = $conso = $toc = $dhpMax = $consoMax = $tocMax = "---";
            }

            if ($this->equip->getCapability($capabilitie , 1024)) {
                $addEquipment = true;
            } else {
                $addEquipment = false;
            }

            $item["viewEquip"] = $viewEquip;
            $item["optionTr"] = $optionTr;
            $item["addEquipment"] = $addEquipment;
            $item["tr"] = $tr;
            $item["dhp"] = $dhp;
            $item["conso"] = $conso;
            $item["toc"] = $toc;
            $item["dhpMax"] = $dhpMax;
            $item["consoMax"] = $consoMax;
            $item["tocMax"] = $tocMax;

            $result[] = $item;
        }

        $sizingFolder = $this->output->public_path('sizing');

        $userName = $study->USERNAM;
        if (!is_dir($sizingFolder)) {
            mkdir($sizingFolder, 0777);
        }
        if (!is_dir($sizingFolder . '/' . $userName)) {
            mkdir($sizingFolder . '/' . $userName, 0777);
        }

        if (!is_dir($sizingFolder . '/' . $userName . '/' . $idStudy)) {
            mkdir($sizingFolder . '/' . $userName . '/' . $idStudy, 0777);
        }
        
        foreach ($studyEquipments as $row) {
            $f = '/tmp/sizing.inp';
            file_put_contents($f, '');
            file_put_contents($f, '"Equip Name" "Product flowrate" "Maximum product flowrate" "Cryogen consumption (product + equipment heat losses)" "Maximum cryogen consumption (product + equipment heat losses)"'. "\n" .'');

            $capabilitie = $row->CAPABILITIES;
            $equipStatus = $row->EQUIP_STATUS;
            $brainType = $row->BRAIN_TYPE;
            $idCoolingFamily = $row->ID_COOLING_FAMILY;
            $itemGrap["id"] = $idStudyEquipment = $row->ID_STUDY_EQUIPMENTS;
            $itemGrap["equipName"] = $equipName = $this->equip->getSpecificEquipName($idStudyEquipment);

            $dimaResults = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->orderBy("SETPOINT", "DESC")->get();
            $dhp = $conso = $dhpMax = $consoMax = $chartName = "";

            foreach ($dimaResults as $key => $dimaR) {
                $dhp = $this->unit->productFlow($production->PROD_FLOW_RATE);
                if ($key == 0 || $key == 2) {
                    if ($this->equip->isValidTemperature($idStudyEquipment, $trSelect)) {
                        $dhp = $this->unit->productFlow($production->PROD_FLOW_RATE);
                    } else {
                        $dhp = 0;
                    }
                }

                switch ($key) {
                    case 0:
                       $trName = 'TR-10°C';
                        break;
                    
                    case 1:
                       $trName = 'TR';
                        break;

                    case 2:
                       $trName = 'TR+10°C';
                        break;
                }

                $dhpMax = $this->unit->productFlow($dimaR->HOURLYOUTPUTMAX);

                if ($this->equip->getCapability($capabilitie, 256)) {
                    if ($lfcoef != 0.0) {
                        if ($this->dima->isConsoToDisplay($dimaR->DIMA_STATUS) == 0) {
                            $conso = $this->unit->consumption(0.0, $idCoolingFamily, 1);
                        } else {
                            $consumption = $dimaR->CONSUM / $lfcoef;
                            $conso = $this->unit->consumption($consumption, $idCoolingFamily, 1);
                        }
                        $consumptionMax = $dimaR->CONSUMMAX / $lfcoef;
                        $consoMax = $this->unit->consumption($consumptionMax, $idCoolingFamily, 1);
                    } else {
                        $conso = $this->unit->consumption(0.0, $idCoolingFamily, 1);
                        $consoMax = $this->unit->consumption(0.0, $idCoolingFamily, 1);
                    }
                } else {
                    $conso = $this->unit->consumption(0.0, $idCoolingFamily, 1);
                    $consoMax = $this->unit->consumption(0.0, $idCoolingFamily, 1);
                }

                $itemGrap["data"][$key]["dhp"] = (double) $dhp;
                $itemGrap["data"][$key]["conso"] = (double) $conso;
                $itemGrap["data"][$key]["dhpMax"] = (double) $dhpMax;
                $itemGrap["data"][$key]["consoMax"] = (double) $consoMax; 
                file_put_contents($f, '"'. $trName .'" '. (double) $dhp .' '. (double) $dhpMax .' '. (double) $conso .' '. (double) $consoMax .'' . "\n", FILE_APPEND);  

                $chartName =  $idStudy . '-' . $row->ID_STUDY_EQUIPMENTS;

                system('gnuplot -c '. $this->plotFolder .'/sizing.plot "Flowrate '. $this->unit->productFlowSymbol() .'" "Conso '. $this->unit->consumptionSymbol($this->equip->initEnergyDef($idStudy), 1) .'/'. $this->unit->perUnitOfMassSymbol() .'" "'. $sizingFolder . '/' . $userName . '/' . $idStudy . '" '. $chartName .' '. $productFlowRate .' "Custom Flowrate" "/tmp/sizing.inp"');
                $itemGrap['image'] = $imageSizing = getenv('APP_URL') . '/sizing/' . $userName . '/' . $idStudy . '/' . $chartName . '.png?time=' . time();                
            } 
            
            $dataGraphChart[] =  $itemGrap;
        }

        return compact("result", "dataGraphChart", "productFlowRate");
    }

    public function initTempDataForReport($idStudy)
    {
        $bornesTemp = $this->getReportTemperatureBorne($idStudy);

        $tempInterval[0] = $bornesTemp[0];
        $tempInterval[1] = $bornesTemp[1];

        $bornesTemp[0] = $this->unit->prodTemperature($tempInterval[0], ['format' => false]);
        $bornesTemp[1] = $this->unit->prodTemperature($tempInterval[1], ['format' => false]);

        $res = $this->calculatePasTemp($bornesTemp[0], $bornesTemp[1], true);
        $bornesTemp[0] = $res[0];
        $bornesTemp[1] = $res[1];
        $pasTemp = $res[2];

        $tempInterval[0] = $this->unit->prodTemperature($bornesTemp[0]);
        $tempInterval[1] = $this->unit->prodTemperature($bornesTemp[1]);

        $data = [$tempInterval[0], $tempInterval[1], $pasTemp];

        return $data;
    }

    public function getReportTemperatureBorne($idStudy)
    {
        $tabBorne[0] = 0.0;
        $tabBorne[1] = 0.0;

        $query =  DB::select("SELECT MIN( TRD.TEMP ) AS MIN_TEMP, MAX( TRD.TEMP ) AS MAX_TEMP FROM TEMP_RECORD_DATA AS TRD 
        JOIN (SELECT REC_POS.ID_REC_POS FROM RECORD_POSITION AS REC_POS JOIN (SELECT REC_POS1.ID_STUDY_EQUIPMENTS, 
        MAX(REC_POS1.RECORD_TIME) AS RECORD_TIME FROM RECORD_POSITION AS REC_POS1 
        JOIN STUDY_EQUIPMENTS AS STD_EQP ON REC_POS1.ID_STUDY_EQUIPMENTS = STD_EQP.ID_STUDY_EQUIPMENTS 
        WHERE STD_EQP.ID_STUDY = " . $idStudy . " GROUP BY REC_POS1.ID_STUDY_EQUIPMENTS) AS REC_POS2 
        ON REC_POS.ID_STUDY_EQUIPMENTS = REC_POS2.ID_STUDY_EQUIPMENTS AND REC_POS.RECORD_TIME = REC_POS2.RECORD_TIME) AS REC_POS3 ON TRD.ID_REC_POS = REC_POS3.ID_REC_POS");

        if ($query) {
            $tabBorne[0] = $query[0]->MIN_TEMP;
            $tabBorne[1] = $query[0]->MAX_TEMP;
        }

        return $tabBorne;
    }

    public function initTempDataForReportData($idStudyEquipment)
    {
        $recordPosition = RecordPosition::select('RECORD_TIME')->where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->orderBy("RECORD_TIME", "ASC")->get();
        $recordTime = $this->unit->time($recordPosition[count($recordPosition) - 1]->RECORD_TIME);
        $tempInterval = [0.0, 0.0];

        $result = $this->initReportTempInterval($idStudyEquipment, $recordTime, $tempInterval, $this->pasTemp);

        $data = [$this->unit->prodTemperature($result[0]), $this->unit->prodTemperature($result[1]), $result[2]];

        return $data;
    }

    public function initTempDataForReportDataParam($idStudyEquipment, $temperatureMin, $temperatureMax, $pasTemp)
    {
        $recordPosition = RecordPosition::select('RECORD_TIME')->where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->orderBy("RECORD_TIME", "ASC")->get();
        $recordTime = $this->unit->time($recordPosition[count($recordPosition) - 1]->RECORD_TIME);
        $tempInterval = [$temperatureMin, $temperatureMax];

        $result = $this->initReportTempInterval($idStudyEquipment, $recordTime, $tempInterval, $pasTemp);

        $data = [$this->unit->prodTemperature($result[0]), $this->unit->prodTemperature($result[1]), $result[2]];

        return $data;
    }

    public function initReportTempInterval($idStudyEquipment, $recordTime, $tempInterval, $pasTemp)
    {
        $tempResult = [];
        $result = [];

        $studyEquipment = StudyEquipment::find($idStudyEquipment);
        $productElmt = ProductElmt::where('ID_STUDY', $studyEquipment->ID_STUDY)->first();
        $shape = $productElmt->SHAPECODE;

        if ($shape < 10) {
            if ($recordTime < 0) {
                $tempRecordDataMin = TempRecordData::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->orderBy('TEMP', 'ASC')->first();
                $tempRecordDataMax = TempRecordData::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->orderBy('TEMP', 'DESC')->first();
                $tempResult = [$tempRecordDataMin->TEMP, $tempRecordDataMax->TEMP];
            } else {
                /*$tempRecordDataMin = TempRecordData::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->where('RECORD_TIME', $recordTime)->orderBy('TEMP', 'ASC')->first();
                $tempRecordDataMax = TempRecordData::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->where('RECORD_TIME', $recordTime)->orderBy('TEMP', 'DESC')->first();*/
                $tempRecordDataMin = DB::table('TEMP_RECORD_DATA')
                ->join('RECORD_POSITION', 'TEMP_RECORD_DATA.ID_REC_POS', '=', 'RECORD_POSITION.ID_REC_POS')
                ->whereRaw('RECORD_POSITION.ID_STUDY_EQUIPMENTS = '. $idStudyEquipment .' AND CAST(RECORD_POSITION.RECORD_TIME AS DECIMAL(10,1)) = '. $recordTime .'')
                ->orderBy('TEMP', 'ASC')
                ->first();
                $tempRecordDataMax = DB::table('TEMP_RECORD_DATA')
                ->join('RECORD_POSITION', 'TEMP_RECORD_DATA.ID_REC_POS', '=', 'RECORD_POSITION.ID_REC_POS')
                ->whereRaw('RECORD_POSITION.ID_STUDY_EQUIPMENTS = '. $idStudyEquipment .' AND CAST(RECORD_POSITION.RECORD_TIME AS DECIMAL(10,1)) = '. $recordTime .'')
                ->orderBy('TEMP', 'DESC')
                ->first();
                $tempResult = [$tempRecordDataMin->TEMP, $tempRecordDataMax->TEMP];
            }

            $bornesTemp = [];
            if (!empty($tempResult)) {
                if ($tempInterval[0] >= $tempInterval[1]) {
                    $tempInterval[0] = $tempResult[0];
                    $tempInterval[1] = $tempResult[1];
                } else {
                    if ($tempInterval[0] > $tempResult[0]) {
                        $tempInterval[0] = $tempResult[0];
                    }
                    if ($tempInterval[1] < $tempResult[1]) {
                        $tempInterval[1] = $tempResult[1];
                    }
                }
                $bornesTemp = [$this->unit->prodTemperature($tempInterval[0], ['save' => true]), $this->unit->prodTemperature($tempInterval[1], ['save' => true])];

                $result = $this->calculatePasTemp($bornesTemp[0], $bornesTemp[1], false, $pasTemp);
            }
        } else {
           $prodFolder = 'Prod_' . $studyEquipment->study->ID_PROD;
           $stdeqpFolder = 'Equipment' . $idStudyEquipment;
           $timeStepFileName = 'contourParam_Z.inp';

           $dataTimeStep = file_get_contents($this->plotFolder3D . '/MeshBuilder3D/' . $prodFolder . '/' . $stdeqpFolder . '/' . $timeStepFileName);

            $dataTimeStepArr = explode("\n", $dataTimeStep);
            $dataTimeStepArr = array_filter($dataTimeStepArr);
            $lastTimeStep = $dataTimeStepArr[count($dataTimeStepArr) - 1];
            $lastTimeStepArr = explode(';', $lastTimeStep);
            $lastTimeStepArrMin = explode('=', $lastTimeStepArr[1]);
            $lastTimeStepArrMax = explode('=', $lastTimeStepArr[2]);
            $lastTimeStepArrStep = explode('=', $lastTimeStepArr[3]);
            $result = [(int) $lastTimeStepArrMin[1], (int) $lastTimeStepArrMax[1], (int) $lastTimeStepArrStep[1]];
        }

        return $result;
    }

    protected function calculatePasTemp($lfTmin, $lfTMax, $auto, $pasTemp)
    {
        set_time_limit(1000);
        $tab = [];
        $dTMin = 0;
        $dTMax = 0;
        $dpas = 0;
        $dnbpas = 0;

        $dTMin = intval(floor($lfTmin));
        $dTMax = intval(ceil($lfTMax));

        if ($auto) {
            $dpas = intval(floor(abs($dTMax - $dTMin) / 14) - 1);
        } else {
            $dpas = intval(floor($pasTemp) - 1);
        }

        if ($dpas < 0) {
            $dpas = 0;
        }

        do {
            $dpas++;

            while ($dTMin % $dpas != 0) {
                $dTMin--;
            }

            while ($dTMax % $dpas != 0) {
                $dTMax++;
            }

            $dnbpas = abs($dTMax - $dTMin) / $dpas;
        } while ($dnbpas > 16);

        $tab = [$this->unit->prodTemperature($dTMin), $this->unit->prodTemperature($dTMax), $dpas];

        return $tab;
    }

    public function getMeshView(Product &$product)
    {
        $elements = ProductElmt::where('ID_PROD', $product->ID_PROD)->orderBy('SHAPE_POS2', 'DESC')->get();

        $meshGeneration = $product->meshGenerations->first();
        if ($meshGeneration) {
            if ($elements[0]->ID_SHAPE == 1 || $elements[0]->ID_SHAPE == 6 ) {
                $meshGeneration->MESH_1_SIZE = doubleval(0);
                $meshGeneration->MESH_1_INT = doubleval(0);
            } else {
                $meshGeneration->MESH_1_SIZE = $this->unit->meshesUnit($meshGeneration->MESH_1_SIZE);
                $meshGeneration->MESH_1_INT = $this->unit->meshesUnit($meshGeneration->MESH_1_INT);
            }

            if ($meshGeneration->MESH_3_INT != 0 || $meshGeneration->MESH_3_SIZE !=  0) {
                $meshGeneration->MESH_3_INT = $this->unit->meshesUnit($meshGeneration->MESH_3_INT);
                $meshGeneration->MESH_3_SIZE = $this->unit->meshesUnit($meshGeneration->MESH_3_SIZE);
            } else {
                $meshGeneration->MESH_3_SIZE = doubleval(0);
                $meshGeneration->MESH_3_INT = doubleval(0);
            }

            if ($meshGeneration->MESH_2_INT != 0 || $meshGeneration->MESH_2_SIZE != 0) {
                $meshGeneration->MESH_2_INT = $this->unit->meshesUnit($meshGeneration->MESH_2_INT);
                $meshGeneration->MESH_2_SIZE = $this->unit->meshesUnit($meshGeneration->MESH_2_SIZE);
            } else {
                $meshGeneration->MESH_2_INT = doubleval(0);
                $meshGeneration->MESH_2_SIZE = doubleval(0);
            }
        }

        $elmtMeshPositions = [];
        $productElmtInitTemp = [];
        $initTempPositions = [];
        $nbMeshPointElmt = [];
        $heights = [];

        foreach ($elements as $elmt) {
            // shape < 10
            if ($elmt->ID_SHAPE < 10) {
                $meshPositions = MeshPosition::where('ID_PRODUCT_ELMT', $elmt->ID_PRODUCT_ELMT)->orderBy('MESH_ORDER')->get();
                array_push($elmtMeshPositions, $meshPositions[0]);

                $pointMeshOrder2 = $this->product->searchNbPtforElmt($elmt, 2);
                array_push($initTempPositions, $pointMeshOrder2['positions']);
                array_push($nbMeshPointElmt, count($pointMeshOrder2['points']));

                $elmtInitTemp = $this->productElmts->searchTempMeshPoint($elmt, $pointMeshOrder2['points']);
                array_push($productElmtInitTemp, $elmtInitTemp);
            } else {
                if ($meshGeneration) {
                    $pointMeshOrder2 = $this->product->calculateNumberPoint3D($meshGeneration, $elmt);
                    array_push($initTempPositions, $pointMeshOrder2['positions']);
                    array_push($nbMeshPointElmt, count($pointMeshOrder2['positions']));

                    array_push($productElmtInitTemp, $pointMeshOrder2['points']);
                }
            }

            $shapeParam2 = $this->productElmts->getProdElmtthickness($elmt->ID_PRODUCT_ELMT);
            array_push($heights, $shapeParam2);

            $elmt->componentName = $this->product->getComponentDisplayName($elmt->ID_COMP);
        }

        $productIsoTemp = null;
        
        if ($product->PROD_ISO) {
            // 3D initial temperature
            if (count($elements) > 0 && $elements[0]->ID_SHAPE >= 10) {
                if (InitTemp3D::where('ID_PRODUCT_ELMT', $elements[0]->ID_PRODUCT_ELMT)->count() > 0) {
                    $productIsoTemp = InitTemp3D::where('ID_PRODUCT_ELMT', $elements[0]->ID_PRODUCT_ELMT)->first();
                    if ($productIsoTemp) {
                        $productIsoTemp = $this->unit->temperature($productIsoTemp->INIT_TEMP);
                    }
                }
            } else {
                if (InitialTemperature::where('ID_PRODUCTION', $product->study->ID_PRODUCTION)->count() > 0) {
                    $productIsoTemp = InitialTemperature::where('ID_PRODUCTION', $product->study->ID_PRODUCTION)->first();
                    if ($productIsoTemp) {
                        $productIsoTemp = $this->unit->temperature($productIsoTemp->INITIAL_T);
                    }
                }
            }
        }

        return compact('meshGeneration', 'elements', 'elmtMeshPositions', 'productIsoTemp', 'nbMeshPointElmt', 'productElmtInitTemp', 'initTempPositions', 'heights');
    }

    public function getShapeName3D($shapeCode)
    {
        switch ($shapeCode) {
            case 10:
                $shapeName = 'Rectangular Block (Vertical layers)';
                break;

            case 11:
                $shapeName = 'Rectangular Block (Horizontal layers)';
                break;

            case 12:
                $shapeName = 'Standing cylinder';
                break;

            case 13:
                $shapeName = 'Lying cylinder';
                break;

            case 14:
                $shapeName = 'Sphere (mono or multi components)';
                break;

            case 15:
                $shapeName = 'Standing concentric cylinder';
                break;

            case 16:
                $shapeName = 'Lying concentric cylinder';
                break;

            case 17:
                $shapeName = 'Rectangular block (breaded)';
                break;

            case 18:
                $shapeName = 'Trapezoid 3D';
                break;

            case 19:
                $shapeName = 'Standing Oval';
                break;

            case 20:
                $shapeName = 'Lying Oval';
                break;

            case 21:
                $shapeName = 'Standing concentric oval';
                break;

            case 22:
                $shapeName = 'Lying concentric oval';
                break;

            case 23:
                $shapeName = 'Semi Cylinder';
                break;

            default:
                $shapeName = '';
                break;
        }

        return $shapeName;
    }
}