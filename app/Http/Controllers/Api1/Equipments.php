<?php

namespace App\Http\Controllers\Api1;

use DB;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\EquipmentsService;
use App\Cryosoft\StudyService;
use App\Cryosoft\StudyEquipmentService;
use App\Models\Equipment;
use App\Models\Study;
use App\Models\Price;
use App\Models\PrecalcLdgRatePrm;
use App\Models\EquipGeneration;
use App\Models\Equipseries;
use App\Models\Equipfamily;
use App\Models\Ramps;
use App\Models\Shelves;
use App\Models\Consumptions;
use App\Kernel\KernelService;
use Illuminate\Support\Facades\Crypt;
use App\Models\EquipCharact;
use App\Models\EquipGenZone;
use App\Models\EquipZone;
use App\Models\MinMax;
use App\Models\StudyEquipment;
use App\Models\CoolingFamily;
use App\Cryosoft\SVGService;
use App\Cryosoft\UnitsService;
use App\Cryosoft\MinMaxService;
use App\Cryosoft\CalculateService;


class Equipments extends Controller
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
     * @var App\Cryosoft\ValueListService
     */
    protected $value;

    /**
     * @var App\Cryosoft\UnitsConverterService
     */
    protected $convert;

    /**
     * @var App\Kernel\KernelService
     */
    protected $kernel;

    /**
     * @var App\Cryosoft\EquipmentsService
     */
    protected $equip;

    /**
     * @var App\Cryosoft\EquipmentsService
     */
    protected $studies;

    /**
     * @var App\Cryosoft\SVGService
     */
    protected $svg;

    /**
     * @var \App\Cryosoft\StudyEquipmentService
     */
    protected $stdeqp;

    /**
     * @var App\Cryosoft\UnitsService
     */
    protected $units;
    
    /**
     * @var App\Cryosoft\MinMaxService
     */
    protected $minmax;
    protected $cal;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth, UnitsConverterService $convert, EquipmentsService $equip
    , KernelService $kernel, StudyService $studies, StudyEquipmentService $stdeqp, SVGService $svg, UnitsService $units, MinMaxService $minmax, CalculateService  $cal)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->convert = $convert;
        $this->equip = $equip;
        $this->kernel = $kernel;
        $this->studies = $studies;
        $this->stdeqp = $stdeqp;
        $this->svg = $svg;
        $this->units = $units;
        $this->minmax = $minmax;
        $this->cal = $cal;
    }

    public function getEquipments()
    {
        $input = $this->request->all();
        $idStudy = (isset($input['idStudy'])) ? $input['idStudy'] : 0;
        $energy = (isset($input['energy'])) ? $input['energy'] : -1;
        $manufacturer = (isset($input['manufacturer'])) ? $input['manufacturer'] : '';
        $sery = (isset($input['sery'])) ? $input['sery'] : -1;
        $origine = (isset($input['origine'])) ? $input['origine'] : -1;
        $process = (isset($input['process'])) ? $input['process'] : -1;
        $model = (isset($input['model'])) ? $input['model'] : -1;
        $size = (isset($input['size'])) ? $input['size'] : '';
        
        $querys = Equipment::query();

        if ($energy != -1) {
            $querys->where('ID_COOLING_FAMILY', $energy);
        }

        $querys->where(function($query) use ($idStudy) {
            $query->where('EQP_IMP_ID_STUDY', $idStudy)
                ->orWhere('EQP_IMP_ID_STUDY', 0);
        });

        $querys->where(function ($query) use ($idStudy) {
            $query->where('EQP_IMP_ID_STUDY', $idStudy)
                ->orWhere('EQP_IMP_ID_STUDY', 0);

            $query->where(function ($q) {
                $q->where('ID_USER', $this->auth->user()->ID_USER)
                    ->where('EQUIP_RELEASE', 2);
            });

            $query->orWhere(function ($q) {
                $q->where('EQUIP_RELEASE', 3)
                    ->orWhere('EQUIP_RELEASE', 4);
            });
        });

        if ($size != '') {
            $sizeLabel = explode('x', $size);
            $length = $sizeLabel[0];
            $width = $sizeLabel[1];

            $querys->where('EQP_LENGTH', $length)->where('EQP_WIDTH', $width);
        }

        if ($sery != -1) {
            $querys->where('ID_FAMILY', $sery);
        }

        if ($process != -1) {
            $querys->where('BATCH_PROCESS', $process);
        }

        if ($model != -1) {
            $querys->where('ID_EQUIPSERIES', $model);
        }

        if ($manufacturer != '') {
            $querys->where('CONSTRUCTOR', $manufacturer);
        }

        $querys->where('EQUIP_RELEASE', '<>', 1);
        $querys->orderBy('EQUIP_NAME');

        $equipments = $querys->get();

        return $equipments;
    }

    public function getSelectionCriteriaFilter()
    {
        $input = $this->request->all();
        $energyId = (isset($input['energy'])) ? $input['energy'] : -1;
        $manufacturerValue = (isset($input['manufacturer'])) ? $input['manufacturer'] : '';
        $seryId = (isset($input['sery'])) ? $input['sery'] : -1;
        $originId = (isset($input['origin'])) ? $input['origin'] : -1;
        $processId = (isset($input['process'])) ? $input['process'] : -1;
        $modelId = (isset($input['model'])) ? $input['model'] : -1;

        $energies = CoolingFamily::distinct()->select('cooling_family.ID_COOLING_FAMILY', 'translation.LABEL')
        ->join('translation', 'cooling_family.ID_COOLING_FAMILY', '=', 'translation.ID_TRANSLATION')
        ->where('translation.TRANS_TYPE', 2)
        ->where('translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('translation.LABEL')
        ->get();

        $manufacturer = $this->getConstructors($energyId);
        $series = $this->getFamilies($energyId, $manufacturerValue);
        $origines = $this->getOrigines($energyId, $manufacturerValue, $seryId);
        $processes = $this->getProcesses($energyId, $manufacturerValue, $seryId, $originId);
        $model = $this->getModel($energyId, $manufacturerValue, $seryId, $originId, $processId);
        $size = $this->getSize($energyId, $manufacturerValue, $seryId, $originId, $processId, $modelId);

        
        return compact('energies', 'manufacturer', 'series', 'origines', 'processes', 'model', 'size');
    }

    public function getConstructors($energy = -1)
    {
        $query = Equipseries::distinct()->select('Equipseries.CONSTRUCTOR')
        ->join('equipment', 'Equipseries.ID_EQUIPSERIES', '=', 'equipment.ID_EQUIPSERIES');

        if ($energy != -1) {
            $query->where('equipment.ID_COOLING_FAMILY', $energy);
        }

        $query->orderBy('Equipseries.CONSTRUCTOR');

        return $query->get();
    }

    public function getFamilies($energy = -1, $manufacturer = '')
    {
        $query = Equipfamily::distinct()->select('equipfamily.ID_FAMILY', 'translation.LABEL')
        ->join('translation', 'equipfamily.ID_FAMILY', '=', 'translation.ID_TRANSLATION')
        ->join('equipseries', 'equipfamily.ID_FAMILY', '=', 'equipseries.ID_FAMILY')
        ->join('equipment', 'equipseries.ID_EQUIPSERIES', '=', 'equipment.ID_EQUIPSERIES')
        ->where('translation.TRANS_TYPE', 5)->where('translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE);

        if ($energy != -1) {
            $query->where('equipment.ID_COOLING_FAMILY', $energy);
        }

        if ($manufacturer != '') {
            $query->where('equipseries.CONSTRUCTOR', $manufacturer);
        }

        $query->orderBy('translation.LABEL');

        $equipFamily = $query->get();
        return $equipFamily;
    }

    public function getOrigines($energy = -1, $manufacturer = '', $family = -1)
    {
        $query = Equipment::distinct()->select('equipment.STD', 'translation.LABEL')
        ->join('translation', 'equipment.STD', '=', 'translation.ID_TRANSLATION')
        ->join('equipseries', 'equipment.ID_EQUIPSERIES', 'equipseries.ID_EQUIPSERIES')
        ->join('equipfamily', 'equipseries.ID_FAMILY', '=', 'equipfamily.ID_FAMILY')
        ->where('translation.TRANS_TYPE', 17)->where('translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE);

        if ($energy != -1) {
            $query->where('equipment.ID_COOLING_FAMILY', $energy);
        }

        if ($manufacturer != null && $manufacturer != '') {
            $query->where('equipseries.CONSTRUCTOR', $manufacturer);
        }

        if ($family != -1) {
            $query->where('equipfamily.ID_FAMILY', $family);
        }

        $query->orderBy('translation.LABEL');

        $equipMents = $query->get();

        return $equipMents;
    }

    public function getProcesses($energy = -1, $manufacturer = '', $family = -1, $origine = -1)
    {
        $query = Equipfamily::distinct()->select('equipfamily.BATCH_PROCESS', 'translation.LABEL')
        ->join('translation', 'equipfamily.BATCH_PROCESS', '=', 'translation.ID_TRANSLATION')
        ->join('equipseries', 'equipfamily.ID_FAMILY', '=', 'equipseries.ID_FAMILY')
        ->join('equipment', 'equipseries.ID_EQUIPSERIES', '=', 'equipment.ID_EQUIPSERIES')
        ->where('translation.TRANS_TYPE', 13)->where('translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE);

        if ($energy != -1) {
            $query->where('equipment.ID_COOLING_FAMILY', $energy);
        }

        if ($manufacturer != null && $manufacturer != '') {
            $query->where('equipseries.CONSTRUCTOR', $manufacturer);
        }

        if ($family != -1) {
            $query->where('equipfamily.ID_FAMILY', $family);
        }

        if ($origine != -1) {
            $query->where('equipment.STD', $origine);
        }

        $query->orderBy('translation.LABEL');

        $processes = $query->get();

        return $processes;
    }

    public function getModel($energy = -1, $manufacturer = '', $family = -1, $origine = -1, $process = -1)
    {
        $query = Equipseries::distinct()->select('equipseries.ID_EQUIPSERIES', 'translation.LABEL')
        ->join('translation', 'equipseries.ID_EQUIPSERIES', '=', 'translation.ID_TRANSLATION')
        ->join('equipfamily', 'equipseries.ID_FAMILY', '=', 'equipfamily.ID_FAMILY')
        ->join('equipment', 'equipseries.ID_EQUIPSERIES', '=', 'equipment.ID_EQUIPSERIES')
        ->where('translation.TRANS_TYPE', 7)->where('translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE);

        if ($energy != -1) {
            $query->where('equipment.ID_COOLING_FAMILY', $energy);
        }

        if ($manufacturer != null && $manufacturer != '') {
            $query->where('equipseries.CONSTRUCTOR', $manufacturer);
        }

        if ($family != -1) {
            $query->where('equipfamily.ID_FAMILY', $family);
        }

        if ($origine != -1) {
            $query->where('equipment.STD', $origine);
        }

        if ($process != -1) {
            $query->where('equipfamily.BATCH_PROCESS', $process);
        }

        $query->orderBy('translation.LABEL');

        $equipSeries = $query->get();

        return $equipSeries;
    }

    public function getSize($energy = -1, $manufacturer = '', $family = -1, $origine = -1, $process = -1, $series = -1)
    {
        $query = Equipment::distinct()->select('equipment.EQP_LENGTH', 'equipment.EQP_WIDTH')
        ->join('equipseries', 'equipment.ID_EQUIPSERIES', '=', 'equipseries.ID_EQUIPSERIES')
        ->join('equipfamily', 'equipseries.ID_FAMILY', '=', 'equipfamily.ID_FAMILY');

        if ($energy != -1) {
            $query->where('equipment.ID_COOLING_FAMILY', $energy);
        }

        if ($manufacturer != null && $manufacturer != '') {
            $query->where('equipseries.CONSTRUCTOR', $manufacturer);
        }

        if ($family != -1) {
            $query->where('equipfamily.ID_FAMILY', $family);
        }

        if ($origine != -1) {
            $query->where('equipment.STD', $origine);
        }

        if ($process != -1) {
            $query->where('equipfamily.BATCH_PROCESS', $process);
        }

        if ($series != -1) {
            $query->where('equipseries.ID_EQUIPSERIES', $series);
        }

        $query->orderBy('equipment.EQP_LENGTH');
        $query->orderBy('equipment.EQP_WIDTH');

        $equipMents = $query->get();

        $result = [];
        if (count($equipMents) > 0) {
            foreach ($equipMents as $key => $value) {
                $result[] = $value;
                $result[$key]['DISPLAY_LENGTH'] = $this->convert->equipDimension($value->EQP_LENGTH);
                $result[$key]['DISPLAY_WIDTH'] = $this->convert->equipDimension($value->EQP_WIDTH);
            }
        }
        return $result;
    }

    public function findRefEquipment()
    {
        $mine = Equipment::where('ID_USER', $this->auth->user()->ID_USER)->orderBy('EQUIP_NAME', 'ASC')->get();

        $others = Equipment::where('ID_USER', '!=', $this->auth->user()->ID_USER)->orderBy('EQUIP_NAME', 'ASC')->get();

        foreach ($mine as $key) {
            $key->capabilitiesCalc = $this->equip->getCapability($key->CAPABILITIES, 65536);
            $key->capabilitiesCalc256 = $this->equip->getCapability($key->CAPABILITIES, 256);
            $key->timeSymbol = $this->convert->timeSymbolUser();
            $key->temperatureSymbol = $this->convert->temperatureSymbolUser();
            $key->dimensionSymbol = $this->convert->equipDimensionSymbolUser();
            $key->consumptionSymbol1 = $this->convert->consumptionSymbolUser($key->ID_COOLING_FAMILY, 1);
            $key->consumptionSymbol2 = $this->convert->consumptionSymbolUser($key->ID_COOLING_FAMILY, 2);
            $key->consumptionSymbol3 = $this->convert->consumptionSymbolUser($key->ID_COOLING_FAMILY, 3);
            $key->shelvesWidthSymbol = $this->convert->shelvesWidthSymbol();
            $key->rampsPositionSymbol = $this->convert->rampsPositionSymbol();
            $key->EQP_LENGTH = $this->units->equipDimension($key->EQP_LENGTH, 2, 1);            
            $key->EQP_WIDTH = $this->units->equipDimension($key->EQP_WIDTH, 2, 1);
            $key->EQP_HEIGHT = $this->units->equipDimension($key->EQP_HEIGHT, 2, 1);
            $key->MAX_FLOW_RATE = doubleval($this->units->consumption($key->MAX_FLOW_RATE, $key->ID_COOLING_FAMILY, 1, 2, 1));
            $key->TMP_REGUL_MIN = $this->units->controlTemperature($key->TMP_REGUL_MIN, 0, 1);

            $equipGener = EquipGeneration::find($key->ID_EQUIPGENERATION);
        
            if ($equipGener) { 
                $equipGener->TEMP_SETPOINT = doubleval($this->units->controlTemperature($equipGener->TEMP_SETPOINT, 2, 1));
                $equipGener->DWELLING_TIME = $this->units->time($equipGener->DWELLING_TIME, 2, 1);
                $equipGener->NEW_POS = $this->units->time($equipGener->NEW_POS, 2, 1);
            }
            $key->equipGeneration = $equipGener;
        }

        foreach ($others as $key) {
            $key->capabilitiesCalc = $this->equip->getCapability($key->CAPABILITIES, 65536);
            $key->capabilitiesCalc256 = $this->equip->getCapability($key->CAPABILITIES, 256);
            $key->timeSymbol = $this->convert->timeSymbolUser();
            $key->temperatureSymbol = $this->convert->temperatureSymbolUser();
            $key->dimensionSymbol = $this->convert->equipDimensionSymbolUser();
            $key->consumptionSymbol1 = $this->convert->consumptionSymbolUser($key->ID_COOLING_FAMILY, 1);
            $key->consumptionSymbol2 = $this->convert->consumptionSymbolUser($key->ID_COOLING_FAMILY, 2);
            $key->consumptionSymbol3 = $this->convert->consumptionSymbolUser($key->ID_COOLING_FAMILY, 3);
            $key->shelvesWidthSymbol = $this->convert->shelvesWidthSymbol();
            $key->rampsPositionSymbol = $this->convert->rampsPositionSymbol();

            $key->EQP_LENGTH = $this->units->equipDimension($key->EQP_LENGTH, 2, 1);
            $key->EQP_WIDTH = $this->units->equipDimension($key->EQP_WIDTH, 2, 1);
            $key->EQP_HEIGHT = $this->units->equipDimension($key->EQP_HEIGHT, 2, 1);
            $key->MAX_FLOW_RATE = $this->units->consumption($key->MAX_FLOW_RATE, $key->ID_COOLING_FAMILY, 1, 2, 1);
            $key->TMP_REGUL_MIN = $this->units->controlTemperature($key->TMP_REGUL_MIN, 0, 1);

            $equipGener = EquipGeneration::find($key->ID_EQUIPGENERATION);
        
            if ($equipGener) { 
                $equipGener->TEMP_SETPOINT = doubleval($this->units->controlTemperature($equipGener->TEMP_SETPOINT, 2, 1));
                $equipGener->DWELLING_TIME = $this->units->time($equipGener->DWELLING_TIME, 2, 1);
                $equipGener->NEW_POS = $this->units->time($equipGener->NEW_POS, 2, 1);
            }
            $key->equipGeneration = $equipGener;
        }

        $array  = [
            'mine' => $mine,
            'others' => $others,
            'ID_USER' => $this->auth->user()->ID_USER,
        ];

        return $array;
    }

    public function newEquipment()
    {
        $current = Carbon::now('Asia/Ho_Chi_Minh');
        $idUserLogon = $this->auth->user()->ID_USER;
        $input = $this->request->all();

        $nameE = $typeCalculate = $versionE = $equipId1 = $equipId2 = $tempSetPoint = $dwellingTime = $newPos = $typeEquipment = null;
        
        if (isset($input['typeEquipment'])) $typeEquipment = intval($input['typeEquipment']);
        if (isset($input['nameEquipment'])) $nameE = $input['nameEquipment'];
        if (isset($input['versionEquipment'])) $versionE = floatval($input['versionEquipment']);
        if (isset($input['typeCalculate'])) $typeCalculate = intval($input['typeCalculate']);
        
        if ($typeEquipment == 0) {
            if (isset($input['equipmentId1'])) $equipId1 = intval($input['equipmentId1']);
            if (isset($input['tempSetPoint'])) $tempSetPoint = $this->units->controlTemperature(floatval($input['tempSetPoint']), 2, 0);
            if (isset($input['dwellingTime'])) $dwellingTime = $this->units->time(floatval($input['dwellingTime']), 2, 0);
        } else if ($typeEquipment == 1) {
            if (isset($input['equipmentId1'])) $equipId1 = intval($input['equipmentId1']);
            if (isset($input['dwellingTime'])) $dwellingTime = $this->units->time(floatval($input['dwellingTime']), 2, 0);
            if (isset($input['newPos'])) $newPos = $this->units->time(floatval($input['newPos']), 2, 0);
        } else if ($typeEquipment == 2) {
            if (isset($input['equipmentId1'])) $equipId1 = intval($input['equipmentId1']);
        } else {
            if (isset($input['equipmentId1'])) $equipId1 = intval($input['equipmentId1']);
            if (isset($input['equipmentId2'])) $equipId2 = intval($input['equipmentId2']);
            if (isset($input['dwellingTime'])) $dwellingTime = $this->units->time(floatval($input['dwellingTime']), 2, 0);
            if (isset($input['tempSetPoint'])) $tempSetPoint = $this->units->controlTemperature(floatval($input['tempSetPoint']), 2, 0);
        }
        $equipGenZone = $input['equipGenZone'];

        if (!$this->checkNameAndVersion($nameE, $versionE)) return -4;
        
        $equipment1 = Equipment::find($equipId1);
        
        if ($equipment1) {
            $newEquip = new Equipment();
            $newEquip->ID_USER = $idUserLogon;
            $newEquip->EQUIP_NAME = $nameE;
            $newEquip->EQUIP_VERSION = $versionE;
            $newEquip->EQUIP_RELEASE = 2;
            $newEquip->STD = 0;
            $newEquip->OPEN_BY_OWNER = false;
            $mask = 1096;
            $capabilities = $equipment1->CAPABILITIES;

            if ($this->equip->getCapability($capabilities, 65536)) {
                $mask |= 0x40002;
            } else {
                $mask |= 0x80001;
            }
            $capabilities &= ($mask ^ 0xFFFFFFFFFFF); //0xFFFFFFFFFFFFFFF
            $newEquip->CAPABILITIES = $capabilities;

            if (count($newEquip->EQUIP_COMMENT) == 0) {
                $comment = 'Create on ' . $current->toDateTimeString() . ' by ' . $this->auth->user()->USERNAM;
            } else if (count($newEquip->EQUIP_COMMENT) < 2100) {
                $comment = $newEquip->EQUIP_COMMENT . '. Create on ' . $current->toDateTimeString() . ' by ' . $this->auth->user()->USERNAM;
            } else {
                $comment = substr($newEquip->EQUIP_COMMENT, 0, 1999) . '. Create on ' . $current->toDateTimeString() . ' by ' . $this->auth->user()->USERNAM;;
            }

            $newEquip->EQUIP_COMMENT = $comment;
            $newEquip->DLL_IDX = $equipment1->DLL_IDX;
            $newEquip->ID_EQUIPSERIES = $this->mapToGeneratedEqp($equipment1->ID_EQUIPSERIES);
            $newEquip->ID_COOLING_FAMILY = $equipment1->ID_COOLING_FAMILY;
            $newEquip->ID_EQUIPGENERATION = 0;
            $newEquip->EQUIPPICT = $equipment1->EQUIPPICT;
            $newEquip->EQP_LENGTH = $equipment1->EQP_LENGTH;
            $newEquip->EQP_WIDTH = $equipment1->EQP_WIDTH;
            $newEquip->EQP_HEIGHT = $equipment1->EQP_HEIGHT;
            $newEquip->MODUL_LENGTH = $equipment1->MODUL_LENGTH;
            $newEquip->NB_MAX_MODUL = $equipment1->NB_MAX_MODUL;
            $newEquip->NB_TR = $equipment1->NB_TR;
            $newEquip->NB_TS = $equipment1->NB_TS;
            $newEquip->NB_VC = $equipment1->NB_VC;
            $newEquip->BUYING_COST = $equipment1->BUYING_COST;
            $newEquip->RENTAL_COST = $equipment1->RENTAL_COST;
            $newEquip->INSTALL_COST = $equipment1->INSTALL_COST;
            $newEquip->MAX_FLOW_RATE = $equipment1->MAX_FLOW_RATE;
            $newEquip->MAX_NOZZLES_BY_RAMP = $equipment1->MAX_NOZZLES_BY_RAMP;
            $newEquip->MAX_RAMPS = $equipment1->MAX_RAMPS;
            $newEquip->NUMBER_OF_ZONES = $equipment1->NUMBER_OF_ZONES;
            $newEquip->TMP_REGUL_MIN = $equipment1->TMP_REGUL_MIN;
            $newEquip->ITEM_TR = $equipment1->ITEM_TR;
            $newEquip->ITEM_TS = $equipment1->ITEM_TS;
            $newEquip->ITEM_VC = $equipment1->ITEM_VC;
            $newEquip->ITEM_PRECIS = $equipment1->ITEM_PRECIS;
            $newEquip->ITEM_TIMESTEP = $equipment1->ITEM_TIMESTEP;
            $newEquip->FATHER_DLL_IDX = $equipment1->FATHER_DLL_IDX;
            $newEquip->EQP_IMP_ID_STUDY = $equipment1->EQP_IMP_ID_STUDY;
            $newEquip->save();

            if ($newEquip->ID_EQUIP) {
                Equipment::where('ID_EQUIP', $newEquip->ID_EQUIP)->update(['EQUIP_DATE' => $current->toDateTimeString()]);
                // $this->getDecryptBinary($equipment1->ID_EQUIP, $newEquip->ID_EQUIP);
            }

            // add paramester equip generation
            $minMaxAvg = $this->getMinMax(1066);
            $minMaxDwell = $this->getMinMax($equipment1->ITEM_TS);
            $minMaxTemp = $this->getMinMax(1093);
            $equipGen = EquipGeneration::find($equipment1->ID_EQUIPGENERATION);
            $eqpGenLoadRate = $avgProdintemp = $rotate = $posChange = null;

            if ($typeEquipment == 0) {
                $minMaxTemp = $this->getMinMax($equipment1->ITEM_TR);
                $tempSetPoint = floatval($minMaxTemp->DEFAULT_VALUE);
                $dwellingTime = floatval($minMaxDwell->DEFAULT_VALUE);

                if ($this->equip->getCapability($equipment1->CAPABILITIES, 65536)) {
                    $dwellingTime = $this->units->time(floatval($input['tempSetPoint']), 2, 0);
                } else {
                    $tempSetPoint = $this->units->controlTemperature(floatval($input['tempSetPoint']), 2, 0);
                }

            } else if ($typeEquipment == 1) {
                if ($equipGen) {
                    $tempSetPoint = $equipGen->TEMP_SETPOINT;
                    $dwellingTime = $equipGen->DWELLING_TIME;
                    $eqpGenLoadRate = $equipGen->EQP_GEN_LOADRATE;
                    $avgProdintemp = $equipGen->AVG_PRODINTEMP;
                    $posChange = 1;
                }
            } else if ($typeEquipment == 2) {
                if ($equipGen) {
                    $tempSetPoint = $equipGen->TEMP_SETPOINT;
                    $dwellingTime = $equipGen->DWELLING_TIME;
                    $eqpGenLoadRate = $equipGen->EQP_GEN_LOADRATE;
                    $avgProdintemp = $equipGen->AVG_PRODINTEMP;
                    $rotate  = 1;
                }
            } else if ($typeEquipment == 3) {
                $minMaxTemp = $this->getMinMax($equipment1->ITEM_TR);
                $tempSetPoint = floatval($minMaxTemp->DEFAULT_VALUE);
                $dwellingTime = floatval($minMaxDwell->DEFAULT_VALUE);

                $_equip1 = Equipment::find($equipId1);
                $_equip2 = Equipment::find($equipId2);

                if ($_equip1 && $_equip2 &&
                    $this->equip->getCapability($_equip1->CAPABILITIES, 65536) &&
                    $this->equip->getCapability($_equip2->CAPABILITIES, 65536)) {
                    $dwellingTime = $this->units->time(floatval($input['tempSetPoint']), 2, 0);
                } else {
                    $tempSetPoint = $this->units->controlTemperature(floatval($input['tempSetPoint']), 2, 0);
                }
            }

            $equipGeneration = new EquipGeneration();
            $equipGeneration->ID_EQUIP = $newEquip->ID_EQUIP;
            $equipGeneration->ID_ORIG_EQUIP1 = $equipId1;
            $equipGeneration->ID_ORIG_EQUIP2 = ($equipId2 != null) ? $equipId2 : 0;
            $equipGeneration->AVG_PRODINTEMP = $minMaxAvg->DEFAULT_VALUE;
            $equipGeneration->TEMP_SETPOINT = ($tempSetPoint != null) ? $tempSetPoint : 0;
            $equipGeneration->DWELLING_TIME = ($dwellingTime > 0) ? $dwellingTime : $minMaxDwell->DEFAULT_VALUE;
            $equipGeneration->MOVING_CHANGE = 0;
            $equipGeneration->MOVING_POS = 0;
            $equipGeneration->ROTATE = ($rotate > 0) ? $rotate : 0;;
            $equipGeneration->POS_CHANGE = ($posChange > 0) ? $posChange : 0;
            $equipGeneration->NEW_POS = ($newPos != null) ? $newPos : 0;
            $equipGeneration->EQP_GEN_STATUS = 0;
            $equipGeneration->EQP_GEN_LOADRATE = ($eqpGenLoadRate > 0) ? $eqpGenLoadRate : 0;
            $equipGeneration->save();
            Equipment::where('ID_EQUIP', $newEquip->ID_EQUIP)->update(['ID_EQUIPGENERATION' => $equipGeneration->ID_EQUIPGENERATION]);

            $this->copyRamps($equipment1->ID_EQUIP, $newEquip->ID_EQUIP);
            $this->copyConsumptions($equipment1->ID_EQUIP, $newEquip->ID_EQUIP);
            $this->copyShelves($equipment1->ID_EQUIP, $newEquip->ID_EQUIP);
            $this->copyEquipCharact($equipment1->ID_EQUIP, $newEquip->ID_EQUIP);
            $this->duplicateEquipGenZone($equipGeneration->ID_EQUIPGENERATION, $equipGenZone, $equipment1->NUMBER_OF_ZONES);
            $this->duplicateEquipZone($equipment1->ID_EQUIP, $newEquip->ID_EQUIP);
            $newEquip->ID_EQUIPGENERATION = $equipGeneration->ID_EQUIPGENERATION;
            $newEquip->save();

            if ($typeCalculate == 1) {
                if (!$this->runEquipmentCalculation($newEquip->ID_EQUIPGENERATION)) {
                    $this->deleteEquipment($newEquip->ID_EQUIP);
                    return -5;
                }
            }
        }

        $equipRs =  Equipment::find($newEquip->ID_EQUIP);

        $equipRs->capabilitiesCalc = $this->equip->getCapability($equipRs->CAPABILITIES, 65536);
        $equipRs->capabilitiesCalc256 = $this->equip->getCapability($equipRs->CAPABILITIES, 256);
        $equipRs->timeSymbol = $this->convert->timeSymbolUser();
        $equipRs->temperatureSymbol = $this->convert->temperatureSymbolUser();
        $equipRs->dimensionSymbol = $this->convert->equipDimensionSymbolUser();
        $equipRs->consumptionSymbol1 = $this->convert->consumptionSymbolUser($equipRs->ID_COOLING_FAMILY, 1);
        $equipRs->consumptionSymbol2 = $this->convert->consumptionSymbolUser($equipRs->ID_COOLING_FAMILY, 2);
        $equipRs->consumptionSymbol3 = $this->convert->consumptionSymbolUser($equipRs->ID_COOLING_FAMILY, 3);
        $equipRs->shelvesWidthSymbol = $this->convert->shelvesWidthSymbol();
        $equipRs->rampsPositionSymbol = $this->convert->rampsPositionSymbol();

        $equipRs->EQP_LENGTH = $this->convert->equipDimensionUser($equipRs->EQP_LENGTH);
        $equipRs->EQP_WIDTH = $this->convert->equipDimensionUser($equipRs->EQP_WIDTH);
        $equipRs->EQP_HEIGHT = $this->convert->equipDimensionUser($equipRs->EQP_HEIGHT);
        $equipRs->MAX_FLOW_RATE = $this->convert->consumptionUser($equipRs->MAX_FLOW_RATE, $equipRs->ID_COOLING_FAMILY, 1);
        $equipRs->TMP_REGUL_MIN = $this->convert->controlTemperatureUser($equipRs->TMP_REGUL_MIN);

        $equipGenerRs = EquipGeneration::find($equipRs->ID_EQUIPGENERATION);
    
        if ($equipGenerRs) { 
            $equipGenerRs->TEMP_SETPOINT = doubleval($this->convert->controlTemperatureUser($equipGenerRs->TEMP_SETPOINT));
            $equipGenerRs->DWELLING_TIME = doubleval($this->convert->timeUser($equipGenerRs->DWELLING_TIME));
            $equipGenerRs->NEW_POS = doubleval($this->convert->timeUser($equipGenerRs->NEW_POS));
        }
        $equipRs->equipGeneration = $equipGenerRs;

        return $equipRs;
    }

    public function getInputEquipment($idEquip)
    {
        $equipRs =  Equipment::find($idEquip);

        if ($equipRs) {
            $equipRs->capabilitiesCalc = $this->equip->getCapability($equipRs->CAPABILITIES, 65536);
            $equipRs->capabilitiesCalc256 = $this->equip->getCapability($equipRs->CAPABILITIES, 256);
            $equipRs->timeSymbol = $this->convert->timeSymbolUser();
            $equipRs->temperatureSymbol = $this->convert->temperatureSymbolUser();
            $equipRs->dimensionSymbol = $this->convert->equipDimensionSymbolUser();
            $equipRs->consumptionSymbol1 = $this->convert->consumptionSymbolUser($equipRs->ID_COOLING_FAMILY, 1);
            $equipRs->consumptionSymbol2 = $this->convert->consumptionSymbolUser($equipRs->ID_COOLING_FAMILY, 2);
            $equipRs->consumptionSymbol3 = $this->convert->consumptionSymbolUser($equipRs->ID_COOLING_FAMILY, 3);
            $equipRs->shelvesWidthSymbol = $this->convert->shelvesWidthSymbol();
            $equipRs->rampsPositionSymbol = $this->convert->rampsPositionSymbol();

            $equipRs->EQP_LENGTH = $this->convert->equipDimensionUser($equipRs->EQP_LENGTH);
            $equipRs->EQP_WIDTH = $this->convert->equipDimensionUser($equipRs->EQP_WIDTH);
            $equipRs->EQP_HEIGHT = $this->convert->equipDimensionUser($equipRs->EQP_HEIGHT);
            $equipRs->MAX_FLOW_RATE = $this->convert->consumptionUser($equipRs->MAX_FLOW_RATE, $equipRs->ID_COOLING_FAMILY, 1);
            $equipRs->TMP_REGUL_MIN = $this->convert->controlTemperatureUser($equipRs->TMP_REGUL_MIN);

            $equipGenerRs = EquipGeneration::find($equipRs->ID_EQUIPGENERATION);
        
            if ($equipGenerRs) { 
                $equipGenerRs->TEMP_SETPOINT = doubleval($this->convert->controlTemperatureUser($equipGenerRs->TEMP_SETPOINT));
                $equipGenerRs->DWELLING_TIME = doubleval($this->convert->timeUser($equipGenerRs->DWELLING_TIME));
                $equipGenerRs->NEW_POS = doubleval($this->convert->timeUser($equipGenerRs->NEW_POS));
            }
            $equipRs->equipGeneration = $equipGenerRs;
        }

        return $equipRs;
    }

    public function saveAsEquipment()
    {
        $current = Carbon::now('Asia/Ho_Chi_Minh');
        $idUserLogon = $this->auth->user()->ID_USER;
        $input = $this->request->all();

        $nameEquipment  = $versionEquipment = $idEquip = null;
        
        if (isset($input['nameEquipment'])) $nameEquipment = $input['nameEquipment'];
        if (isset($input['versionEquipment'])) $versionEquipment = floatval($input['versionEquipment']);
        if (isset($input['equipmentId'])) $idEquip = intval($input['equipmentId']);

        if ($nameEquipment == null) return -1;
        if (($idEquip == null) || ($idEquip == 0)) return -2;
        if ($versionEquipment == null) return -3;
        if (!$this->checkNameAndVersion($nameEquipment, $versionEquipment)) return -4;

        $equipmentId = Equipment::find($idEquip);

        if ($equipmentId) {
            $newEquip = new Equipment();

            $newEquip->ID_USER = $idUserLogon;
            $newEquip->EQUIP_NAME = $nameEquipment;
            $newEquip->EQUIP_VERSION = $versionEquipment;
            $newEquip->EQUIP_RELEASE = 2;
            $newEquip->STD = $equipmentId->STD;
            $newEquip->OPEN_BY_OWNER = false;
            $newEquip->CAPABILITIES = $equipmentId->CAPABILITIES;

            if (count($newEquip->EQUIP_COMMENT) == 0) {
                $comment = 'Create on ' . $current->toDateTimeString() . ' by ' . $this->auth->user()->USERNAM;
            } else if (count($newEquip->EQUIP_COMMENT) < 2100) {
                $comment = $newEquip->EQUIP_COMMENT . '. Create on ' . $current->toDateTimeString() . ' by ' . $this->auth->user()->USERNAM;
            } else {
                $comment = substr($newEquip->EQUIP_COMMENT, 0, 1999) . '. Create on ' . $current->toDateTimeString() . ' by ' . $this->auth->user()->USERNAM;;
            }

            $newEquip->EQUIP_COMMENT = $comment;
            $newEquip->ID_EQUIPSERIES = $equipmentId->ID_EQUIPSERIES;
            $newEquip->ID_COOLING_FAMILY = $equipmentId->ID_COOLING_FAMILY;
            $newEquip->ID_EQUIPGENERATION = $equipmentId->ID_EQUIPGENERATION;
            $newEquip->DLL_IDX = $equipmentId->DLL_IDX;
            $newEquip->EQUIPPICT = $equipmentId->EQUIPPICT;
            $newEquip->EQP_LENGTH = $equipmentId->EQP_LENGTH;
            $newEquip->EQP_WIDTH = $equipmentId->EQP_WIDTH;
            $newEquip->EQP_HEIGHT = $equipmentId->EQP_HEIGHT;
            $newEquip->MODUL_LENGTH = $equipmentId->MODUL_LENGTH;
            $newEquip->NB_MAX_MODUL = $equipmentId->NB_MAX_MODUL;
            $newEquip->NB_TR = $equipmentId->NB_TR;
            $newEquip->NB_TS = $equipmentId->NB_TS;
            $newEquip->NB_VC = $equipmentId->NB_VC;
            $newEquip->BUYING_COST = $equipmentId->BUYING_COST;
            $newEquip->RENTAL_COST = $equipmentId->RENTAL_COST;
            $newEquip->INSTALL_COST = $equipmentId->INSTALL_COST;
            $newEquip->MAX_FLOW_RATE = $equipmentId->MAX_FLOW_RATE;
            $newEquip->MAX_NOZZLES_BY_RAMP = $equipmentId->MAX_NOZZLES_BY_RAMP;
            $newEquip->MAX_RAMPS = $equipmentId->MAX_RAMPS;
            $newEquip->NUMBER_OF_ZONES = $equipmentId->NUMBER_OF_ZONES;
            $newEquip->TMP_REGUL_MIN = $equipmentId->TMP_REGUL_MIN;
            $newEquip->ITEM_TR = $equipmentId->ITEM_TR;
            $newEquip->ITEM_TS = $equipmentId->ITEM_TS;
            $newEquip->ITEM_VC = $equipmentId->ITEM_VC;
            $newEquip->ITEM_PRECIS = $equipmentId->ITEM_PRECIS;
            $newEquip->ITEM_TIMESTEP = $equipmentId->ITEM_TIMESTEP;
            $newEquip->EQP_IMP_ID_STUDY = $equipmentId->EQP_IMP_ID_STUDY;
            $newEquip->FATHER_DLL_IDX = $equipmentId->FATHER_DLL_IDX;
            $newEquip->save();

            if ($newEquip->ID_EQUIP) {
                Equipment::where('ID_EQUIP', $newEquip->ID_EQUIP)->update(['EQUIP_DATE' => $current->toDateTimeString()]);
                // $this->getDecryptBinary($equipmentId->ID_EQUIP, $newEquip->ID_EQUIP);
            }
            
            $this->copyRamps($equipmentId->ID_EQUIP, $newEquip->ID_EQUIP);
            $this->copyConsumptions($equipmentId->ID_EQUIP, $newEquip->ID_EQUIP);
            $this->copyShelves($equipmentId->ID_EQUIP, $newEquip->ID_EQUIP);
            $this->copyEquipCharact($equipmentId->ID_EQUIP, $newEquip->ID_EQUIP);

            $oldGeneration = EquipGeneration::where('ID_EQUIP', $equipmentId->ID_EQUIP)->first();

            if (count($oldGeneration) > 0) {
                $equipGeneration = new EquipGeneration();
                $equipGeneration->ID_EQUIP = $newEquip->ID_EQUIP;
                $equipGeneration->ID_ORIG_EQUIP1 = $oldGeneration->ID_ORIG_EQUIP1;
                $equipGeneration->ID_ORIG_EQUIP2 = $oldGeneration->ID_ORIG_EQUIP2;
                $equipGeneration->AVG_PRODINTEMP = $oldGeneration->AVG_PRODINTEMP;
                $equipGeneration->TEMP_SETPOINT = $oldGeneration->TEMP_SETPOINT;
                $equipGeneration->DWELLING_TIME = $oldGeneration->DWELLING_TIME;
                $equipGeneration->MOVING_CHANGE = $oldGeneration->MOVING_CHANGE;
                $equipGeneration->MOVING_POS = $oldGeneration->MOVING_POS;
                $equipGeneration->ROTATE = $oldGeneration->ROTATE;
                $equipGeneration->POS_CHANGE = $oldGeneration->POS_CHANGE;
                $equipGeneration->NEW_POS = $oldGeneration->NEW_POS;
                $equipGeneration->EQP_GEN_STATUS = $oldGeneration->EQP_GEN_STATUS;
                $equipGeneration->EQP_GEN_LOADRATE = $oldGeneration->EQP_GEN_LOADRATE;
                $equipGeneration->save();
                
                $equipGenZone = $this->getEquipmentFilter($idEquip);
                $this->duplicateEquipGenZone($equipGeneration->ID_EQUIPGENERATION, $equipGenZone, $equipmentId->NUMBER_OF_ZONES);
            }

            $this->duplicateEquipZone($equipmentId->ID_EQUIP, $newEquip->ID_EQUIP);
        }

        return 1;
    }

    public function mapToGeneratedEqp($idEquipSeries)
    {
        $idEs = 0;
        $equipSeries = Equipseries::find($idEquipSeries);

        if ($equipSeries) {

            if ($equipSeries->ID_FAMILY != 0) {

                $equipFamily = Equipfamily::find($equipSeries->ID_FAMILY);
                
                if ($equipFamily) {

                    if ($equipFamily->BATCH_PROCESS == 0) {
                        $idEs = 42;
                    } else {
                        $idEs = 43;
                    }
                }
            }
        }

        return $idEs;
    }

    public function getEquipmentFamily()
    {
        $list = Equipfamily::join('Translation', 'ID_FAMILY', '=', 'Translation.ID_TRANSLATION')
        ->where('Translation.TRANS_TYPE', 5)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->get();
        
        return $list;
    }

    public function getEquipmentSeries() 
    {
        $input = $this->request->all();

        if (isset($input['idFamily'])) $idFamily = $input['idFamily'];

        if (!$idFamily) {
            $list = Equipseries::orderBy('SERIES_NAME', 'ASC')->get();
        } else {
            $list = Equipseries::where('ID_FAMILY', $idFamily)->orderBy('SERIES_NAME', 'ASC')->get();
        }
        
        return $list;
    }

    public function getRamps()
    {
        $input = $this->request->all();
        $list = [];

        if (isset($input['idEquip'])) $idEquip = $input['idEquip'];

        if ($idEquip) {
            $list = Ramps::where('ID_EQUIP', $idEquip)->orderBy('POSITION', 'ASC')->get();

            foreach ( $list as $ramps) {
                $ramps->POSITION = $this->units->rampsPosition($ramps->POSITION, 2, 1);
            }
        }

        return $list;
    }

    public function getShelves()
    {
        $input = $this->request->all();
        $list = [];

        if (isset($input['idEquip'])) $idEquip = $input['idEquip'];

        if ($idEquip) {
            $list = Shelves::where('ID_EQUIP', $idEquip)->orderBy('SPACE', 'ASC')->get();

            foreach ( $list as $shelves) {
                $shelves->SPACE = $this->units->shelvesWidth($shelves->SPACE, 2, 1);
            }
        }

        return $list;
    }

    public function getConsumptions()
    {
        $input = $this->request->all();
        $consumptions = [];
        if (isset($input['idEquip'])) $idEquip = $input['idEquip'];
        
        $equip = Equipment::find($idEquip);
        $consumptions = Consumptions::where('ID_EQUIP', $idEquip)->get();

        if (count($consumptions) > 0) {
            foreach ($consumptions as $consumption) {
                if ($this->equip->getCapability($equip->CAPABILITIES, 65536)) {
                    $consumption->TEMPERATURE = $this->units->time($consumption->TEMPERATURE, 2, 1);
                } else {
                    $consumption->TEMPERATURE = $this->units->controlTemperature($consumption->TEMPERATURE, 2, 1);
                }

                $consumption->CONSUMPTION_PERM = $this->units->consumption($consumption->CONSUMPTION_PERM, $equip->ID_COOLING_FAMILY, 2, 2, 1);
                $consumption->CONSUMPTION_GETCOLD = $this->units->consumption($consumption->CONSUMPTION_GETCOLD, $equip->ID_COOLING_FAMILY, 3, 2, 1);
            }
        }

        return $consumptions;
    }

    public function getEquipmentCharacts($idEquip)
    {
        $equipCharacts = EquipCharact::where('ID_EQUIP', $idEquip)->orderBy('X_POSITION', 'ASC')->get();
        $equipment = Equipment::find($idEquip);
        if (count($equipCharacts) > 0 && $equipment && ($equipment->STD == 0)) {
            foreach ($equipCharacts as $equipCharact) {
                $equipCharact->X_POSITION = $this->units->time(floatval($equipCharact->X_POSITION), 1, 1);
                $equipCharact->ALPHA_TOP = $this->units->convectionCoeff($equipCharact->ALPHA_TOP, 2, 1);
                $equipCharact->ALPHA_BOTTOM = $this->units->convectionCoeff($equipCharact->ALPHA_BOTTOM, 2, 1);
                $equipCharact->ALPHA_LEFT = $this->units->convectionCoeff($equipCharact->ALPHA_LEFT, 2, 1);
                $equipCharact->ALPHA_RIGHT = $this->units->convectionCoeff($equipCharact->ALPHA_RIGHT, 2, 1);
                $equipCharact->ALPHA_FRONT = $this->units->convectionCoeff($equipCharact->ALPHA_FRONT, 2, 1);
                $equipCharact->ALPHA_REAR = $this->units->convectionCoeff($equipCharact->ALPHA_REAR, 2, 1);

                $equipCharact->TEMP_TOP = $this->units->temperature($equipCharact->TEMP_TOP, 2, 1);
                $equipCharact->TEMP_BOTTOM = $this->units->temperature($equipCharact->TEMP_BOTTOM, 2, 1);
                $equipCharact->TEMP_LEFT = $this->units->temperature($equipCharact->TEMP_LEFT, 2, 1);
                $equipCharact->TEMP_RIGHT = $this->units->temperature($equipCharact->TEMP_RIGHT, 2, 1);
                $equipCharact->TEMP_FRONT = $this->units->temperature($equipCharact->TEMP_FRONT, 2, 1);
                $equipCharact->TEMP_REAR = $this->units->temperature($equipCharact->TEMP_REAR, 2, 1);
            }
        } else if ((count($equipCharacts) < 0) && $equipment && ($equipment->STD != 0)) {
            // some code here
        }
        return $equipCharacts;
    }

    public function deleteEquipCharacts($idEquip)
    {
        $equipCharact = EquipCharact::where('ID_EQUIP', $idEquip)->get();
        if (count($equipCharact) > 0) { 
            EquipCharact::where('ID_EQUIP', $idEquip)->delete();
        }
        return 1;
    }

    public function addEquipCharact()
    {
        $input = $this->request->all();

        $ID_EQUIP = $X_POSITION = $ecPrevious = $ecNext = null;
        $bAbort = false;
        $result = -1;

        if (isset($input['ID_EQUIP'])) $ID_EQUIP = intval($input['ID_EQUIP']);
        if (isset($input['X_POSITION'])) $X_POSITION = floatval($input['X_POSITION']);

        $equipCharacts = EquipCharact::where('ID_EQUIP', $ID_EQUIP)->get();

        if (count($equipCharacts) >= 120) {
            $bAbort = true;
            return -2;
        }

        if ((count($equipCharacts) > 0) && (!$bAbort)) {
            foreach ($equipCharacts as $equipCharact) {
                if (floatval($equipCharact->X_POSITION) < $X_POSITION) {
                    $ecPrevious = $equipCharact;
                }

                if (floatval($equipCharact->X_POSITION) == $X_POSITION) {
                    $bAbort = true;
                }

                if ((floatval($equipCharact->X_POSITION) > $X_POSITION) && ($ecNext == null)) {
                    $ecNext = $equipCharact;
                }
            }
        }

        if (!$bAbort) {
            $ec = new EquipCharact();
            if (($ecNext != null) && ($ecPrevious != null)) {
                $ec->ID_EQUIP = $ecPrevious->ID_EQUIP;
                $ec->X_POSITION = round($X_POSITION, 1);
                $ec->TEMP_REGUL = $ecPrevious->TEMP_REGUL;
                $ec->ALPHA_TOP = $this->interpolate($ecPrevious->X_POSITION, $ecNext->X_POSITION, $ecPrevious->ALPHA_TOP, $ecNext->ALPHA_TOP, $X_POSITION);
                $ec->ALPHA_BOTTOM = $this->interpolate($ecPrevious->X_POSITION, $ecNext->X_POSITION, $ecPrevious->ALPHA_BOTTOM, $ecNext->ALPHA_BOTTOM, $X_POSITION);
                $ec->ALPHA_LEFT = $this->interpolate($ecPrevious->X_POSITION, $ecNext->X_POSITION, $ecPrevious->ALPHA_LEFT, $ecNext->ALPHA_LEFT, $X_POSITION);
                $ec->ALPHA_RIGHT = $this->interpolate($ecPrevious->X_POSITION, $ecNext->X_POSITION, $ecPrevious->ALPHA_RIGHT, $ecNext->ALPHA_RIGHT, $X_POSITION);
                $ec->ALPHA_FRONT = $this->interpolate($ecPrevious->X_POSITION, $ecNext->X_POSITION, $ecPrevious->ALPHA_FRONT, $ecNext->ALPHA_FRONT, $X_POSITION);
                $ec->ALPHA_REAR = $this->interpolate($ecPrevious->X_POSITION, $ecNext->X_POSITION, $ecPrevious->ALPHA_REAR, $ecNext->ALPHA_REAR, $X_POSITION);
                $ec->TEMP_TOP = $this->interpolate($ecPrevious->X_POSITION, $ecNext->X_POSITION, $ecPrevious->TEMP_TOP, $ecNext->TEMP_TOP, $X_POSITION);
                $ec->TEMP_BOTTOM = $this->interpolate($ecPrevious->X_POSITION, $ecNext->X_POSITION, $ecPrevious->TEMP_BOTTOM, $ecNext->TEMP_BOTTOM, $X_POSITION);
                $ec->TEMP_LEFT = $this->interpolate($ecPrevious->X_POSITION, $ecNext->X_POSITION, $ecPrevious->TEMP_LEFT, $ecNext->TEMP_LEFT, $X_POSITION);
                $ec->TEMP_RIGHT = $this->interpolate($ecPrevious->X_POSITION, $ecNext->X_POSITION, $ecPrevious->TEMP_RIGHT, $ecNext->TEMP_RIGHT, $X_POSITION);
                $ec->TEMP_FRONT = $this->interpolate($ecPrevious->X_POSITION, $ecNext->X_POSITION, $ecPrevious->TEMP_FRONT, $ecNext->TEMP_FRONT, $X_POSITION);
                $ec->TEMP_REAR = $this->interpolate($ecPrevious->X_POSITION, $ecNext->X_POSITION, $ecPrevious->TEMP_REAR, $ecNext->TEMP_REAR, $X_POSITION);
                $ec->save();
            } else if ($ecNext != null) {
                $ec->X_POSITION = round($X_POSITION, 1);
                $ec->ID_EQUIP = $ecNext->ID_EQUIP;
                $ec->TEMP_REGUL = $ecNext->TEMP_REGUL;
                $ec->save();
            } else if ($ecPrevious != null) {
                $ec->X_POSITION = round($X_POSITION, 1);
                $ec->ID_EQUIP = $ecPrevious->ID_EQUIP;
                $ec->TEMP_REGUL = $ecPrevious->TEMP_REGUL;
                $ec->save();
            }

            if ($ec) {
                $ec->ALPHA_TOP = $this->convert->convectionCoeff($ec->ALPHA_TOP);
                $ec->ALPHA_BOTTOM = $this->convert->convectionCoeff($ec->ALPHA_BOTTOM);
                $ec->ALPHA_LEFT = $this->convert->convectionCoeff($ec->ALPHA_LEFT);
                $ec->ALPHA_RIGHT = $this->convert->convectionCoeff($ec->ALPHA_RIGHT);
                $ec->ALPHA_FRONT = $this->convert->convectionCoeff($ec->ALPHA_FRONT);
                $ec->ALPHA_REAR = $this->convert->convectionCoeff($ec->ALPHA_REAR);

                $ec->TEMP_TOP = $this->convert->temperature($ec->TEMP_TOP);
                $ec->TEMP_BOTTOM = $this->convert->temperature($ec->TEMP_BOTTOM);
                $ec->TEMP_LEFT = $this->convert->temperature($ec->TEMP_LEFT);
                $ec->TEMP_RIGHT = $this->convert->temperature($ec->TEMP_RIGHT);
                $ec->TEMP_FRONT = $this->convert->temperature($ec->TEMP_FRONT);
                $ec->TEMP_REAR = $this->convert->temperature($ec->TEMP_REAR);
                $result = $ec;
            }
        }

        return $result;
    }

    private function interpolate($x1, $x2, $y1, $y2, $x) 
    {
        $y = ($y2 - $y1) * ($x - $x1) / ($x2 - $x1) + $y1;

        return $y;
    }

    public function getDataHighChart()
    {
        $minMax = $minScaleY = $maxScaleY = $minValueY = $maxValueY = $nbFractionDigits = $maxiMum = null;
        $unitIdent = $miniMum = 10;
        $ID_EQUIP = $profileType = $profileFace = $listOfPoints = $path = $nbpoints = null;
        $YAxis = $XAxis = $pos = $start = $end = 0;
        $X = $Y = $resultPoint = $axisline = $valuesTabX =  $valuesTabY = $selectedPoints = $posTabY = array();
        $textX = 75;
        $minScale = $maxScale = $typeChart = $listofPointsOld = null;
        $newProfil = '';
        $checkTop = $checkButton = $checkLeft = $checkRight = $checkFront = $checkRear = null;
                    
        $input = $this->request->all();

        if (isset($input['profilType'])) $profileType = intval($input['profilType']);
        if (isset($input['profilFace'])) $profileFace = intval($input['profilFace']);
        if (isset($input['ID_EQUIP'])) $ID_EQUIP = intval($input['ID_EQUIP']);
        if (isset($input['minScaleY'])) $minScale = floatval($input['minScaleY']);
        if (isset($input['maxScaleY'])) $maxScale = floatval($input['maxScaleY']);
        if (isset($input['typeChart'])) $typeChart = intval($input['typeChart']);
        if (isset($input['newProfil'])) $newProfil = $input['newProfil'];

        if ($profileType == 1) {
            $minMax = $this->getMinMax(1039);
            $unitIdent = 5;
            $nbFractionDigits = 2;
        } else {
            $minMax = $this->getMinMax(1040);
            $unitIdent = 1;
            $nbFractionDigits = 0;
        }

        if ($profileFace == 0) {
            $checkTop = true;
        } else if ($profileFace == 1) {
            $checkButton = 1;
        } else if ($profileFace == 2) {
            $checkLeft = 2;
        } else if ($profileFace == 3) {
            $checkRight = 3;
        } else if ($profileFace == 4) {
            $checkFront = 4;
        } else if ($profileFace == 5) {
            $checkRear = 5;
        }

        $minScaleY = doubleval($minMax->LIMIT_MIN);
        $maxScaleY = doubleval($minMax->LIMIT_MAX);
        $minValueY = doubleval($minMax->LIMIT_MAX);
        $maxValueY = doubleval($minMax->LIMIT_MIN);

        $listOfPoints = $this->svg->getSelectedProfile($ID_EQUIP, $profileType, $profileFace);
        $listofPointsOld = $listOfPoints;
        $nbpoints = count($listOfPoints);

        // Generate new profile
        if ($typeChart == 2) {
            if (count($listOfPoints) > 0) {
                for($i = 0; $i < count($listOfPoints); $i++) {
                    $end = strpos($newProfil, '_', $start);
                    $value = substr($newProfil, $start, $end);

                    if ($value != '') {
                        if ($profileType == 1) {
                            $listOfPoints[$i]['Y_POINT'] = doubleval($value);
                        } else {
                            $listOfPoints[$i]['Y_POINT'] = doubleval($value);
                        }
                    } else {
                        $listOfPoints[$i]['Y_POINT'] = DOUBLE_MIN_VALUE;
                    }

                    $start = $end + 1;
                }

                $listOfPoints = $this->svg->generateNewProfile($listofPointsOld, $listOfPoints, $minMax->LIMIT_MIN, $minMax->LIMIT_MAX, $profileType);

            }
        }
        // End generate new profile

        if (count($listOfPoints) > 0) {
            for($i = 0; $i < count($listOfPoints); $i++) {
                array_push($valuesTabX, $listOfPoints[$i]['X_POSITION']);
                array_push($valuesTabY, round($listOfPoints[$i]['Y_POINT'], 2));
                array_push($selectedPoints, 1);

                if (doubleval($listOfPoints[$i]['Y_POINT']) < $minValueY) {
                    $minValueY = doubleval($listOfPoints[$i]['Y_POINT']);
                }

                if (doubleval($listOfPoints[$i]['Y_POINT']) > $maxValueY) {
                    $maxValueY = doubleval($listOfPoints[$i]['Y_POINT']);
                }

                if ($i == 0) {
                    $item['position'] = $pos;
                    $item['textX'] = $textX;
                    array_push($X, $item);
                }

                if (($i > 0) && ($i % 10 == 0) && $i <= 100) {
                    $pos = $pos + 10;
                    $textX = $textX + 80;
                    $item['textX'] = $textX;
                    $item['position'] = $pos;
                    array_push($X, $item);
                }
            }
        }

        $lfOffset = abs($maxValueY - $minValueY) * 0.15;

        if ($lfOffset > 0.0) {
            $minScaleY = $minValueY - $lfOffset;
            $maxScaleY = $maxValueY + $lfOffset;
        } else {
            $lfOffset1 = abs($minMax->LIMIT_MIN - $minValueY) * 0.15;
            $lfOffset2 = abs($minMax->LIMIT_MAX - $maxValueY) * 0.15;
            if ($lfOffset1 < $lfOffset2) {
                $lfOffset = $lfOffset1;
            } else {
                $lfOffset = $lfOffset2;
            }

            $minScaleY = $minValueY - $lfOffset;
            $maxScaleY = $maxValueY + $lfOffset;
        }

        $minScaleYtmp = $minScaleY;
        $maxScaleYtmp = $maxScaleY;
   
        if ($minScaleYtmp != $maxScaleYtmp) {
            $minScaleY = $minScaleYtmp;
            $maxScaleY = $maxScaleYtmp;
        }

        $tempMin = $minMax->LIMIT_MIN;
        $tempMax = $minMax->LIMIT_MAX;

        if ($minScaleY < $tempMin) $minScaleY = $minMax->LIMIT_MIN;
        if ($maxScaleY > $tempMax) $maxScaleY = $minMax->LIMIT_MAX;

        $miniMum = round($this->convert->convertIdent($minScaleY, $unitIdent), 2);
        $maxiMum = round($this->convert->convertIdent($maxScaleY, $unitIdent), 2);

        //refresh
        if ($typeChart == 1) {
            $miniMum = $minScale;
            $maxiMum = $maxScale;
        }

        // Write axis X
        $axisX = $this->svg->getAxisX();
        // End write axis X

        // Write axis Y
        $axisY = $this->svg->getAxisY($miniMum, $maxiMum, $minValueY, $maxValueY, $nbFractionDigits, $unitIdent);
        $axisline = $axisY['axisline'];
        $Y = $axisY['listOfGraduation'];
        // End write axis Y
        
        // write path and circle point
        $path1 = null;

        for($i = 0; $i < count($listOfPoints); $i++) {
    
            $listOfPoints[$i]['X_POSITION'] = $this->svg->getAxisXPos(doubleval($listOfPoints[$i]['X_POSITION']));
            // $listOfPoints[$i]['Y_POINT'] = $this->svg->getAxisYPos(doubleval($listOfPoints[$i]['Y_POINT']), $miniMum, $maxiMum);
            $listOfPoints[$i]['Y_POINT'] = $this->svg->getAxisYPos(
                $this->convert->convertIdent(doubleval($listOfPoints[$i]['Y_POINT']), $unitIdent), 
                $miniMum, 
                $maxiMum);

            array_push($posTabY, $listOfPoints[$i]['Y_POINT']);
            if ($i == 0) {
                $path1 = 'M '. $listOfPoints[0]['X_POSITION']. ' '.$listOfPoints[0]['Y_POINT'] .' L';
            } else {
                $path .= ' '.$listOfPoints[$i]['X_POSITION'] .' ' .$listOfPoints[$i]['Y_POINT'];
            }
        }
        $path = $path1 .' '.$path;
        // end write path and circle point
        $minPixY = (PROFILE_CHARTS_HEIGHT - PROFILE_CHARTS_MARGIN_HEIGHT) - (PROFILE_CHARTS_HEIGHT - (2 * PROFILE_CHARTS_MARGIN_HEIGHT)) ;
        $nbpixY = (PROFILE_CHARTS_HEIGHT - PROFILE_CHARTS_MARGIN_HEIGHT) - $minPixY;

        $array = [
            'MiniMum' => $miniMum,
            'MaxiMum' => $maxiMum,
            'minValueY' => $minValueY,
            'maxValueY' => $maxValueY,
            'imageWidth' => PROFILE_CHARTS_WIDTH,
            'imageHeight' => PROFILE_CHARTS_HEIGHT, 
            'imageMargeWidth' => PROFILE_CHARTS_MARGIN_WIDTH,
            'imageMargeHeight' => PROFILE_CHARTS_MARGIN_HEIGHT,
            'X' => $X,
            'Y' =>  $Y,
            'ListOfPoints' => $listOfPoints,
            'path' => $path,
            'axisline' => $axisline,
            'originY' => (PROFILE_CHARTS_HEIGHT - PROFILE_CHARTS_MARGIN_HEIGHT),
            'minPixY' => $minPixY,
            'maxPixY' => (PROFILE_CHARTS_HEIGHT - PROFILE_CHARTS_MARGIN_HEIGHT),
            'nbpixY' => $nbpixY,
            'valuesTabX' => $valuesTabX,
            'valuesTabY' => $valuesTabY,
            'selectedPoints' => $selectedPoints,
            'nbpoints' => $nbpoints,
            'axisYLength' => (PROFILE_CHARTS_WIDTH - (2 * PROFILE_CHARTS_MARGIN_WIDTH)) + 20,
            'posTabY' => $posTabY,
            'checkTop' => $checkTop,
            'checkButton' => $checkButton,
            'checkLeft' => $checkLeft,
            'checkRight' => $checkRight,
            'checkFront' => $checkFront,
            'checkRear' => $checkRear
        ];
        
        return $array;
    }

    public function saveSelectedProfile()
    {
        $ID_EQUIP = $profileType = $profileFace = $minScale = $maxScale = $typeChart = null;
        $newProfil = $sFace = '';
        $bsaveTop = $bsaveBottom = $bsaveLeft = $bsaveRight = $bsaveFront = $bsaveRear = null;
        $checkTop = $checkButton = $checkLeft = $checkRight = $checkFront = $checkRear = null;
        $start = $end = 0;

        $input = $this->request->all();

        if (isset($input['profilType'])) $profileType = intval($input['profilType']);
        if (isset($input['profilFace'])) $profileFace = intval($input['profilFace']);
        if (isset($input['ID_EQUIP'])) $ID_EQUIP = intval($input['ID_EQUIP']);
        if (isset($input['minScaleY'])) $minScale = floatval($input['minScaleY']);
        if (isset($input['maxScaleY'])) $maxScale = floatval($input['maxScaleY']);
        if (isset($input['typeChart'])) $typeChart = intval($input['typeChart']);
        if (isset($input['newProfil'])) $newProfil = $input['newProfil'];
        if (isset($input['checkTop'])) $checkTop = intval($input['checkTop']);
        if (isset($input['checkButton'])) $checkButton = intval($input['checkButton']);
        if (isset($input['checkLeft'])) $checkLeft = intval($input['checkLeft']);
        if (isset($input['checkRight'])) $checkRight = intval($input['checkRight']);
        if (isset($input['checkFront'])) $checkFront = intval($input['checkFront']);
        if (isset($input['checkRear'])) $checkRear = intval($input['checkRear']);

        $listOfPoints = $this->svg->getSelectedProfile($ID_EQUIP, $profileType, $profileFace);

        $bsaveTop = (($checkTop != null) || ($profileFace == PROFILE_TOP)) ? true : false;
        $bsaveBottom = (($checkButton != null) || ($profileFace == PROFILE_BOTTOM)) ? true : false;
        $bsaveLeft = (($checkLeft != null) || ($profileFace == PROFILE_LEFT)) ? true : false;
        $bsaveRight = (($checkRight != null) || ($profileFace == PROFILE_RIGHT)) ? true : false;
        $bsaveFront = (($checkFront != null) || ($profileFace == PROFILE_FRONT)) ? true : false;
        $bsaveRear = (($checkRear != null) || ($profileFace == PROFILE_REAR)) ? true : false;

        // get new profile
        if (count($listOfPoints) > 0) {
            for($i = 0; $i < count($listOfPoints); $i++) {
                $end = strpos($newProfil, '_', $start);
                $value = substr($newProfil, $start, $end);
                
                if ($value != '') {
                    if ($profileType == 1) {
                        $listOfPoints[$i]['Y_POINT'] = doubleval($value);
                    } else {
                        $listOfPoints[$i]['Y_POINT'] = doubleval($value);
                    }
                } else {
                    $listOfPoints[$i]['Y_POINT'] = DOUBLE_MIN_VALUE;
                }

                $start = $end + 1;
            }
        }

        // get old profile
        $equipCharacts = EquipCharact::where('ID_EQUIP', $ID_EQUIP)->get();
        if ($equipCharacts) {
            for ($i = 0; $i < count($equipCharacts); $i++) {
                if ($profileType == CONVECTION_PROFILE) {
                    if($bsaveTop) $equipCharacts[$i]->ALPHA_TOP = $listOfPoints[$i]['Y_POINT'];
                    if($bsaveBottom) $equipCharacts[$i]->ALPHA_BOTTOM = $listOfPoints[$i]['Y_POINT'];
                    if($bsaveLeft) $equipCharacts[$i]->ALPHA_LEFT = $listOfPoints[$i]['Y_POINT'];
                    if($bsaveRight) $equipCharacts[$i]->ALPHA_RIGHT = $listOfPoints[$i]['Y_POINT'];
                    if($bsaveFront) $equipCharacts[$i]->ALPHA_FRONT = $listOfPoints[$i]['Y_POINT'];
                    if($bsaveRear) $equipCharacts[$i]->ALPHA_REAR = $listOfPoints[$i]['Y_POINT'];
                } else {
                    if($bsaveTop) $equipCharacts[$i]->TEMP_TOP = $listOfPoints[$i]['Y_POINT'];
                    if($bsaveBottom) $equipCharacts[$i]->TEMP_BOTTOM = $listOfPoints[$i]['Y_POINT'];
                    if($bsaveLeft) $equipCharacts[$i]->TEMP_LEFT = $listOfPoints[$i]['Y_POINT'];
                    if($bsaveRight) $equipCharacts[$i]->TEMP_RIGHT = $listOfPoints[$i]['Y_POINT'];
                    if($bsaveFront) $equipCharacts[$i]->TEMP_FRONT = $listOfPoints[$i]['Y_POINT'];
                    if($bsaveRear) $equipCharacts[$i]->TEMP_REAR = $listOfPoints[$i]['Y_POINT'];
                }
                $equipCharacts[$i]->save();
            }
        }

        return 1;
    }

    public function getDataCurve($idEquip) 
    {
        $array = [];
        $isCapabilities = null;
        $equipment = Equipment::find($idEquip);
        if ($equipment) {
            if ($this->equip->getCapability($equipment->CAPABILITIES, REF_CAP_EQP_DEPEND_ON_TS)) {
                $isCapabilities = 1;
            } else {
                $isCapabilities = 0;
            }
        }
        
        $equipGeneration = EquipGeneration::where('ID_EQUIP', $idEquip)->first();
        if ($equipGeneration) {
            $array = [
                'isCapabilities' => $isCapabilities,
                'REGUL_TEMP' => $this->units->controlTemperature($equipGeneration->TEMP_SETPOINT, 0, 1),
                'DWELLING_TIME' => $this->units->time($equipGeneration->DWELLING_TIME, 0, 1),
                'PRODTEMP' => $this->units->prodTemperature($equipGeneration->AVG_PRODINTEMP, 1, 1),
                'LOADINGRATE' => $this->units->toc($equipGeneration->EQP_GEN_LOADRATE, 1, 1)
            ];
        }
        return $array;
    }

    public function redrawCurves()
    {
        $input = $this->request->all();

        $ID_EQUIP = $REGUL_TEMP = $DWELLING_TIME = $PRODTEMP = $LOADINGRATE = $result = null;

        if (isset($input['ID_EQUIP'])) $ID_EQUIP = intval($input['ID_EQUIP']);
        if (isset($input['REGUL_TEMP'])) $REGUL_TEMP = $this->units->controlTemperature(floatval($input['REGUL_TEMP']), 0, 0);
        if (isset($input['DWELLING_TIME'])) $DWELLING_TIME = $this->units->time(floatval($input['DWELLING_TIME']), 0, 0);
        if (isset($input['PRODTEMP'])) $PRODTEMP = $this->units->prodTemperature(floatval($input['PRODTEMP']), 1, 0);
        if (isset($input['LOADINGRATE'])) $LOADINGRATE = floatval($input['LOADINGRATE']);

        $equipment = Equipment::find($ID_EQUIP);
        if ($equipment) {
            if ($equipment->STD == 1) {
                $equipGeneration = EquipGeneration::where('ID_EQUIP', $equipment->ID_EQUIPGENERATION)->first();
                if ($equipGeneration) {
                    if ($this->equip->getCapability($equipment->CAPABILITIES, 65536)) {
                        $equipGeneration->DWELLING_TIME = $DWELLING_TIME;
                    } else {
                        $equipGeneration->TEMP_SETPOINT = $REGUL_TEMP;
                    }
                    $equipGeneration->AVG_PRODINTEMP = $PRODTEMP;
                    $equipGeneration->EQP_GEN_LOADRATE = $LOADINGRATE;
                    $equipGeneration->save();
                }
                $this->runEquipmentCalculation($equipment->ID_EQUIPGENERATION);
            }
        }
    }

    public function getUnitData($id)
    {
        $study = Study::find($id);
        $priceEnergy = 0;
        if ($study) {
            $idPrice = $study->ID_PRICE;
            $priceEnergy =  $this->studies->getStudyPrice($study);

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
        }

        if ($priceEnergy != 0) $priceEnergy =  $priceEnergy;

        $energy = $this->equip->initEnergyDef($id);

        $res = [
            'Price' => $this->equip->cryogenPrice($priceEnergy, $energy),
            'IntervalWidth' => $this->convert->prodDimension(doubleval($intervalW)),
            'IntervalLength' => $this->convert->prodDimension(doubleval($intervalL)),
            'MonetarySymbol' => $this->convert->monetarySymbol(),
            'DimensionSymbol' => $this->convert->prodDimensionSymbolUser()
        ];

        return $res;
    }

    public function updatePrice($id)
    {
        $input = $this->request->all();

        if (!isset($input['price']))
            throw new \Exception("Error Processing Request", 1);

        $study = Study::find($id);
        $energy = $this->equip->initEnergyDef($id);

        if (isset($input['price'])) $priceEnergy = $this->equip->cryogenPriceSave($input['price'], $energy); 

        if ($priceEnergy >= 0) {

            if ($study) {
                $price = Price::find($study->ID_PRICE);
                if ($price) {
                    $uMoney = $this->convert->uMoney();
                    if ($uMoney) {
                        $apply1 = 1 * ($uMoney["coeffA"]) + ($uMoney["coeffB"]);
                        if ($apply1 != 0) {
                            $priceEnergy = $priceEnergy / $apply1;
                        }                        
                        $price->ENERGY = $priceEnergy;
                        $price->update();
                    }
                }
            }
            return 1;
        } else {
            return 0;
        }
    }

    public function updateInterval($id)
    {
        $input = $this->request->all();

        if (!isset($input['lenght']) || !isset($input['width']))
            throw new \Exception("Error Processing Request", 1);

        if (isset($input['lenght'])) $lenght = $this->convert->prodDimensionSave(doubleval($input['lenght'])); 

        if (isset($input['width'])) $width = $this->convert->prodDimensionSave(doubleval($input['width']));

        if ($lenght && $width) {
            $study = Study::find($id);
            if ($study) {
                $precalcLdgRatePrm = PrecalcLdgRatePrm::find($study->ID_PRECALC_LDG_RATE_PRM);
                
                if ($precalcLdgRatePrm) {
                    $precalcLdgRatePrm->W_INTERVAL = $width;
                    $precalcLdgRatePrm->L_INTERVAL = $lenght;
                    $precalcLdgRatePrm->update();
                }
                $studyEquipments = $study->studyEquipments;
                if (count($studyEquipments) > 0) {
                    foreach ($studyEquipments as $sEquip) {
                        $layoutGen = $this->stdeqp->getStudyEquipmentLayoutGen($sEquip);
                        // $layoutGen->ORI
                        $layoutGen->WIDTH_INTERVAL = $width;
                        $layoutGen->LENGTH_INTERVAL = $lenght;
                        $layoutGen->save();
                        $this->stdeqp->calculateEquipmentParams($sEquip);
                    }
                }
            }
            return 1;
        } else {
            return 0;
        }
    }

    public function saveEquipment()
    {
        $input = $this->request->all();
        
        $ID_EQUIP = $EQUIP_NAME = $EQUIP_VERSION = $EQUIP_RELEASE = $EQUIP_COMMENT = $EQP_LENGTH = $EQP_WIDTH = $EQP_HEIGHT = $NB_TR = $NB_TS = $NB_VC = $MAX_FLOW_RATE = $TMP_REGUL_MIN = $MAX_NOZZLES_BY_RAMP = $MAX_RAMPS = $Ramps = $Shelves = $Consumptions = null;

        if (isset($input['ID_EQUIP'])) $ID_EQUIP = intval($input['ID_EQUIP']);
        if (isset($input['EQUIP_NAME'])) $EQUIP_NAME = $input['EQUIP_NAME'];
        if (isset($input['EQUIP_VERSION'])) $EQUIP_VERSION = floatval($input['EQUIP_VERSION']);
        if (isset($input['EQUIP_RELEASE'])) $EQUIP_RELEASE = intval($input['EQUIP_RELEASE']);
        if (isset($input['EQUIP_COMMENT'])) $EQUIP_COMMENT = $input['EQUIP_COMMENT'];
        if (isset($input['EQP_LENGTH'])) $EQP_LENGTH = floatval($input['EQP_LENGTH']);
        if (isset($input['EQP_WIDTH'])) $EQP_WIDTH = floatval($input['EQP_WIDTH']);
        if (isset($input['EQP_HEIGHT'])) $EQP_HEIGHT = floatval($input['EQP_HEIGHT']);
        if (isset($input['NB_TR'])) $NB_TR = intval($input['NB_TR']);
        if (isset($input['NB_TS'])) $NB_TS = intval($input['NB_TS']);
        if (isset($input['NB_VC'])) $NB_VC = intval($input['NB_VC']);
        if (isset($input['MAX_FLOW_RATE'])) $MAX_FLOW_RATE = floatval($input['MAX_FLOW_RATE']);
        if (isset($input['TMP_REGUL_MIN'])) $TMP_REGUL_MIN = floatval($input['TMP_REGUL_MIN']);
        if (isset($input['MAX_NOZZLES_BY_RAMP'])) $MAX_NOZZLES_BY_RAMP = intval($input['MAX_NOZZLES_BY_RAMP']);
        if (isset($input['MAX_RAMPS'])) $MAX_RAMPS = intval($input['MAX_RAMPS']);
        if (isset($input['Ramps'])) $Ramps = $input['Ramps'];
        if (isset($input['Shelves'])) $Shelves = $input['Shelves'];
        if (isset($input['Consumptions'])) $Consumptions = $input['Consumptions'];

        $equipment = Equipment::find($ID_EQUIP);
        if ($equipment) {
            $equipment->EQUIP_NAME = $EQUIP_NAME;
            $equipment->EQUIP_VERSION = $EQUIP_VERSION;
            $equipment->EQUIP_RELEASE = $EQUIP_RELEASE;
            $equipment->EQUIP_COMMENT = $EQUIP_COMMENT;
            $equipment->EQP_LENGTH = $EQP_LENGTH;
            $equipment->EQP_HEIGHT = $EQP_HEIGHT;
            $equipment->EQP_WIDTH = $EQP_WIDTH;
            $equipment->NB_TR = $NB_TR;
            $equipment->NB_TS = $NB_TS;
            $equipment->NB_VC = $NB_VC;
            $equipment->MAX_FLOW_RATE = $MAX_FLOW_RATE;
            $equipment->TMP_REGUL_MIN = $TMP_REGUL_MIN;
            $equipment->MAX_NOZZLES_BY_RAMP = $MAX_NOZZLES_BY_RAMP;
            $equipment->MAX_RAMPS = $MAX_RAMPS;
            $equipment->save();

            if (count($Ramps) > 0) {
                $oldRamps = Ramps::where('ID_EQUIP', $ID_EQUIP)->get();
                if (count($oldRamps) > 0) {
                    Ramps::where('ID_EQUIP', $ID_EQUIP)->delete();
                }
                
                for ($i = 0; $i < count($Ramps); $i++) { 
                    $newRamp = new Ramps();
                    $newRamp->ID_EQUIP = $ID_EQUIP;
                    $newRamp->POSITION = $Ramps[$i]['POSITION'];
                    $newRamp->save();
                }
            }

            if (count($Shelves) > 0) {
                $shelves = Shelves::where('ID_EQUIP', $ID_EQUIP)->get();
                if (count($shelves) > 0) {
                    Shelves::where('ID_EQUIP', $ID_EQUIP)->delete();
                }

                for ($i = 0; $i < count($Shelves); $i++) {
                    $newShelve = new Shelves();
                    $newShelve->ID_EQUIP = $ID_EQUIP;
                    $newShelve->NB = $Shelves[$i]['NB'];
                    $newShelve->SPACE = $Shelves[$i]['SPACE'];
                    $newShelve->save();
                }
            }

            if (count($Consumptions) > 0) {
                $consumptions = Consumptions::where('ID_EQUIP', $ID_EQUIP)->get();
                if (count($consumptions) > 0) {
                    Consumptions::where('ID_EQUIP', $ID_EQUIP)->delete();
                }

                for ($i = 0; $i < count($Consumptions); $i++) {
                    $newConsumption = new Consumptions();
                    $newConsumption->ID_EQUIP = $ID_EQUIP;
                    $newConsumption->TEMPERATURE = $Consumptions[$i]['TEMPERATURE'];
                    $newConsumption->CONSUMPTION_PERM = $Consumptions[$i]['CONSUMPTION_PERM'];
                    $newConsumption->CONSUMPTION_GETCOLD = $Consumptions[$i]['CONSUMPTION_GETCOLD'];
                    $newConsumption->save();
                }
            }
        }

        return 1;
    }

    public function startEquipmentCalculate($id)
    {
        $result = null;
        $equipment = Equipment::find($id);
        if ($equipment) {
            $result = $this->runEquipmentCalculation($equipment->ID_EQUIPGENERATION);
        }
        return $result;
    }

    public function deleteEquipment($id)
    {
        $equipment = Equipment::find($id);
        if ($equipment) {
            $equipGenZone = EquipGenZone::where('ID_EQUIPGENERATION', $equipment->ID_EQUIPGENERATION)->get();
            if (count($equipGenZone) > 0) {
                EquipGenZone::where('ID_EQUIPGENERATION', $equipment->ID_EQUIPGENERATION)->delete();
            }

            $equipGeneration = EquipGeneration::find($equipment->ID_EQUIPGENERATION);

            if ((count($equipGeneration) > 0) || ($equipGeneration != null)) {
                EquipGeneration::where('ID_EQUIPGENERATION', $equipment->ID_EQUIPGENERATION)->delete();
            }

            // $eGeneration = EquipGeneration::where('ID_EQUIP', $equipment->ID_EQUIP)->get();
            // if (count($eGeneration) > 0) {
            //     EquipGeneration::where('ID_EQUIP', $id)->delete();
            // }

            $equipCharact = EquipCharact::where('ID_EQUIP', $equipment->ID_EQUIP)->get();
            if (count($equipCharact) > 0) { 
                EquipCharact::where('ID_EQUIP', $equipment->ID_EQUIP)->delete();
            }

            $ramps = Ramps::where('ID_EQUIP', $equipment->ID_EQUIP)->get();
            if (count($ramps) > 0) {
                Ramps::where('ID_EQUIP', $equipment->ID_EQUIP)->delete();
            }

            $shelves = Shelves::where('ID_EQUIP', $equipment->ID_EQUIP)->get();
            if (count($shelves) > 0) {
                Shelves::where('ID_EQUIP', $equipment->ID_EQUIP)->delete();
            }

            $consumptions = Consumptions::where('ID_EQUIP', $equipment->ID_EQUIP)->get();
            if (count($consumptions) > 0) {
                Consumptions::where('ID_EQUIP', $equipment->ID_EQUIP)->delete();
            }

            $equipZones = EquipZone::where('ID_EQUIP', $equipment->ID_EQUIP)->get();
            if (count($equipZones) > 0) {
                EquipZone::where('ID_EQUIP', $equipment->ID_EQUIP)->delete();
            }
            
            $equipment->delete();
        }
        return 1;
    }

    public function getEquipCharactById($id) 
    {
        $equipCharact = EquipCharact::find($id);
        if ($equipCharact) {
            $equipCharact->ALPHA_TOP = $this->units->convectionCoeff($equipCharact->ALPHA_TOP, 2, 1);
            $equipCharact->ALPHA_BOTTOM = $this->units->convectionCoeff($equipCharact->ALPHA_BOTTOM, 2, 1);
            $equipCharact->ALPHA_LEFT = $this->units->convectionCoeff($equipCharact->ALPHA_LEFT, 2, 1);
            $equipCharact->ALPHA_RIGHT = $this->units->convectionCoeff($equipCharact->ALPHA_RIGHT, 2, 1);
            $equipCharact->ALPHA_FRONT = $this->units->convectionCoeff($equipCharact->ALPHA_FRONT, 2, 1);
            $equipCharact->ALPHA_REAR = $this->units->convectionCoeff($equipCharact->ALPHA_REAR, 2, 1);

            $equipCharact->TEMP_TOP = $this->units->temperature($equipCharact->TEMP_TOP, 2, 1);
            $equipCharact->TEMP_BOTTOM = $this->units->temperature($equipCharact->TEMP_BOTTOM, 2, 1);
            $equipCharact->TEMP_LEFT = $this->units->temperature($equipCharact->TEMP_LEFT, 2, 1);
            $equipCharact->TEMP_RIGHT = $this->units->temperature($equipCharact->TEMP_RIGHT, 2, 1);
            $equipCharact->TEMP_FRONT = $this->units->temperature($equipCharact->TEMP_FRONT, 2, 1);
            $equipCharact->TEMP_REAR = $this->units->temperature($equipCharact->TEMP_REAR, 2, 1);
        }
        return $equipCharact;
    }

    public function deleteEquipCharact($id)
    {
        $equipCharact = EquipCharact::find($id);
        if ($equipCharact) {
            $equipCharact->delete();
            return 1;
        }
        return 0;
    }

    public function reCalculate($id)
    {
        $study = Study::find($id);
        $studyEquipments = $study->studyEquipments;
        $this->stdeqp->runStudyCleaner($id, -1, SC_CLEAN_OUPTUT_LAYOUT_CHANGED);
        if (count($studyEquipments) > 0) {
            foreach ($studyEquipments as $sEquip) {
                $sEquip->BRAIN_SAVETODB = 0;
                $sEquip->BRAIN_TYPE = 0;
                $sEquip->EQUIP_STATUS = 0;
                $sEquip->AVERAGE_PRODUCT_ENTHALPY = 0;
                $sEquip->AVERAGE_PRODUCT_TEMP = 0;
                $sEquip->ENTHALPY_VARIATION = 0;
                $sEquip->PRECIS = 0;
                $sEquip->RUN_CALCULATE = 1;
                $sEquip->save();
            }
        }
        $this->stdeqp->afterStudyCleaner($id, -1, SC_CLEAN_OUPTUT_LAYOUT_CHANGED, false, false, false, false);

        return 1;
    }

    public function getTempSetPoint($idEquip)
    {
        $tr_current = $tr_new = $arr = null;
        $equipment = Equipment::find($idEquip);
        if ($equipment) {
            $equipGeneration = EquipGeneration::where('ID_EQUIP', $equipment->ID_EQUIP)->first();
            if ($equipGeneration) {
                $tr_current = $this->units->controlTemperature($equipGeneration->TEMP_SETPOINT, 0, 1);
                $tr_new = $this->units->controlTemperature($equipGeneration->TEMP_SETPOINT, 0, 1);
            }
        }

        $arr = [
            'tr_current' => $tr_current,
            'tr_new' => $tr_new
        ];

        return $arr;
    }

    public function updateEquipCharact()
    {
        $input = $this->request->all();

        $ID_EQUIPCHARAC = $ALPHA_TOP = $ALPHA_BOTTOM = $ALPHA_LEFT = $ALPHA_RIGHT = $ALPHA_FRONT = $ALPHA_REAR = null;
        $TEMP_TOP = $TEMP_BOTTOM = $TEMP_LEFT = $TEMP_RIGHT = $TEMP_FRONT = $TEMP_REAR = null;

        if (isset($input['ID_EQUIPCHARAC'])) $ID_EQUIPCHARAC = intval($input['ID_EQUIPCHARAC']);

        if (isset($input['ALPHA_TOP'])) $ALPHA_TOP = $this->units->convectionCoeff(floatval($input['ALPHA_TOP']), 2, 0);
        if (isset($input['ALPHA_BOTTOM'])) $ALPHA_BOTTOM = $this->units->convectionCoeff(floatval($input['ALPHA_BOTTOM']), 2, 0);
        if (isset($input['ALPHA_LEFT'])) $ALPHA_LEFT = $this->units->convectionCoeff(floatval($input['ALPHA_LEFT']), 2, 0);
        if (isset($input['ALPHA_RIGHT'])) $ALPHA_RIGHT = $this->units->convectionCoeff(floatval($input['ALPHA_RIGHT']), 2, 0);
        if (isset($input['ALPHA_FRONT'])) $ALPHA_FRONT = $this->units->convectionCoeff(floatval($input['ALPHA_FRONT']), 2, 0);
        if (isset($input['ALPHA_REAR'])) $ALPHA_REAR = $this->units->convectionCoeff(floatval($input['ALPHA_REAR']), 2, 0);

        if (isset($input['TEMP_TOP'])) $TEMP_TOP = $this->units->temperature(floatval($input['TEMP_TOP']), 2, 0);
        if (isset($input['TEMP_BOTTOM'])) $TEMP_BOTTOM = $this->units->temperature(floatval($input['TEMP_BOTTOM']), 2, 0);
        if (isset($input['TEMP_LEFT'])) $TEMP_LEFT = $this->units->temperature(floatval($input['TEMP_LEFT']), 2, 0);
        if (isset($input['TEMP_RIGHT'])) $TEMP_RIGHT = $this->units->temperature(floatval($input['TEMP_RIGHT']), 2, 0);
        if (isset($input['TEMP_FRONT'])) $TEMP_FRONT = $this->units->temperature(floatval($input['TEMP_FRONT']), 2, 0);
        if (isset($input['TEMP_REAR'])) $TEMP_REAR = $this->units->temperature(floatval($input['TEMP_REAR']), 2, 0);

        $equipCharact = EquipCharact::find($ID_EQUIPCHARAC);
        if ($equipCharact) {
            $equipCharact->ALPHA_TOP = $ALPHA_TOP;
            $equipCharact->ALPHA_BOTTOM = $ALPHA_BOTTOM;
            $equipCharact->ALPHA_LEFT = $ALPHA_LEFT;
            $equipCharact->ALPHA_RIGHT = $ALPHA_RIGHT;
            $equipCharact->ALPHA_FRONT = $ALPHA_FRONT;
            $equipCharact->ALPHA_REAR = $ALPHA_REAR;

            $equipCharact->TEMP_TOP = $TEMP_TOP;
            $equipCharact->TEMP_BOTTOM = $TEMP_BOTTOM;
            $equipCharact->TEMP_LEFT = $TEMP_LEFT;
            $equipCharact->TEMP_RIGHT = $TEMP_RIGHT;
            $equipCharact->TEMP_FRONT = $TEMP_FRONT;
            $equipCharact->TEMP_REAR = $TEMP_REAR;
            $equipCharact->save();
        }
        return 1;
    }

    public function buildForNewTR()
    {
        $input = $this->request->all();

        $lfOldTR = $lfNewTR = $ID_STUDY = $ID_EQUIP = $equipment = $equipGeneration = $result = null;
        $nbStudies = $lastIdStudy = $id_equip_ = 0;

        if (isset($input['ID_EQUIP'])) $ID_EQUIP = intval($input['ID_EQUIP']);
        if (isset($input['ID_STUDY'])) $ID_STUDY = intval($input['ID_STUDY']);
        if (isset($input['tr_current'])) $lfOldTR = floatval($input['tr_current']);
        if (isset($input['tr_new'])) $lfNewTR = floatval($input['tr_new']);
        if (isset($input['isComefromStudy'])) $isComefromStudy = intval($input['isComefromStudy']);

        $equipment = Equipment::find($ID_EQUIP);
        if ($equipment) {
            $equipGeneration = EquipGeneration::where('ID_EQUIP', $equipment->ID_EQUIP)->first();

            if (abs($lfOldTR - $lfNewTR) > 0.01) {
                $id_equip_ = $ID_EQUIP;
                $studyEquipments = null;

                // if ($isComefromStudy == 1) {
                //     $studyEquipments = StudyEquipment::where('ID_EQUIP', $ID_EQUIP)
                //                         ->where('ID_STUDY', $ID_STUDY)->get();
                // } else {
                //     $studyEquipments = StudyEquipment::where('ID_EQUIP', $ID_EQUIP)->get();
                // }

                // if (count($studyEquipments) > 0) {
                //     for ($i = 0; $i < count($studyEquipments); $i++) {
                //         if ($ID_STUDY != $lastIdStudy) {
                //             $nbStudies++;
                //             $lastIdStudy = $ID_STUDY;
                //         }
                //     }
                // }

                // if ($nbStudies > 0) {
                //     $equipment->OPEN_BY_OWNER = 0;
                //     $this->changeNameAndVersionForNewTR($equipment, $lfNewTR, true);
                //     $equipment->ID_EQUIP = 0;
                //     $equipment->ID_USER = $this->auth->user()->ID_USER;
                //     // some code new equipment
                // } else {
                //     $this->changeNameAndVersionForNewTR($equipment, $lfNewTR, false);
                //     if ($equipGeneration) {
                //         $equipGeneration->MOVING_POS = $lfOldTR;
                //         $equipGeneration->TEMP_SETPOINT = $lfNewTR;
                //         $equipGeneration->MOVING_CHANGE = 2;
                //         $equipGeneration->save();
                //     }
                // }

                // code have study;

                if ($equipGeneration) {
                    $equipGeneration->MOVING_POS = $this->units->controlTemperature($lfOldTR, 0, 0);
                    $equipGeneration->TEMP_SETPOINT = $this->units->controlTemperature($lfNewTR, 0, 0);
                    $equipGeneration->MOVING_CHANGE = 2;
                    $equipGeneration->save();

                    $result = $this->runEquipmentCalculation($equipGeneration->ID_EQUIPGENERATION);

                    // update study equipment
                    if ($isComefromStudy == 1) {
                        $studyEquipments = StudyEquipment::where('ID_EQUIP', $ID_EQUIP)
                                            ->where('ID_STUDY', $ID_STUDY)->get();
                        if (count($studyEquipments) > 0) {
                            for ($i = 0; $i < count($studyEquipments); $i++) {
                                if ($studyEquipments[$i]->ID_EQUIP == $id_equip_) {
                                    $idStudyEquipment = $studyEquipments[$i]->ID_STUDY_EQUIPMENTS;
                                    $this->studies->RunStudyCleaner($ID_STUDY, SC_CLEAN_OUTPUT_EQP_PRM, $idStudyEquipment);

                                    $this->cal->setChildsStudiesToRecalculate($ID_STUDY, $idStudyEquipment);

                                    try {
                                        $this->studies->updateStudyEquipmentAfterChangeTR($idStudyEquipment, $ID_EQUIP);
                                    } catch (\Exception $e) {
                                        echo ("Exception while updating study equipment: " . $e);
                                    }
                                }

                            }
                        }
                    }
                }
            } else {
                echo 'No change in temperature : nothing to do';
            }
        }
        $equipRs =  Equipment::find($ID_EQUIP);
        
        $equipRs->capabilitiesCalc = $this->equip->getCapability($equipRs->CAPABILITIES, 65536);
        $equipRs->capabilitiesCalc256 = $this->equip->getCapability($equipRs->CAPABILITIES, 256);
        $equipRs->timeSymbol = $this->convert->timeSymbolUser();
        $equipRs->temperatureSymbol = $this->convert->temperatureSymbolUser();
        $equipRs->dimensionSymbol = $this->convert->equipDimensionSymbolUser();
        $equipRs->consumptionSymbol1 = $this->convert->consumptionSymbolUser($equipRs->ID_COOLING_FAMILY, 1);
        $equipRs->consumptionSymbol2 = $this->convert->consumptionSymbolUser($equipRs->ID_COOLING_FAMILY, 2);
        $equipRs->consumptionSymbol3 = $this->convert->consumptionSymbolUser($equipRs->ID_COOLING_FAMILY, 3);
        $equipRs->shelvesWidthSymbol = $this->convert->shelvesWidthSymbol();
        $equipRs->rampsPositionSymbol = $this->convert->rampsPositionSymbol();

        $equipRs->EQP_LENGTH = $this->units->equipDimension($equipRs->EQP_LENGTH, 2, 1);            
        $equipRs->EQP_WIDTH = $this->units->equipDimension($equipRs->EQP_WIDTH, 2, 1);
        $equipRs->EQP_HEIGHT = $this->units->equipDimension($equipRs->EQP_HEIGHT, 2, 1);
        $equipRs->MAX_FLOW_RATE = doubleval($this->units->consumption($equipRs->MAX_FLOW_RATE, $equipRs->ID_COOLING_FAMILY, 1, 2, 1));
        $equipRs->TMP_REGUL_MIN = $this->units->controlTemperature($equipRs->TMP_REGUL_MIN, 0, 1);

        $equipGenerRs = EquipGeneration::find($equipRs->ID_EQUIPGENERATION);
    
        if ($equipGenerRs) { 

            $equipGenerRs->TEMP_SETPOINT = doubleval($this->units->controlTemperature($equipGenerRs->TEMP_SETPOINT, 2, 1));
            $equipGenerRs->DWELLING_TIME = $this->units->time($equipGenerRs->DWELLING_TIME, 2, 1);
            $equipGenerRs->NEW_POS = $this->units->time($equipGenerRs->NEW_POS, 2, 1);
        }
        $equipRs->equipGeneration = $equipGenerRs;
        
        return [
            "RefEquipment" => $equipRs,
            "CheckKernel" => $result
        ];
    }

    private function changeNameAndVersionForNewTR($equipment, $lfNewTR, $bDuplicate)
    {
        $bRet = false;
        $strName = $AppendStr = "";
        $IdxTemp = -1;

        $strName = $equipment->EQUIP_NAME;
        $IdxTemp = strrpos($strName, "-(@"); // Find the position of the last occurrence of a substring in a string
        if ($IdxTemp == -1) {
            echo ("No temp in the name");
        } else {
            $strName = substr($strName, 0, $IdxTemp);
            echo ("Name contains temp : we will replace it");
        }

        if (($IdxTemp > 0) || ($bDuplicate)) {
            $AppendStr = "-(@". $this->convert->temperature($lfNewTR) . ")";
            $strName = $strName . $AppendStr;
            $equipment->EQUIP_NAME = $strName;
            if (!$this->checkNameAndVersion($equipment->EQUIP_NAME, $equipment->EQUIP_VERSION)) {
                $lfVersion = $this->getMaxVersion($equipment);
                if ($lfVersion > 0.0) {
                    $lfVersion = $lfVersion + 0.1;
                    $equipment->EQUIP_VERSION = $lfVersion;
                }
            }
            $equipment->save();
            $bRet = true;
        }
        return $bRet;
    }

    private function getMaxVersion($equipment)
    {
        $maxVersion = $equipment->EQUIP_VERSION;
        $equipments = Equipment::select('EQUIP_VERSION')
                    ->where('EQUIP_NAME', '=', $equipment->EQUIP_NAME)
                    ->orderBy('EQUIP_VERSION')->get();

        if (count($equipments) > 0) {
            foreach ($equipments as $equip) {
                if (floatval($equip->EQUIP_VERSION) > floatval($maxVersion)) {
                    $maxVersion = floatval($equip->EQUIP_VERSION);
                }
            }
        }
        return $maxVersion;
    }

    private function runEquipmentCalculation($IdEquipgeneration)
    {
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $IdEquipgeneration, 0, 1, 1, 'c:\\temp\\equipment_builder_log.txt');
        return $this->kernel->getKernelObject('EquipmentBuilder')->EBEquipmentCalculation($conf);
    }

    private function copyRamps($oldIdEquip, $newIdEquip)
    {
        $oldRamps = Ramps::where('ID_EQUIP', $oldIdEquip)->get();
        if (count($oldRamps) > 0) {
            foreach ($oldRamps as $ramp) {
                $newRamp = new Ramps();
                $newRamp->ID_EQUIP = $newIdEquip;
                $newRamp->POSITION = $ramp->POSITION;
                $newRamp->save();
            }
        }
    }

    private function copyConsumptions($oldIdEquip, $newIdEquip)
    {
        $oldConsumptions = Consumptions::where('ID_EQUIP', $oldIdEquip)->get();
        
        if (count($oldConsumptions) > 0) {
            foreach ($oldConsumptions as $consumption) {
                $newConsumption = new Consumptions();
                $newConsumption->ID_EQUIP = $newIdEquip;
                $newConsumption->TEMPERATURE = $consumption->TEMPERATURE;
                $newConsumption->CONSUMPTION_PERM = $consumption->CONSUMPTION_PERM;
                $newConsumption->CONSUMPTION_GETCOLD = $consumption->CONSUMPTION_GETCOLD;
                $newConsumption->save();
            }
        }
    }

    private function copyShelves($oldIdEquip, $newIdEquip)
    {
        $oldShelves = Shelves::where('ID_EQUIP', $oldIdEquip)->get();
        if (count($oldShelves) > 0) {
            foreach ($oldShelves as $shelve) {
                $newShelve = new Shelves();
                $newShelve->ID_EQUIP = $newIdEquip;
                $newShelve->NB = $shelve->NB;
                $newShelve->SPACE = $shelve->SPACE;
                $newShelve->save();
            }
        }
    }

    private function copyEquipCharact($oldIdEquip, $newIdEquip)
    {
        $oldEquipCharacts = EquipCharact::where('ID_EQUIP', $oldIdEquip)->get();
        if (count($oldEquipCharacts) > 0) {
            foreach ($oldEquipCharacts as $equipCharact) {
                $newEquipCharact = new EquipCharact();
                $newEquipCharact->ID_EQUIP = $newIdEquip;
                $newEquipCharact->X_POSITION = $equipCharact->X_POSITION;
                $newEquipCharact->TEMP_REGUL = $equipCharact->TEMP_REGUL;
                $newEquipCharact->ALPHA_TOP = $equipCharact->ALPHA_TOP;
                $newEquipCharact->ALPHA_BOTTOM = $equipCharact->ALPHA_BOTTOM;
                $newEquipCharact->ALPHA_LEFT = $equipCharact->ALPHA_LEFT;
                $newEquipCharact->ALPHA_RIGHT = $equipCharact->ALPHA_RIGHT;
                $newEquipCharact->ALPHA_FRONT = $equipCharact->ALPHA_FRONT;
                $newEquipCharact->ALPHA_REAR = $equipCharact->ALPHA_REAR;

                $newEquipCharact->TEMP_TOP = $equipCharact->TEMP_TOP;
                $newEquipCharact->TEMP_BOTTOM = $equipCharact->TEMP_BOTTOM;
                $newEquipCharact->TEMP_LEFT = $equipCharact->TEMP_LEFT;
                $newEquipCharact->TEMP_RIGHT = $equipCharact->TEMP_RIGHT;
                $newEquipCharact->TEMP_FRONT = $equipCharact->TEMP_FRONT;
                $newEquipCharact->TEMP_REAR = $equipCharact->TEMP_REAR;

                $newEquipCharact->save();
            }
        }
    }

    private function getMinMax($limitItem) 
    {
        return MinMax::where('LIMIT_ITEM', $limitItem)->first();
    }

    private function duplicateEquipGenZone($idEquipGeneration, $listEquipGenZone, $numberOfZone)
    {
        if ($listEquipGenZone != null) {
            for ($i = 0; $i < count($listEquipGenZone); $i++) {
                $equipGenZone = new EquipGenZone();
                $equipGenZone->ID_EQUIPGENERATION = $idEquipGeneration;
                $equipGenZone->ZONE_NUMBER = $i + 1;
                $equipGenZone->TEMP_SENSOR = $listEquipGenZone[$i]['TEMP_SENSOR'];
                $equipGenZone->TOP_ADIABAT = $listEquipGenZone[$i]['TOP_ADIABAT'];
                $equipGenZone->BOTTOM_ADIABAT = $listEquipGenZone[$i]['BOTTOM_ADIABAT'];
                $equipGenZone->LEFT_ADIABAT = $listEquipGenZone[$i]['LEFT_ADIABAT'];
                $equipGenZone->RIGHT_ADIABAT = $listEquipGenZone[$i]['RIGHT_ADIABAT'];
                $equipGenZone->FRONT_ADIABAT = $listEquipGenZone[$i]['FRONT_ADIABAT'];
                $equipGenZone->REAR_ADIABAT = $listEquipGenZone[$i]['REAR_ADIABAT'];
                $equipGenZone->TOP_CHANGE = $listEquipGenZone[$i]['TOP_CHANGE'];
                $equipGenZone->TOP_PRM1 = $listEquipGenZone[$i]['TOP_PRM1'];
                $equipGenZone->TOP_PRM2 = $listEquipGenZone[$i]['TOP_PRM2'];
                $equipGenZone->TOP_PRM3 = $listEquipGenZone[$i]['TOP_PRM3'];
                $equipGenZone->BOTTOM_CHANGE = $listEquipGenZone[$i]['BOTTOM_CHANGE'];
                $equipGenZone->BOTTOM_PRM1 = $listEquipGenZone[$i]['BOTTOM_PRM1'];
                $equipGenZone->BOTTOM_PRM2 = $listEquipGenZone[$i]['BOTTOM_PRM2'];
                $equipGenZone->BOTTOM_PRM3 = $listEquipGenZone[$i]['BOTTOM_PRM3'];
                $equipGenZone->LEFT_CHANGE = $listEquipGenZone[$i]['LEFT_CHANGE'];
                $equipGenZone->LEFT_PRM1 = $listEquipGenZone[$i]['LEFT_PRM1'];
                $equipGenZone->LEFT_PRM2 = $listEquipGenZone[$i]['LEFT_PRM2'];
                $equipGenZone->LEFT_PRM3 = $listEquipGenZone[$i]['LEFT_PRM3'];
                $equipGenZone->RIGHT_CHANGE = $listEquipGenZone[$i]['RIGHT_CHANGE'];
                $equipGenZone->RIGHT_PRM1 = $listEquipGenZone[$i]['RIGHT_PRM1'];
                $equipGenZone->RIGHT_PRM2 = $listEquipGenZone[$i]['RIGHT_PRM2'];
                $equipGenZone->RIGHT_PRM3 = $listEquipGenZone[$i]['RIGHT_PRM3'];
                $equipGenZone->FRONT_CHANGE = $listEquipGenZone[$i]['FRONT_CHANGE'];
                $equipGenZone->FRONT_PRM1 = $listEquipGenZone[$i]['FRONT_PRM1'];
                $equipGenZone->FRONT_PRM2 = $listEquipGenZone[$i]['FRONT_PRM2'];
                $equipGenZone->FRONT_PRM3 = $listEquipGenZone[$i]['FRONT_PRM3'];
                $equipGenZone->REAR_CHANGE = $listEquipGenZone[$i]['REAR_CHANGE'];
                $equipGenZone->REAR_PRM1 = $listEquipGenZone[$i]['REAR_PRM1'];
                $equipGenZone->REAR_PRM2 = $listEquipGenZone[$i]['REAR_PRM2'];
                $equipGenZone->REAR_PRM3 = $listEquipGenZone[$i]['REAR_PRM3'];

                 // *_CHANGE = 2
                if ($equipGenZone->TOP_ADIABAT == 0 && $equipGenZone->TOP_CHANGE == 2) {
                    $equipGenZone->TOP_PRM1 = $this->units->convectionCoeff($equipGenZone->TOP_PRM1, 2, 0);
                    $equipGenZone->TOP_PRM2 = $this->units->convectionCoeff($equipGenZone->TOP_PRM2, 2, 0);
                    $equipGenZone->TOP_PRM3 = $this->units->convectionCoeff($equipGenZone->TOP_PRM3, 2, 0);
                }
                if ($equipGenZone->BOTTOM_ADIABAT == 0 && $equipGenZone->BOTTOM_CHANGE == 2) {
                    $equipGenZone->BOTTOM_PRM1 = $this->units->convectionCoeff($equipGenZone->BOTTOM_PRM1, 2, 0);
                    $equipGenZone->BOTTOM_PRM2 = $this->units->convectionCoeff($equipGenZone->BOTTOM_PRM2, 2, 0);
                    $equipGenZone->BOTTOM_PRM3 = $this->units->convectionCoeff($equipGenZone->BOTTOM_PRM3, 2, 0);
                }
                if ($equipGenZone->LEFT_ADIABAT == 0 && $equipGenZone->LEFT_CHANGE == 2) {
                    $equipGenZone->LEFT_PRM1 = $this->units->convectionCoeff($equipGenZone->LEFT_PRM1, 2, 0);
                    $equipGenZone->LEFT_PRM2 = $this->units->convectionCoeff($equipGenZone->LEFT_PRM2, 2, 0);
                    $equipGenZone->LEFT_PRM3 = $this->units->convectionCoeff($equipGenZone->LEFT_PRM3, 2, 0);
                }
                if ($equipGenZone->RIGHT_ADIABAT == 0 && $equipGenZone->RIGHT_CHANGE == 2) {
                    $equipGenZone->RIGHT_PRM1 = $this->units->convectionCoeff($equipGenZone->RIGHT_PRM1, 2, 0);
                    $equipGenZone->RIGHT_PRM2 = $this->units->convectionCoeff($equipGenZone->RIGHT_PRM2, 2, 0);
                    $equipGenZone->RIGHT_PRM3 = $this->units->convectionCoeff($equipGenZone->RIGHT_PRM3, 2, 0);
                }
                if ($equipGenZone->FRONT_ADIABAT == 0 && $equipGenZone->FRONT_CHANGE == 2) {
                    $equipGenZone->FRONT_PRM1 = $this->units->convectionCoeff($equipGenZone->FRONT_PRM1, 2, 0);
                    $equipGenZone->FRONT_PRM2 = $this->units->convectionCoeff($equipGenZone->FRONT_PRM2, 2, 0);
                    $equipGenZone->FRONT_PRM3 = $this->units->convectionCoeff($equipGenZone->FRONT_PRM3, 2, 0);
                }
                if ($equipGenZone->REAR_ADIABAT == 0 && $equipGenZone->REAR_CHANGE == 2) {
                    $equipGenZone->REAR_PRM1 = $this->units->convectionCoeff($equipGenZone->REAR_PRM1, 2, 0);
                    $equipGenZone->REAR_PRM2 = $this->units->convectionCoeff($equipGenZone->REAR_PRM2, 2, 0);
                    $equipGenZone->REAR_PRM3 = $this->units->convectionCoeff($equipGenZone->REAR_PRM3, 2, 0);
                }

                // *_CHANGE = 3
                if ($equipGenZone->TOP_ADIABAT == 0 && $equipGenZone->TOP_CHANGE == 3) {
                    $equipGenZone->TOP_PRM1 = $this->units->lenght($equipGenZone->TOP_PRM1, 2, 0);
                    $equipGenZone->TOP_PRM2 = $this->units->conductivity($equipGenZone->TOP_PRM2, 2, 0);
                }
                if ($equipGenZone->BOTTOM_ADIABAT == 0 && $equipGenZone->BOTTOM_CHANGE == 3) {
                    $equipGenZone->BOTTOM_PRM1 = $this->units->lenght($equipGenZone->BOTTOM_PRM1, 2, 0);
                    $equipGenZone->BOTTOM_PRM2 = $this->units->conductivity($equipGenZone->BOTTOM_PRM2, 2, 0);
                }
                if ($equipGenZone->LEFT_ADIABAT == 0 && $equipGenZone->LEFT_CHANGE == 3) {
                    $equipGenZone->LEFT_PRM1 = $this->units->lenght($equipGenZone->LEFT_PRM1, 2, 1);
                    $equipGenZone->LEFT_PRM2 = $this->units->conductivity($equipGenZone->LEFT_PRM2, 2, 0);
                }
                if ($equipGenZone->RIGHT_ADIABAT == 0 && $equipGenZone->RIGHT_CHANGE == 3) {
                    $equipGenZone->RIGHT_PRM1 = $this->units->lenght($equipGenZone->RIGHT_PRM1, 2, 1);
                    $equipGenZone->RIGHT_PRM2 = $this->units->conductivity($equipGenZone->RIGHT_PRM2, 2, 0);
                }
                if ($equipGenZone->FRONT_ADIABAT == 0 && $equipGenZone->FRONT_CHANGE == 3) {
                    $equipGenZone->FRONT_PRM1 = $this->units->lenght($equipGenZone->FRONT_PRM1, 2, 1);
                    $equipGenZone->FRONT_PRM2 = $this->units->conductivity($equipGenZone->FRONT_PRM2, 2, 0);
                }
                if ($equipGenZone->REAR_ADIABAT == 0 && $equipGenZone->REAR_CHANGE == 3) {
                    $equipGenZone->REAR_PRM1 = $this->units->lenght($equipGenZone->REAR_PRM1, 2, 1);
                    $equipGenZone->REAR_PRM2 = $this->units->conductivity($equipGenZone->REAR_PRM2, 2, 0);
                }
    
                $equipGenZone->save();
            }
        } else {
            for ($i = 1; $i <= intval($numberOfZone); $i++) {
                $equipGenZone = new EquipGenZone(); 
                $equipGenZone->ID_EQUIPGENERATION = $idEquipGeneration;
                $equipGenZone->ZONE_NUMBER = $i;
                $equipGenZone->TEMP_SENSOR = 0;
                $equipGenZone->TOP_ADIABAT = 0;
                $equipGenZone->BOTTOM_ADIABAT = 0;
                $equipGenZone->LEFT_ADIABAT = 0;
                $equipGenZone->RIGHT_ADIABAT = 0;
                $equipGenZone->FRONT_ADIABAT = 0;
                $equipGenZone->REAR_ADIABAT = 0;
                $equipGenZone->TOP_CHANGE = 1;
                $equipGenZone->TOP_PRM1 = 1.0;
                $equipGenZone->TOP_PRM2 = 0;
                $equipGenZone->TOP_PRM3 = 0;
                $equipGenZone->BOTTOM_CHANGE = 1;
                $equipGenZone->BOTTOM_PRM1 = 1.0;
                $equipGenZone->BOTTOM_PRM2 = 0;
                $equipGenZone->BOTTOM_PRM3 = 0;
                $equipGenZone->LEFT_CHANGE = 1;
                $equipGenZone->LEFT_PRM1 = 1.0;
                $equipGenZone->LEFT_PRM2 = 0;
                $equipGenZone->LEFT_PRM3 = 0;
                $equipGenZone->RIGHT_CHANGE = 1;
                $equipGenZone->RIGHT_PRM1 = 1.0;
                $equipGenZone->RIGHT_PRM2 = 0;
                $equipGenZone->RIGHT_PRM3 = 0;
                $equipGenZone->FRONT_CHANGE = 1;
                $equipGenZone->FRONT_PRM1 = 1.0;
                $equipGenZone->FRONT_PRM2 = 0;
                $equipGenZone->FRONT_PRM3 = 0;
                $equipGenZone->REAR_CHANGE = 1;
                $equipGenZone->REAR_PRM1 = 1.0;
                $equipGenZone->REAR_PRM2 = 0;
                $equipGenZone->REAR_PRM3 = 0;

                $equipGenZone->save();
            }
        }
        
    }

    private function duplicateEquipZone($oldIdEquip, $newIdEquip)
    {
        $equipZones = EquipZone::where('ID_EQUIP', $oldIdEquip)->get();
        if (count($equipZones) > 0) {
            foreach ($equipZones as $equipZone) {
                $newEquipZone = new EquipZone();
                $newEquipZone->ID_EQUIP = $newIdEquip;
                $newEquipZone->EQUIP_ZONE_NUMBER = $equipZone->EQUIP_ZONE_NUMBER;
                $newEquipZone->EQUIP_ZONE_LENGTH = $equipZone->EQUIP_ZONE_LENGTH;
                $newEquipZone->EQUIP_ZONE_NAME = $equipZone->EQUIP_ZONE_NAME;
                $newEquipZone->save();
            }
        }
    }

    public function getDecryptBinary($oldIdEquip, $newIdEquip)
    {
        $decrypt = null;

        $decrypt = DB::select("select convert(varchar(100), DecryptByPassPhrase('".ENCRYPT_KEY."', DLL_IDX)) as DLL, convert(varchar(100), DecryptByPassPhrase('".ENCRYPT_KEY."', FATHER_DLL_IDX)) as FATHER_DLL from EQUIPMENT where ID_EQUIP = " . $oldIdEquip);

        $DLL = "EncryptByPassPhrase('".ENCRYPT_KEY."', CAST(". $decrypt[0]->DLL ." AS varchar(100)), null)";
        $FATHER_DLL = "EncryptByPassPhrase('".ENCRYPT_KEY."', CAST(". $decrypt[0]->FATHER_DLL ." AS varchar(100)), null)";

        DB::update(DB::RAW('update EQUIPMENT set DLL_IDX = ' .$DLL.' where ID_EQUIP = ' . $newIdEquip));
        DB::update(DB::RAW('update EQUIPMENT set FATHER_DLL_IDX = '  .$FATHER_DLL.'  where ID_EQUIP = ' . $newIdEquip));
    }

    private function checkNameAndVersion($equipName, $equipVersion)
    {
        $equipments = Equipment::all();

        for($i = 0; $i < count($equipments); $i++) {
            if (strtoupper($equipments[$i]->EQUIP_NAME) == strtoupper($equipName)) {
                if (intval($equipments[$i]->EQUIP_VERSION) == intval($equipVersion)) {
                    return false;
                }
            }
        }
        return true;
    }

    public function getEquipmentFilter($id)
    {
        $equipment = Equipment::find($id);
        $checkGenZone = false;
        
        if ($equipment) {
            $listEquipZone = EquipZone::where('ID_EQUIP', $id)->orderBy('EQUIP_ZONE_NUMBER', 'ASC')->get();
            
            if (count($listEquipZone) > 0) {
                $equipment->EquipZone = $listEquipZone;
            } else {
                $equipment->EquipZone = null;
            }

            if ($equipment->ID_EQUIPGENERATION > 0) {
                $listEquipGenZone = EquipGenZone::where('ID_EQUIPGENERATION', $equipment->ID_EQUIPGENERATION)
                ->orderBy('ZONE_NUMBER', 'ASC')->get();

                if (count($listEquipGenZone) > 0) {

                    foreach ($listEquipGenZone as $key) {
                        // AlphaI/Alpha in the mylar zone
                        if ($key->TOP_ADIABAT == 0 && $key->TOP_CHANGE == 2) {
                            $key->TOP_PRM1 = $this->units->convectionCoeff($key->TOP_PRM1, 2, 1);
                        }
                        if ($key->BOTTOM_ADIABAT == 0 && $key->BOTTOM_CHANGE == 2) {
                            $key->BOTTOM_PRM1 = $this->units->convectionCoeff($key->BOTTOM_PRM1, 2, 1);
                        }
                        if ($key->LEFT_ADIABAT == 0 && $key->LEFT_CHANGE == 2) {
                            $key->LEFT_PRM1 = $this->units->convectionCoeff($key->LEFT_PRM1, 2, 1);
                        }
                        if ($key->RIGHT_ADIABAT == 0 && $key->RIGHT_CHANGE == 2) {
                            $key->RIGHT_PRM1 = $this->units->convectionCoeff($key->RIGHT_PRM1, 2, 1);
                        }
                        if ($key->FRONT_ADIABAT == 0 && $key->FRONT_CHANGE == 2) {
                            $key->FRONT_PRM1 = $this->units->convectionCoeff($key->FRONT_PRM1, 2, 1);
                        }
                        if ($key->REAR_ADIABAT == 0 && $key->REAR_CHANGE == 2) {
                            $key->REAR_PRM1 = $this->units->convectionCoeff($key->REAR_PRM1, 2, 1);
                        }
                        // Alpha spraying zone
                        if ($key->TOP_ADIABAT == 0 && $key->TOP_CHANGE == 2) {
                            $key->TOP_PRM2 = $this->units->convectionCoeff($key->TOP_PRM2, 2, 1);
                        }
                        if ($key->BOTTOM_ADIABAT == 0 && $key->BOTTOM_CHANGE == 2) {
                            $key->BOTTOM_PRM2 = $this->units->convectionCoeff($key->BOTTOM_PRM2, 2, 1);
                        }
                        if ($key->LEFT_ADIABAT == 0 && $key->LEFT_CHANGE == 2) {
                            $key->LEFT_PRM2 = $this->units->convectionCoeff($key->LEFT_PRM2, 2, 1);
                        }
                        if ($key->RIGHT_ADIABAT == 0 && $key->RIGHT_CHANGE == 2) {
                            $key->RIGHT_PRM2 = $this->units->convectionCoeff($key->RIGHT_PRM2, 2, 1);
                        }
                        if ($key->FRONT_ADIABAT == 0 && $key->FRONT_CHANGE == 2) {
                            $key->FRONT_PRM2 = $this->units->convectionCoeff($key->FRONT_PRM2, 2, 1);
                        }
                        if ($key->REAR_ADIABAT == 0 && $key->REAR_CHANGE == 2) {
                            $key->REAR_PRM2 = $this->units->convectionCoeff($key->REAR_PRM2, 2, 1);
                        }
                        // Alpha stabilization zone
                        if ($key->TOP_ADIABAT == 0 && $key->TOP_CHANGE == 2) {
                            $key->TOP_PRM3 = $this->units->convectionCoeff($key->TOP_PRM3, 2, 1);
                        }
                        if ($key->BOTTOM_ADIABAT == 0 && $key->BOTTOM_CHANGE == 2) {
                            $key->BOTTOM_PRM3 = $this->units->convectionCoeff($key->BOTTOM_PRM3, 2, 1);
                        }
                        if ($key->LEFT_ADIABAT == 0 && $key->LEFT_CHANGE == 2) {
                            $key->LEFT_PRM3 = $this->units->convectionCoeff($key->LEFT_PRM3, 2, 1);
                        }
                        if ($key->RIGHT_ADIABAT == 0 && $key->RIGHT_CHANGE == 2) {
                            $key->RIGHT_PRM3 = $this->units->convectionCoeff($key->RIGHT_PRM3, 2, 1);
                        }
                        if ($key->FRONT_ADIABAT == 0 && $key->FRONT_CHANGE == 2) {
                            $key->FRONT_PRM3 = $this->units->convectionCoeff($key->FRONT_PRM3, 2, 1);
                        }
                        if ($key->REAR_ADIABAT == 0 && $key->REAR_CHANGE == 2) {
                            $key->REAR_PRM3 = $this->units->convectionCoeff($key->REAR_PRM3, 2, 1);
                        }
                        // Insulation tickness
                        if ($key->TOP_ADIABAT == 0 && $key->TOP_CHANGE == 3) {
                            $key->TOP_PRM1 = $this->units->lenght($key->TOP_PRM1, 2, 1);
                        }
                        if ($key->BOTTOM_ADIABAT == 0 && $key->BOTTOM_CHANGE == 3) {
                            $key->BOTTOM_PRM1 = $this->units->lenght($key->BOTTOM_PRM1, 2, 1);
                        }
                        if ($key->LEFT_ADIABAT == 0 && $key->LEFT_CHANGE == 3) {
                            $key->LEFT_PRM1 = $this->units->lenght($key->LEFT_PRM1, 2, 1);
                        }
                        if ($key->RIGHT_ADIABAT == 0 && $key->RIGHT_CHANGE == 3) {
                            $key->RIGHT_PRM1 = $this->units->lenght($key->RIGHT_PRM1, 2, 1);
                        }
                        if ($key->FRONT_ADIABAT == 0 && $key->FRONT_CHANGE == 3) {
                            $key->FRONT_PRM1 = $this->units->lenght($key->FRONT_PRM1, 2, 1);
                        }
                        if ($key->REAR_ADIABAT == 0 && $key->REAR_CHANGE == 3) {
                            $key->REAR_PRM1 = $this->units->lenght($key->REAR_PRM1, 2, 1);
                        }
                        // Insulation conductivity
                        if ($key->TOP_ADIABAT == 0 && $key->TOP_CHANGE == 3) {
                            $key->TOP_PRM2 = $this->units->conductivity($key->TOP_PRM2, 2, 1);
                        }
                        if ($key->BOTTOM_ADIABAT == 0 && $key->BOTTOM_CHANGE == 3) {
                            $key->BOTTOM_PRM2 = $this->units->conductivity($key->BOTTOM_PRM2, 2, 1);
                        }
                        if ($key->LEFT_ADIABAT == 0 && $key->LEFT_CHANGE == 3) {
                            $key->LEFT_PRM2 = $this->units->conductivity($key->LEFT_PRM2, 2, 1);
                        }
                        if ($key->RIGHT_ADIABAT == 0 && $key->RIGHT_CHANGE == 3) {
                            $key->RIGHT_PRM2 = $this->units->conductivity($key->RIGHT_PRM2, 2, 1);
                        }
                        if ($key->FRONT_ADIABAT == 0 && $key->FRONT_CHANGE == 3) {
                            $key->FRONT_PRM2 = $this->units->conductivity($key->FRONT_PRM2, 2, 1);
                        }
                        if ($key->REAR_ADIABAT == 0 && $key->REAR_CHANGE == 3) {
                            $key->REAR_PRM2 = $this->units->conductivity($key->REAR_PRM2, 2, 1);
                        }
                    }
                    $equipment->EquipGenZone = $listEquipGenZone;
                } else {
                    $checkGenZone = true;
                }
            } else {
                $checkGenZone = true;
            }

            if ($checkGenZone) {
                $equipGenZone = new EquipGenZone();
                $equipGenZone->TEMP_SENSOR = 0;
                $equipGenZone->TOP_ADIABAT = 0;
                $equipGenZone->BOTTOM_ADIABAT = 0;
                $equipGenZone->LEFT_ADIABAT = 0;
                $equipGenZone->RIGHT_ADIABAT = 0;
                $equipGenZone->FRONT_ADIABAT = 0;
                $equipGenZone->REAR_ADIABAT = 0;

                $equipGenZone->TOP_CHANGE = 1;
                $equipGenZone->BOTTOM_CHANGE = 1;
                $equipGenZone->LEFT_CHANGE = 1;
                $equipGenZone->RIGHT_CHANGE = 1;
                $equipGenZone->FRONT_CHANGE = 1;
                $equipGenZone->REAR_CHANGE = 1;

                $equipGenZone->TOP_PRM1 = 1;
                $equipGenZone->BOTTOM_PRM1 = 1;
                $equipGenZone->LEFT_PRM1 = 1;
                $equipGenZone->RIGHT_PRM1 = 1;
                $equipGenZone->FRONT_PRM1 = 1;
                $equipGenZone->REAR_PRM1 = 1;

                $equipGenZone->TOP_PRM2 = 0;
                $equipGenZone->BOTTOM_PRM2 = 0;
                $equipGenZone->LEFT_PRM2 = 0;
                $equipGenZone->RIGHT_PRM2 = 0;
                $equipGenZone->FRONT_PRM2 = 0;
                $equipGenZone->REAR_PRM2 = 0;

                $equipGenZone->TOP_PRM3 = 0;
                $equipGenZone->BOTTOM_PRM3 = 0;
                $equipGenZone->LEFT_PRM3 = 0;
                $equipGenZone->RIGHT_PRM3 = 0;
                $equipGenZone->FRONT_PRM3 = 0;
                $equipGenZone->REAR_PRM3 = 0;

                $arr = array();
                
                for ($i = 0; $i < intval($equipment->NUMBER_OF_ZONES); $i++ ) {
                    array_push($arr, $equipGenZone);
                }
                $equipment->EquipGenZone = $arr;
            }
        }

        return $equipment;
    }

    public function checkEquipment()
    {
        $input = $this->request->all();

        $nameE = $typeCalculate = $versionE = $equipId1 = $equipId2 = $tempSetPoint0 = $tempSetPoint3 = $dwellingTime0 = $dwellingTime1 = $dwellingTime3 = $newPos = $typeEquipment = null;
        
        if (isset($input['typeEquipment'])) $typeEquipment = intval($input['typeEquipment']);
        if (isset($input['nameEquipment'])) $nameE = $input['nameEquipment'];
        if (isset($input['versionEquipment'])) $versionE = floatval($input['versionEquipment']);
        if (isset($input['typeCalculate'])) $typeCalculate = intval($input['typeCalculate']);
        
        if ($typeEquipment == 0) {
            if (isset($input['equipmentId1'])) $equipId1 = intval($input['equipmentId1']);
            if (isset($input['tempSetPoint'])) $tempSetPoint0 = floatval($input['tempSetPoint']);
            if (isset($input['dwellingTime'])) $dwellingTime0 = floatval($input['dwellingTime']);
        } else if ($typeEquipment == 1) {
            if (isset($input['equipmentId1'])) $equipId1 = intval($input['equipmentId1']);
            if (isset($input['dwellingTime'])) $dwellingTime1 = floatval($input['dwellingTime']);
            if (isset($input['newPos'])) $newPos = $input['newPos'];
        } else if ($typeEquipment == 2) {
            if (isset($input['equipmentId1'])) $equipId1 = intval($input['equipmentId1']);
        } else {
            if (isset($input['equipmentId1'])) $equipId1 = intval($input['equipmentId1']);
            if (isset($input['equipmentId2'])) $equipId2 = intval($input['equipmentId2']);
            if (isset($input['tempSetPoint'])) $tempSetPoint3 = floatval($input['tempSetPoint']);
            if (isset($input['dwellingTime'])) $dwellingTime3 = floatval($input['dwellingTime']);
        }

        $eq = Equipment::find($equipId1);

        if ($typeEquipment == 3) {
            $eq2 = Equipment::find($equipId2);
            $str1 = null;
            $str2 = null;
            $min_Dt = null;
            $max_Dt = null;
            $min_Tr = null;
            $max_Tr = null;

            // get min max for TS
            if ($dwellingTime3 != null) {
                $limitItem = ( $eq && ($eq->ITEM_TS > 0)) ? $eq->ITEM_TS : 1068;
                $limitItem2 = ( $eq2 && ($eq2->ITEM_TS > 0)) ? $eq2->ITEM_TS : 1068;

                $mm = $this->minmax->getMinMaxTimes($limitItem, 2);
                $mm2 = $this->minmax->getMinMaxTimes($limitItem2, 2);

                if ($mm->LIMIT_MIN < $mm2->LIMIT_MIN) {
                    $str1 = $this->units->time($mm2->LIMIT_MIN, 2, 1);
                } else {
                    $str1 = $this->units->time($mm->LIMIT_MIN, 2, 1);
                }

                if ($mm->LIMIT_MAX < $mm2->LIMIT_MAX) {
                    $str2 = $this->units->time($mm->LIMIT_MAX, 2, 1);
                } else {
                    $str2 = $this->units->time($mm2->LIMIT_MAX, 2, 1);
                }

                $min_Dt = doubleval($str1);
                $max_Dt = doubleval($str2);

                if ($min_Dt > $max_Dt) {
                    $lfTmp  = $min_Dt;
                    $min_Dt = $max_Dt;
                    $max_Dt = $lfTmp;
                }

                $dwellingTime3 = $this->units->time($dwellingTime3, 2, 0);

                if ($dwellingTime3 < $min_Dt || $dwellingTime3 > $max_Dt ) {
                    return  [
                        "Message" => "Value out of range in Dwelling Time (" . doubleval($min_Dt) . " : " . doubleval($max_Dt) . ")"
                    ];
                }
            }

            // get min max for TR

            if ($tempSetPoint3 != null) {
                $limitItemTR = ( $eq && ($eq->ITEM_TR > 0)) ? $eq->ITEM_TR : 1067;
                $limitItem2TR = ( $eq2 && ($eq2->ITEM_TR > 0)) ? $eq2->ITEM_TR : 1067;

                $mm = $this->minmax->getMinMaxTimes($limitItemTR, 2);
                $mm2 = $this->minmax->getMinMaxTimes($limitItem2TR, 2);

                if ($mm->LIMIT_MIN < $mm2->LIMIT_MIN) {
                    $str1 = $this->units->controlTemperature($mm2->LIMIT_MIN, 2, 1);
                } else {
                    $str1 = $this->units->controlTemperature($mm->LIMIT_MIN, 2, 1);
                }

                if ($mm->LIMIT_MAX < $mm2->LIMIT_MAX) {
                    $str2 = $this->units->controlTemperature($mm->LIMIT_MAX, 2, 1);
                } else {
                    $str2 = $this->units->controlTemperature($mm2->LIMIT_MAX, 2, 1);
                }

                $min_Tr = doubleval($str1);
                $max_Tr = doubleval($str2);

                if ($min_Tr > $max_Tr) {
                    $lfTmp  = $min_Tr;
                    $min_Tr = $max_Tr;
                    $max_Tr = $lfTmp;
                }
                
                $tempSetPoint3 = $this->units->time($tempSetPoint3, 2, 0);
                if ( $tempSetPoint3 < $min_Tr || $tempSetPoint3 > $max_Tr ) {
                    return  [
                        "Message" => "Value out of range in Regulation temperature (" . doubleval($min_Tr) . " : " . doubleval($max_Tr) . ")"
                    ];
                }
            }
        } else {
            if ($typeEquipment == 0) {
                if ($tempSetPoint0 != null) {
                    $tempSetPoint0 = $this->units->controlTemperature($tempSetPoint0, 2, 0);
                    $limitItem0 = $eq->ITEM_TR > 0 ? $eq->ITEM_TR : 1067;
                    $checkTempSetPoint0 = $this->minmax->checkMinMaxValue($tempSetPoint0, $limitItem0);
                    if ( !$checkTempSetPoint0 ) {
                        $mm = $this->minmax->getMinMaxControlTemperature($limitItem0, 2);
                        return  [
                            "Message" => "Value out of range in Regulation temperature (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
                        ];
                    }
                }
                
                if ($dwellingTime0 != null) {
                    $dwellingTime0 = $this->units->time($dwellingTime0, 2, 0);
                    $limitItem0 = $eq->ITEM_TS > 0 ? $eq->ITEM_TS : 1068;
                    $checkDwellingTime0 = $this->minmax->checkMinMaxValue($dwellingTime0, $limitItem0);
                    if ( !$checkDwellingTime0 ) {
                        $mm = $this->minmax->getMinMaxTimes($limitItem0, 2);
                        return  [
                            "Message" => "Value out of range in Dwelling Time (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
                        ];
                    }
                }
            }
            
            if ($typeEquipment == 1) {
                if ($dwellingTime1 != null) {
                    $dwellingTime1 = $this->units->time($dwellingTime1, 2, 0);
                    $limitItem = $eq->ITEM_TS > 0 ? $eq->ITEM_TS : 1068;
                    $checkDwellingTime1 = $this->minmax->checkMinMaxValue($dwellingTime1, $limitItem);
                    if ( !$checkDwellingTime1 ) {
                        $mm = $this->minmax->getMinMaxTimes($limitItem, 2);
                        return  [
                            "Message" => "Value out of range in Dwelling Time (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
                        ];
                    }
                }

                if ($newPos != null) {
                    $newPos = $this->units->time($newPos, 2, 0);
                    $limitItem = $eq->ITEM_TS > 0 ? $eq->ITEM_TS : 1068;
                    $checkNewPos = $this->minmax->checkMinMaxValue($newPos, $limitItem);
                    if ( !$checkNewPos ) {
                        $mm = $this->minmax->getMinMaxTimes($limitItem, 2);
                        return  [
                            "Message" => "Value out of range in New position (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
                        ];
                    }
                }
            }
            
        }

        return 1;
    }

    public function checkRedrawCurves()
    {
        $input = $this->request->all();

        $ID_EQUIP = $REGUL_TEMP = $DWELLING_TIME = $PRODTEMP = $LOADINGRATE = $result = null;

        if (isset($input['ID_EQUIP'])) $ID_EQUIP = intval($input['ID_EQUIP']);
        if (isset($input['REGUL_TEMP'])) $REGUL_TEMP = floatval($input['REGUL_TEMP']);
        if (isset($input['DWELLING_TIME'])) $DWELLING_TIME = floatval($input['DWELLING_TIME']);
        if (isset($input['PRODTEMP'])) $PRODTEMP = floatval($input['PRODTEMP']);
        if (isset($input['LOADINGRATE'])) $LOADINGRATE = floatval($input['LOADINGRATE']);

        $equipment = Equipment::find($ID_EQUIP);
        if ($equipment) {

            if ($equipment->STD == 1) {
                $equipGeneration = EquipGeneration::where('ID_EQUIP', $equipment->ID_EQUIPGENERATION)->first();

                if ($equipGeneration) {

                    if ($this->equip->getCapability($equipment->CAPABILITIES, 65536)) {
                        $DWELLING_TIME = $this->units->time($DWELLING_TIME, 0, 0);
                        $checkDWELLING_TIME = $this->minmax->checkMinMaxValue($DWELLING_TIME, $equipment->ITEM_TS);

                        if ( !$checkDWELLING_TIME) {
                            $mm = $this->minmax->getMinMaxTimes($equipment->ITEM_TS, 0);
                            return  [
                                "Message" => "Value out of range in Dwelling Time (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
                            ];
                        }
                    } else {
                        $REGUL_TEMP = $this->units->controlTemperature($REGUL_TEMP, 0, 0);
                        $checkREGUL_TEMP = $this->minmax->checkMinMaxValue($REGUL_TEMP, $equipment->ITEM_TR);

                        if ( !$checkREGUL_TEMP) {
                            $mm = $this->minmax->getMinMaxControlTemperature($equipment->ITEM_TR, 0);
                            return  [
                                "Message" => "Value out of range in Regulation temperature (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
                            ];
                        }
                    }
                    $PRODTEMP = $this->units->prodTemperature($PRODTEMP, 1, 0);
                    $checkPRODTEMP = $this->minmax->checkMinMaxValue($PRODTEMP, 1066);

                    if ( !$checkPRODTEMP) {
                        $mm = $this->minmax->getMinMaxProdTemperature(1066, 0);
                        return  [
                            "Message" => "Value out of range in Initial temperature of the product (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
                        ];
                    }

                    $checkLOADINGRATE = $this->minmax->checkMinMaxValue($LOADINGRATE, 704);

                    if ( !$checkLOADINGRATE) {
                        $mm = $this->minmax->getMinMaxLimitItem(704, 1);
                        return  [
                            "Message" => "Value out of range in Loading Rate (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
                        ];
                    }
                }
            }
        }

        return 1;
    }

    public function checkBuildForNewTR()
    {
        $input = $this->request->all();

        $lfOldTR = $lfNewTR = $ID_STUDY = $ID_EQUIP = $equipment = $equipGeneration = $result = null;
        $nbStudies = $lastIdStudy = $id_equip = 0;

        if (isset($input['ID_EQUIP'])) $ID_EQUIP = intval($input['ID_EQUIP']);
        if (isset($input['ID_STUDY'])) $ID_STUDY = intval($input['ID_STUDY']);
        if (isset($input['tr_current'])) $lfOldTR = floatval($input['tr_current']);
        if (isset($input['tr_new'])) $lfNewTR = $this->units->controlTemperature(floatval($input['tr_new']), 0, 0);

        $equipment = Equipment::find($ID_EQUIP);
        if ($equipment) {

            $checkLfNewTR = $this->minmax->checkMinMaxValue($lfNewTR, $equipment->ITEM_TR);

            if ( !$checkLfNewTR) {
                $mm = $this->minmax->getMinMaxLimitItem($equipment->ITEM_TR, 0);
                return  [
                    "Message" => "Value out of range in New temperature (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
                ];
            }
        }

        return 1;
    }
}
