<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\ProdcharColorsDef;
use App\Models\User;
use App\Models\CoolingFamily;
use App\Models\Translation;
use App\Models\Equipseries;
use App\Models\Equipment;
use App\Models\Equipfamily;
use App\Models\Unit;
use App\Models\UserUnit;
use App\Models\MonetaryCurrency;
use App\Models\Connection;


class Users extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth)
    {
        $this->request = $request;
        $this->auth = $auth;
    }

    public function getActiveUsers()
    {
        $users = User::where('USERPRIO', '<>', 0)->get();
        foreach ($users as $key => $user) {
            $users[$key]['USERNAM'] = strtoupper($user['USERNAM']);
        }
        return $users;
    }

    public function changePassword($id)
    {
        $input = $this->request->all();
        $connections = null;
        $current = Carbon::now();
        $current->timezone = 'Asia/Ho_Chi_Minh';

        if (!isset($input['oldPass']) || !isset($input['newPass']))
            throw new \Exception("Error Processing Request", 1);

        $oldPass = $input['oldPass'];
        $newPass = $input['newPass'];
        $hashNewPass = Hash::make($newPass);

        $user = User::find($id);
        if ($user) {
            $connections = Connection::where('ID_USER', $id)->where('TYPE_DISCONNECTION', 0)->get();
            if (count($connections) > 0) {
                foreach ($connections as $connection) {
                    $connection->DATE_DISCONNECTION = $current->toDateTimeString();
                    $connection->TYPE_DISCONNECTION = 2;
                    $connection->update();
                }
            }

            if (!Hash::check($oldPass, $user->USERPASS)) {
                return -1;
            }
        }

        User::where('ID_USER', $id)->update(['USERPASS' => $hashNewPass]);

        return 1;
    }

    public function getEnergies()
    {
        $list = Translation::join('COOLING_FAMILY', 'ID_TRANSLATION', '=', 'COOLING_FAMILY.ID_COOLING_FAMILY')
        ->where('TRANS_TYPE', 2)->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->orderBy('LABEL', 'ASC')
        ->distinct()->get();

        return $list;
    }

    public function getConstructors()
    {
        $input = $this->request->all();
        $energy = 0;
        if (isset($input['idCooling'])) $energy = $input['idCooling'];

        if ($energy != 0) {
            $list = Equipseries::select('CONSTRUCTOR')->join('EQUIPMENT', 'EQUIPSERIES.ID_EQUIPSERIES', '=', 'EQUIPMENT.ID_EQUIPSERIES')
            ->where('EQUIPMENT.ID_COOLING_FAMILY', $energy)->distinct()->get();
        } else {
            $list = Equipseries::select('CONSTRUCTOR')->join('EQUIPMENT', 'EQUIPSERIES.ID_EQUIPSERIES', '=', 'EQUIPMENT.ID_EQUIPSERIES')
            ->distinct()->get();
        }
        
        return $list;
    }

    public function getFamilies()
    {
        $input = $this->request->all();
        $energy = 0;
        $manufacturerLabel = '';

        if (isset($input['idCooling'])) $energy = $input['idCooling'];

        if (isset($input['manufacturerLabel'])) $manufacturerLabel = $input['manufacturerLabel'];

        $list = Translation::select('ID_TRANSLATION', 'LABEL')
        ->join('EQUIPFAMILY', function($fam){
            $fam->on('ID_TRANSLATION', '=', 'EQUIPFAMILY.ID_FAMILY')
            ->join('EQUIPSERIES', function ($series){
                $series->on('EQUIPFAMILY.ID_FAMILY', '=', 'EQUIPSERIES.ID_FAMILY')
                ->join('EQUIPMENT', function($equip){
                    $equip->on('EQUIPSERIES.ID_EQUIPSERIES', '=', 'EQUIPMENT.ID_EQUIPSERIES');
                });
            });
        })
        ->where('TRANS_TYPE', 5)->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->distinct()->get();

        return $list;
    }

    public function getOrigines()
    {
        $input = $this->request->all();
        $energy = 0;
        $manufacturerLabel = '';
        $idFamily = 0;

        if (isset($input['idCooling'])) $energy = $input['idCooling'];

        if (isset($input['manufacturerLabel'])) $manufacturerLabel = $input['manufacturerLabel'];

        if (isset($input['idFamily'])) $idFamily = $input['idFamily'];

        $list = Translation::select('ID_TRANSLATION', 'LABEL')
        ->join('EQUIPMENT', function($equip){
            $equip->on('ID_TRANSLATION', '=', 'EQUIPMENT.STD')
            ->join('EQUIPSERIES', function ($series){
                $series->on('EQUIPSERIES.ID_EQUIPSERIES', '=', 'EQUIPMENT.ID_EQUIPSERIES')
                ->join('EQUIPFAMILY', function($fam){
                    $fam->on('EQUIPFAMILY.ID_FAMILY', '=', 'EQUIPSERIES.ID_FAMILY');
                });
            });
        })
        ->where('TRANS_TYPE', 17)->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->distinct()->get();

        return $list;
    }

    public function getProcesses()
    {
        $input = $this->request->all();
        $energy = 0;
        $manufacturerLabel = '';
        $idFamily = 0;
        $idSdt = 0;

        if (isset($input['idCooling'])) $energy = $input['idCooling'];

        if (isset($input['manufacturerLabel'])) $manufacturerLabel = $input['manufacturerLabel'];

        if (isset($input['idFamily'])) $idFamily = $input['idFamily'];

        if (isset($input['idStd'])) $idStd = $input['idStd'];

        $list = Translation::select('ID_TRANSLATION', 'LABEL')
        ->join('EQUIPFAMILY', function($fam){
            $fam->on('ID_TRANSLATION', '=', 'EQUIPFAMILY.BATCH_PROCESS')
            ->join('EQUIPSERIES', function ($series){
                $series->on('EQUIPFAMILY.ID_FAMILY', '=', 'EQUIPSERIES.ID_FAMILY')
                ->join('EQUIPMENT', function($equip){
                    $equip->on('EQUIPSERIES.ID_EQUIPSERIES', '=', 'EQUIPMENT.ID_EQUIPSERIES');
                });
            });
        })
        ->where('TRANS_TYPE', 13)->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->distinct()->get();

        return $list;
    }

    public function getModels()
    {
        $input = $this->request->all();
        $energy = 0;
        $manufacturerLabel = '';
        $idFamily = 0;
        $idSdt = 0;
        $idProcess = 0;

        if (isset($input['idCooling'])) $energy = $input['idCooling'];

        if (isset($input['manufacturerLabel'])) $manufacturerLabel = $input['manufacturerLabel'];

        if (isset($input['idFamily'])) $idFamily = $input['idFamily'];

        if (isset($input['idStd'])) $idStd = $input['idStd'];

        if (isset($input['idProcess'])) $idProcess = $input['idProcess'];

        $list = Translation::select('ID_TRANSLATION', 'LABEL')
        ->join('EQUIPSERIES', function ($series){
            $series->on('ID_TRANSLATION', '=', 'EQUIPSERIES.ID_EQUIPSERIES');
        })
        ->join('EQUIPFAMILY', function($fam){
            $fam->on('EQUIPFAMILY.ID_FAMILY', '=', 'EQUIPSERIES.ID_FAMILY');
        })
        ->join('EQUIPMENT', function($equip){
            $equip->on('EQUIPSERIES.ID_EQUIPSERIES', '=', 'EQUIPMENT.ID_EQUIPSERIES');
        })
        ->where('TRANS_TYPE', 7)->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->orderBy('LABEL', 'ASC')->distinct()->get();

        return $list;
    }

    public function getLangue()
    {   
        $list = Translation::where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->where('TRANS_TYPE', 9)->get();

        return $list;
    }

    public function getMonetary()
    {
        $list = MonetaryCurrency::all();

        return $list;
    }

    public function getUnits($id) 
    {
        $list = UserUnit::join('UNIT', 'USER_UNIT.ID_UNIT', '=', 'UNIT.ID_UNIT')
        ->where('ID_USER', $id)->orderBy('UNIT.TYPE_UNIT', 'ASC')->get();

        foreach ($list as $key) {
            $symbol = Unit::select('ID_UNIT','TYPE_UNIT','SYMBOL')->where('TYPE_UNIT', $key->TYPE_UNIT)->get();
            $key->listSymbol = $symbol;
        }

        return $list;
    }

    public function getMonetaryUser($id) 
    {
        $userMonetary = MonetaryCurrency::find($this->auth->user()->ID_MONETARY_CURRENCY);
        
        $unitMonetary = Unit::where('SYMBOL', $userMonetary->MONEY_SYMB)->first();

        return $unitMonetary;
    }

    public function updateUnits($id)
    {
        $input = $this->request->all();

        if (!isset($input['Langue']) || !isset($input['DefaultEquipment']) || !isset($input['Units']))
            throw new \Exception("Error Processing Request", 1);
            
        $lang = $input['Langue'];
        $defaultEquip = $input['DefaultEquipment'];
        $units = $input['Units'];
        $user = User::find($id);
        $user->CODE_LANGUE = intval($lang['langId']);
        $user->ID_MONETARY_CURRENCY = intval($lang['monetaryId']);
        $user->USER_ENERGY = intval($defaultEquip['energyId']);
        $user->USER_CONSTRUCTOR = $defaultEquip['construct'];
        $user->USER_FAMILY = intval($defaultEquip['familyId']);
        $user->USER_ORIGINE = intval($defaultEquip['stdId']);
        $user->USER_PROCESS = intval($defaultEquip['batchProcess']);
        $user->USER_MODEL = intval($defaultEquip['equipseriesId']);
        $user->update();
        UserUnit::where('ID_USER', $id)->delete();

        foreach ($units as $key) {
            $userUnit = new UserUnit();
            $userUnit->ID_USER = $id;
            $userUnit->ID_UNIT = intval($key['ID_UNIT']);
            $userUnit->save();
        }

        return $this->getUnits($id);
    }

    public function getUser($id)
    {
        return User::find($id);
    }

    public function getColorDefs()
    {
        $colors = ProdcharColorsDef::where('ID_USER', $this->auth->user()->ID_USER)->orderBy('LAYER_ORDER', 'ASC')->get();
        $ret = [];

        foreach ($colors as $color) {
            array_push($ret, $color->colorPalette);
        }

        return $ret;
    }
}
