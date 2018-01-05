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

class Admin extends Controller
{	
	/**
	 * @var Illuminate\Http\Request
	 */
	protected $request;

	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	public function newUser()
	{
		$input = $this->request->all();

		$username = isset($input['username']);
		$email = isset($input['email']);
		$password = isset($input['password']);
		$confirm = isset($input['confirmpassword']);
		if ($username == null || $email == null || $password == null || $confirm == null) return 0;

		$hashPassword = Hash::make($password);
		$hashConfirm = Hash::make($confirm);

		if ($password != $confirm) {
			return 2;
		}

		$user = new User();

		$calParaDef = new CalculationParametersDef();

		$meshParamDef = new MeshParamDef();

		$tempRecordDef = new TempRecordPtsDef();

		$users = $this->getUsers();

		// for ($i = 0; $i < count($users); $i++) { 
		// 	if ($users[$i]->USERNAM == $username) {
		// 		return 0;
		// 	}
		// }

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
		$users = User::all()->toArray();
		return $users;
	}

	public function getMinMax($limitItem) 
    {
		return MinMax::where('LIMIT_ITEM', $limitItem)->first();
	}

	public function initProdCharColorWithKernel($newIdUser)
	{
		$user = User::where('USERNAM', 'KERNEL')->first();
		$defaultProds = ProdcharColorsDef::where('ID_USER', $user->ID_USER)->get();
		for ($i = 0; $i < count($defaultProds) ; $i++) {
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
		for ($i = 0; $i < count($defaultUnits) ; $i++) { 
			$userUnit = new UserUnit();
			$userUnit->ID_USER = $newIdUser;
			$userUnit->ID_UNIT = $defaultUnits[$i]->ID_UNIT;
			$userUnit->save();
		}
	}
}