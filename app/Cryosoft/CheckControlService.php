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
namespace App\Cryosoft;

use App\Models\Study;
use App\Models\Production;
use App\Models\Product;
use App\Models\ProductElmt;
use App\Models\InitialTemperature;
use App\Models\MeshPosition;
use App\Models\MeshGeneration;
use App\Models\StudyEquipment;
use App\Models\Shape;
use App\Models\Packing;
use App\Models\PipeGen;

class CheckControlService
{
	public function __construct() 
	{

	}

	public function isStdCalcModeChecked($idStudy) 
	{
		$study = Study::select('CALCULATION_MODE')->where('ID_STUDY', $idStudy)->first();

		if ($study != null) {
			$calcMode = $study->CALCULATION_MODE;
			if (($calcMode == 1) || ($calcMode == 2) || ($calcMode == 3)) {
				return true;
			}
		}
		return false;	
	}

	public function isStdCustomerChecked($idStudy) 
	{
		if (($idStudy == null) || ($idStudy < 1)) {
			return false;
		}

		$production = null;

	    $production = Production::where('ID_STUDY', $idStudy)->first();

	    if ($production == null) {
	    	return false;
	    }

	    if ($production->NB_PROD_WEEK_PER_YEAR <= 0.0) {
	    	return false;
	    }

	    $product = Product::where('ID_STUDY', $idStudy)->first();

		if ($product == null) {
	        return false;
	    }

      	$idProd = $product->ID_PROD;
	    $productElmts = ProductElmt::where('ID_PROD', $idProd)->first();

	    if (count($productElmts) <= 0) {
			return false;
		}

		return true;
	}

	public function isStdProductChecked($idStudy) 
	{
		try {
			$product = Product::where('ID_STUDY', $idStudy)->first();

			if ($product == null) {
		        return false;
		    }

	      	$idProd = $product->ID_PROD;
		    $productElmts = ProductElmt::where('ID_PROD', $idProd)->first();

		    if (count($productElmts) <= 0) {
				return false;
			}

			return true;
		} catch (Exception $e) {
			echo "Exception Found-" . $e;
		}
			
		return false;
	}

	public function isStdMesh_InitTempChecked($idStudy, $idProd) 
	{
		try {
			$productElmt = ProductElmt::where('ID_PROD', $idProd)->first();
			$idProdElmt = null;

			if ($productElmt != null) {
				$idProdElmt = $productElmt->ID_PRODUCT_ELMT;
			}

			if ($idProdElmt != null) {
				$meshPositions = MeshPosition::where('ID_PRODUCT_ELMT', $idProdElmt)->first();

				if (count($meshPositions) <= 0) {
					return false;
				}

				$meshGenerations = MeshGeneration::where('ID_PROD', $idProd)->first();

				if (count($meshGenerations) <= 0) {
					return false;
				}

				$production = Production::where('ID_STUDY', $idStudy)->first();

				if ($production == null) {
					return false;
				}

				$idProduction = $production->ID_PRODUCTION;
				$initialTemperatures = InitialTemperature::where('ID_PRODUCTION', $idProduction)->first();

				if (count($initialTemperatures) <= 0) {
					return false;
				}

				return true;
			}

			return false;

		} catch (Exception $e) {
       		echo "Exception Found - " . $e . "<br/>";
    	}

		return false;
	}

	public function isStdEquipmentChecked($idStudy) 
	{
		$studyEquipment = StudyEquipment::where('ID_STUDY', $idStudy);

		if ($studyEquipment == null) {
			return false;
		}

		return true;
	}

	public function isStdPackingChecked($idStudy) 
	{
		try {
			$product = Product::where('ID_STUDY', $idStudy)->first();

			if ($product == null) {
				return false;
      		}

			$idProd = $product->ID_PROD;
	    	$productElmt = ProductElmt::where('ID_PROD', $idProd)->first();
			$idShape = null;

			if ($productElmt != null) {
				$idShape = $productElmt->ID_SHAPE;
			}

			if ($productElmt == null) {
				return false;
			}

			$shape = Shape::where('ID_SHAPE', $idShape)->first();

			if ($shape == null) {
		        return false;
		    }

	    	$packing = Packing::where('ID_STUDY', $idStudy)->first();

			if ($packing == null) {
				return false;
			}

			return true;

		} catch (Exception $e) {
	       echo "Exception Found - " . $e . "<br/>";
	       return false;
	    }

		return true;
	}

	public function isLineEnabled($idStudy) 
	{
		$study = Study::where('ID_STUDY', $idStudy)->first();

		if ($study != null) {
			$pipeline = $study->OPTION_CRYOPIPELINE;
			if ($pipeline == 0) {
				return false;
			}
		}
		return true;
	}

	public function isStdLineChecked($idStudy) 
	{
		$pipe = null;
		try {
			$studyEquipment = StudyEquipment::where('ID_STUDY', $idStudy)->first();
			
			if ($studyEquipment != null) {
				$idStudyEquipments = $studyEquipment->ID_STUDY_EQUIPMENTS;
				$pipe = PipeGen::where('ID_STUDY_EQUIPMENTS', $idStudyEquipments)->first();
			}
		} catch (Exception $e) {
	      echo "Exception Found - " . $e . "<br/>";
	      return false;
	    }

	    if ($pipe == null) {
	        return false;
	    }

		return true;
	}

	public function checkControl($idStudy, $idProd)
	{
		$checkControl = false;

		if ($this->isLineEnabled($idStudy)) {
			if ($this->isStdCalcModeChecked($idStudy) && $this->isStdCustomerChecked($idStudy) 
				&& $this->isStdMesh_InitTempChecked($idStudy, $idProd) && 
				$this->isStdEquipmentChecked($idStudy) && $this->isStdPackingChecked($idStudy) 
				&& $this->isStdLineChecked($idStudy)) {
				$checkControl = true;
			}
		} else {
			if ($this->isStdCalcModeChecked($idStudy) && $this->isStdCustomerChecked($idStudy) 
				&& $this->isStdMesh_InitTempChecked($idStudy, $idProd) && 
				$this->isStdEquipmentChecked($idStudy) && $this->isStdPackingChecked($idStudy)) {
				$checkControl = true;
			}
		}

		return $checkControl;
	}
}