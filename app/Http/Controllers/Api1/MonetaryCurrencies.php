<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Models\MonetaryCurrency;

class MonetaryCurrencies extends Controller
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
    public function __construct(Request $request, Auth $auth)
    {
        $this->request = $request;
        $this->auth = $auth;
    }

    public function createMonetaryCurrency()
    {
        $monetaryCurrency = new MonetaryCurrency();
        $input = $this->request->all();
        $monetaryCurrency->MONEY_TEXT = $input['MONEY_TEXT'];
        $monetaryCurrency->MONEY_SYMB = $input['MONEY_SYMB'];
        $monetaryCurrency->save();

        return $monetaryCurrency;
    }

    public function saveMonetaryCurrency()
    {   
        $input = $this->request->all();
        $id = $input['ID_MONETARY_CURRENCY'];
        $monetaryCurrency = MonetaryCurrency::find($id);
        $monetaryCurrency->MONEY_TEXT = $input['MONEY_TEXT'];
        $monetaryCurrency->MONEY_SYMB = $input['MONEY_SYMB'];
        $monetaryCurrency->save();

        return $monetaryCurrency;
    }

     public function getMonetaryCurrencyById($id)
    {
        $monetaryCurrency = MonetaryCurrency::find($id);
        return $monetaryCurrency;
    }
}
