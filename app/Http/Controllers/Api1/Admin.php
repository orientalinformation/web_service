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
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\User;
use App\Models\CalculationParametersDef;
use App\Models\MeshParamDef;
use App\Models\TempRecordPtsDef;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\MinMax;
use App\Models\ProdcharColorsDef;
use App\Models\UserUnit;
use Illuminate\Support\Facades\DB;
use App\Models\Study;
use App\Models\Component;
use App\Models\Equipment;
use App\Models\LineElmt;
use App\Models\PackingElmt;
use App\Models\Connection;
use Carbon\Carbon;
use App\Models\StudyEquipment;
use App\Models\MonetaryCurrency;
use App\Models\Unit;
use App\Cryosoft\UnitsConverterService;

class Admin extends Controller
{   
    /**
     * @var Illuminate\Http\Request
     */
    protected $request;

    public function __construct(Request $request, Auth $auth, UnitsConverterService $unit)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->unit = $unit;
    }

    public function newUser()
    {
        $input = $this->request->all();

        if (!isset($input['username']) || !isset($input['email']) || !isset($input['password']) || !isset($input['confirmpassword']))
            throw new \Exception("Error Processing Request", 1);   

        $username = $input['username'];
        $email = $input['email'];
        $password = $input['password'];
        $confirm = $input['confirmpassword'];

        $hashPassword = Hash::make($password);

        if ($password != $confirm) {
            return 2;
        }

        $user = new User();

        $calParaDef = new CalculationParametersDef();

        $meshParamDef = new MeshParamDef();

        $tempRecordDef = new TempRecordPtsDef();

        $users = User::all();

        for ($i = 0; $i < count($users); $i++) { 
            if (strtoupper($users[$i]->USERNAM) == strtoupper($username)) {
                return 0;
            }
        }

        if (isset($input['username'])) $user->USERNAM = $username;
        if (isset($input['password'])) $user->USERPASS = $hashPassword;
        if (isset($input['email'])) $user->USERMAIL = $email;
        
        $user->USERPRIO = 2;
        $user->USER_ENERGY = -1;
        $user->USER_CONSTRUCTOR = "";
        $user->USER_FAMILY = -1;
        $user->USER_ORIGINE = -1;
        $user->USER_PROCESS = -1;
        $user->USER_MODEL = -1;
        $user->TRACE_LEVEL = 0;
        $user->CODE_LANGUE = 1;
        $user->ID_CALC_PARAMSDEF = 0;
        $user->ID_TEMP_RECORD_PTS_DEF = 0;
        $user->ID_MONETARY_CURRENCY = 1;
        $user->save();

        $calParaDef->ID_USER = $user->ID_USER;
        $calParaDef->MAX_IT_NB_DEF = (int)$this->getMinMax(1010)->DEFAULT_VALUE;
        $calParaDef->TIME_STEPS_NB_DEF = (int)$this->getMinMax(1011)->DEFAULT_VALUE;
        $calParaDef->RELAX_COEFF_DEF = (float)$this->getMinMax(1012)->DEFAULT_VALUE;
        $calParaDef->PRECISION_REQUEST_DEF = (float)$this->getMinMax(1019)->DEFAULT_VALUE;
        $calParaDef->STOP_TOP_SURF_DEF = (float)$this->getMinMax(1014)->DEFAULT_VALUE;
        $calParaDef->STOP_INT_DEF = (float)$this->getMinMax(1015)->DEFAULT_VALUE;
        $calParaDef->STOP_BOTTOM_SURF_DEF = (float)$this->getMinMax(1016)->DEFAULT_VALUE;
        $calParaDef->STOP_AVG_DEF = (float)$this->getMinMax(1017)->DEFAULT_VALUE;
        $calParaDef->TIME_STEP_DEF = (float)$this->getMinMax(1013)->DEFAULT_VALUE;
        $calParaDef->STORAGE_STEP_DEF = (int)$this->getMinMax(1106)->DEFAULT_VALUE;
        $calParaDef->PRECISION_LOG_STEP_DEF = (int)$this->getMinMax(1107)->DEFAULT_VALUE;
        $calParaDef->HORIZ_SCAN_DEF = true;
        $calParaDef->VERT_SCAN_DEF = true;
        $calParaDef->STUDY_ALPHA_BOTTOM_DEF = false;
        $calParaDef->STUDY_ALPHA_FRONT_DEF = false;
        $calParaDef->STUDY_ALPHA_LEFT_DEF = false;
        $calParaDef->STUDY_ALPHA_REAR_DEF = false;
        $calParaDef->STUDY_ALPHA_RIGHT_DEF = false;
        $calParaDef->STUDY_ALPHA_TOP_DEF = false;
        $calParaDef->STUDY_ALPHA_BOTTOM_FIXED_DEF = false;
        $calParaDef->STUDY_ALPHA_FRONT_FIXED_DEF = false;
        $calParaDef->STUDY_ALPHA_LEFT_FIXED_DEF = false;
        $calParaDef->STUDY_ALPHA_REAR_FIXED_DEF = false;
        $calParaDef->STUDY_ALPHA_RIGHT_FIXED_DEF = false;
        $calParaDef->STUDY_ALPHA_TOP_FIXED_DEF = false;
        $calParaDef->save();

        $meshParamDef->ID_USER = $user->ID_USER;
        $meshParamDef->MESH_1_SIZE = $this->getMinMax(1)->DEFAULT_VALUE;
        $meshParamDef->MESH_2_SIZE = $this->getMinMax(2)->DEFAULT_VALUE;
        $meshParamDef->MESH_3_SIZE = $this->getMinMax(3)->DEFAULT_VALUE;
        $meshParamDef->MESH_RATIO = $this->getMinMax(1064)->DEFAULT_VALUE;
        $meshParamDef->save();

        $tempRecordDef->ID_USER = $user->ID_USER;
        $tempRecordDef->AXIS1_PT_TOP_SURF_DEF = 50;
        $tempRecordDef->AXIS2_PT_TOP_SURF_DEF = 100;
        $tempRecordDef->AXIS3_PT_TOP_SURF_DEF = 50;
        $tempRecordDef->AXIS1_PT_INT_PT_DEF = 50;
        $tempRecordDef->AXIS2_PT_INT_PT_DEF = 50;
        $tempRecordDef->AXIS3_PT_INT_PT_DEF = 50;
        $tempRecordDef->AXIS1_PT_BOT_SURF_DEF = 50;
        $tempRecordDef->AXIS2_PT_BOT_SURF_DEF = 0;
        $tempRecordDef->AXIS3_PT_BOT_SURF_DEF = 50;
        $tempRecordDef->AXIS2_AX_1_DEF = 50;
        $tempRecordDef->AXIS3_AX_1_DEF = 50;
        $tempRecordDef->AXIS1_AX_2_DEF = 50;
        $tempRecordDef->AXIS3_AX_2_DEF = 50;
        $tempRecordDef->AXIS1_AX_3_DEF = 50;
        $tempRecordDef->AXIS2_AX_3_DEF = 50;
        $tempRecordDef->AXIS1_PL_2_3_DEF = 50;
        $tempRecordDef->AXIS2_PL_1_3_DEF = 50;
        $tempRecordDef->AXIS3_PL_1_2_DEF = 50;
        $tempRecordDef->NB_STEPS_DEF = 10;
        $tempRecordDef->CONTOUR2D_TEMP_MIN_DEF = 0;
        $tempRecordDef->CONTOUR2D_TEMP_MAX_DEF = 0;
        $tempRecordDef->save();

        $this->initProdCharColorWithKernel($user->ID_USER);
        $this->initUserUnitsWithKernelU($user->ID_USER);

        $user->ID_CALC_PARAMSDEF = $calParaDef->ID_CALC_PARAMSDEF;
        $user->ID_TEMP_RECORD_PTS_DEF = $tempRecordDef->ID_TEMP_RECORD_PTS_DEF;
        $user->ID_MONETARY_CURRENCY = 1;
        $user->save();

        return 1;
    }

    public function getUsers()
    {
        $idUserLogon = $this->auth->user()->ID_USER;
        $offline = User::where('USERPRIO', '<>', 0)
        ->where('ID_USER', '<>', $idUserLogon)->orderBy('USERNAM', 'ASC')->get();
        
        $online = Connection::where('DATE_CONNECTION', '<>', null)
            ->where('DATE_DISCONNECTION', null)
            ->where('ID_USER', '<>', $idUserLogon)->get();
        
        return compact('online', 'offline');
    }

    public function getMinMax($limitItem) 
    {
        return MinMax::where('LIMIT_ITEM', $limitItem)->first();
    }

    public function initProdCharColorWithKernel($newIdUser)
    {
        $user = User::where('USERNAM', 'KERNEL')->first();
        $defaultProds = ProdcharColorsDef::where('ID_USER', $user->ID_USER)->get();
        for ($i = 0; $i < count($defaultProds); $i++) {
            $prodcharColorsDef = new ProdcharColorsDef();
            $prodcharColorsDef->ID_USER = $newIdUser;
            $prodcharColorsDef->ID_COLOR = $defaultProds[$i]->ID_COLOR;
            $prodcharColorsDef->LAYER_ORDER = $defaultProds[$i]->LAYER_ORDER;
            $prodcharColorsDef->save();
        }
    }

    public function initUserUnitsWithKernelU($newIdUser)
    {
        $user = User::where('USERNAM', 'KERNEL')->first();
        $defaultUnits = UserUnit::where('ID_USER', $user->ID_USER)->get();
        for ($i = 0; $i < count($defaultUnits); $i++) { 
            $userUnit = new UserUnit();
            $userUnit->ID_USER = $newIdUser;
            $userUnit->ID_UNIT = $defaultUnits[$i]->ID_UNIT;
            $userUnit->save();
        }
    }

    public function deleteUser($idUser) 
    {
        $user = User::find($idUser);

        if (!$user) {
            return -1;
        } else {
            UserUnit::where('ID_USER', $user->ID_USER)->delete();
            CalculationParametersDef::where('ID_USER', $user->ID_USER)->delete();
            TempRecordPtsDef::where('ID_USER', $user->ID_USER)->delete();
            MeshParamDef::where('ID_USER', $user->ID_USER)->delete();
            ProdcharColorsDef::where('ID_USER', $user->ID_USER)->delete();
            Connection::where('ID_USER', $user->ID_USER)->delete();
            $user->delete();

            return 1;
        }
    }

    public function updateUser($idUser)
    {
        $input = $this->request->all();
        if (!isset($input['username']) || !isset($input['email']) || !isset($input['password']) || !isset($input['confirmpassword']))
            throw new \Exception("Error Processing Request", 1);   

        $username = $input['username'];
        $password = $input['password'];
        $email = $input['email'];
        $confirm = $input['confirmpassword'];
        $hashPassword = Hash::make($password);

        if ($password != $confirm) {
            return 2;
        }
        $user = User::find($idUser);

        if (!$user) {
            return -1;
        } else {
            if (isset($input['username'])) $user->USERNAM = $username;
            if (isset($input['password'])) $user->USERPASS = $hashPassword;
            if ($user->USERMAIL === '' || $user->USERMAIL === null) {
                $user->USERMAIL = $email;
            }
            $user->save();

            return 1;
        }
    }

    public function disconnectUser($idUser)
    {
        $input = $this->request->all();

        if (!isset($input['reset'])) 
            throw new \Exception("Error Processing Request", 1);

        $reset = $input['reset'];
        if ($reset == 1) {
            $this->resetStudiesStatusLockedByUser($idUser);
        }
        $this->releaseEltLockedByUser($idUser);
        $this->disconnectUserDB($idUser);

        return 1;
    }

    public function releaseEltLockedByUser($idUser)
    {
        Study::where('ID_USER', $idUser)
            ->where('OPEN_BY_OWNER', 1)
            ->update(['OPEN_BY_OWNER' => 0]);
        Component::where('ID_USER', $idUser)
            ->where('OPEN_BY_OWNER', 1)
            ->update(['OPEN_BY_OWNER' => 0]);
        Equipment::where('ID_USER', $idUser)
            ->where('OPEN_BY_OWNER', 1)
            ->update(['OPEN_BY_OWNER' => 0]);
        LineElmt::where('ID_USER', $idUser)
            ->where('OPEN_BY_OWNER', 1)
            ->update(['OPEN_BY_OWNER' => 0]);
        PackingElmt::where('ID_USER', $idUser)
            ->where('OPEN_BY_OWNER', 1)
            ->update(['OPEN_BY_OWNER' => 0]);
    }

    public function disconnectUserDB($idUser)
    {
        $current = Carbon::now();
        $current->timezone = 'Asia/Ho_Chi_Minh';

        Connection::where('ID_USER', $idUser)
            ->where('DATE_DISCONNECTION', null)
            ->update(['DATE_DISCONNECTION' => $current->toDateTimeString(), 'TYPE_DISCONNECTION' => 3]);
    }

    public function resetStudiesStatusLockedByUser($idUser)
    {
        $studys = Study::where('ID_USER', $idUser)->get();

        if (count($studys) > 0) {
            foreach ($studys as $study) {
                StudyEquipment::with('study')
                ->where('ID_STUDY', $study->ID_STUDY)
                ->where('EQUIP_STATUS', '=', 100000)
                ->update(['EQUIP_STATUS' => 0]);
            }
        }
    }

    public function loadConnections()
    {
        $input = $this->request->all();
        if (!isset($input['record'])) 
            throw new \Exception("Error Processing Request", 1);

        $record = $input['record'];
        if ($record != 0) {
            $connections = Connection::with('user')
            ->take($record)
            ->orderBy("DATE_CONNECTION", "DESC")
            ->get();
        } else {
            $connections = Connection::with('user')
            ->orderBy("DATE_CONNECTION", "DESC")
            ->get();
        }
        
        return $connections;
    }

    public function units()
    {
        $monetary = MonetaryCurrency::get();
        $kernelMonetary = MonetaryCurrency::select('MONETARY_CURRENCY.*')
                        ->join('LN2USER', 'MONETARY_CURRENCY.ID_MONETARY_CURRENCY', '=', 'LN2USER.ID_MONETARY_CURRENCY')
                        ->where('LN2USER.USERNAM', 'KERNEL')->first();
        $units = $this->unit->tmUnitTypeMapping();
        $listUnit = [];

        if (count($units) > 0) {
            foreach ($units as $key => $value) {
                $kernelUnit = DB::table('UNIT')
                            ->where('ID_UNIT', '=', DB::raw('TYPE_UNIT'))
                            ->where('TYPE_UNIT', $value['value'])
                            ->where('TYPE_UNIT', '<>', 27)
                            ->first();
                $symbolSelect = Unit::where("TYPE_UNIT", $value['value'])->get();
                $arrSymbol = [];
                if (count($symbolSelect) > 0) {
                    foreach ($symbolSelect as $row) {
                        $item['ID_UNIT'] = $row->ID_UNIT;
                        $item['SYMBOL'] = $row->SYMBOL;
                        $item['COEFF_A'] = (strlen(substr(strrchr($row->COEFF_A, "."), 1) > 1)) ? $row->COEFF_A : $this->unit->time($row->COEFF_A);
                        $item['COEFF_B'] = (strlen(substr(strrchr($row->COEFF_B, "."), 1) > 1)) ? $row->COEFF_B : $this->unit->time($row->COEFF_B);
                        $arrSymbol[] = $item;
                    }
                }

                $listUnit[] = $value;
                $listUnit[$key]['SYMBOL'] = $kernelUnit->SYMBOL;
                $listUnit[$key]['symbolSelect'] = $arrSymbol;
                $listUnit[$key]['COEFF_A'] = $this->unit->none($kernelUnit->COEFF_A);
                $listUnit[$key]['COEFF_B'] = $this->unit->none($kernelUnit->COEFF_B);
            }
        }

        return compact('monetary', 'kernelMonetary', 'listUnit');
    }

    public function saveFileTranslate()
    {
        $admintrans = [];
        $dataJson = array();
        $language = 'en';
        $input = $this->request->all();

        if (isset($input['admintrans'])) $admintrans = $input['admintrans'];
        if (isset($input['idlang'])) $idlang = intval($input['idlang']);

        getcwd();
        chdir('../../cryosoft-ui/src/assets/i18n/');

        if ($idlang == 2) {
            $language = 'fr';
        } else if ($idlang == 3) {
            $language = 'it';
        } else if ($idlang == 4) {
            $language = 'de';
        } else if ($idlang == 5) {
            $language = 'es';
        }

        foreach ($admintrans as $key => $admintran) {
            $dataJson[$admintran['key']] = $admintran['value'];
        }

        // encode array to json
        $json = json_encode($dataJson, JSON_UNESCAPED_UNICODE);

        // write json to file
        if (file_put_contents(getcwd(). "/". $language . ".json", $json)) {
            echo "File JSON sukses dibuat...";
        } else {
            echo "Oops! Terjadi error saat membuat file JSON...";
        }
    }
}