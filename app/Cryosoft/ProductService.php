<?php

namespace App\Cryosoft;

use App\Models\Study;
use App\Models\Translation;

class ProductService
{
    

    public function __construct(\Laravel\Lumen\Application $app)
    {
        $this->app = $app;
        $this->auth = $app['Illuminate\\Contracts\\Auth\\Factory'];
    }

    public function getAllCompFamily()
    {
        $translations = Translation::where('TRANS_TYPE', 14)
            ->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
            ->get();

        for ($i = 0; $i < $translations->count(); $i++) {
            $translations[$i]->LABEL = \mb_convert_encoding($translations[$i]->LABEL, "UTF-8");
        }
        
        return $translations;
    }

    public function getAllSubFamily($compFamily = 0)
    {
        //compFamily this is value return from combobox after select
        $querys = Translation::where('TRANS_TYPE', 16)
            ->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE);

        if ($compFamily != 0) {
            $querys->where('ID_TRANSLATION', '>=', $compFamily * 100)
                ->where('ID_TRANSLATION', '<', ($compFamily + 1) * 100);
        }

        $translations = $querys->get();

        for ($i = 0; $i < $translations->count(); $i++) {
            $translations[$i]->LABEL = \mb_convert_encoding($translations[$i]->LABEL, "UTF-8");
        }
        
        return $translations;
    }

    public function getWaterPercentList()
    {
        $data = ["0 - 10%", "10 - 20%", "20 - 30%", "30 - 40%", "40 - 50%", "50 - 60%", "60 - 70%", "70 - 80%", "80 - 90%", "90 - 100%"];

        return $data;
    }

    public function getAllStandardComponents($idStudy = 0, $compFamily = 0, $subFamily = 0, $percentWater = 0)
    {
        $querys = Translation::select('Translation.ID_TRANSLATION', 'Translation.LABEL', 'component.ID_USER', 'component.COMP_RELEASE', 'component.COMP_VERSION', 'component.OPEN_BY_OWNER', 'component.ID_COMP', 'ln2user.USERNAM')
        ->join('component', 'Translation.ID_TRANSLATION', '=', 'component.ID_COMP')
        ->join('ln2user', 'component.ID_USER', '=', 'ln2user.ID_USER')
        ->where('Translation.TRANS_TYPE', 1)
        ->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE);
        

        if ($idStudy != 0) {
            $querys->where(function ($query) use ($idStudy) {
                $query->where('component.COMP_IMP_ID_STUDY', 0)
                    ->orWhere('component.COMP_IMP_ID_STUDY', $idStudy);
            })->where(function($query) {
                $query->where('component.COMP_RELEASE', 3)
                    ->orWhere('component.COMP_RELEASE', 4)
                    ->orWhere('component.COMP_RELEASE', 8)
                    ->orWhere(function($q){
                        $q->where('component.COMP_RELEASE', 2)
                        ->where('component.ID_USER', $this->auth->user()->ID_USER);
                    });
            });  
        } else {
            $querys->where('component.COMP_RELEASE', '<>', 6);   
        }

        if ($compFamily > 0) {
            $querys->where('component.CLASS_TYPE', $compFamily); 
        }

        if ($subFamily > 0) {
            $querys->where('component.SUB_FAMILY', $subFamily); 
        }

        if ($percentWater > 0) {
            $querys->where('component.WATER', '>=', ($percentWater - 1) * 10); 
            $querys->where('component.WATER', '<=', $percentWater * 10); 
        }

        $querys->orderBy('Translation.LABEL');

        $components = $querys->get();

        $result = [];
        if (count($components) > 0) {
            $i = 0;
            foreach ($components as $cp) {
                if ($cp->COMP_RELEASE != 9 || $cp->ID_USER == $this->auth->user()->ID_USER || $this->auth->user()->USERPRIO <= 1) {
                    $displayName = $cp->LABEL . ' - ' . $cp->COMP_VERSION . ' (Active)';
                    if ($cp->USERNAM != 'KERNEL') {
                        $displayName .= ' - ' . $cp->USERNAM; 
                    }
                    $result[$i]['ID_COMP'] = $cp->ID_COMP; 
                    $result[$i]['displayName'] = trim($displayName);
                    $i++;
                }
            }
        }

        return $result;
    }
    
    public function getAllSleepingComponents($compFamily = 0, $subFamily = 0, $percentWater = 0)
    {
        $querys = Translation::select('Translation.ID_TRANSLATION', 'Translation.LABEL', 'component.COMP_VERSION', 'component.ID_COMP')
        ->join('component', 'Translation.ID_TRANSLATION', '=', 'component.ID_COMP')
        ->where('Translation.CODE_LANGUE', $this->auth->user()->CODE_LANGUE)
        ->where('Translation.TRANS_TYPE', 1)
        ->where('component.COMP_RELEASE', 6);
        

        if ($compFamily > 0) {
            $querys->where('component.CLASS_TYPE', $compFamily); 
        }

        if ($subFamily > 0) {
            $querys->where('component.SUB_FAMILY', $subFamily); 
        }

        if ($percentWater > 0) {
            $querys->where('component.WATER', '>=', ($percentWater - 1) * 10); 
            $querys->where('component.WATER', '<=', $percentWater * 10); 
        }

        $querys->orderBy('Translation.LABEL');

        $components = $querys->get();

        $result = [];
        if (count($components) > 0) {
            $i = 0;
            foreach ($components as $cp) {
                $displayName = $cp->LABEL . ' - ' . $cp->COMP_VERSION;
                $result[$i]['ID_COMP'] = $cp->ID_COMP; 
                $result[$i]['displayName'] = trim($displayName);
                $i++;
            }
        }

        return $result;
    }
}