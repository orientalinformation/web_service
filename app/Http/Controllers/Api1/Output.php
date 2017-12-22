<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;

use App\Models\Study;
use App\Models\StudyEquipment;
use App\Models\Production;
use App\Models\Product;
use App\Models\DimaResults;
use App\Models\EconomicResults;
use App\Models\StudEqpPrm;
use App\Models\MinMax;

use App\Cryosoft\ValueListService;
use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\EquipmentsService;
use App\Cryosoft\DimaResultsService;
use App\Cryosoft\EconomicResultsService;
use App\Cryosoft\StudyService;



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
    public function __construct(Request $request, Auth $auth, UnitsConverterService $unit, EquipmentsService $equip, DimaResultsService $dima, ValueListService $value, EconomicResultsService $eco, StudyService $study)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->unit = $unit;
        $this->equip = $equip;
        $this->dima = $dima;
        $this->value = $value;
        $this->eco = $eco;
        $this->study = $study;
    }

    public function getSymbol($idStudy)
    {
        $productFlowSymbol = $this->unit->productFlowSymbol();
        $massSymbol = $this->unit->massSymbol();
        $temperatureSymbol = mb_convert_encoding($this->unit->temperatureSymbol(), "UTF-8", "ISO-8859-1");
        $timeSymbol = $this->unit->timeSymbol();
        $perUnitOfMassSymbol = $this->unit->perUnitOfMassSymbol();
        $enthalpySymbol = $this->unit->enthalpySymbol();
        $monetarySymbol = $this->unit->monetarySymbol();
        $equipDimensionSymbol = $this->unit->equipDimensionSymbol();
        $convectionSpeedSymbol = $this->unit->convectionSpeedSymbol();
        $percentSymbol = "%";
        $consumSymbol = $this->unit->consumptionSymbol($this->equip->initEnergyDef($idStudy), 1);
        $consumMaintienSymbol = $this->unit->consumptionSymbol($this->equip->initEnergyDef($idStudy), 2);
        $mefSymbol = $this->unit->consumptionSymbol($this->equip->initEnergyDef($idStudy), 3);

        $ret = compact("productFlowSymbol", "massSymbol", "temperatureSymbol", "percentSymbol", "timeSymbol", "perUnitOfMassSymbol", "enthalpySymbol", "monetarySymbol", "equipDimensionSymbol", "convectionSpeedSymbol", "consumSymbol", "consumMaintienSymbol", "mefSymbol");
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

            $item["runBrainPopup"] = false;
            if ($this->equip->getCapability($capabilitie, 128)) {
                $item["runBrainPopup"] = true;
            }

            if (!($this->equip->getCapability($capabilitie, 128))) {
                $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $toc = $precision = "null";
                $calculate = "disabled";
            } else if (($equipStatus != 0) && ($equipStatus != 1) && ($equipStatus != 100000)) {
                $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $toc = $precision = "****";
                $calculate = "disabled";
            } else if ($equipStatus == 10000) {
                $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $toc = $precision = "";
                $calculate = "disabled";
            } else {
                $dimaResult = DimaResults::where("ID_STUDY_EQUIPMENTS", $idStudyEquipment)->where("DIMA_TYPE", 1)->first();
                if ($dimaResult == null) {
                    $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $toc = $precision = "";
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

                        $conso = $this->dima->consumptionCell($lfcoef, $calculationStatus, $valueStr);

                    } else {
                        $conso = "****";
                    }

                    if ($this->equip->getCapability($capabilitie, 32)) {
                        $dhp = $this->unit->productFlow($dimaResult->HOURLYOUTPUTMAX);

                        $batch = $row->BATCH_PROCESS;
                        if ($batch) {
                            $massConvert = $this->unit->mass($dimaResult->USERATE);
                            $massSymbol = $this->unit->massSymbol();
                            $toc = $massConvert . $massSymbol . "/batch";
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
                $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $toc = $precision = "****";
            } else if ($dimaResult == null) {
                $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $toc = $precision = "";
            } else {
                $calculWarning = $this->dima->getCalculationWarning($dimaResult->DIMA_STATUS);

                if ($calculWarning != 0) {
                    $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $toc = $precision = "****";
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

                        $conso = $this->dima->consumptionCell($lfcoef, $calculationStatus, $valueStr);

                    } else {
                        $conso = "****";
                    }

                    if ($this->equip->getCapability($capabilitie, 32)) {
                        $dhp = $this->unit->productFlow($dimaResult->HOURLYOUTPUTMAX);

                        $batch = $row->BATCH_PROCESS;
                        if ($batch) {
                            $massConvert = $this->unit->mass($dimaResult->USERATE);
                            $massSymbol = $this->unit->massSymbol();
                            $toc = $massConvert . $massSymbol . "/batch";
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

            $tr = $ts = $vc = $vep = $tfp = $dhp = $conso = $toc = $tocMax = $consoMax = $precision = "null";

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
                if (!empty($dimaResults)) {
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
                                $toc = $this->unit->mass($dimaR->USERATE) . $this->unit->massSymbol() . "/batch";
                            } else {
                                $toc = $this->unit->toc($dimaR->USERATE) . "%";
                            }

                            if ($batch) {
                                $tocMax = $this->unit->mass($dimaR->USERATEMAX) . $this->unit->massSymbol() . "/batch";
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
}
