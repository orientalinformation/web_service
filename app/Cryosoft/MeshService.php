<?php

namespace App\Cryosoft;

use App\Models\MeshGeneration;
use App\Models\InitialTemperature;
use App\Models\Study;
use App\Models\InitTemp3D;

class MeshService
{
    const IRREGULAR_MESH = 0;
    const REGULAR_MESH = 1;

    const MAILLAGE_MODE_REGULAR = 0;
    const MAILLAGE_MODE_IRREGULAR = 1;

    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
        $this->value = $app['App\\Cryosoft\\ValueListService'];
        $this->unit = $app['App\\Cryosoft\\UnitsConverterService'];
        $this->kernel = $app['App\\Kernel\\KernelService'];
    }

    public function findGenerationByProduct(&$product)
    {
        /** @var MeshGeneration $meshGeneration */
        $meshGeneration = MeshGeneration::where('ID_PROD', $product->ID_PROD)->first();

        if (!$meshGeneration) {
            $meshGeneration = new MeshGeneration();
            $meshGeneration->ID_PROD = $product->ID_PROD;
            $meshGeneration->save();
        }

        if ($product->ID_MESH_GENERATION != $meshGeneration->ID_MESH_GENERATION) {
            $product->ID_MESH_GENERATION = $meshGeneration->ID_MESH_GENERATION;
            $product->save();
        }

        return $meshGeneration;
    }

    public function rebuildMesh(Study &$study)
    {
        $product = $study->products->first();
        // $meshGen = $study->products->first()->meshGenerations->first();
        
        // run study cleaner, mode 51 change by haipt SC_CLEAN_OUTPUT_SIZINGCONSO => SC_CLEAN_OUTPUT_PRODUCT
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $product->ID_STUDY, -1);
        $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, SC_CLEAN_OUTPUT_PRODUCT);

        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $product->ID_STUDY);
        $this->kernel->getKernelObject('MeshBuilder')->MBMeshBuild($conf);

        InitialTemperature::where('ID_PRODUCTION', $product->study->ID_PRODUCTION)->delete();
    }

    public function refreshMesh(Study &$study)
    {
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $study->ID_STUDY, 10);
        
        return $this->kernel->getKernelObject('MeshBuilder')->MBMeshBuild($conf);
    }

    public function generate(&$meshGen, $type, $mode, $size1 = -1, $size2 = -1, $size3 = -1)
    {
        // regular mesh
        $calcultype = $type; //estimation
        $result = 1;

        $meshGen->MESH_1_FIXED = $calcultype;
        $meshGen->MESH_2_FIXED = $calcultype;
        $meshGen->MESH_3_FIXED = $calcultype;

        $meshGen->MESH_1_MODE = $mode;
        $meshGen->MESH_2_MODE = $mode;
        $meshGen->MESH_3_MODE = $mode;

        if ($calcultype == self::REGULAR_MESH) {
            $meshGen->MESH_1_SIZE = $size1;
            $meshGen->MESH_2_SIZE = $size2;
            $meshGen->MESH_3_SIZE = $size3;
        } else { // IRREGULAR_MESH
            $meshGen->MESH_1_INT = $size1;
            $meshGen->MESH_2_INT = $size2;
            $meshGen->MESH_3_INT = $size3;

            $meshGen->MESH_1_RATIO = $this->auth->user()->meshParamDef->MESH_RATIO;
            $meshGen->MESH_2_RATIO = $this->auth->user()->meshParamDef->MESH_RATIO;
            $meshGen->MESH_3_RATIO = $this->auth->user()->meshParamDef->MESH_RATIO;

            $meshGen->MESH_1_SIZE = 0;
            $meshGen->MESH_2_SIZE = 0;
            $meshGen->MESH_3_SIZE = 0;
        }
        $meshGen->save();
        
        $product = $meshGen->product;
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $product->ID_STUDY);
        $result = $this->kernel->getKernelObject('MeshBuilder')->MBMeshBuild($conf);

        $prodElmt = $product->productElmts->first();

        // clear initial temperature
        $product->PROD_ISO = 1;
        $product->save();

        InitialTemperature::where('ID_PRODUCTION', $product->study->ID_PRODUCTION)->delete();
        // clear 3D
        if ($prodElmt) {
            InitTemp3D::where('ID_PRODUCT_ELMT', $prodElmt->ID_PRODUCT_ELMT)->delete();
        }

        return $result;
    }
}