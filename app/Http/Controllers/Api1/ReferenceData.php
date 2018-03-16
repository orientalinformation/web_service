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
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth, UnitsConverterService $convert, ValueListService $value, KernelService $kernel)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->convert = $convert;
        $this->value = $value;
        $this->kernel = $kernel;
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
            'FREEZE_TEMP' => $FREEZE_TEMP,
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
        ->join('Translation', 'ID_COMP', '=', 'Translation.ID_TRANSLATION')
        ->where('Translation.TRANS_TYPE', 1)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->get();

        foreach ($mine as $m) {
            $m->AIR = round(($m->AIR / 0.01205));
        }

        $others = Component::join('Ln2user', 'Ln2user.ID_USER', '=', 'Component.ID_USER')
            ->join('Translation', 'Component.ID_COMP', '=', 'Translation.ID_TRANSLATION')
            ->where('Translation.TRANS_TYPE', 1)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
            ->where('Component.ID_USER', '!=', $this->auth->user()->ID_USER)
            ->orderBy('LABEL', 'ASC')->get();
        foreach ($others as $other) {
            $other->AIR = round(($other->AIR / 0.01205));
        }

        return compact('mine', 'others');
    }

    public function saveDataComponent()
    {
        $result = $this->saveComponent($this->request, 0);

        if ($result > 0) {
            return Component::where('ID_USER', $this->auth->user()->ID_USER)
            ->join('Translation', 'ID_COMP', '=', 'Translation.ID_TRANSLATION')
            ->where('Translation.TRANS_TYPE', 1)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
            ->where('ID_COMP', $result)->first();
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
            if ($idComp == -5) return -5;

            $result = $this->startFCCalculation($idComp);

            $comp = Component::where('ID_USER', $this->auth->user()->ID_USER)
            ->join('Translation', 'ID_COMP', '=', 'Translation.ID_TRANSLATION')
            ->where('Translation.TRANS_TYPE', 1)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
            ->where('ID_COMP', $idComp)->first();
        } else {
            $component = Component::find($idComp);

            if ($component) {
                if ($component->COMP_RELEASE == 7) $component->COMP_RELEASE = 8;

                $rsIdComp = $this->saveCalculate($this->request, $component, 1);

                if ($rsIdComp == -3) return -3;
                if ($rsIdComp == -2) return -2;
                if ($rsIdComp == -4) return -4;
                if ($rsIdComp == -5) return -5;    
            }
            $result = $this->startFCCalculation($idComp); 
                
            $comp = Component::where('ID_USER', $this->auth->user()->ID_USER)
            ->join('Translation', 'ID_COMP', '=', 'Translation.ID_TRANSLATION')
            ->where('Translation.TRANS_TYPE', 1)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
            ->where('ID_COMP', $idComp)->first();
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

        $COMP_COMMENT = $COMP_NAME = $COMP_NAME_NEW = $COMP_VERSION_NEW = $TYPE_COMP = null;
        $LIPID = $GLUCID = $PROTID = $WATER = $FREEZE_TEMP = $COMP_VERSION = $CONDUCT_TYPE = $compositionTotal = 0;
        $SALT = $AIR = $NON_FROZEN_WATER = $PRODUCT_TYPE = $SUB_TYPE = $FATTYPE = $DENSITY = $HEAT = 0;
        $release = $NATURE_TYPE = 1;
        $tempertures = array();
        $current = Carbon::now('Asia/Ho_Chi_Minh');

        if (isset($FREEZE_TEMP)) $FREEZE_TEMP = (float) $this->convert->unitConvert($this->value->TEMPERATURE, floatval($input['FREEZE_TEMP']));
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
        if (isset($input['COMP_VERSION'])) $COMP_VERSION = intval($input['COMP_VERSION']);
        if (isset($input['COMP_VERSION_NEW'])) $COMP_VERSION_NEW = intval($input['COMP_VERSION_NEW']);
        if (isset($input['FATTYPE'])) $FATTYPE = intval($input['FATTYPE']);
        if (isset($input['NATURE_TYPE'])) $NATURE_TYPE = intval($input['NATURE_TYPE']);
        if (isset($input['PRODUCT_TYPE'])) $PRODUCT_TYPE = intval($input['PRODUCT_TYPE']);
        if (isset($input['SUB_TYPE'])) $SUB_TYPE = intval($input['SUB_TYPE']);
        if (isset($input['Temperatures'])) $temperatures = $input['Temperatures'];
        if (isset($input['release'])) $release = intval($input['release']);
        if (isset($input['TYPE_COMP'])) $TYPE_COMP = intval($input['TYPE_COMP']);

        if ($freeze == 1) {
            $compositionTotal = ($AIR *0.01205) + $WATER + $SALT + $PROTID + $GLUCID + $LIPID;
            if ($compositionTotal < 90 || $compositionTotal > 110) {
                return -5;
            }
        }

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
        $component->COMP_RELEASE = $release;
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
        $component->FREEZE_TEMP = $FREEZE_TEMP;
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

        if (count($temperatures) > 0) {
            for ($i = 0; $i < count($temperatures); $i++) { 
                $compenth = new Compenth();
                $compenth->ID_COMP = $component->ID_COMP;
                $compenth->COMPTEMP = floatval($temperatures[$i]['temperature']);
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
                
        if (isset($input['FREEZE_TEMP'])) $FREEZE_TEMP = (float) $this->convert->unitConvert($this->value->TEMPERATURE, floatval($input['FREEZE_TEMP']));
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
        if (isset($input['COMP_VERSION'])) $COMP_VERSION = intval($input['COMP_VERSION']);
        if (isset($input['COMP_VERSION_NEW'])) $COMP_VERSION_NEW = intval($input['COMP_VERSION_NEW']);
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
        $component->FREEZE_TEMP = $FREEZE_TEMP;
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
                $compenth->COMPTEMP = floatval($temperatures[$i]['temperature']);
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
                $item['temperature'] = $compenth->COMPTEMP;
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
        return $compenths;
    }

    public function getCompenthById($id)
    {
        $compenth = Compenth::find($id);
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
            $compenth->COMPTEMP = $COMPTEMP;
            $compenth->ID_COMP = $ID_COMP;
            $compenth->COMPCOND = $COMPCOND;
            $compenth->COMPDENS = $COMPDENS;
            $compenth->COMPENTH = $COMPENTH;
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
        $components = Component::select(array('Translation.LABEL', 'Component.COMP_VERSION'))
        ->join('Translation', 'ID_COMP', '=', 'Translation.ID_TRANSLATION')
        ->where('Translation.TRANS_TYPE', 1)->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->get();

        for ($i = 0; $i < count($components); $i++) { 
            if (($components[$i]->LABEL == $compName) && 
                (floatval($components[$i]->COMP_VERSION) == floatval($compVersion))) {
                return 1;
            }
                
        }
        return 0;
    }
}