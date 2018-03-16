<?php

namespace App\Http\Controllers\Api1;

use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Cryosoft\UnitsConverterService;
use App\Cryosoft\ValueListService;
use App\Http\Controllers\Api1\Lines;
use App\Cryosoft\ReportService;
use App\Models\Report;
use App\Models\StudyEquipment;
use App\Models\ProductElmt;
use App\Models\MeshPosition;
use App\Http\Requests;
use App\Models\TempRecordPts;
use App\Models\MinMax;
use App\Models\Study;
use App\Models\Production;
use App\Models\Product;
use App\Models\Translation;
use App\Models\InitialTemperature;
use App\Cryosoft\StudyEquipmentService;
use PDF;
use View;

// HAIDT
use MediaUploader;
use Symfony\Component\HttpFoundation\UploadedFile;
use Symfony\Component\HttpFoundation\File;
use Psr\Http\Message\StreamInterface;
use  Illuminate\Database\Query\Builder;
// end HAIDT

class Reports extends Controller
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
     * @var App\Cryosoft\UnitsConverterService
     */
    protected $convert;

    /**
     * @var App\Cryosoft\ValueListService
     */
    protected $value;

    /**
     * @var \App\Cryosoft\StudyEquipmentService
     */
    protected $stdeqp;
    /**
     * @var \App\Http\Controllers\Api1\Lines
     */
    protected $pipelines;
    /**
     * @var \App\CryoSoft\ReportService
     */
    protected $reportserv;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth, UnitsConverterService $convert, 
    ValueListService $value, StudyEquipmentService $stdeqp, Lines $pipelines, ReportService $reportserv)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->convert = $convert;
        $this->value = $value;
        $this->stdeqp = $stdeqp;
        $this->pipelines = $pipelines;
        $this->reportserv = $reportserv;
    }

    public function getReport($id)
    {
        $report = Report::where('ID_STUDY', $id)->first();

        if ($report) {
            $report->consumptionSymbol = $this->convert->consumptionSymbolUser(2, 1);
            $report->isSizingValuesChosen = ($report->SIZING_VALUES & 1);
            $report->isSizingValuesMax = ($report->SIZING_VALUES & 16);
            $studyEquip = StudyEquipment::where('ID_STUDY', $id)->where('BRAIN_TYPE', 4)->get();

            if (count($studyEquip) > 0) {
                $report->isThereCompleteNumericalResults = true;
            } else {
                $report->isThereCompleteNumericalResults = false;
            }

            $productElmt = ProductElmt::where('ID_STUDY', $id)->first();
            $report->productElmt = $productElmt;
            $report->temperatureSymbol = $this->convert->temperatureSymbolUser();
            $report->CONTOUR2D_TEMP_STEP = doubleval($report->CONTOUR2D_TEMP_STEP);
            $report->CONTOUR2D_TEMP_MIN = doubleval($this->convert->prodTemperature($report->CONTOUR2D_TEMP_MIN));
            $report->CONTOUR2D_TEMP_MAX = doubleval($this->convert->prodTemperature($report->CONTOUR2D_TEMP_MAX));
            $borne = $this->getReportTemperatureBorne($id); 
            $report->refContRep2DTempMinRef = $this->convert->prodTemperature($borne[0]->MIN_TEMP);
            $report->refContRep2DTempMaxRef = $this->convert->prodTemperature($borne[0]->MAX_TEMP);

            $pasTemp = $this->calculatePasTemp($report->refContRep2DTempMinRef, $report->refContRep2DTempMaxRef, true);
            $report->refContRep2DTempMinRef = doubleval($pasTemp['dTmin']);
            $report->refContRep2DTempMaxRef = doubleval($pasTemp['dTMax']);
            $report->refContRep2DTempStepRef = doubleval($pasTemp['dpas']);

        } else {
            $minMaxSample = MinMax::where('LIMIT_ITEM', 1116)->first();
            $report = new Report();
            $report->ID_STUDY = $id;
            $report->DEST_NAME = "";
            $report->DEST_SURNAME = "";
            $report->DEST_FUNCTION = "";
            $report->DEST_COORD = "";
            $report->WRITER_NAME = "";
            $report->WRITER_SURNAME = "";
            $report->WRITER_FUNCTION = "";
            $report->WRITER_COORD = "";
            $report->CUSTOMER_LOGO = "";
            $report->PHOTO_PATH = "";
            $report->REPORT_COMMENT = "";
            $report->PROD_LIST = 1;
            $report->PROD_3D = 1;
            $report->EQUIP_LIST = 1;
            $report->REP_CUSTOMER = 1;
            $report->PACKING = 0;
            $report->PIPELINE = 0;
            $report->ASSES_CONSUMP = 0;
            $report->CONS_SPECIFIC = 0;
            $report->CONS_OVERALL = 0;
            $report->CONS_TOTAL = 0;
            $report->CONS_HOUR = 0;
            $report->CONS_DAY = 0;
            $report->CONS_WEEK = 0;
            $report->CONS_MONTH = 0;
            $report->CONS_YEAR = 0;
            $report->CONS_EQUIP = 0;
            $report->CONS_PIPE = 0;
            $report->CONS_TANK = 0;
            $report->REP_CONS_PIE = 0;
            $report->SIZING_VALUES = 0;
            $report->SIZING_GRAPHE = 0;
            $report->SIZING_TR = 1;
            $report->ENTHALPY_G = 0;
            $report->ENTHALPY_V = 0;
            $report->ENTHALPY_SAMPLE = intval(round($minMaxSample->DEFAULT_VALUE));
            $report->ISOCHRONE_G = 0;
            $report->ISOCHRONE_V = 0;
            $report->ISOCHRONE_SAMPLE = intval(round($minMaxSample->DEFAULT_VALUE));
            $report->ISOVALUE_G = 0;
            $report->ISOVALUE_V = 0;
            $report->ISOVALUE_SAMPLE = intval(round($minMaxSample->DEFAULT_VALUE));
            $report->CONTOUR2D_G = 0;
            $report->CONTOUR2D_TEMP_STEP = 0;
            $report->CONTOUR2D_TEMP_MIN = 0;
            $report->CONTOUR2D_TEMP_MAX = 0;
            $report->POINT1_X = 0;
            $report->POINT1_Y = 0;
            $report->POINT1_Z = 0;
            $report->POINT2_X = 0;
            $report->POINT2_Y = 0;
            $report->POINT2_Z = 0;
            $report->POINT3_X = 0;
            $report->POINT3_Y = 0;
            $report->POINT3_Z = 0;
            $report->AXE1_X = 0;
            $report->AXE1_Y = 0;
            $report->AXE2_X = 0;
            $report->AXE2_Z = 0;
            $report->AXE3_Y = 0;
            $report->AXE3_Z = 0;
            $report->PLAN_X = 0;
            $report->PLAN_Y = 0;
            $report->PLAN_Z = 0;
            $report->ASSES_ECO = 0;
            $report->save();
        }
        // HAIDT
        $report->ip = 'http://'.$_SERVER['HTTP_HOST'];
        // end HAIDT
        
        return $report;
    }

    public function getReportTemperatureBorne($id)
    {
        return DB::select("SELECT MIN( TRD.TEMP ) AS MIN_TEMP, MAX( TRD.TEMP ) AS MAX_TEMP FROM TEMP_RECORD_DATA AS TRD 
        JOIN (SELECT REC_POS.ID_REC_POS FROM RECORD_POSITION AS REC_POS JOIN (SELECT REC_POS1.ID_STUDY_EQUIPMENTS, 
        MAX(REC_POS1.RECORD_TIME) AS RECORD_TIME FROM RECORD_POSITION AS REC_POS1 
        JOIN STUDY_EQUIPMENTS AS STD_EQP ON REC_POS1.ID_STUDY_EQUIPMENTS = STD_EQP.ID_STUDY_EQUIPMENTS 
        WHERE STD_EQP.ID_STUDY = " . $id . " GROUP BY REC_POS1.ID_STUDY_EQUIPMENTS) AS REC_POS2 
        ON REC_POS.ID_STUDY_EQUIPMENTS = REC_POS2.ID_STUDY_EQUIPMENTS AND REC_POS.RECORD_TIME = REC_POS2.RECORD_TIME) AS REC_POS3 ON TRD.ID_REC_POS = REC_POS3.ID_REC_POS");
    }

    public function calculatePasTemp($lfTmin, $lfTMax, $auto)
    {
        $dpas = 0;
        $dnbpas = 0;
        $dTmin = intval(floor($lfTmin));
        $dTMax = intval(ceil($lfTMax));

        if ($auto) {
            $dpas = intval(floor(abs($dTMax - $dTmin) / 14) - 1);
        }
        do {
            $dpas++;
            if ($dpas != 0) {

                while ($dTmin % $dpas != 0) {
                    $dTmin--;
                }
                while ($dTMax % $dpas != 0) {
                    $dTMax++;
                }
                $dnbpas = abs($dTMax - $dTmin) / $dpas;    
            }
        } while ($dnbpas > 16);

        return [
            'dTmin' => $dTmin,
            'dTMax' => $dTMax,
            'dpas' => $dpas
        ];
    }

    public function initListPoints($id, $axe)
    {
        return MeshPosition::join('product_elmt', 'mesh_position.ID_PRODUCT_ELMT', '=', 'product_elmt.ID_PRODUCT_ELMT')
        ->join('product', 'product_elmt.ID_PROD' , '=', 'product.ID_PROD')
        ->where('product.ID_STUDY', $id)->where('MESH_AXIS', $axe)->distinct()->orderBy('MESH_AXIS_POS', 'ASC')->get();
    }

    public function getMeshAxisPos($id) 
    {
        $list1 = $this->initListPoints($id, 1);
        $list2 = $this->initListPoints($id, 2);
        $list3 = $this->initListPoints($id, 3);
        
        foreach ($list1 as $key) {
            $key->meshAxisPosValue = floatval($this->convert->meshes($key->MESH_AXIS_POS, $this->value->MESH_CUT));
        }
        foreach ($list2 as $key) {
            $key->meshAxisPosValue = floatval($this->convert->meshes($key->MESH_AXIS_POS, $this->value->MESH_CUT));
        }
        foreach ($list3 as $key) {
            $key->meshAxisPosValue = floatval($this->convert->meshes($key->MESH_AXIS_POS, $this->value->MESH_CUT));
        }

        return [
            'axis1' => $list1,
            'axis2' => $list2,
            'axis3' => $list3
        ];
    }

    public function saveReport($id)
    {
        $input = $this->request->all();

        if (isset($input['DEST_NAME'])) $DEST_NAME = $input['DEST_NAME'];
        
        if (isset($input['DEST_SURNAME'])) $DEST_SURNAME = $input['DEST_SURNAME'];

        if (isset($input['DEST_FUNCTION'])) $DEST_FUNCTION = $input['DEST_FUNCTION'];

        if (isset($input['DEST_COORD'])) $DEST_COORD = $input['DEST_COORD'];

        if (isset($input['WRITER_NAME'])) $WRITER_NAME = $input['WRITER_NAME'];
        
        if (isset($input['WRITER_SURNAME'])) $WRITER_SURNAME = $input['WRITER_SURNAME'];

        if (isset($input['WRITER_FUNCTION'])) $WRITER_FUNCTION = $input['WRITER_FUNCTION'];

        if (isset($input['WRITER_COORD'])) $WRITER_COORD = $input['WRITER_COORD'];

        if (isset($input['CUSTOMER_LOGO'])) $CUSTOMER_LOGO = $input['CUSTOMER_LOGO'];

        if (isset($input['PHOTO_PATH'])) $PHOTO_PATH = $input['PHOTO_PATH'];

        if (isset($input['REPORT_COMMENT'])) $REPORT_COMMENT = $input['REPORT_COMMENT'];

        if (isset($input['PROD_LIST'])) $PROD_LIST = $input['PROD_LIST'];

        if (isset($input['PROD_3D'])) $PROD_3D = $input['PROD_3D'];

        if (isset($input['EQUIP_LIST'])) $EQUIP_LIST = $input['EQUIP_LIST'];

        if (isset($input['REP_CUSTOMER'])) $REP_CUSTOMER = $input['REP_CUSTOMER'];

        if (isset($input['PACKING'])) $PACKING = $input['PACKING'];

        if (isset($input['PIPELINE'])) $PIPELINE = $input['PIPELINE'];

        if (isset($input['ASSES_CONSUMP'])) $ASSES_CONSUMP = $input['ASSES_CONSUMP'];

        if (isset($input['CONS_SPECIFIC'])) $CONS_SPECIFIC = $input['CONS_SPECIFIC'];

        if (isset($input['CONS_OVERALL'])) $CONS_OVERALL = $input['CONS_OVERALL'];

        if (isset($input['CONS_TOTAL'])) $CONS_TOTAL = $input['CONS_TOTAL'];

        if (isset($input['CONS_HOUR'])) $CONS_HOUR = $input['CONS_HOUR'];

        if (isset($input['CONS_DAY'])) $CONS_DAY = $input['CONS_DAY'];

        if (isset($input['CONS_WEEK'])) $CONS_WEEK = $input['CONS_WEEK'];

        if (isset($input['CONS_MONTH'])) $CONS_MONTH = $input['CONS_MONTH'];

        if (isset($input['CONS_YEAR'])) $CONS_YEAR = $input['CONS_YEAR'];

        if (isset($input['CONS_EQUIP'])) $CONS_EQUIP = $input['CONS_EQUIP'];

        if (isset($input['CONS_PIPE'])) $CONS_PIPE = $input['CONS_PIPE'];

        if (isset($input['CONS_TANK'])) $CONS_TANK = $input['CONS_TANK'];

        if (isset($input['REP_CONS_PIE'])) $REP_CONS_PIE = $input['REP_CONS_PIE'];

        if (isset($input['SIZING_VALUES'])) $SIZING_VALUES = $input['SIZING_VALUES'];

        if (isset($input['SIZING_GRAPHE'])) $SIZING_GRAPHE = $input['SIZING_GRAPHE'];

        if (isset($input['SIZING_TR'])) $SIZING_TR = $input['SIZING_TR'];

        if (isset($input['ENTHALPY_G'])) $ENTHALPY_G = $input['ENTHALPY_G'];

        if (isset($input['ENTHALPY_V'])) $ENTHALPY_V = $input['ENTHALPY_V'];

        if (isset($input['ENTHALPY_SAMPLE'])) $ENTHALPY_SAMPLE = $input['ENTHALPY_SAMPLE'];

        if (isset($input['ISOCHRONE_G'])) $ISOCHRONE_G = $input['ISOCHRONE_G'];

        if (isset($input['ISOCHRONE_V'])) $ISOCHRONE_V = $input['ISOCHRONE_V'];

        if (isset($input['ISOCHRONE_SAMPLE'])) $ISOCHRONE_SAMPLE = $input['ISOCHRONE_SAMPLE'];

        if (isset($input['ISOVALUE_G'])) $ISOVALUE_G = $input['ISOVALUE_G'];

        if (isset($input['ISOVALUE_V'])) $ISOVALUE_V = $input['ISOVALUE_V'];

        if (isset($input['ISOVALUE_SAMPLE'])) $ISOVALUE_SAMPLE = $input['ISOVALUE_SAMPLE'];

        if (isset($input['CONTOUR2D_G'])) $CONTOUR2D_G = $input['CONTOUR2D_G'];

        if (isset($input['CONTOUR2D_TEMP_STEP'])) $CONTOUR2D_TEMP_STEP = $input['CONTOUR2D_TEMP_STEP'];

        if (isset($input['CONTOUR2D_TEMP_MIN'])) $CONTOUR2D_TEMP_MIN = $input['CONTOUR2D_TEMP_MIN'];

        if (isset($input['CONTOUR2D_TEMP_MAX'])) $CONTOUR2D_TEMP_MAX = $input['CONTOUR2D_TEMP_MAX'];

        if (isset($input['POINT1_X'])) $POINT1_X = $input['POINT1_X'];

        if (isset($input['POINT1_Y'])) $POINT1_Y = $input['POINT1_Y'];

        if (isset($input['POINT1_Z'])) $POINT1_Z = $input['POINT1_Z'];

        if (isset($input['POINT2_X'])) $POINT2_X = $input['POINT2_X'];

        if (isset($input['POINT2_Y'])) $POINT2_Y = $input['POINT2_Y'];

        if (isset($input['POINT2_Z'])) $POINT2_Z = $input['POINT2_Z'];

        if (isset($input['POINT3_X'])) $POINT3_X = $input['POINT3_X'];

        if (isset($input['POINT3_Y'])) $POINT3_Y = $input['POINT3_Y'];

        if (isset($input['POINT3_Z'])) $POINT3_Z = $input['POINT3_Z'];

        if (isset($input['AXE1_X'])) $AXE1_X = $input['AXE1_X'];

        if (isset($input['AXE1_Y'])) $AXE1_Y = $input['AXE1_Y'];

        if (isset($input['AXE2_X'])) $AXE2_X = $input['AXE2_X'];

        if (isset($input['AXE2_Z'])) $AXE2_Z = $input['AXE2_Z'];

        if (isset($input['AXE3_Y'])) $AXE3_Y = $input['AXE3_Y'];

        if (isset($input['AXE3_Z'])) $AXE3_Z = $input['AXE3_Z'];

        if (isset($input['PLAN_X'])) $PLAN_X = $input['PLAN_X'];

        if (isset($input['PLAN_Y'])) $PLAN_Y = $input['PLAN_Y'];

        if (isset($input['PLAN_Z'])) $PLAN_Z = $input['PLAN_Z'];

        if (isset($input['ID_STUDY'])) $ID_STUDY = $input['ID_STUDY'];

        if (isset($input['ASSES_ECO'])) $ASSES_ECO = $input['ASSES_ECO'];

        $report = Report::where('ID_STUDY', $id)->first();

        // $report->ID_STUDY = $ID_STUDY;
        $report->DEST_NAME = $DEST_NAME;
        $report->DEST_SURNAME = $DEST_SURNAME;
        $report->DEST_FUNCTION = $DEST_FUNCTION;
        $report->DEST_COORD = $DEST_COORD;
        $report->WRITER_NAME = $WRITER_NAME;
        $report->WRITER_SURNAME = $WRITER_SURNAME;
        $report->WRITER_FUNCTION = $WRITER_FUNCTION;
        $report->WRITER_COORD = $WRITER_COORD;
        $report->CUSTOMER_LOGO = $CUSTOMER_LOGO;
        $report->PHOTO_PATH = $PHOTO_PATH;
        $report->REPORT_COMMENT = $REPORT_COMMENT;
        $report->PROD_LIST = $PROD_LIST;
        $report->PROD_3D = $PROD_3D;
        $report->EQUIP_LIST = $EQUIP_LIST;
        $report->REP_CUSTOMER = $REP_CUSTOMER;
        $report->PACKING = $PACKING;
        $report->PIPELINE = $PIPELINE;
        $report->ASSES_CONSUMP = $ASSES_CONSUMP;
        $report->CONS_SPECIFIC = $CONS_SPECIFIC;
        $report->CONS_OVERALL = $CONS_OVERALL;
        $report->CONS_TOTAL = $CONS_TOTAL;
        $report->CONS_HOUR = $CONS_HOUR;
        $report->CONS_DAY = $CONS_DAY;
        $report->CONS_WEEK = $CONS_WEEK;
        $report->CONS_MONTH = $CONS_MONTH;
        $report->CONS_YEAR = $CONS_YEAR;
        $report->CONS_EQUIP = $CONS_EQUIP;
        $report->CONS_PIPE = $CONS_PIPE;
        $report->CONS_TANK = $CONS_TANK;
        $report->REP_CONS_PIE = $REP_CONS_PIE;
        $report->SIZING_VALUES = $SIZING_VALUES;
        $report->SIZING_GRAPHE = $SIZING_GRAPHE;
        $report->SIZING_TR = $SIZING_TR;
        $report->ENTHALPY_G = $ENTHALPY_G;
        $report->ENTHALPY_V = $ENTHALPY_V;
        $report->ENTHALPY_SAMPLE = $ENTHALPY_SAMPLE;
        $report->ISOCHRONE_G = $ISOCHRONE_G;
        $report->ISOCHRONE_V = $ISOCHRONE_V;
        $report->ISOCHRONE_SAMPLE = $ISOCHRONE_SAMPLE;
        $report->ISOVALUE_G = $ISOVALUE_G;
        $report->ISOVALUE_V = $ISOVALUE_V;
        $report->ISOVALUE_SAMPLE = $ISOVALUE_SAMPLE;
        $report->CONTOUR2D_G = $CONTOUR2D_G;
        $report->CONTOUR2D_TEMP_STEP = $CONTOUR2D_TEMP_STEP;
        $report->CONTOUR2D_TEMP_MIN = $CONTOUR2D_TEMP_MIN;
        $report->CONTOUR2D_TEMP_MAX = $CONTOUR2D_TEMP_MAX;
        $report->POINT1_X = $POINT1_X;
        $report->POINT1_Y = $POINT1_Y;
        $report->POINT1_Z = $POINT1_Z;
        $report->POINT2_X = $POINT2_X;
        $report->POINT2_Y = $POINT2_Y;
        $report->POINT2_Z = $POINT2_Z;
        $report->POINT3_X = $POINT3_X;
        $report->POINT3_Y = $POINT3_Y;
        $report->POINT3_Z = $POINT3_Z;
        $report->AXE1_X = $AXE1_X;
        $report->AXE1_Y = $AXE1_Y;
        $report->AXE2_X = $AXE2_X;
        $report->AXE2_Z = $AXE2_Z;
        $report->AXE3_Y = $AXE3_Y;
        $report->AXE3_Z = $AXE3_Z;
        $report->PLAN_X = $PLAN_X;
        $report->PLAN_Y = $PLAN_Y;
        $report->PLAN_Z = $PLAN_Z;
        $report->ASSES_ECO = $ASSES_ECO;

        $report->update();

        return 1;
    }

    function downLoadPDF($id) {
        $study = Study::find($id);
        $host = 'http://' . $_SERVER['HTTP_HOST'];
        echo "{'processing':true}";
        ob_end_flush();
        $public_path = rtrim(app()->basePath("public/"), '/');
        $progressFile = "$study->ID_STUDY- $study->STUDY_NAME-Report.progess";
        $name_report = "$study->ID_STUDY- $study->STUDY_NAME-Report.pdf";
        
        if (!is_dir($public_path . "/reports/" . $study->USERNAM)) {
            mkdir($public_path . "/reports/" . $study->USERNAM, 0777, true);
        } 
        $production = Production::Where('ID_STUDY', $id)->first();
        $progress = "Production";
        
        $product = Product::Where('ID_STUDY', $id)->first();
        $proElmt = ProductElmt::Where('ID_PROD', $product->ID_PROD)->first();
        
        $idComArr = [];
        $comprelease = [];
        
        foreach ($product->productElmts as $productElmt) {
            $shapeCode = $productElmt->shape->SHAPECODE;
            $idComArr[] = $productElmt->ID_COMP;
            $idElmArr[] = $productElmt->ID_PRODUCT_ELMT;
            $comprelease[] = $productElmt->component->COMP_RELEASE;
        }
        
        $shapeName = Translation::where('TRANS_TYPE', 4)->where('ID_TRANSLATION', $shapeCode)->where('CODE_LANGUE', $study->user->CODE_LANGUE)->orderBy('LABEL', 'ASC')->first();
        $componentName = ProductElmt::select('LABEL','ID_COMP', 'ID_PRODUCT_ELMT', 'PROD_ELMT_ISO', 'PROD_ELMT_NAME', 'PROD_ELMT_REALWEIGHT', 'SHAPE_PARAM2')
        ->join('Translation', 'ID_COMP', '=', 'Translation.ID_TRANSLATION')->whereIn('ID_PRODUCT_ELMT', $idElmArr)
        ->where('TRANS_TYPE', 1)->whereIn('ID_TRANSLATION', $idComArr)
        ->where('CODE_LANGUE', $study->user->CODE_LANGUE)->orderBy('LABEL', 'DESC')->get();
        $progress .= "\nProduct";
        

        $equipData = $this->stdeqp->findStudyEquipmentsByStudy($study);
        $progress .= "\nEquiment";
        
        
        $symbol = $this->reportserv->getSymbol($study->ID_STUDY);
        $infoReport = $study->reports;
        // return $study;
        if ($study->OPTION_CRYOPIPELINE == 1) {
            $cryogenPipeline = $this->pipelines->loadPipeline($study->ID_STUDY);
            $progress .= "\nPipeline Elements";
            
        } else {
            $cryogenPipeline = "";
        }
        
        $consumptions = $this->reportserv->getAnalyticalConsumption($study->ID_STUDY);
        
        $progress .= "\nConsumptions Results";
        
        if ($study->CALCULATION_MODE == 3) {
            $calModeHeadBalance = $this->reportserv->getOptimumHeadBalance($study->ID_STUDY);
            $calModeHbMax = $this->reportserv->getOptimumHeadBalanceMax($study->ID_STUDY);
            $progress .= "\nConsumptions Pies";
        } else if ($study->CALCULATION_MODE == 1) {
            $calModeHeadBalance = $this->reportserv->getEstimationHeadBalance($study->ID_STUDY, 1);
            $calModeHbMax = "";
        }
        
        $proInfoStudy = $this->reportserv->getProInfoStudy($study->ID_STUDY);
        $proSections = [];
        $pro2Dchart = [];
        $progress .= "\nSizing";
        
        foreach ($study->studyEquipments as $key=> $idstudyequips) {
            if ($idstudyequips->BRAIN_TYPE == 4) {
                $heatexchange[] = $this->reportserv->heatExchange($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS);
                $timeBase[] = $this->reportserv->timeBased($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS);
                if ($shapeCode == 1) { 
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                } else if ($shapeCode == 2) {
                    if ($equipData[$key]['ORIENTATION'] == 1) {
                        $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                        $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3);
                    } else {
                        $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                        $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                    }
                    $pro2Dchart[] = $this->reportserv->productchart2D($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                } else if (($shapeCode == 4) && ($shapeCode == 7) && ($shapeCode == 8) && ($shapeCode == 5)) {
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                } else if ($shapeCode == 6) {
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                } else if ($shapeCode == 9) {
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3);
                    $pro2Dchart[] = $this->reportserv->productchart2D($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                } else if ($shapeCode == 3) {
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                    $pro2Dchart[] = $this->reportserv->productchart2D($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3);
                }
            } else {
                $proSections = [];
                $heatexchange = [];
                $timeBase = [];
            }
            // return $pro2Dchart;
        }
        if ($idstudyequips->BRAIN_TYPE == 4) {
            $progress .= "\nEnthpies";
            $progress .= "\nTime Based";
            $progress .= "\nProduct Section";
            if (($shapeCode == 3) || ($shapeCode == 2) || ($shapeCode == 9)) {
                $progress .= "\nContour";
            }
        }
        
        $productComps = [];
        foreach ($componentName as $key => $value) {
            $componentStatus = Translation::select('LABEL')->where('TRANS_TYPE', 100)->whereIn('ID_TRANSLATION', $comprelease)->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->orderBy('LABEL', 'ASC')->first();
            $productComps[] = $value;
            $productComps[$key]['display_name'] = $value->LABEL . ' - ' . $productElmt->component->COMP_VERSION . '(' . $componentStatus->LABEL . ' )';
        }
        file_put_contents($public_path. "/reports/" . $study->USERNAM. "/" .$progressFile, $progress);
        // set document information
        // PDF::SetCreator(PDF_CREATOR);
        PDF::setPageOrientation('L');
        PDF::SetAuthor('');
        PDF::SetTitle('Cryosoft Report');
        PDF::SetSubject('UserName - StudyName');
        PDF::SetKeywords('');

        // set default header data
        PDF::SetHeaderData($public_path . "/reports/" . 'air-liquide-logo.png', 30, $study->STUDY_NAME,'Report');

        // set header and footer fonts
        PDF::setHeaderFont(Array('helvetica', '', 10));
        // PDF::setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        // PDF::SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        PDF::SetMargins(15, 27, 15);
        PDF::SetHeaderMargin(5);
        PDF::SetFooterMargin(10);

        // set auto page breaks
        PDF::SetAutoPageBreak(TRUE, 15);

        // set image scale factor
        PDF::setImageScale(1.25);

        // set some language-dependent strings (optional)
        if (@file_exists($tcpdf_path.'/lang/eng.php')) {
            require_once($tcpdf_path.'/lang/eng.php');
            PDF::setLanguageArray($l);
        }
        // ---------------------------------------------------------
        PDF::AddPage();
        PDF::Bookmark('Chapter 1', 0, 0, '', 'B', array(0,64,128));
        // print a line using Cell()
        PDF::Cell(0, 10, 'Chapter 1', 0, 1, 'L');
        $view = $this->viewPDF($study, $production, $product, $proElmt, $shapeName, 
        $productComps, $equipData, $cryogenPipeline, $consumptions, $proInfoStudy,
        $calModeHbMax, $calModeHeadBalance, $heatexchange, $proSections, $timeBase, 
        $symbol, $public_path, $pro2Dchart);
        $html= $view->render();
        // return $html;
        PDF::SetFont('helvetica', '', 6);
        PDF::writeHTML($html, true, false, true, false, '');
        // return 10000;
        PDF::AddPage();
        PDF::Bookmark('Paragraph 1.1', 1, 0, '', '', array(128,0,0));
        PDF::Cell(0, 10, 'Paragraph 1.1', 0, 1, 'L');
        PDF::AddPage();

        // add some pages and bookmarks
        for ($i = 2; $i < 12; $i++) {
            PDF::AddPage();
            PDF::Bookmark('Chapter '.$i, 0, 0, '', 'B', array(0,64,128));
            PDF::Cell(0, 10, 'Chapter '.$i, 0, 1, 'L');
        }

        // add a new page for TOC
        PDF::addTOCPage();

        // write the TOC title and/or other elements on the TOC page
        // PDF::SetFont('times', 'B', 6);
        PDF::MultiCell(0, 0, 'Table Of Content', 0, 'C', 0, 1, '', '', true, 0);
        PDF::Ln();
        // define styles for various bookmark levels
        $bookmark_templates = array();

        $bookmark_templates[0] = '<table border="0" cellpadding="0" cellspacing="0" style="background-color:#EEFAFF"><tr><td width="155mm"><span style="font-family:times;font-weight:bold;font-size:12pt;color:black;">#TOC_DESCRIPTION#</span></td><td width="25mm"><span style="font-family:courier;font-weight:bold;font-size:12pt;color:black;" align="right">#TOC_PAGE_NUMBER#</span></td></tr></table>';
        $bookmark_templates[1] = '<table border="0" cellpadding="0" cellspacing="0"><tr><td width="5mm">&nbsp;</td><td width="150mm"><span style="font-family:times;font-size:11pt;color:green;">#TOC_DESCRIPTION#</span></td><td width="25mm"><span style="font-family:courier;font-weight:bold;font-size:11pt;color:green;" align="right">#TOC_PAGE_NUMBER#</span></td></tr></table>';
        $bookmark_templates[2] = '<table border="0" cellpadding="0" cellspacing="0"><tr><td width="10mm">&nbsp;</td><td width="145mm"><span style="font-family:times;font-size:10pt;color:#666666;"><i>#TOC_DESCRIPTION#</i></span></td><td width="25mm"><span style="font-family:courier;font-weight:bold;font-size:10pt;color:#666666;" align="right">#TOC_PAGE_NUMBER#</span></td></tr></table>';
        // add other bookmark level templates here ...

        // add table of content at page 1
        // (check the example n. 45 for a text-only TOC
        PDF::addHTMLTOC(1, 'INDEX', $bookmark_templates, true, 'B', array(128,0,0));

        // end of TOC page
        PDF::endTOCPage();
        PDF::Output( $public_path. "/reports/" . $study->USERNAM."/" . $name_report, 'F');
            
        // } 
        return ["url" => "$host/reports/$study->USERNAM/$name_report"];
    }
    
    public function viewPDF($study ,$production, $product, $proElmt, $shapeName, 
    $productComps, $equipData, $cryogenPipeline, $consumptions, $proInfoStudy,
    $calModeHbMax, $calModeHeadBalance, $heatexchange, $proSections, $timeBase , $symbol, 
    $public_path, $pro2Dchart) 
    {
        $arrayParam = [
            'study' => $study,
            'production' => $production,
            'product' => $product,
            'proElmt' => $proElmt,
            'shapeName' => $shapeName,
            'proInfoStudy' => $proInfoStudy,
            'symbol' => $symbol,
            'public_path' => $public_path,
        ];
        $param = [
            'arrayParam' => $arrayParam,
            'productComps' => $productComps,
            'equipData' => $equipData,
            'cryogenPipeline' => $cryogenPipeline,
            'consumptions' => $consumptions,
            'calModeHeadBalance' => $calModeHeadBalance,
            'calModeHbMax' => $calModeHbMax,
            'heatexchange' => $heatexchange,
            'proSections' => $proSections,
            'timeBase' => $timeBase,
            'pro2Dchart' => $pro2Dchart
        ];
        return view('report.view_report', $param);
    }

    
    public function downLoadHtmlToPDF($id)
    {   
        $study = Study::find($id);
        $host = 'http://' . $_SERVER['HTTP_HOST'];
        $public_path = rtrim(app()->basePath("public/"), '/');
        $name_report = "$study->ID_STUDY- $study->STUDY_NAME-Report.html";
        if (!is_dir( $public_path. "/reports/"  . $study->USERNAM)) {
            mkdir( $public_path. "/reports/" . $study->USERNAM, 0777, true);
        } 
        // if (!file_exists($public_path. "/" . $study->USERNAM. "/" .$name_report)) {
        $production = Production::Where('ID_STUDY', $id)->first();
        $product = Product::Where('ID_STUDY', $id)->first();
        $proElmt = ProductElmt::Where('ID_PROD', $product->ID_PROD)->first();
        $idComArr = [];
        $comprelease = [];
        foreach ($product->productElmts as $productElmt) {
            $shapeCode = $productElmt->shape->SHAPECODE;
            $idComArr[] = $productElmt->ID_COMP;
            $idElmArr[] = $productElmt->ID_PRODUCT_ELMT;
            $comprelease[] = $productElmt->component->COMP_RELEASE;
        }
        $componentName = ProductElmt::select('LABEL','ID_COMP', 'ID_PRODUCT_ELMT', 'PROD_ELMT_ISO', 'PROD_ELMT_NAME', 'PROD_ELMT_REALWEIGHT', 'SHAPE_PARAM2')
        ->join('Translation', 'ID_COMP', '=', 'Translation.ID_TRANSLATION')->whereIn('ID_PRODUCT_ELMT', $idElmArr)
        ->where('TRANS_TYPE', 1)->whereIn('ID_TRANSLATION', $idComArr)
        ->where('CODE_LANGUE', $study->user->CODE_LANGUE)->orderBy('LABEL', 'DESC')->get();
        $equipData = $this->stdeqp->findStudyEquipmentsByStudy($study);
        $shapeName = Translation::where('TRANS_TYPE', 4)->where('ID_TRANSLATION', $shapeCode)->where('CODE_LANGUE', $study->user->CODE_LANGUE)->orderBy('LABEL', 'ASC')->first();
        $symbol = $this->reportserv->getSymbol($study->ID_STUDY);
        $infoReport = $study->reports;
        // return $study;
        if ($study->OPTION_CRYOPIPELINE == 1) {
            $cryogenPipeline = $this->pipelines->loadPipeline($study->ID_STUDY);
        } else {
            $cryogenPipeline = "";
        }
        $consumptions = $this->reportserv->getAnalyticalConsumption($study->ID_STUDY);
        if ($study->CALCULATION_MODE == 3) {
            $calModeHeadBalance = $this->reportserv->getOptimumHeadBalance($study->ID_STUDY);
            $calModeHbMax = $this->reportserv->getOptimumHeadBalanceMax($study->ID_STUDY);
        } else if ($study->CALCULATION_MODE == 1) {
            $calModeHeadBalance = $this->reportserv->getEstimationHeadBalance($study->ID_STUDY, 1);
            $calModeHbMax = "";
        }
        // return compact("consumptions", "calModeHeadBalance", "calModeHbMax");
        $proInfoStudy = $this->reportserv->getProInfoStudy($study->ID_STUDY);
        $proSections = [];
        $pro2Dchart = [];
        
        foreach ($study->studyEquipments as $key=> $idstudyequips) {
            if ($idstudyequips->BRAIN_TYPE == 4) {
                $heatexchange[] = $this->reportserv->heatExchange($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS);
                $timeBase[] = $this->reportserv->timeBased($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS);
                if ($shapeCode == 1) { 
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                } else if ($shapeCode == 2) {
                    if ($equipData[$key]['ORIENTATION'] == 1) {
                        $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                        $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3);
                    } else {
                        $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                        $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                    }
                    $pro2Dchart[] = $this->reportserv->productchart2D($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                } else if (($shapeCode == 4) && ($shapeCode == 7) && ($shapeCode == 8) && ($shapeCode == 5)) {
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                } else if ($shapeCode == 6) {
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                } else if ($shapeCode == 9) {
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3);
                    $pro2Dchart[] = $this->reportserv->productchart2D($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                } else if ($shapeCode == 3) {
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                    $proSections[] = $this->reportserv->productSection($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                    $pro2Dchart[] = $this->reportserv->productchart2D($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3);
                }
            } else {
                $proSections = [];
                $heatexchange = [];
                $timeBase = [];
            }
        }

        $productComps = [];
        foreach ($componentName as $key => $value) {
            $componentStatus = Translation::select('LABEL')->where('TRANS_TYPE', 100)->whereIn('ID_TRANSLATION', $comprelease)
            ->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->orderBy('LABEL', 'ASC')->first();
            $productComps[] = $value;
            $productComps[$key]['display_name'] = $value->LABEL . ' - ' . $productElmt->component->COMP_VERSION . '(' . $componentStatus->LABEL . ' )';
        }
        $myfile = fopen( $public_path. "/reports/" . "/" . $study->USERNAM."/" . $name_report, "w") or die("Unable to open file!");
        $html = $this->viewHtml($study ,$production, $product, $proElmt, $shapeName, 
        $productComps, $equipData, $cryogenPipeline, $consumptions, $proInfoStudy,
        $calModeHbMax, $calModeHeadBalance, $heatexchange, $proSections, $timeBase, 
        $symbol, $host, $pro2Dchart);
        fwrite($myfile, $html);
        fclose($myfile);
        $url = ["url" => "$host/reports/$study->USERNAM/$name_report"];
        return $url;
    }

    public function viewHtml($study ,$production, $product, $proElmt, $shapeName, 
    $productComps, $equipData, $cryogenPipeline, $consumptions, $proInfoStudy,
    $calModeHbMax, $calModeHeadBalance, $heatexchange, $proSections, $timeBase , 
    $symbol, $host, $pro2Dchart)
    {
        $arrayParam = [
            'study' => $study,
            'production' => $production,
            'product' => $product,
            'proElmt' => $proElmt,
            'shapeName' => $shapeName,
            'proInfoStudy' => $proInfoStudy,
            'symbol' => $symbol,
            'host' => $host,
        ];
        $param = [
            'arrayParam' => $arrayParam,
            'productComps' => $productComps,
            'equipData' => $equipData,
            'cryogenPipeline' => $cryogenPipeline,
            'consumptions' => $consumptions,
            'calModeHeadBalance' => $calModeHeadBalance,
            'calModeHbMax' => $calModeHbMax,
            'heatexchange' => $heatexchange,
            'proSections' => $proSections,
            'timeBase' => $timeBase,
            'pro2Dchart' => $pro2Dchart,
        ];
        return view('report.viewHtmlToPDF', $param);
    }

    function processingReport($id) {
        $study = Study::find($id);
        $public_path = rtrim(app()->basePath("public/"), '/');
        $progressFile = "$study->ID_STUDY- $study->STUDY_NAME-Report.progess";
        $file = file_get_contents($public_path. "/reports/" . $study->USERNAM. "/" .$progressFile);
        $array = explode("\n",$file);
        return $array;
    }




















    // HAIDT
    public function postFile() 
    {  
        $input = $this->request->all();
        $file = $input['fileKey'];

        $media = MediaUploader::fromSource($file)
        ->useFilename(rand(1,100000).'029'.rand(1,100000))
        ->onDuplicateIncrement()
        ->setMaximumSize(9999999)
        ->setStrictTypeChecking(true)
        ->setAllowUnrecognizedTypes(true)
        ->setAllowedAggregateTypes(['image'])
        ->upload();


        $url = 'http://'.$_SERVER['HTTP_HOST'].'/uploads/'.$media->filename.'.'.$media->extension;
        
        return $url;
    }
    // end HAIDT
}