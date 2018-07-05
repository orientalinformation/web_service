<?php

namespace App\Cryosoft;

use App\Models\Study;
use App\Models\Packing;
use App\Models\PackingLayer;
use App\Models\Product;

class PackingService
{
    

    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->value = $app['App\\Cryosoft\\ValueListService'];
        $this->unit = $app['App\\Cryosoft\\UnitsConverterService'];
        $this->kernel = $app['App\\Kernel\\KernelService'];
    }

    public function isTopPackInParent(Study &$study)
    {
        /** @var Study */
        $parentStudy = Study::find($study->PARENT_ID);
        if (!$parentStudy) return false;

        return count(PackingLayer::where('ID_PACKING', $parentStudy->ID_PACKING)->where('PACKING_SIDE_NUMBER', '1')->get()) > 0;
    }

    public function isSidePackInParent(Study &$study)
    {
        /** @var Study */
        $parentStudy = Study::find($study->PARENT_ID);
        if (!$parentStudy) return false;

        return count(PackingLayer::where('ID_PACKING', $parentStudy->ID_PACKING)->where('PACKING_SIDE_NUMBER', '3')->get()) > 0;
    }

    public function isBottomPackInParent(Study &$study)
    {
        /** @var Study */
        $parentStudy = Study::find($study->PARENT_ID);
        if (!$parentStudy) return false;

        return count(PackingLayer::where('ID_PACKING', $parentStudy->ID_PACKING)->where('PACKING_SIDE_NUMBER', '2')->get()) > 0;
    }
}