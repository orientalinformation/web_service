<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use App\Models\Translation;
use App\Models\Component;
use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\ValueListService;
use Carbon\Carbon;
use App\Models\Compenth;
use App\Models\MinMax;
use App\Models\Language;
use App\Models\User;
use App\Kernel\KernelService;
use App\Models\ProductElmt;
use App\Models\Equipment;
use App\Models\EquipGenZone;
use App\Models\EquipZone;
use App\Cryosoft\UnitsService;
use App\Cryosoft\MinMaxService;
use App\Cryosoft\EquipmentsService;

class ReferenceData extends Controller
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
     * @var App\Cryosoft\UnitsService
     */
    protected $units;
    
        /**
     * @var App\Cryosoft\MinMaxService
     */
    protected $minmax;

    protected $equip;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth, UnitsConverterService $convert, ValueListService $value, 
    KernelService $kernel, UnitsService $units, MinMaxService $minmax, EquipmentsService $equip)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->convert = $convert;
        $this->value = $value;
        $this->kernel = $kernel;
        $this->units = $units;
        $this->minmax = $minmax;
        $this->equip = $equip;
    }

    public function getFamilyTranslations($transType)
    {
        $translations = Translation::where('TRANS_TYPE', $transType)
            ->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
            ->get();

        for ($i = 0; $i < $translations->count(); $i++) {
            $translations[$i]->LABEL = \mb_convert_encoding($translations[$i]->LABEL, "UTF-8");
        }
        
        return $translations;
    }

    public function getSubFamilyTranslations($transType, $compfamily)
    {
        //compfamily this is value return from combobox after select
        $translations = Translation::where('TRANS_TYPE', $transType)
            ->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
            ->where('ID_TRANSLATION', '>=', $compfamily*100)
            ->where('ID_TRANSLATION', '<', ($compfamily + 1)*100)
            ->get();

        for ($i = 0; $i < $translations->count(); $i++) {
            $translations[$i]->LABEL = \mb_convert_encoding($translations[$i]->LABEL, "UTF-8");
        }
        
        return $translations;
    }

    public function getDataComponent()
    {
        $input = $this->request->all();

        $compFamily = null;
        $COMP_COMMENT = $COMP_NAME = '';
        $LIPID = $GLUCID = $PROTID = $WATER = $FREEZE_TEMP = $COMP_VERSION = $CONDUCT_TYPE = 0;
        $SALT = $AIR = $NON_FROZEN_WATER = $PRODUCT_TYPE = $SUB_TYPE = $FATTYPE = 0;
        $release = $NATURE_TYPE = 1;

        if (isset($input['compfamily'])) $compFamily = intval($input['compfamily']);

        $productFamily = $this->getFamilyTranslations(14);
        if ($compFamily == 0) {
            $subFamily = $this->getFamilyTranslations(16);
        } else {
            $subFamily = $this->getSubFamilyTranslations(16, intval($compFamily));
            $PRODUCT_TYPE = $compFamily;
        }

        $productNature = $this->getFamilyTranslations(15);
        $conductivity = $this->getFamilyTranslations(9);
        $fatType = $this->getFamilyTranslations(12);

        $array = [
            'productFamily' => $productFamily,
            'PRODUCT_TYPE' => $PRODUCT_TYPE,
            'subFamily' => $subFamily,
            'SUB_TYPE' => $SUB_TYPE,
            'productNature' => $productNature,
            'NATURE_TYPE' => $NATURE_TYPE,
            'Conductivity' => $conductivity,
            'CONDUCT_TYPE' => $CONDUCT_TYPE,
            'FatType' => $fatType,
            'FATTYPE' => $FATTYPE,
            'COMP_NAME' => $COMP_NAME,
            'COMP_COMMENT' => $COMP_COMMENT,
            'COMP_VERSION' => $COMP_VERSION,
            'FREEZE_TEMP' => $this->units->temperature($FREEZE_TEMP, 2, 1),
            'WATER' => $WATER,
            'PROTID' => $PROTID,
            'LIPID' => $LIPID,
            'GLUCID' => $GLUCID,
            'SALT' => $SALT,
            'AIR' => $AIR,
            'NON_FROZEN_WATER' => $NON_FROZEN_WATER,
            'release' => $release,
            'ID_USER' => $this->auth->user()->ID_USER,
        ];

        return $array;
    }

    public function getDataSubFamily() 
    {
        $input = $this->request->all();
        $compFamily = null;
        if (isset($input['compfamily'])) $compFamily = intval($input['compfamily']);

        if ($compFamily == 0) {
            $subFamily = $this->getFamilyTranslations(16);
        } else {
            $subFamily = $this->getSubFamilyTranslations(16, intval($compFamily));
        }

        return $subFamily;
    }

    public function getMyComponent()
    {
        $mine = Component::where('ID_USER', $this->auth->user()->ID_USER)
        ->join('TRANSLATION', 'ID_COMP', '=', 'TRANSLATION.ID_TRANSLATION')
        ->where('TRANSLATION.TRANS_TYPE', 1)->where('TRANSLATION.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->get();

        foreach ($mine as $m) {
            $m->AIR = round(($m->AIR / 0.01205));
            $m->FREEZE_TEMP = $this->units->temperature($m->FREEZE_TEMP, 2, 1);
            $m->NON_FROZEN_WATER = number_format((float)$m->NON_FROZEN_WATER, 2, '.', '');
        }

        $others = Component::join('LN2USER', 'LN2USER.ID_USER', '=', 'COMPONENT.ID_USER')
            ->join('TRANSLATION', 'COMPONENT.ID_COMP', '=', 'TRANSLATION.ID_TRANSLATION')
            ->where('TRANSLATION.TRANS_TYPE', 1)->where('TRANSLATION.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
            ->where('COMPONENT.ID_USER', '!=', $this->auth->user()->ID_USER)
            ->orderBy('LABEL', 'ASC')->get();

        foreach ($others as $other) {
            $other->AIR = round(($other->AIR / 0.01205));
            $other->FREEZE_TEMP = $this->units->temperature($other->FREEZE_TEMP, 2, 1);
            $other->NON_FROZEN_WATER = number_format((float)$other->NON_FROZEN_WATER, 2, '.', '');
        }

        return compact('mine', 'others');
    }

    public function getComponentById($id) 
    {
        $comp = Component::join('TRANSLATION', 'ID_COMP', '=', 'TRANSLATION.ID_TRANSLATION')
        ->where('TRANSLATION.TRANS_TYPE', 1)
        ->where('TRANSLATION.ID_TRANSLATION', $id)
        ->where('TRANSLATION.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->first();

        if ($comp) {
            $comp->AIR = round(($comp->AIR / 0.01205));
            $comp->FREEZE_TEMP = $this->units->temperature($comp->FREEZE_TEMP, 2, 1);
            $comp->NON_FROZEN_WATER = number_format((float)$comp->NON_FROZEN_WATER, 2, '.', '');
        }

        return $comp;
    }

    public function saveDataComponent()
    {
        $result = $this->saveComponent($this->request, 0);

        if ($result > 0) {
            $comp = Component::where('ID_USER', $this->auth->user()->ID_USER)
            ->join('TRANSLATION', 'ID_COMP', '=', 'TRANSLATION.ID_TRANSLATION')
            ->where('TRANSLATION.TRANS_TYPE', 1)->where('TRANSLATION.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
            ->where('ID_COMP', $result)->first();

            $comp->AIR = round(($comp->AIR / 0.01205));
            $comp->FREEZE_TEMP = $this->units->temperature($comp->FREEZE_TEMP, 2, 1);
            $comp->NON_FROZEN_WATER = number_format((float)$comp->NON_FROZEN_WATER, 2, '.', '');

            return $comp;
        }

        return $result;
    }

    public function getMinMax($limitItem) 
    {
        return MinMax::where('LIMIT_ITEM', $limitItem)->first();
    }

    public function calculateFreeze()
    {   
        $input = $this->request->all();
        $idComp = $result = $comp = null;
        
        if (isset($input['ID_COMP'])) $idComp = intval($input['ID_COMP']);

        if ($idComp == null) {
            $idComp = $this->saveComponent($this->request, 1);

            if ($idComp == -3) return -3;
            if ($idComp == -2) return -2;
            if ($idComp == -4) return -4;
            // if ($idComp == -5) return -5;

            $result = $this->startFCCalculation($idComp);

            $comp = Component::where('ID_USER', $this->auth->user()->ID_USER)
            ->join('TRANSLATION', 'ID_COMP', '=', 'TRANSLATION.ID_TRANSLATION')
            ->where('TRANSLATION.TRANS_TYPE', 1)->where('TRANSLATION.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
            ->where('ID_COMP', $idComp)->first();

            $comp->AIR = round(($comp->AIR / 0.01205));
            $comp->FREEZE_TEMP = $this->units->temperature($comp->FREEZE_TEMP, 2, 1);
            $comp->NON_FROZEN_WATER = number_format((float)$comp->NON_FROZEN_WATER, 2, '.', '');
        } else {
            $component = Component::find($idComp);

            if ($component) {
                if ($component->COMP_RELEASE == 7) $component->COMP_RELEASE = 8;

                $rsIdComp = $this->saveCalculate($this->request, $component, 1);

                if ($rsIdComp == -3) return -3;
                if ($rsIdComp == -2) return -2;
                if ($rsIdComp == -4) return -4;
                // if ($rsIdComp == -5) return -5;    
            }
            $result = $this->startFCCalculation($idComp); 
                
            $comp = Component::where('ID_USER', $this->auth->user()->ID_USER)
            ->join('TRANSLATION', 'ID_COMP', '=', 'TRANSLATION.ID_TRANSLATION')
            ->where('TRANSLATION.TRANS_TYPE', 1)->where('TRANSLATION.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
            ->where('ID_COMP', $idComp)->first();

            $comp->AIR = round(($comp->AIR / 0.01205));
            $comp->FREEZE_TEMP = $this->units->temperature($comp->FREEZE_TEMP, 2, 1);
            $comp->NON_FROZEN_WATER = number_format((float)$comp->NON_FROZEN_WATER, 2, '.', '');
        }

        return [
            "CheckCalculate" => $result,
            "VComponent" => $comp
        ];
    }

    public function startFCCalculate()
    {
        $input = $this->request->all();
        $idComp = null;

        if (isset($input['ID_COMP'])) $idComp = intval($input['ID_COMP']);

        $component = Component::find($idComp);
        if ($component) {
            if ($component->COMP_RELEASE == 7) $component->COMP_RELEASE = 8;
            $this->saveCalculate($this->request, $component, 0);
        }

        return $this->startCBCalculation($idComp);
    }

    public function startFCCalculation($idComp)
    {
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idComp, 0, 1, 1, 'c:\\temp\\Freeze_log.txt');
        return $this->kernel->getKernelObject('FreezeCalculator')->FCFreezeCalculation($conf);
    }

    public function startCBCalculation($idComp)
    {
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $idComp);
        return $this->kernel->getKernelObject('ComponentBuilder')->CBComponentCalculation($conf);
    }

    private function saveComponent(Request $request, $freeze = 0)
    {
        $input = $this->request->all();

        $COMP_COMMENT = $COMP_NAME = $COMP_NAME_NEW = $COMP_VERSION_NEW = $TYPE_COMP = $ID_COMP = null;
        $LIPID = $GLUCID = $PROTID = $WATER = $FREEZE_TEMP = $COMP_VERSION = $CONDUCT_TYPE = $compositionTotal = 0;
        $SALT = $AIR = $NON_FROZEN_WATER = $PRODUCT_TYPE = $SUB_TYPE = $FATTYPE = $DENSITY = $HEAT = 0;
        $release = $NATURE_TYPE = 1;
        $tempertures = array();
        $current = Carbon::now('Asia/Ho_Chi_Minh');

        if (isset($FREEZE_TEMP)) $FREEZE_TEMP = floatval($input['FREEZE_TEMP']);
        if (isset($input['COMP_COMMENT'])) $COMP_COMMENT = $input['COMP_COMMENT'];
        if (isset($input['COMP_NAME'])) $COMP_NAME = $input['COMP_NAME'];
        if (isset($input['COMP_NAME_NEW'])) $COMP_NAME_NEW = $input['COMP_NAME_NEW'];
        if (isset($input['NON_FROZEN_WATER'])) $NON_FROZEN_WATER = floatval($input['NON_FROZEN_WATER']);
        if (isset($input['WATER'])) $WATER = floatval($input['WATER']);
        if (isset($input['AIR'])) $AIR = floatval($input['AIR']);
        if (isset($input['SALT'])) $SALT = floatval($input['SALT']);
        if (isset($input['PROTID'])) $PROTID = floatval($input['PROTID']);
        if (isset($input['GLUCID'])) $GLUCID = floatval($input['GLUCID']);
        if (isset($input['LIPID'])) $LIPID = floatval($input['LIPID']);
        if (isset($input['CONDUCT_TYPE'])) $CONDUCT_TYPE = intval($input['CONDUCT_TYPE']);
        if (isset($input['COMP_VERSION'])) $COMP_VERSION = floatval($input['COMP_VERSION']);
        if (isset($input['COMP_VERSION_NEW'])) $COMP_VERSION_NEW = floatval($input['COMP_VERSION_NEW']);
        if (isset($input['FATTYPE'])) $FATTYPE = intval($input['FATTYPE']);
        if (isset($input['NATURE_TYPE'])) $NATURE_TYPE = intval($input['NATURE_TYPE']);
        if (isset($input['PRODUCT_TYPE'])) $PRODUCT_TYPE = intval($input['PRODUCT_TYPE']);
        if (isset($input['SUB_TYPE'])) $SUB_TYPE = intval($input['SUB_TYPE']);
        if (isset($input['Temperatures'])) $temperatures = $input['Temperatures'];
        if (isset($input['release'])) $release = intval($input['release']);
        if (isset($input['TYPE_COMP'])) $TYPE_COMP = intval($input['TYPE_COMP']);
        if (isset($input['ID_COMP'])) $ID_COMP = intval($input['ID_COMP']);


        if ($COMP_NAME == null) return -3;
        if ($PRODUCT_TYPE == 0) return -2;

        if ($TYPE_COMP == null) {
            if ($COMP_VERSION_NEW != null) {
                if ($this->checkNameAndVersion($COMP_NAME_NEW, $COMP_VERSION_NEW)) return -4;
            } else {
                if ($this->checkNameAndVersion($COMP_NAME, $COMP_VERSION)) return -4;
            }
        }

        $comment = 'Created on ' . $current->toDateTimeString() . ' by '. $this->auth->user()->USERNAM;
        $commentTrue = $COMP_COMMENT . "\n". $comment;

        $minMaxDensity = $this->getMinMax($this->value->MIN_MAX_DENSITY);
        $DENSITY = $minMaxDensity->DEFAULT_VALUE;

        $minMaxHeat = $this->getMinMax($this->value->MIN_MAX_HEAT);
        $HEAT = $minMaxHeat->DEFAULT_VALUE;

        $component = new Component();
        $component->ID_USER = $this->auth->user()->ID_USER;
        $component->COMP_COMMENT = ($COMP_COMMENT == '') ? $comment : $commentTrue;
        // $component->COMP_DATE = $current->toDateTimeString();
        if ($COMP_VERSION_NEW != null) {
            $component->COMP_VERSION = $COMP_VERSION_NEW;
        } else {
            $component->COMP_VERSION = $COMP_VERSION;
        }

        if ($TYPE_COMP == 3) {
            $component->COMP_RELEASE = 7;
            $component->BLS_CODE = $ID_COMP;
            $component->COMP_VERSION = $this->getSleepVersion($ID_COMP);
        } else {
            $component->COMP_RELEASE = $release;
            $component->BLS_CODE = '';
        }
        
        $component->COMP_NATURE = $NATURE_TYPE;
        $component->FAT_TYPE = $FATTYPE;
        $component->CLASS_TYPE = $PRODUCT_TYPE;
        $component->SUB_FAMILY = $SUB_TYPE;
        $component->AIR = $AIR * 0.01205;
        $component->WATER = $WATER;
        $component->GLUCID = $GLUCID;
        $component->LIPID = $LIPID;
        $component->PROTID = $PROTID;
        $component->SALT = $SALT;
        $component->FREEZE_TEMP = $this->units->temperature($FREEZE_TEMP, 2, 0);
        $component->NON_FROZEN_WATER = $NON_FROZEN_WATER;
        $component->DENSITY = $DENSITY;
        $component->SPECIFIC_HEAT = $HEAT;
        $component->COND_DENS_MODE = $CONDUCT_TYPE;
        $component->COMP_GEN_STATUS = 0;
        $component->COMP_IMP_ID_STUDY = 0;
        $component->OPEN_BY_OWNER = 0;
        $component->save();

        Component::where('ID_COMP', $component->ID_COMP)->update(['COMP_DATE' => $current->toDateTimeString()]);

        if (count($temperatures) > 0) {
            for ($i = 0; $i < count($temperatures); $i++) { 
                $compenth = new Compenth();
                $compenth->ID_COMP = $component->ID_COMP;
                $compenth->COMPTEMP = $this->units->temperature(floatval($temperatures[$i]['temperature']), 3, 0);
                $compenth->COMPENTH = 0;
                $compenth->COMPCOND = 0;
                $compenth->COMPDENS = 0;
                $compenth->save();
            }
        }

        $languages = Language::all();

        for ($i = 0; $i < count($languages); $i++) {
            $translation = new Translation();
            $translation->TRANS_TYPE = 1;
            $translation->ID_TRANSLATION = $component->ID_COMP;
            if ($COMP_NAME_NEW != null) {
                $translation->LABEL = $COMP_NAME_NEW;
            } else {
                $translation->LABEL = $COMP_NAME;
            }
            $translation->CODE_LANGUE = $languages[$i]->CODE_LANGUE;
            $translation->save();
        }

        return $component->ID_COMP;
    }

    private function saveCalculate(Request $request, $component, $freeze = 0)
    {
        $input = $this->request->all();

        $COMP_COMMENT = $COMP_NAME = $COMP_NAME_NEW = $COMP_VERSION_NEW = null;
        $LIPID = $GLUCID = $PROTID = $WATER = $FREEZE_TEMP = $COMP_VERSION = $CONDUCT_TYPE = $compositionTotal = 0;
        $SALT = $AIR = $NON_FROZEN_WATER = $PRODUCT_TYPE = $SUB_TYPE = $FATTYPE = $DENSITY = $HEAT = 0;
        $COMP_RELEASE = $NATURE_TYPE = 1;
        $tempertures = array();
        $current = Carbon::now('Asia/Ho_Chi_Minh');

        // if (isset($FREEZE_TEMP)) $FREEZE_TEMP = (float) $this->convert->unitConvert($this->value->TEMPERATURE, floatval($input['FREEZE_TEMP']));
                
        if (isset($input['FREEZE_TEMP'])) $FREEZE_TEMP =  floatval($input['FREEZE_TEMP']);
        if (isset($input['COMP_COMMENT'])) $COMP_COMMENT = $input['COMP_COMMENT'];
        if (isset($input['COMP_NAME'])) $COMP_NAME = $input['COMP_NAME'];
        if (isset($input['COMP_NAME_NEW'])) $COMP_NAME_NEW = $input['COMP_NAME_NEW'];
        if (isset($input['NON_FROZEN_WATER'])) $NON_FROZEN_WATER = floatval($input['NON_FROZEN_WATER']);
        if (isset($input['WATER'])) $WATER = floatval($input['WATER']);
        if (isset($input['AIR'])) $AIR = floatval($input['AIR']);
        if (isset($input['SALT'])) $SALT = floatval($input['SALT']);
        if (isset($input['PROTID'])) $PROTID = floatval($input['PROTID']);
        if (isset($input['GLUCID'])) $GLUCID = floatval($input['GLUCID']);
        if (isset($input['LIPID'])) $LIPID = floatval($input['LIPID']);
        if (isset($input['CONDUCT_TYPE'])) $CONDUCT_TYPE = intval($input['CONDUCT_TYPE']);
        if (isset($input['COMP_VERSION'])) $COMP_VERSION = floatval($input['COMP_VERSION']);
        if (isset($input['COMP_VERSION_NEW'])) $COMP_VERSION_NEW = floatval($input['COMP_VERSION_NEW']);
        if (isset($input['FATTYPE'])) $FATTYPE = intval($input['FATTYPE']);
        if (isset($input['NATURE_TYPE'])) $NATURE_TYPE = intval($input['NATURE_TYPE']);
        if (isset($input['PRODUCT_TYPE'])) $PRODUCT_TYPE = intval($input['PRODUCT_TYPE']);
        if (isset($input['SUB_TYPE'])) $SUB_TYPE = intval($input['SUB_TYPE']);
        if (isset($input['Temperatures'])) $temperatures = $input['Temperatures'];
        if (isset($input['COMP_RELEASE'])) $COMP_RELEASE = intval($input['COMP_RELEASE']);

        if ($freeze == 1) {
            $compositionTotal = ($AIR *0.01205) + $WATER + $SALT + $PROTID + $GLUCID + $LIPID;
            if ($compositionTotal < 90 || $compositionTotal > 110) {
                return -5;
            }
        }

        if ($COMP_NAME == null) return -3;
        if ($PRODUCT_TYPE == 0) return -2;

        if ($COMP_VERSION_NEW != null) {
            if ($COMP_VERSION_NEW != -2) {
                if ($this->checkNameAndVersion($COMP_NAME_NEW, $COMP_VERSION_NEW)) return -4;
            }
        } else {
            if ($this->checkNameAndVersion($COMP_NAME, $COMP_VERSION)) return -4;
        }
        $comment = 'Created on ' . $current->toDateTimeString() . ' by '. $this->auth->user()->USERNAM;
        $commentTrue = $COMP_COMMENT . "\n". $comment;

        $minMaxDensity = $this->getMinMax($this->value->MIN_MAX_DENSITY);
        $DENSITY = $minMaxDensity->DEFAULT_VALUE;

        $minMaxHeat = $this->getMinMax($this->value->MIN_MAX_HEAT);
        $HEAT = $minMaxHeat->DEFAULT_VALUE;

        $component->ID_USER = $this->auth->user()->ID_USER;
        // $component->COMP_DATE = $current->toDateTimeString();
        if ($COMP_VERSION_NEW != null) {
            if ($COMP_VERSION_NEW != -2) {
                $component->COMP_COMMENT = ($COMP_COMMENT == '') ? $comment : $commentTrue;
                $component->COMP_VERSION = $COMP_VERSION_NEW;
            }
        } else {
            $component->COMP_VERSION = $COMP_VERSION;
        }
        $component->COMP_RELEASE = $COMP_RELEASE;
        $component->COMP_NATURE = $NATURE_TYPE;
        $component->FAT_TYPE = $FATTYPE;
        $component->CLASS_TYPE = $PRODUCT_TYPE;
        $component->SUB_FAMILY = $SUB_TYPE;
        $component->AIR = $AIR * 0.01205;
        $component->WATER = $WATER;
        $component->GLUCID = $GLUCID;
        $component->LIPID = $LIPID;
        $component->PROTID = $PROTID;
        $component->SALT = $SALT;
        $component->FREEZE_TEMP =  $this->units->temperature($FREEZE_TEMP, 2, 0);
        $component->NON_FROZEN_WATER = $NON_FROZEN_WATER;
        $component->DENSITY = $DENSITY;
        $component->SPECIFIC_HEAT = $HEAT;
        $component->COND_DENS_MODE = $CONDUCT_TYPE;
        $component->COMP_GEN_STATUS = 0;
        $component->COMP_IMP_ID_STUDY = 0;
        $component->OPEN_BY_OWNER = 0;
        $component->BLS_CODE = '';

        $component->save();

        Component::where('ID_COMP', $component->ID_COMP)->update(['COMP_DATE' => $current->toDateTimeString()]);

        Translation::where('TRANS_TYPE', '=', 1)->where('ID_TRANSLATION', $component->ID_COMP)->delete();

        $compenths = Compenth::where('ID_COMP', $component->ID_COMP)->get();
        if (count($compenths) > 0) {
            foreach ($compenths as $compenth) {
                $compenth->delete();
            }
        }

        if (count($temperatures) > 0) {
            for ($i = 0; $i < count($temperatures); $i++) { 
                $compenth = new Compenth();
                $compenth->ID_COMP = $component->ID_COMP;
                $compenth->COMPTEMP = $this->units->temperature(floatval($temperatures[$i]['temperature']), 3, 0);
                $compenth->COMPENTH = 0;
                $compenth->COMPCOND = 0;
                $compenth->COMPDENS = 0;
                $compenth->save();
            }
        }

        $languages = Language::all();

        for ($i = 0; $i < count($languages); $i++) {
            $translation = new Translation();
            $translation->TRANS_TYPE = 1;
            $translation->ID_TRANSLATION = $component->ID_COMP;
            if ($COMP_NAME_NEW != null) {
                $translation->LABEL = $COMP_NAME_NEW;
            } else {
                $translation->LABEL = $COMP_NAME;
            }
            $translation->CODE_LANGUE = $languages[$i]->CODE_LANGUE;
            $translation->save();
        }

        return $component->ID_COMP;
    }

    public function getTemperaturesByIdComp($idComp)
    {
        $temperatures = array();
        $item = array();

        $compenths = Compenth::where('ID_COMP', $idComp)->get();
        if (count($compenths) > 0) {
            foreach ($compenths as $compenth) {
                $item['temperature'] = $this->units->temperature($compenth->COMPTEMP, 2, 1);
                array_push($temperatures, $item);
            }
        }

        return $temperatures;
    }

    public function deleteComponent($idComp)
    {
        $productElmt = ProductElmt::where('ID_COMP', $idComp)->get();
        if (count($productElmt) > 0) {
            $component = Component::find($idComp);
            if ($component && ($component->COMP_RELEASE != 5)) {
                $component->COMP_RELEASE = 5;
                $component->save();
            }
            return 0;
        }

        $this->cleaningComp($idComp);
        return 1;
    }

    public function getCompenthsByIdComp($idComp)
    {
        $compenths = Compenth::where('ID_COMP', $idComp)->get();
        $results = $item = array();

        foreach ($compenths as $key) {
            $key->COMPTEMP = $this->units->temperature($key->COMPTEMP, 2, 1);
            $key->COMPENTH = $this->units->enthalpy($key->COMPENTH, 3, 1);
            $key->COMPCOND = $this->units->conductivity($key->COMPCOND, 4, 1);
            $key->COMPDENS = $this->units->density($key->COMPDENS, 1, 1);

            $item['ID_COMPENTH'] = $key->ID_COMPENTH;
            $item['COMPTEMP'] = $key->COMPTEMP;
            $item['COMPENTH'] = $key->COMPENTH;
            $item['COMPCOND'] = $key->COMPCOND;
            $item['COMPDENS'] = $key->COMPDENS;

            array_push($results, $item);
        }

        array_multisort(array_column($results, 'COMPTEMP'), SORT_ASC, $results); //SORT_DESC SORT_ASC

        return $results;
    }

    public function getCompenthById($id)
    {
        $compenth = Compenth::find($id);
        $compenth->COMPTEMP = $this->units->temperature($compenth->COMPTEMP, 2, 1);
        $compenth->COMPENTH = $this->units->enthalpy($compenth->COMPENTH, 3, 1);
        $compenth->COMPCOND = $this->units->conductivity($compenth->COMPCOND, 4, 1);
        $compenth->COMPDENS = $this->units->density($compenth->COMPDENS, 1, 1);

        return $compenth;
    }

    public function updateCompenth()
    {
        $input = $this->request->all();

        $ID_COMPENTH = $ID_COMP = $COMPTEMP = $COMPCOND = $COMPDENS = $COMPENTH = null;

        if (isset($input['ID_COMPENTH'])) $ID_COMPENTH = intval($input['ID_COMPENTH']);
        if (isset($input['ID_COMP'])) $ID_COMP = intval($input['ID_COMP']);
        if (isset($input['COMPTEMP'])) $COMPTEMP = doubleval($input['COMPTEMP']);
        if (isset($input['COMPCOND'])) $COMPCOND = doubleval($input['COMPCOND']);
        if (isset($input['COMPDENS'])) $COMPDENS = doubleval($input['COMPDENS']);
        if (isset($input['COMPENTH'])) $COMPENTH = doubleval($input['COMPENTH']);

        $compenth = Compenth::find($ID_COMPENTH);
        if ($compenth) {
            $compenth->ID_COMP = $ID_COMP;
            $compenth->COMPTEMP = $this->units->temperature($COMPTEMP, 2, 0);
            $compenth->COMPENTH = $this->units->enthalpy($COMPENTH, 3, 0);
            $compenth->COMPCOND = $this->units->conductivity($COMPCOND, 4, 0);
            $compenth->COMPDENS = $this->units->density($COMPDENS, 1, 0);
            $compenth->save();
        }
        return 1;
    }

    private function cleaningComp($idComp)
    {
        Translation::where('TRANS_TYPE', '=', 1)->where('ID_TRANSLATION', $idComp)->delete();


        // if (count($translations) > 0) {
        //     foreach ($translations as $translation) {
        //         $translation->delete();
        //     }    
        // }

        $compenths = Compenth::where('ID_COMP', $idComp)->get();
        if (count($compenths) > 0) {
            foreach ($compenths as $compenth) {
                $compenth->delete();
            }
        }

        $component = Component::find($idComp);
        if ($component) $component->delete();
    }

    private function checkNameAndVersion($compName, $compVersion)
    {
        $components = Component::select(array('TRANSLATION.LABEL', 'COMPONENT.COMP_VERSION'))
        ->join('TRANSLATION', 'ID_COMP', '=', 'TRANSLATION.ID_TRANSLATION')
        ->where('TRANSLATION.TRANS_TYPE', 1)->where('TRANSLATION.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->get();

        for ($i = 0; $i < count($components); $i++) { 
            if (($components[$i]->LABEL == $compName) && 
                (floatval($components[$i]->COMP_VERSION) == floatval($compVersion))) {
                return 1;
            }
                
        }
        return 0;
    }

    public function checkDataComponent()
    {
        $input = $this->request->all();

        $LIPID = $GLUCID = $PROTID = $WATER = $FREEZE_TEMP = 0;
        $SALT = $AIR = $NON_FROZEN_WATER = 0;

        if (isset($input['COMP_NAME'])) $COMP_NAME = $input['COMP_NAME'];
        if (isset($input['COMP_VERSION'])) $COMP_VERSION = floatval($input['COMP_VERSION']);

        if (isset($input['FREEZE_TEMP'])) $FREEZE_TEMP = floatval($input['FREEZE_TEMP']);
        if (isset($input['WATER'])) $WATER = floatval($input['WATER']);
        if (isset($input['PROTID'])) $PROTID = floatval($input['PROTID']);
        if (isset($input['LIPID'])) $LIPID = floatval($input['LIPID']);
        if (isset($input['GLUCID'])) $GLUCID = floatval($input['GLUCID']);
        if (isset($input['SALT'])) $SALT = floatval($input['SALT']);
        if (isset($input['AIR'])) $AIR = floatval($input['AIR']);
        if (isset($input['NON_FROZEN_WATER'])) $NON_FROZEN_WATER = floatval($input['NON_FROZEN_WATER']);
        if (isset($input['check'])) $check = intval($input['check']);

        if ($check != 2 && $check != 3) {
            if ($this->checkNameAndVersion($COMP_NAME, $COMP_VERSION)) {
                return  [
                    "Message" => "Name and version already in use"
                ];
            }
        }

        $FREEZE_TEMP = $this->units->temperature($FREEZE_TEMP, 2, 0);
        $checkFREEZE_TEMP = $this->minmax->checkMinMaxValue($FREEZE_TEMP, 1062);
        if ( !$checkFREEZE_TEMP ) {
            $mm = $this->minmax->getMinMaxTemperature(1062);
            return  [
                "Message" => "Value out of range in  Freeze temperature (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkWATER = $this->minmax->checkMinMaxValue($WATER, 1058);
        if ( !$checkWATER ) {
            $mm = $this->minmax->getMinMaxUPercentNone(1058);
            return  [
                "Message" => "Value out of range in Water (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkPROTID = $this->minmax->checkMinMaxValue($PROTID, 1055);
        if ( !$checkPROTID ) {
            $mm = $this->minmax->getMinMaxUPercentNone(1055);
            return  [
                "Message" => "Value out of range in Protein & dry material (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkLIPID = $this->minmax->checkMinMaxValue($LIPID, 1056);
        if ( !$checkLIPID ) {
            $mm = $this->minmax->getMinMaxUPercentNone(1056);
            return  [
                "Message" => "Value out of range in Lipid (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkGLUCID = $this->minmax->checkMinMaxValue($GLUCID, 1057);
        if ( !$checkGLUCID ) {
            $mm = $this->minmax->getMinMaxUPercentNone(1057);
            return  [
                "Message" => "Value out of range in Glucid (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkSALT = $this->minmax->checkMinMaxValue($SALT, 1059);
        if ( !$checkSALT ) {
            $mm = $this->minmax->getMinMaxUPercentNone(1059);
            return  [
                "Message" => "Value out of range in Salt (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkAIR = $this->minmax->checkMinMaxValue($AIR, 1060);
        if ( !$checkAIR ) {
            $mm = $this->minmax->getMinMaxUPercentNone(1060);
            return  [
                "Message" => "Value out of range in Air (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        $checkNON_FROZEN_WATER = $this->minmax->checkMinMaxValue($NON_FROZEN_WATER, 1061);
        if ( !$checkNON_FROZEN_WATER ) {
            $mm = $this->minmax->getMinMaxUPercentNone(1061);
            return  [
                "Message" => "Value out of range in Unfreezable water (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        if ($check == 0 || $check == 2) {
            $compositionTotal = ($AIR *0.01205) + $WATER + $SALT + $PROTID + $GLUCID + $LIPID;
            $checkCompositionTotal = $this->minmax->checkMinMaxValue($compositionTotal, 1118);
            if ( !$checkCompositionTotal ) {
                $mm = $this->minmax->getMinMaxUPercentNone(1118);
                return  [
                    "Message" => "Value out of range in Composition total  (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
                ];
            }
        }
        
        return 1;

    }

    public function checkTemperature()
    {
        $input = $this->request->all();
        $temperatures = 0;

        if (isset($input['temperatures'])) $temperatures = floatval($input['temperatures']);

        $temperatures = $this->units->temperature($temperatures, 3, 0);
        $checkTemperatures = $this->minmax->checkMinMaxValue($temperatures, 1086);
        if ( !$checkTemperatures ) {
            $mm = $this->minmax->getMinMaxTemperature(1086);
            return  [
                "Message" => "Value out of range in Temperature (" . doubleval($mm->LIMIT_MIN) . " : " . doubleval($mm->LIMIT_MAX) . ")"
            ];
        }

        return 1;
    }

    public function getCapabitity($idEquip)
    {   
        $capabilities = null;
        $equipment = Equipment::find($idEquip);
        if ($equipment) {
            $capabilities = $equipment->CAPABILITIES;
        }

        $CAP_EQP_DEPEND_ON_TS = $this->equip->getCapability($capabilities, 65536);

        $array = [
            'CAP_EQP_DEPEND_ON_TS' => $CAP_EQP_DEPEND_ON_TS,
        ];

        return $array;
    }

    private function getSleepVersion($idCompParent)
    {
        $components = Component::all();
        $version = 1;
        for ($i = 0; $i < count($components); $i++) { 
            if (intval($components[$i]->BLS_CODE) == intval($idCompParent)) {
                $version++;
            }
        }
        return $version;
    }
}