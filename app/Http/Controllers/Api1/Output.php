<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
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

use App\Cryosoft\ValueListService;
use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\EquipmentsService;
use App\Cryosoft\DimaResultsService;
use App\Cryosoft\EconomicResultsService;
use App\Cryosoft\StudyService;
use App\Cryosoft\OutputService;
use App\Models\LayoutGeneration;



class Output extends Controller
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
    public function __construct(Request $request, Auth $auth, UnitsConverterService $unit, EquipmentsService $equip, DimaResultsService $dima, ValueListService $value, EconomicResultsService $eco, StudyService $study, OutputService $output)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->unit = $unit;
        $this->equip = $equip;
        $this->dima = $dima;
        $this->value = $value;
        $this->eco = $eco;
        $this->study = $study;
        $this->output = $output;
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
        $percentSymbol = "%";
        $consumSymbol = $this->unit->consumptionSymbol($this->equip->initEnergyDef($idStudy), 1);
        $consumMaintienSymbol = $this->unit->consumptionSymbol($this->equip->initEnergyDef($idStudy), 2);
        $mefSymbol = $this->unit->consumptionSymbol($this->equip->initEnergyDef($idStudy), 3);

        $ret = compact("productFlowSymbol", "massSymbol", "temperatureSymbol", "percentSymbol", "timeSymbol", "perUnitOfMassSymbol", "enthalpySymbol", "monetarySymbol", "equipDimensionSymbol", "convectionSpeedSymbol", "convectionCoeffSymbol", "timePositionSymbol", "prodchartDimensionSymbol", "consumSymbol", "consumMaintienSymbol", "mefSymbol");
        // var_dump($ret);
        return $ret;
    }

    public function getProInfoStudy($idStudy)
    {
        $production = Production::select("PROD_FLOW_RATE", "AVG_T_INITIAL")->where("ID_STUDY", $idStudy)->first();
        $product = Product::select("PROD_REALWEIGHT")->where("ID_STUDY", $idStudy)->first();

        $prodFlowRate = $production->PROD_FLOW_RATE;
        $avgTInitial = $production->AVG_T_INITIAL;
        $prodElmtRealweight = $this->unit->mass($product->PROD_REALWEIGHT);

        return compact("prodFlowRate", "prodElmtRealweight", "avgTInitial");
    }

    public function getOptimumHeadBalance($idStudy)
    {
        $idUser = $this->auth->user()->ID_USER;
        $study = Study::find($idStudy);
        $calculationMode = $study->CALCULATION_MODE;

        //get study equipment
        $studyEquipments = StudyEquipment::where("ID_STUDY", $idStudy)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();

        $lfcoef = $this->unit->unitConvert($this->value->MASS_PER_UNIT, 1.0);

        $result = array();

        foreach ($studyEquipments as $row) {
            $capabilitie = $row->CAPABILITIES;
            $equipStatus = $row->EQUIP_STATUS;
            $brainType = $row->BRAIN_TYPE;
            $calculWarning = "";
            $item["id"] = $idStudyEquipment = $row->ID_STUDY_EQUIPMENTS;
            $item["specificSize"] = $this->equip->getSpecificEquipSize($idStudyEquipment);
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

        foreach ($studyEquipments as $row) {
            $capabilitie = $row->CAPABILITIES;
            $equipStatus = $row->EQUIP_STATUS;
            $brainType = $row->BRAIN_TYPE;
            $calculWarning = "";
            $item["id"] = $idStudyEquipment = $row->ID_STUDY_EQUIPMENTS;
            $item["specificSize"] = $this->equip->getSpecificEquipSize($idStudyEquipment);
            $item["equipName"] = $this->equip->getResultsEquipName($idStudyEquipment);

            $dimaResult = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("DIMA_TYPE", 16)->first();

            if (!($this->equip->getCapability($capabilitie, 128))) {
                $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $conso_warning = $toc = $precision = "****";
            } else if ($dimaResult == null) {
                $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $conso_warning = $toc = $precision = "";
            } else {
                $calculWarning = $this->dima->getCalculationWarning($dimaResult->DIMA_STATUS);

                if ($calculWarning != 0) {
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

            $item["calculWarning"] = $calculWarning;
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

    public function getEstimationHeadBalance()
    {
        $idStudy = $this->request->input('idStudy');
        $trSelect = ($this->request->input('tr') != "") ? $this->request->input('tr') : 1;
        $idUser = $this->auth->user()->ID_USER;

        $study = Study::find($idStudy);

        $calculationMode = $study->CALCULATION_MODE;

        //get study equipment
        $studyEquipments = StudyEquipment::where("ID_STUDY", $idStudy)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();

        $lfcoef = $this->unit->unitConvert($this->value->MASS_PER_UNIT, 1.0);

        $result = array();

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
                        $equipName = "****";
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

                    $result[] = $item;
                }
            }

        }

        return $result;
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
                        $equipName = "****";
                        $tc = "****";
                        $kgProduct = "****";
                        $product = "****";
                        $day = "****";
                        $week = "****";
                        $hour = "****";
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

    public function getEquipSizing($idStudyEquipment)
    {
        $studyEquipment = StudyEquipment::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->first();
        if (!empty($studyEquipment)) {
            $equipName = $this->equip->getSpecificEquipName($idStudyEquipment);

            $initWidth = $this->unit->equipDimension($studyEquipment->STDEQP_WIDTH);
            $initLength = $this->unit->equipDimension($studyEquipment->STDEQP_LENGTH);

            $minWidth = 0;
            $maxWidth = -1;
            $minLength = 0;
            $maxLength = -1;

            $mmWidth = MinMax::where("LIMIT_ITEM", $this->value->MIN_MAX_EQUIPMENT_WIDTH)->first();
            $minWidth = $this->unit->equipDimension($mmWidth->LIMIT_MIN);
            $minWidth = $this->unit->equipDimension($mmWidth->LIMIT_MAX);

            $mmLength = MinMax::where("LIMIT_ITEM", $this->value->MIN_MAX_EQUIPMENT_LENGTH)->first();
            $minLength = $this->unit->equipDimension($mmWidth->LIMIT_MIN);
            $maxLength = $this->unit->equipDimension($mmWidth->LIMIT_MAX);

            $minSurf = $minWidth * $minLength;
            $maxSurf = $maxWidth * $maxLength;

            $disabled = "";
            if (!($this->study->isMyStudy($studyEquipment->ID_STUDY)) && $this->auth->user()->USERPRIO < $this->value->PROFIL_EXPERT) {
                $disabled = "disabled";
            }

            return compact("idStudyEquipment", "equipName", "minWidth", "maxWidth", "minLength", "maxLength", "minSurf", "maxSurf", "disabled");
        }
    }

    public function temperatureCalculation($idStudyEquipment)
    {
        $studyEquipment = StudyEquipment::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->first();
        if (!empty($studyEquipment)) {
            $idStudy = $studyEquipment->ID_STUDY;

            $equipName = $this->equip->getSpecificEquipName($idStudyEquipment);

            $disabled = $this->study->disableFields($idStudy);

            $trPrm = $this->equip->getStudEqpPrm($idStudyEquipment, 300);
            $tsPrm = $this->equip->getStudEqpPrm($idStudyEquipment, 200);
            $vcPrm = $this->equip->getStudEqpPrm($idStudyEquipment, 100);
            $tePrm = $this->equip->getStudEqpPrm($idStudyEquipment, 500);
            if (count($tePrm) > 0) $tePrm = $tePrm[0];

            $ldSetpointmax = (count($tsPrm) > count($trPrm)) ? (count($tsPrm) > count($vcPrm)) ? count($tsPrm) : count($vcPrm) : (count($trPrm) > count($vcPrm)) ? count($trPrm) : count($vcPrm);

            $mmTr = MinMax::where("LIMIT_ITEM", $studyEquipment->ITEM_TR)->first();
            $mmTs = MinMax::where("LIMIT_ITEM", $studyEquipment->ITEM_TS)->first();

            $trMin = $this->unit->controlTemperature($mmTr->LIMIT_MIN);
            $trMax = $this->unit->controlTemperature($mmTr->LIMIT_MAX);
            $tsMin = $this->unit->controlTemperature($mmTs->LIMIT_MIN);
            $tsMax = $this->unit->controlTemperature($mmTs->LIMIT_MAX);

            return compact("idStudyEquipment", "equipName", "trPrm", "tsPrm", "vcPrm", "tePrm", "ldSetpointmax", "trMin", "trMax", "tsMin", "tsMax", "disabled");
        }
    }

    public function viewEquipTr($idStudyEquipment)
    {
        $studyEquipment = StudyEquipment::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->first();
        if (!empty($studyEquipment)) {
            $idStudy = $studyEquipment->ID_STUDY;
            $equipName = $this->equip->getSpecificEquipName($idStudyEquipment);

            $dimaResult = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->first();

            return compact("equipName", "dimaResult");
        }
    }

    public function sizingOptimumResult($idStudy)
    {
        $study = Study::find($idStudy);
        $calculationMode = $study->CALCULATION_MODE;

        //get study equipment
        $studyEquipments = StudyEquipment::where("ID_STUDY", $idStudy)->orderBy("ID_STUDY_EQUIPMENTS", "ASC")->get();
        $production = Production::where("ID_STUDY", $idStudy)->first();

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
            $itemGrap["equipName"] = $equipName = $this->equip->getResultsEquipName($idStudyEquipment);

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
                    $itemGrap["dhp"] = $dhp;
                    $itemGrap["conso"] = $conso;
                    $itemGrap["dhpMax"] = $dhpMax;
                    $itemGrap["consoMax"] = $consoMax;

                    if ($i < 4) {
                        $selectedEquipment[] = $itemGrap;
                    } else {
                        $availableEquipment[] = $itemGrap;
                    }
                }

                $i++;
            }
        }

        if (!empty($selectedEquipment)) {
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
            }
        }

        return compact("result", "selectedEquipment", "availableEquipment", "dataGrapChart");
    }

    public function sizingEstimationResult() 
    {
        $idStudy = $this->request->input('idStudy');
        $trSelect = ($this->request->input('tr') != "") ? $this->request->input('tr') : 1;

        $production = Production::where("ID_STUDY", $idStudy)->first();

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

        foreach ($studyEquipments as $row) {
            $capabilitie = $row->CAPABILITIES;
            $equipStatus = $row->EQUIP_STATUS;
            $brainType = $row->BRAIN_TYPE;
            $idCoolingFamily = $row->ID_COOLING_FAMILY;
            $itemGrap["id"] = $idStudyEquipment = $row->ID_STUDY_EQUIPMENTS;
            $itemGrap["equipName"] = $equipName = $this->equip->getSpecificEquipName($idStudyEquipment);

            $dimaResults = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->orderBy("SETPOINT", "DESC")->get();
            $dhp = $conso = $dhpMax = $consoMax = "";

            foreach ($dimaResults as $key => $dimaR) {
                $dhp = $this->unit->productFlow($production->PROD_FLOW_RATE);
                if ($key == 0 || $key == 2) {
                    if ($this->equip->isValidTemperature($idStudyEquipment, $trSelect)) {
                        $dhp = $this->unit->productFlow($production->PROD_FLOW_RATE);
                    } else {
                        $dhp = 0;
                    }
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

                $itemGrap["data"][$key]["dhp"] = $dhp;
                $itemGrap["data"][$key]["conso"] = $conso;
                $itemGrap["data"][$key]["dhpMax"] = $dhpMax;
                $itemGrap["data"][$key]["consoMax"] = $consoMax;
            } 


            $dataGraphChart[] =  $itemGrap;
        }

        return compact("result", "dataGraphChart");
    }

    public function temperatureProfile($idStudyEquipment) 
    {
        //get study equip profile
        $tempProfile = StudEquipprofile::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->orderBy("EP_X_POSITION", "ASC")->get();
    
        if (count($tempProfile) > 0) {
            $lfminTemp = INF;
            $lfmaxTemp = -INF;

            $lfminConv = INF;
            $lfmaxConv = -INF;

            $lfTime = 0.0;
            $lfTemp = 0.0;
            $lfConv = 0.0;

            $point2DSeriesTempCurveTop = array();
            $point2DSeriesConvCurveTop = array();

            $point2DSeriesTempCurveBottom = array();
            $point2DSeriesConvCurveBottom = array();

            $point2DSeriesTempCurveLeft = array();
            $point2DSeriesConvCurveLeft = array();

            $point2DSeriesTempCurveRight = array();
            $point2DSeriesConvCurveRight = array();

            $point2DSeriesTempCurveFront = array();
            $point2DSeriesConvCurveFront = array();

            $point2DSeriesTempCurveRear = array();
            $point2DSeriesConvCurveRear = array();

            $tempChartData = array();
            $convChartData = array();


            foreach ($tempProfile as $rowTemp) {
                $lfTime = $this->unit->timePosition($rowTemp->EP_X_POSITION);

                $lfTempTop = $this->unit->temperature($rowTemp->EP_TEMP_TOP);
                $lfConvTop = $this->unit->convectionCoeff($rowTemp->EP_ALPHA_TOP); 

                if ($lfTempTop > $lfmaxTemp) {
                    $lfmaxTemp = $lfTempTop;
                } else if ($lfTempTop < $lfminTemp) {
                    $lfminTemp = $lfTempTop;
                }
                if ($lfConvTop > $lfmaxConv) {
                    $lfmaxConv = $lfConvTop;
                } else if ($lfConvTop < $lfminConv) {
                    $lfminConv = $lfConvTop;
                }

                $lfTempBottom= $this->unit->temperature($rowTemp->EP_TEMP_BOTTOM);
                $lfConvBottom = $this->unit->convectionCoeff($rowTemp->EP_ALPHA_BOTTOM);

                if ($lfTempBottom > $lfmaxTemp) {
                    $lfmaxTemp = $lfTempBottom;
                } else if ($lfTempBottom < $lfminTemp) {
                    $lfminTemp = $lfTempBottom;
                }
                if ($lfConvBottom > $lfmaxConv) {
                    $lfmaxConv = $lfConvBottom;
                } else if ($lfminConv < $lfConvBottom) {
                    $lfminConv = $lfConvBottom;
                }

                $lfTempLeft = $this->unit->temperature($rowTemp->EP_ALPHA_LEFT);
                $lfConvLeft = $this->unit->convectionCoeff($rowTemp->EP_ALPHA_LEFT);

                if ($lfTempLeft > $lfmaxTemp) {
                    $lfmaxTemp = $lfTempLeft;
                } else if ($lfTempLeft < $lfminTemp) {
                    $lfminTemp = $lfTempLeft;
                }
                if ($lfConvLeft > $lfmaxConv) {
                    $lfmaxConv = $lfConvLeft;
                } else if ($lfConvLeft < $lfminConv) {
                    $lfminConv = $lfConvLeft;
                }

                $lfTempRight = $this->unit->temperature($rowTemp->EP_ALPHA_RIGHT);
                $lfConvRight = $this->unit->convectionCoeff($rowTemp->EP_ALPHA_RIGHT);

                if ($lfTempRight > $lfmaxTemp) {
                    $lfmaxTemp = $lfTempRight;
                } else if ($lfTempRight < $lfminTemp) {
                    $lfminTemp = $lfTempRight;
                }
                if ($lfConvRight > $lfmaxConv) {
                    $lfmaxConv = $lfConvRight;
                } else if ($lfConvRight < $lfminConv) {
                    $lfminConv = $lfConvRight;
                }
           
                $lfTempFront = $this->unit->temperature($rowTemp->EP_TEMP_FRONT);
                $lfConvFront = $this->unit->convectionCoeff($rowTemp->EP_ALPHA_FRONT);

                if ($lfTempFront > $lfmaxTemp) {
                    $lfmaxTemp = $lfTempFront;
                } else if ($lfTempFront < $lfminTemp) {
                    $lfminTemp = $lfTempFront;
                }
                if ($lfConvFront > $lfmaxConv) {
                    $lfmaxConv = $lfConvFront;
                } else if ($lfConvFront < $lfminConv) {
                    $lfminConv = $lfConvFront;
                }

                $lfTempRear = $this->unit->temperature($rowTemp->EP_TEMP_REAR);
                $lfConvRear = $this->unit->convectionCoeff($rowTemp->EP_ALPHA_REAR);

                if ($lfTempRear > $lfmaxTemp) {
                    $lfmaxTemp = $lfTempRear;
                } else if ($lfTempRear < $lfminTemp) {
                    $lfminTemp = $lfTempRear;
                }
                if ($lfConvRear > $lfmaxConv) {
                    $lfmaxConv = $lfConvRear;
                } else if ($lfConvRear < $lfminConv) {
                    $lfminConv = $lfConvRear;
                }

                //add item top to array
                $point2DSeriesTempCurveTop[] = ["x" => $lfTime, "y" => $lfTempTop];
                $point2DSeriesConvCurveTop[] = ["x" => $lfTime, "y" => $lfConvTop];

                //add item bottom to array
                $point2DSeriesTempCurveBottom[] = ["x" => $lfTime, "y" => $lfTempBottom];
                $point2DSeriesConvCurveBottom[] = ["x" => $lfTime, "y" => $lfConvBottom];

                //add item left to array
                $point2DSeriesTempCurveLeft[] = ["x" => $lfTime, "y" => $lfTempLeft];
                $point2DSeriesConvCurveLeft[] = ["x" => $lfTime, "y" => $lfConvLeft];

                //add item right to array
                $point2DSeriesTempCurveRight[] = ["x" => $lfTime, "y" => $lfTempRight];
                $point2DSeriesConvCurveRight[] = ["x" => $lfTime, "y" => $lfConvRight];

                //add item Front to array
                $point2DSeriesTempCurveFront[] = ["x" => $lfTime, "y" => $lfTempFront];
                $point2DSeriesConvCurveFront[] = ["x" => $lfTime, "y" => $lfConvFront];

                //add item rear to array
                $point2DSeriesTempCurveRear[] = ["x" => $lfTime, "y" => $lfTempRear];
                $point2DSeriesConvCurveRear[] = ["x" => $lfTime, "y" => $lfConvRear];
            }

            $minScaleTemp = ($lfminTemp < 0.0) ? -ceil(-$lfminTemp) : floor($lfminTemp) - 1;
            $maxScaleTemp = ($lfmaxTemp < 0.0) ? -floor(-$lfmaxTemp) : ceil($lfmaxTemp) + 1;
            if ($lfmaxTemp == $lfminTemp) {
                if ($lfminTemp < 0.0) {
                    $maxScaleTemp = 0;
                } else if ($lfminTemp > 0.0) {
                    $minScaleTemp = 0;
                }
            }

            $minScaleConv = ($lfminConv < 0.0) ? -ceil(-$lfminConv) : floor($lfminConv) - 1;
            $maxScaleConv = ($lfmaxConv < 0.0) ? -floor(-$lfmaxConv) : ceil($lfmaxConv) + 1;
            if ($lfmaxConv == $lfminConv) {
                if ($lfminConv < 0.0) {
                    $maxScaleConv = 0;
                } else if ($lfminConv > 0.0) {
                    $minScaleConv = 0;
                }
            }

            $tempChartData = array("top" => $point2DSeriesTempCurveTop, "bottom" => $point2DSeriesTempCurveBottom, "left" => $point2DSeriesTempCurveLeft, "right" => $point2DSeriesTempCurveRight, "front" => $point2DSeriesTempCurveFront, "rear" => $point2DSeriesTempCurveRear);

            $convChartData = array("top" => $point2DSeriesConvCurveTop, "bottom" => $point2DSeriesConvCurveBottom, "left" => $point2DSeriesConvCurveLeft, "right" => $point2DSeriesConvCurveRight, "front" => $point2DSeriesConvCurveFront, "rear" => $point2DSeriesConvCurveRear);

            return compact("minScaleTemp", "maxScaleTemp", "minScaleConv", "maxScaleConv", "tempChartData", "convChartData");

        }
    }
    
    public function location()
    {
        $idStudy = $this->request->input('idStudy');
        $idStudyEquipment = $this->request->input('idStudyEquipment');

        $tfMesh = [];
        for ($i = 0; $i < 3; $i++) {
            $meshPoints = MeshPosition::distinct()->select('MESH_AXIS_POS')->where('ID_STUDY', $idStudy)->where('MESH_AXIS', $i+1)->orderBy('MESH_AXIS_POS')->get();
            $itemName = [];
            foreach ($meshPoints as $row) {
                $item['value'] = $row->MESH_AXIS_POS;
                $item['name'] = $this->unit->meshesUnit($row->MESH_AXIS_POS);
                $itemName[] = $item;
            }
            $tfMesh[$i] = array_reverse($itemName);
        }
        $meshAxisPos = [];
        if (!empty($tfMesh)) {
            $meshAxisPos = $tfMesh;
            /*$meshAxisPos['x'] = $tfMesh[0];
            $meshAxisPos['y'] = $tfMesh[1];
            $meshAxisPos['z'] = $tfMesh[2];*/
        }

        $productElmt = ProductElmt::where('ID_STUDY', $idStudy)->first();
        $shape = $productElmt->SHAPECODE;
        $layoutGen = LayoutGeneration::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->first();
        $parallel = $layoutGen->PROD_POSITION;
        $v3fSelectedPoints = [];

        $tfMesh = $this->output->convertMeshForAppletDim($shape, $parallel, $tfMesh);

        $tfCoord = $this->output->convertPointForAppletDim($shape, $parallel, $tfMesh); 
        $v3fSelectedPoints[0][0] = $tfCoord;

        $tfCoord = $this->output->convertPointForAppletDim($shape, $parallel, $tfMesh); 
        $v3fSelectedPoints[0][1] = $tfCoord;

        $tfCoord = $this->output->convertPointForAppletDim($shape, $parallel, $tfMesh); 
        $v3fSelectedPoints[0][2] = $tfCoord;

        $tfCoord[0] = 0.0;
        $tfCoord[1] = $tfMesh[0];
        $tfCoord[2] = $tfMesh[1];

        $v3fSelectedPoints[1][0] = $tfCoord;

        $tfCoord[0] = $tfMesh[0];
        $tfCoord[1] = 0.0;
        $tfCoord[2] = $tfMesh[1];
        
        $v3fSelectedPoints[1][1] = $tfCoord;

        $tfCoord[0] = $tfMesh[0];
        $tfCoord[1] = $tfMesh[1];
        $tfCoord[2] = 0.0;
        
        $v3fSelectedPoints[1][2] = $tfCoord;

        $v3fSelectedPoints[1] = $this->output->convertAxisForAppletDim($shape, $parallel, $v3fSelectedPoints[1]);


        return $meshAxisPos;
    }

    public function heatExchange() 
    {
        $idStudy = $this->request->input('idStudy');
        $idStudyEquipment = $this->request->input('idStudyEquipment');
        $listRecordPos = RecordPosition::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->orderBy("RECORD_TIME", "ASC")->get();

        $curve = array();
        $result = array();
        foreach ($listRecordPos as $row) {

            $item["x"] = $this->unit->time($row->RECORD_TIME);
            $item["y"] = $this->unit->enthalpy($row->AVERAGE_ENTH_VAR);

            $curve[] = $item;
        }

        $nbSteps = TempRecordPts::where("ID_STUDY", $idStudy)->first();
        $nbSample = $nbSteps->NB_STEPS;

        $nbRecord = count($listRecordPos);

        $lfTS = $listRecordPos[$nbRecord - 1]->RECORD_TIME;
        $lfStep = $listRecordPos[1]->RECORD_TIME - $listRecordPos[0]->RECORD_TIME;
        $lEchantillon = $this->output->calculateEchantillon($nbSample, $nbRecord, $lfTS, $lfStep);

        foreach ($lEchantillon as $row) {

            $recordPos = $listRecordPos[$row];

            $itemResult["x"] = $this->unit->time($recordPos->RECORD_TIME);
            $itemResult["y"] = $this->unit->enthalpy($recordPos->AVERAGE_ENTH_VAR);

            $result[] = $itemResult;
        }

        return compact("result", "curve");
    }

    public function timeBased()
    {
        $idStudy = $this->request->input('idStudy');
        $idStudyEquipment = $this->request->input('idStudyEquipment');

        $listRecordPos = RecordPosition::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->get();
        $result = array();
        $label = array();
        $curve = array();

        if (count($listRecordPos) > 0) {
            foreach ($listRecordPos as $row) {
                $termRecordDataTop = $this->output->getTemperaturePosition($row->ID_REC_POS, (int) $row->AXIS1_PT_TOP_SURF, (int) $row->AXIS2_PT_TOP_SURF);
                $termRecordDataInt = $this->output->getTemperaturePosition($row->ID_REC_POS, (int) $row->AXIS1_PT_INT_PT, (int) $row->AXIS2_PT_INT_PT);
                $termRecordDataBot = $this->output->getTemperaturePosition($row->ID_REC_POS, (int) $row->AXIS1_PT_BOT_SURF, (int) $row->AXIS2_PT_BOT_SURF);

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
            $tempRecordPts = TempRecordPts::where("ID_STUDY", $idStudy)->first();
            $nbSample = $tempRecordPts->NB_STEPS;

            $nbRecord = count($listRecordPos);

            $lfTS = $listRecordPos[$nbRecord - 1]->RECORD_TIME;
            $lfStep = $listRecordPos[1]->RECORD_TIME - $listRecordPos[0]->RECORD_TIME;
            $lEchantillon = $this->output->calculateEchantillon($nbSample, $nbRecord, $lfTS, $lfStep);

            foreach ($lEchantillon as $row) {
                $recordPos = $listRecordPos[$row];
                $item["points"] = $this->unit->time($recordPos->RECORD_TIME);

                //top
                $termRecordDataTop = $this->output->getTemperaturePosition($recordPos->ID_REC_POS, (int) $tempRecordPts->AXIS1_PT_TOP_SURF, (int) $tempRecordPts->AXIS2_PT_TOP_SURF);
                $item["top"] =  $this->unit->prodTemperature($termRecordDataTop->TEMP);
                
                //int
                $termRecordDataInt = $this->output->getTemperaturePosition($recordPos->ID_REC_POS, (int) $tempRecordPts->AXIS1_PT_INT_PT, (int) $tempRecordPts->AXIS2_PT_INT_PT);
                $item["int"] = $this->unit->prodTemperature($termRecordDataInt->TEMP);

                //bot
                $termRecordDataBot = $this->output->getTemperaturePosition($recordPos->ID_REC_POS, (int) $tempRecordPts->AXIS1_PT_BOT_SURF, (int) $tempRecordPts->AXIS2_PT_BOT_SURF);
                $item["bot"] = $this->unit->prodTemperature($termRecordDataBot->TEMP);

                $item["average"] = $this->unit->prodTemperature($recordPos->AVERAGE_TEMP);
                $result[] = $item; 
            }

            $label["top"] = $this->unit->meshesUnit($tempRecordPts->AXIS1_PT_TOP_SURF) . "," . $this->unit->meshesUnit($tempRecordPts->AXIS2_PT_TOP_SURF) . "," . $this->unit->meshesUnit($tempRecordPts->AXIS3_PT_TOP_SURF);

            $label["int"] = $this->unit->meshesUnit($tempRecordPts->AXIS1_PT_INT_PT) . "," . $this->unit->meshesUnit($tempRecordPts->AXIS2_PT_INT_PT) . "," . $this->unit->meshesUnit($tempRecordPts->AXIS3_PT_INT_PT);

            $label["bot"] = $this->unit->meshesUnit($tempRecordPts->AXIS1_PT_BOT_SURF) . "," . $this->unit->meshesUnit($tempRecordPts->AXIS2_PT_BOT_SURF) . "," . $this->unit->meshesUnit($tempRecordPts->AXIS3_PT_BOT_SURF);
        }

        return compact("label", "curve", "result");
    }

    public function productSection(){
        $idStudy = $this->request->input('idStudy');
        $idStudyEquipment = $this->request->input('idStudyEquipment');
        $selectedAxe = $this->request->input('selectedAxe');

        $productElmt = ProductElmt::where('ID_STUDY', $idStudy)->first();
        $shape = $productElmt->SHAPECODE;
        $layoutGen = LayoutGeneration::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->first();
        $orientation = $layoutGen->PROD_POSITION;

        $resultLabel = [];
        $resultTemperature = [];

        $selPoints = $this->output->getSelectedMeshPoints($idStudy);
        if (empty($selPoints)) {
            $selPoints = $this->output->getMeshSelectionDef();
        }

        $axeTempRecordData = [];
        if (!empty($selPoints)) {
            $axeTempRecordData = [
                [-1.0, $selPoints[9], $selPoints[10]],
                [$selPoints[11], -1.0, $selPoints[12]],
                [$selPoints[13], $selPoints[14], -1.0]
            ];
        }
        $axeTemp = [];
        switch ($selectedAxe) {
            case 1:
                array_push($axeTemp, $this->unit->prodchartDimension($selPoints[9]));
                array_push($axeTemp, $this->unit->prodchartDimension($selPoints[10]));
                break;

            case 2:
                array_push($axeTemp, $this->unit->prodchartDimension($selPoints[11]));
                array_push($axeTemp, $this->unit->prodchartDimension($selPoints[12]));
                break;

            case 3:
                array_push($axeTemp, $this->unit->prodchartDimension($selPoints[13]));
                array_push($axeTemp, $this->unit->prodchartDimension($selPoints[14]));
                break;
        }

        $listRecordPos = RecordPosition::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->orderBy("RECORD_TIME", "ASC")->get();
        $nbSteps = TempRecordPts::where("ID_STUDY", $idStudy)->first();
        $nbSample = $nbSteps->NB_STEPS;

        $nbRecord = count($listRecordPos);

        $lfTS = $listRecordPos[$nbRecord - 1]->RECORD_TIME;
        $lfStep = $listRecordPos[1]->RECORD_TIME - $listRecordPos[0]->RECORD_TIME;
        $lEchantillon = $this->output->calculateEchantillon($nbSample, $nbRecord, $lfTS, $lfStep);
        $dataChart = [];
        // return $axeTempRecordData;

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

        $result = [];
        $resultValue = [];

        foreach ($resultTemperature as $row) {
            foreach ($row as $key => $value) {
                $resultValue[$key][] = $value;
            }
        }
        $result["recAxis"] = $recAxis;
        $result["mesAxis"] = $mesAxis;
        $result["resultValue"] = $resultValue;


        return compact("axeTemp", "dataChart", "resultLabel", "result");
    }

    public function saveTempRecordPts()
    {
        $input = $this->request->all();
        $idStudy = $input['ID_STUDY'];
        $idStudyEquipment = $input['ID_STUDY_EQUIPMENTS'];
        $selectedAxe = $input['AXE'];
        $nbSteps = $input['NB_STEPS'];
        if (empty($nbSteps)) {
            return response([
                'code' => 1000,
                'message' => 'Enter a value in Curve Number !'
            ], 406);
        }
        if (!is_numeric($nbSteps) || $nbSteps < 0) {
            return response([
                'code' => 1001,
                'message' => 'Not a valid number in Curve Number !'
            ], 406);
        }
        $tempRecordPts =  TempRecordPts::where('ID_STUDY', $idStudy)->first();
        $tempRecordPts->NB_STEPS = $nbSteps;
        $tempRecordPts->save();

        $productElmt = ProductElmt::where('ID_STUDY', $idStudy)->first();
        $shape = $productElmt->SHAPECODE;
        $layoutGen = LayoutGeneration::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->first();
        $orientation = $layoutGen->PROD_POSITION;

        $resultLabel = [];
        $resultTemperature = [];

        $selPoints = $this->output->getSelectedMeshPoints($idStudy);
        if (empty($selPoints)) {
            $selPoints = $this->output->getMeshSelectionDef();
        }

        $axeTempRecordData = [];
        if (!empty($selPoints)) {
            $axeTempRecordData = [
                [-1.0, $selPoints[9], $selPoints[10]],
                [$selPoints[11], -1.0, $selPoints[12]],
                [$selPoints[13], $selPoints[14], -1.0]
            ];
        }
        $axeTemp = [];
        switch ($selectedAxe) {
            case 1:
                array_push($axeTemp, $this->unit->prodchartDimension($selPoints[9]));
                array_push($axeTemp, $this->unit->prodchartDimension($selPoints[10]));
                break;

            case 2:
                array_push($axeTemp, $this->unit->prodchartDimension($selPoints[11]));
                array_push($axeTemp, $this->unit->prodchartDimension($selPoints[12]));
                break;

            case 3:
                array_push($axeTemp, $this->unit->prodchartDimension($selPoints[13]));
                array_push($axeTemp, $this->unit->prodchartDimension($selPoints[14]));
                break;
        }
        
        $listRecordPos = RecordPosition::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->orderBy("RECORD_TIME", "ASC")->get();
        $nbSample = $nbSteps;

        $nbRecord = count($listRecordPos);

        $lfTS = $listRecordPos[$nbRecord - 1]->RECORD_TIME;
        $lfStep = $listRecordPos[1]->RECORD_TIME - $listRecordPos[0]->RECORD_TIME;
        $lEchantillon = $this->output->calculateEchantillon($nbSample, $nbRecord, $lfTS, $lfStep);

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

        $result = [];
        $resultValue = [];

        foreach ($resultTemperature as $row) {
            foreach ($row as $key => $value) {
                $resultValue[$key][] = $value;
            }
        }

        $mixRange = $this->output->mixRange('rgb(0,0,255)', 'rgb(0,255,0)');

        $result["recAxis"] = $recAxis;
        $result["mesAxis"] = $mesAxis;
        $result["resultValue"] = $resultValue;

        return compact("axeTemp", "dataChart", "resultLabel", "result");
    }

    public function productchart2D()
    {
        $idStudy = $this->request->input('idStudy');
        $idStudyEquipment = $this->request->input('idStudyEquipment');
        $selectedPlan = $this->request->input('selectedPlan');

        $productElmt = ProductElmt::where('ID_STUDY', $idStudy)->first();
        $shape = $productElmt->SHAPECODE;
        $layoutGen = LayoutGeneration::where('ID_STUDY_EQUIPMENTS', $idStudyEquipment)->first();
        $orientation = $layoutGen->PROD_POSITION;

        // get TimeInterva
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

        return compact("valueRecAxis", "lfDwellingTime", "lftimeInterval");
    }
}
