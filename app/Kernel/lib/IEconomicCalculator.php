<?php
// **********************************************************************
//
// Copyright (c) 2003-2017 ZeroC, Inc. All rights reserved.
//
// This copy of Ice is licensed to you under the terms described in the
// ICE_LICENSE file included in this distribution.
//
// **********************************************************************
//
// Ice version 3.7.0
//
// <auto-generated>
//
// Generated from file `IEconomicCalculator.ice'
//
// Warning: do not edit this file.
//
// </auto-generated>
//


namespace
{
    require_once 'Cryosoft.php';
}

namespace Cryosoft\EconomicCalculator
{
    global $Cryosoft_EconomicCalculator__t_IEconomicCalculator;
    global $Cryosoft_EconomicCalculator__t_IEconomicCalculatorPrx;

    class IEconomicCalculatorPrxHelper
    {
        public static function checkedCast($proxy, $facetOrContext=null, $context=null)
        {
            return $proxy->ice_checkedCast('::Cryosoft::EconomicCalculator::IEconomicCalculator', $facetOrContext, $context);
        }

        public static function uncheckedCast($proxy, $facet=null)
        {
            return $proxy->ice_uncheckedCast('::Cryosoft::EconomicCalculator::IEconomicCalculator', $facet);
        }

        public static function ice_staticId()
        {
            return '::Cryosoft::EconomicCalculator::IEconomicCalculator';
        }
    }

    $Cryosoft_EconomicCalculator__t_IEconomicCalculator = IcePHP_defineClass('::Cryosoft::EconomicCalculator::IEconomicCalculator', '\\Cryosoft\\EconomicCalculator\\IEconomicCalculator', -1, false, true, $Ice__t_Value, null);

    $Cryosoft_EconomicCalculator__t_IEconomicCalculatorPrx = IcePHP_defineProxy('::Cryosoft::EconomicCalculator::IEconomicCalculator', $Ice__t_ObjectPrx, null);

    IcePHP_defineOperation($Cryosoft_EconomicCalculator__t_IEconomicCalculatorPrx, 'ECEconomicCalculation', 0, 0, 0, array(array($Cryosoft__t_stSKConf)), null, array($IcePHP__t_long), null);
}
?>