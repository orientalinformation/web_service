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
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Cryosoft\CheckControlService;

class CheckControl extends Controller
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
     * @var App\Cryosoft\CheckControlService
     */
    protected $check;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth, CheckControlService $check)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->check = $check;
    }

    public function checkControlView()
    {
        $input = $this->request->all();
        $idStudy = $input['idStudy'];
        $idProd = $input['idProd'];

        $definition = $this->check->isStdCalcModeChecked($idStudy);
        $production = $this->check->isStdCustomerChecked($idStudy);
        $mesh = $this->check->isStdMesh_InitTempChecked($idStudy, $idProd);
        $packing = $this->check->isStdPackingChecked($idStudy);
        $equipment = $this->check->isStdEquipmentChecked($idStudy);
        $pipeEnable = $this->check->isLineEnabled($idStudy);
        $pipeLine = $this->check->isStdLineChecked($idStudy);

        $checkControl = [
            'definition' => $definition,
            'production' => $production,
            'mesh' => $mesh,
            'packing' => $packing,
            'equipment' => $equipment,
            'pipeenable' => $pipeEnable,
            'pipeline' => $pipeLine,
        ];

        return $checkControl;
    }

    public function checkControl()
    {
        $input = $this->request->all();
        $idStudy = $input['idStudy'];
        $idProd = $input['idProd'];

        $checkControl = ['checkcontrol' => $this->check->checkControl($idStudy, $idProd)];

        return $checkControl;
    }
}