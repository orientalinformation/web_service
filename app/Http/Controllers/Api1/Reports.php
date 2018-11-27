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
use App\Models\DimaResults;
use App\Cryosoft\EquipmentsService;
use App\Cryosoft\StudyEquipmentService;
use App\Cryosoft\MinMaxService;
use App\Cryosoft\StudyService;
use App\Cryosoft\OutputService;
use PDF;
use View;

// HAIDT
use MediaUploader;
use Symfony\Component\HttpFoundation\UploadedFile;
use Symfony\Component\HttpFoundation\File;
use Psr\Http\Message\StreamInterface;
use  Illuminate\Database\Query\Builder;
use App\Cryosoft\UnitsService;
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
    protected $values;

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
     * @var \App\CryoSoft\MinMaxService
     */
    protected $minmax;
    /**
     * @var \App\CryoSoft\StudyService
     */
    protected $study;
    /**
     * @var App\Cryosoft\UnitsService
     */
    protected $units;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth, UnitsConverterService $convert, 
        ValueListService $values, StudyEquipmentService $stdeqp, Lines $pipelines, 
        ReportService $reportserv, MinMaxService $minmax, StudyService $study, 
        UnitsService $units, OutputService $output, EquipmentsService $equip)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->convert = $convert;
        $this->values = $values;
        $this->stdeqp = $stdeqp;
        $this->pipelines = $pipelines;
        $this->reportserv = $reportserv;
        $this->minmax = $minmax;
        $this->study = $study;
        $this->units = $units;
        $this->output = $output;
        $this->equip = $equip;
        $this->reportFolder = $this->output->public_path('report');
        $this->plotFolder = $this->output->base_path('scripts');
    }

    public function writeProgressFile($fileName, $content)
    {
        $f = fopen($fileName, "w");
        fwrite($f, $content);
        fflush($f);
        fclose($f);
    }

    public function getReport($id)
    {
        $study = Study::where('ID_STUDY', $id)->first();
        $studyEquipments = $study->studyEquipments;
        if ($studyEquipments != null) {

            $countSteqp = 0;
            foreach ($studyEquipments as $studyEquipment) {
                $dimaResult = DimaResults::where("ID_STUDY_EQUIPMENTS", $studyEquipment->ID_STUDY_EQUIPMENTS)->where("DIMA_TYPE", 1)->first();
                if ($studyEquipment->BRAIN_TYPE == 4 && $dimaResult) {
                    $countSteqp++;
                }
            }

            if ($study->CALCULATION_MODE == 1 || $countSteqp == 0) {
                return response("Report is available only when equipments are calculated numerically", 406);
            }

            $report = Report::where('ID_STUDY', $id)->first();
    
            if ($report) {    
                $report->isSizingValuesChosen = ($report->SIZING_VALUES & 1);
                $report->isSizingValuesMax = ($report->SIZING_VALUES & 16);
                $studyEquip = StudyEquipment::where('ID_STUDY', $id)->where('BRAIN_TYPE', '=', 4)->get();
    
                if (count($studyEquip) > 0) {
                    $report->isThereCompleteNumericalResults = true;
                } else {
                    $report->isThereCompleteNumericalResults = false;
                }
    
                $productElmt = ProductElmt::where('ID_STUDY', $id)->first();
                $report->productElmt = $productElmt;
                $report->temperatureSymbol = $this->convert->temperatureSymbolUser();

                if (count($studyEquip) > 0) {
                    $tempDataReport = $this->reportserv->initTempDataForReportData($studyEquip[0]->ID_STUDY_EQUIPMENTS);
                    $report->refContRep2DTempMinRef = $tempDataReport[0];
                    $report->refContRep2DTempMaxRef = $tempDataReport[1];
                    $report->refContRep2DTempStepRef = $tempDataReport[2];
        
                    if ($report->CONTOUR2D_TEMP_MIN == $report->CONTOUR2D_TEMP_MAX ) {
                        $report->CONTOUR2D_TEMP_MIN = $tempDataReport[0];
                        $report->CONTOUR2D_TEMP_MAX = $tempDataReport[1];
                        $report->CONTOUR2D_TEMP_STEP = $report->refContRep2DTempStepRef;
                    } else {
                        $tempDataReport = $this->reportserv->initTempDataForReportDataParam($studyEquip[0]->ID_STUDY_EQUIPMENTS, $report->CONTOUR2D_TEMP_MIN , $report->CONTOUR2D_TEMP_MAX, $report->CONTOUR2D_TEMP_STEP);
                        if (($report->CONTOUR2D_TEMP_STEP != $tempDataReport[2]) || ($report->CONTOUR2D_TEMP_MIN != $tempDataReport[0]) || ($report->CONTOUR2D_TEMP_MAX != $tempDataReport[2])) {
                            $report->CONTOUR2D_TEMP_MIN = $tempDataReport[0];
                            $report->CONTOUR2D_TEMP_MAX = $tempDataReport[1];
                            $report->CONTOUR2D_TEMP_STEP = $tempDataReport[2];
                        }
                    }
                }

                $selPoints = $this->output->getSelectedMeshPoints($id);
                if (empty($selPoints)) {
                    $selPoints = $this->output->getMeshSelectionDef();
                }

                if (!empty($selPoints) && count($selPoints) == 18) {
                    if ($report->POINT1_X == 0) $report->POINT1_X = $selPoints[0];
                    if ($report->POINT1_Y == 0) $report->POINT1_Y = $selPoints[1];
                    if ($report->POINT1_Z == 0) $report->POINT1_Z = $selPoints[2];
                    if ($report->POINT2_X == 0) $report->POINT2_X = $selPoints[3];
                    if ($report->POINT2_Y == 0) $report->POINT2_Y = $selPoints[4];
                    if ($report->POINT2_Z == 0) $report->POINT2_Z = $selPoints[5];
                    if ($report->POINT3_X == 0) $report->POINT3_X = $selPoints[6];
                    if ($report->POINT3_Y == 0) $report->POINT3_Y = $selPoints[7];
                    if ($report->POINT3_Z == 0) $report->POINT3_Z = $selPoints[8];
                    if ($report->AXE3_Y == 0) $report->AXE3_Y = $selPoints[9];
                    if ($report->AXE3_Z == 0) $report->AXE3_Z = $selPoints[10];
                    if ($report->AXE2_X == 0) $report->AXE2_X = $selPoints[11];
                    if ($report->AXE2_Z == 0) $report->AXE2_Z = $selPoints[12];
                    if ($report->AXE1_X == 0) $report->AXE1_X = $selPoints[13];
                    if ($report->AXE1_Y == 0) $report->AXE1_Y = $selPoints[14];
                    if ($report->PLAN_X == 0) $report->PLAN_X = $selPoints[15];
                    if ($report->PLAN_Y == 0) $report->PLAN_Y = $selPoints[16];
                    if ($report->PLAN_Z == 0) $report->PLAN_Z = $selPoints[17];
                }
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

                $report->refContRep2DTempMinRef = 0;
                $report->refContRep2DTempMaxRef = 0;
    
                $report->refContRep2DTempMinRef = 0;
                $report->refContRep2DTempMaxRef = 0;
                $report->refContRep2DTempStepRef = 0;
    
            }
            $report->ip = getenv('APP_URL');
            return $report;
        } else {
            return response("Report is available only when equipments are calculated numerically", 406);
        }
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
        $meshPositions = MeshPosition::distinct()->select('MESH_POSITION.MESH_AXIS_POS')
        ->join('PRODUCT_ELMT', 'MESH_POSITION.ID_PRODUCT_ELMT', '=', 'PRODUCT_ELMT.ID_PRODUCT_ELMT')
        ->join('PRODUCT', 'PRODUCT_ELMT.ID_PROD' , '=', 'PRODUCT.ID_PROD')
        ->where('PRODUCT.ID_STUDY', $id)
        ->where('MESH_AXIS', $axe)
        ->orderBy('MESH_AXIS_POS', 'ASC')->get();

        return $meshPositions;
    }

    public function getMeshAxisPos($id) 
    {
        $list1 = $this->initListPoints($id, 1);
        $list2 = $this->initListPoints($id, 2);
        $list3 = $this->initListPoints($id, 3);
        
        foreach ($list1 as $key) {
            $key->meshAxisPosValue = $this->convert->meshesUnit($key->MESH_AXIS_POS);
        }

        foreach ($list2 as $key) {
            $key->meshAxisPosValue = $this->convert->meshesUnit($key->MESH_AXIS_POS);
        }
        
        foreach ($list3 as $key) {
            $key->meshAxisPosValue = $this->convert->meshesUnit($key->MESH_AXIS_POS);
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

        $DEST_NAME = $input['DEST_NAME'];
        
        $DEST_SURNAME = $input['DEST_SURNAME'];

        $DEST_FUNCTION = $input['DEST_FUNCTION'];

        $DEST_COORD = $input['DEST_COORD'];

        $WRITER_NAME = $input['WRITER_NAME'];
        
        $WRITER_SURNAME = $input['WRITER_SURNAME'];

        $WRITER_FUNCTION = $input['WRITER_FUNCTION'];

        $WRITER_COORD = $input['WRITER_COORD'];

        $CUSTOMER_LOGO = $input['CUSTOMER_LOGO'];

        $PHOTO_PATH = $input['PHOTO_PATH'];

        $REPORT_COMMENT = $input['REPORT_COMMENT'];

        $PROD_LIST = $input['PROD_LIST'];

        $PROD_3D = $input['PROD_3D'];

        $EQUIP_LIST = $input['EQUIP_LIST'];

        $REP_CUSTOMER = $input['REP_CUSTOMER'];

        $PACKING = $input['PACKING'];

        $PIPELINE = $input['PIPELINE'];

        $ASSES_CONSUMP = $input['ASSES_CONSUMP'];

        $CONS_SPECIFIC = $input['CONS_SPECIFIC'];

        $CONS_OVERALL = $input['CONS_OVERALL'];

        $CONS_TOTAL = $input['CONS_TOTAL'];

        $CONS_HOUR = $input['CONS_HOUR'];

        $CONS_DAY = $input['CONS_DAY'];

        $CONS_WEEK = $input['CONS_WEEK'];

        $CONS_MONTH = $input['CONS_MONTH'];

        $CONS_YEAR = $input['CONS_YEAR'];

        $CONS_EQUIP = $input['CONS_EQUIP'];

        $CONS_PIPE = $input['CONS_PIPE'];

        $CONS_TANK = $input['CONS_TANK'];

        $REP_CONS_PIE = $input['REP_CONS_PIE'];

        $SIZING_VALUES = $input['SIZING_VALUES'];

        $SIZING_GRAPHE = $input['SIZING_GRAPHE'];

        $SIZING_TR = $input['SIZING_TR'];

        $ENTHALPY_G = $input['ENTHALPY_G'];

        $ENTHALPY_V = $input['ENTHALPY_V'];

        $ENTHALPY_SAMPLE = $input['ENTHALPY_SAMPLE'];

        $ISOCHRONE_G = $input['ISOCHRONE_G'];

        $ISOCHRONE_V = $input['ISOCHRONE_V'];

        $ISOCHRONE_SAMPLE = $input['ISOCHRONE_SAMPLE'];

        $ISOVALUE_G = $input['ISOVALUE_G'];

        $ISOVALUE_V = $input['ISOVALUE_V'];

        $ISOVALUE_SAMPLE = $input['ISOVALUE_SAMPLE'];

        $CONTOUR2D_G = $input['CONTOUR2D_G'];

        $CONTOUR2D_TEMP_STEP = $input['CONTOUR2D_TEMP_STEP'];

        $CONTOUR2D_TEMP_MIN = $input['CONTOUR2D_TEMP_MIN'];

        $CONTOUR2D_TEMP_MAX = $input['CONTOUR2D_TEMP_MAX'];

        $POINT1_X = $input['POINT1_X'];

        $POINT1_Y = $input['POINT1_Y'];

        $POINT1_Z = $input['POINT1_Z'];

        $POINT2_X = $input['POINT2_X'];

        $POINT2_Y = $input['POINT2_Y'];

        $POINT2_Z = $input['POINT2_Z'];

        $POINT3_X = $input['POINT3_X'];

        $POINT3_Y = $input['POINT3_Y'];

        $POINT3_Z = $input['POINT3_Z'];

        $AXE1_X = $input['AXE1_X'];

        $AXE1_Y = $input['AXE1_Y'];

        $AXE2_X = $input['AXE2_X'];

        $AXE2_Z = $input['AXE2_Z'];

        $AXE3_Y = $input['AXE3_Y'];

        $AXE3_Z = $input['AXE3_Z'];

        $PLAN_X = $input['PLAN_X'];

        $PLAN_Y = $input['PLAN_Y'];

        $PLAN_Z = $input['PLAN_Z'];

        $ID_STUDY = $input['ID_STUDY'];

        $ASSES_ECO = $input['ASSES_ECO'];

        $isSizingValuesChosen = $input['isSizingValuesChosen'];
        $isSizingValuesMax = $input['isSizingValuesMax'];

        $sizingValues = 0;
        if ($isSizingValuesChosen == true) {
            $sizingValues = $sizingValues | 0x1;
        }

        if ($isSizingValuesMax == true) {
            $sizingValues = $sizingValues | 0x10;
        }

        $mmNbSample1 = $this->minmax->checkMinMaxValue($ENTHALPY_SAMPLE, $this->values->MINMAX_REPORT_NBSAMPLE); 
        $mmNbSample2 = $this->minmax->checkMinMaxValue($ISOCHRONE_SAMPLE, $this->values->MINMAX_REPORT_NBSAMPLE); 
        $mmNbSample3 = $this->minmax->checkMinMaxValue($ISOVALUE_SAMPLE, $this->values->MINMAX_REPORT_NBSAMPLE); 
        $mmTempStep1 = $this->minmax->checkMinMaxValue($CONTOUR2D_TEMP_STEP, $this->values->MINMAX_REPORT_TEMP_STEP); 
        $mmTempMin = $this->minmax->checkMinMaxValue($CONTOUR2D_TEMP_MIN, $this->values->MINMAX_REPORT_TEMP_BOUNDS); 
        $mmTempMax = $this->minmax->checkMinMaxValue($CONTOUR2D_TEMP_MAX, $this->values->MINMAX_REPORT_TEMP_BOUNDS); 
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
        $report->SIZING_VALUES = $sizingValues;
        $report->SIZING_GRAPHE = $SIZING_GRAPHE;
        $report->SIZING_TR = $SIZING_TR;
        $report->ENTHALPY_G = $ENTHALPY_G;
        $report->ENTHALPY_V = $ENTHALPY_V;

        if ($mmNbSample1) {
            $report->ENTHALPY_SAMPLE = $ENTHALPY_SAMPLE;
        } else {
            $mm = $this->minmax->getMinMaxNoneLine($this->values->MINMAX_REPORT_NBSAMPLE);
            return response("Value out of range in Number of samples (" . $mm->LIMIT_MIN . " : " . $mm->LIMIT_MAX . ") !" , 406); // Status code here
        }
        $report->ISOCHRONE_G = $ISOCHRONE_G;
        $report->ISOCHRONE_V = $ISOCHRONE_V;

        if ($mmNbSample2) {
            $report->ISOCHRONE_SAMPLE = $ISOCHRONE_SAMPLE;
        } else {
            $mm = $this->minmax->getMinMaxNoneLine($this->values->MINMAX_REPORT_NBSAMPLE);
            return response("Value out of range in Number of samples (" . $mm->LIMIT_MIN . " : " . $mm->LIMIT_MAX . ") !" , 406); // Status code here
        }
        $report->ISOVALUE_G = $ISOVALUE_G;
        $report->ISOVALUE_V = $ISOVALUE_V;

        if ($mmNbSample3) {
            $report->ISOVALUE_SAMPLE = $ISOVALUE_SAMPLE;
        } else {
            $mm = $this->minmax->getMinMaxNoneLine($this->values->MINMAX_REPORT_NBSAMPLE);
            return response("Value out of range in Number of samples (" . $mm->LIMIT_MIN . " : " . $mm->LIMIT_MAX . ") !" , 406); // Status code here
        }
        $report->CONTOUR2D_G = $CONTOUR2D_G;
        
        if ($mmTempStep1) {
            $report->CONTOUR2D_TEMP_STEP = $CONTOUR2D_TEMP_STEP;
        } else {
            $mm = $this->minmax->getMinMaxNoneLine($this->values->MINMAX_REPORT_TEMP_STEP);
            return response("Value out of range in Number of samples (" . $mm->LIMIT_MIN . " : " . $mm->LIMIT_MAX . ") !" , 406); // Status code here
        }

        if ($mmTempMin) {
            $report->CONTOUR2D_TEMP_MIN = $CONTOUR2D_TEMP_MIN;
        } else {
            $mm = $this->minmax->getMinMaxNoneLine($this->values->MINMAX_REPORT_TEMP_BOUNDS);
            return response("Value out of range in Number of samples (" . $mm->LIMIT_MIN . " : " . $mm->LIMIT_MAX . ") !" , 406); // Status code here
        }

        if ($mmTempMax) {
            $report->CONTOUR2D_TEMP_MAX = $CONTOUR2D_TEMP_MAX;
        } else {
            $mm = $this->minmax->getMinMaxNoneLine($this->values->MINMAX_REPORT_TEMP_BOUNDS);
            return response("Value out of range in Number of samples (" . $mm->LIMIT_MIN . " : " . $mm->LIMIT_MAX . ") !" , 406); // Status code here
        }

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
        if ($this->study->isMyStudy($id)) {
            $report->update();
        }

        return 1;
    }

    function backgroundGenerationPDF($params)
    {
        set_time_limit(600);

        $id = $params['studyId'];
        $input = $params['input'];
        $DEST_SURNAME = $input['DEST_SURNAME'];
        $DEST_NAME = $input['DEST_NAME'];
        $DEST_FUNCTION = $input['DEST_FUNCTION'];
        $DEST_COORD = $input['DEST_COORD'];
        $PHOTO_PATH = $input['PHOTO_PATH'];
        $CUSTOMER_LOGO = $input['CUSTOMER_LOGO'];
        $REPORT_COMMENT = $input['REPORT_COMMENT'];
        $WRITER_SURNAME = $input['WRITER_SURNAME'];
        $WRITER_NAME = $input['WRITER_NAME'];
        $WRITER_FUNCTION = $input['WRITER_FUNCTION'];
        $WRITER_COORD = $input['WRITER_COORD'];
        $PROD_LIST = $input['PROD_LIST'];
        $PROD_3D = $input['PROD_3D'];
        $EQUIP_LIST = $input['EQUIP_LIST'];
        $REP_CUSTOMER = $input['REP_CUSTOMER'];
        $PACKING = $input['PACKING'];
        $ASSES_ECO = $input['ASSES_ECO'];
        $PIPELINE = $input['PIPELINE'];
        $CONS_OVERALL = $input['CONS_OVERALL'];
        $CONS_TOTAL = $input['CONS_TOTAL'];
        $CONS_SPECIFIC = $input['CONS_SPECIFIC'];
        $CONS_HOUR = $input['CONS_HOUR'];
        $CONS_DAY = $input['CONS_DAY'];
        $CONS_WEEK = $input['CONS_WEEK'];
        $CONS_MONTH = $input['CONS_MONTH'];
        $CONS_YEAR = $input['CONS_YEAR'];
        $CONS_EQUIP = $input['CONS_EQUIP'];
        $CONS_PIPE = $input['CONS_PIPE'];
        $CONS_TANK = $input['CONS_TANK'];
        $REP_CONS_PIE = $input['REP_CONS_PIE'];
        $isSizingValuesChosen = $input['isSizingValuesChosen'];
        $isSizingValuesMax = $input['isSizingValuesMax'];
        $SIZING_GRAPHE = $input['SIZING_GRAPHE'];
        $ENTHALPY_V = $input['ENTHALPY_V'];
        $ENTHALPY_G = $input['ENTHALPY_G'];
        $ENTHALPY_SAMPLE = $input['ENTHALPY_SAMPLE'];
        $ISOCHRONE_V = $input['ISOCHRONE_V'];
        $ISOCHRONE_G = $input['ISOCHRONE_G'];
        $ISOCHRONE_SAMPLE = $input['ISOCHRONE_SAMPLE'];
        $ISOVALUE_V = $input['ISOVALUE_V'];
        $ISOVALUE_G = $input['ISOVALUE_G'];
        $ISOVALUE_SAMPLE = $input['ISOVALUE_SAMPLE'];
        $CONTOUR2D_G = $input['CONTOUR2D_G'];
        $CONTOUR2D_TEMP_MIN = $input['CONTOUR2D_TEMP_MIN'];
        $CONTOUR2D_TEMP_MAX = $input['CONTOUR2D_TEMP_MAX'];
        $CONTOUR2D_TEMP_STEP = $input['CONTOUR2D_TEMP_STEP'];
        $axeTempRecordData = [
            [-1.0, $input['AXE3_Y'], $input['AXE3_Z']],
            [$input['AXE2_X'], -1.0, $input['AXE2_Z']],
            [$input['AXE1_X'], $input['AXE1_Y'], -1.0]
        ];

        $axisTemp = [
            'POINT1_X' => $input['POINT1_X'],
            'POINT1_Y' => $input['POINT1_Y'],
            'POINT1_Z' => $input['POINT1_Z'],
            'POINT2_X' => $input['POINT2_X'],
            'POINT2_Y' => $input['POINT2_Y'],
            'POINT2_Z' => $input['POINT2_Z'],
            'POINT3_X' => $input['POINT3_X'],
            'POINT3_Y' => $input['POINT3_Y'],
            'POINT3_Z' => $input['POINT3_Z'],
        ];

        $study = Study::find($id);
        $checkStuname = str_replace(' ', '', $study->STUDY_NAME);
        $host = getenv('APP_URL');

        $public_path = rtrim(app()->basePath("public"), '/');
        $progressFile = $public_path. "/reports/" . $study->USERNAM. "/" . "$study->ID_STUDY-" . preg_replace('/[^A-Za-z0-9\-]/', '', $checkStuname) . "-Report.progess";
        $name_report = "$study->ID_STUDY-" . preg_replace('/[^A-Za-z0-9\-]/', '', $checkStuname) . "-Report.pdf";

        if (!is_dir($public_path . "/reports/" . $study->USERNAM)) {
            mkdir($public_path . "/reports/" . $study->USERNAM, 0777, true);
        } 

        if (file_exists($public_path . "/reports/" . $study->USERNAM."/" . $name_report)) {
            @unlink($public_path . "/reports/" . $study->USERNAM."/" . $name_report);
        }

        $progress = "";
        $production = Production::Where('ID_STUDY', $id)->first();
        if ($REP_CUSTOMER == 1) {
            $progress .= "Production";
            // $progress = "\n$study";
            $this->writeProgressFile($progressFile, $progress);
        }
        
        $product = Product::Where('ID_STUDY', $id)->first();
        $meshView = $this->reportserv->getMeshView($product);
        $products = ProductElmt::where('ID_PROD', $product->ID_PROD)->orderBy('SHAPE_POS2', 'DESC')->get();

        $specificDimension = 0.0;
        $count = count($products);
        foreach ($products as $key => $pr) {
            $elements[] = $pr;

            if ($pr->ID_SHAPE == $this->values->SPHERE || $pr->ID_SHAPE == $this->values->CYLINDER_CONCENTRIC_STANDING || $pr->ID_SHAPE == $this->values->CYLINDER_CONCENTRIC_LAYING || $pr->ID_SHAPE == $this->values->PARALLELEPIPED_BREADED) {
                if ($key < $count - 1) {
                    $specificDimension += $pr->SHAPE_PARAM2 * 2;
                } else {
                    $specificDimension += $pr->SHAPE_PARAM2;
                }
            } else {
                $specificDimension += $pr->SHAPE_PARAM2;
            }
        }

        $specificDimension = $this->convert->prodDimension($specificDimension);
        $proElmt = ProductElmt::Where('ID_PROD', $product->ID_PROD)->first();
        
        foreach ($study->studyEquipments as $sequip) {
            $layout = $this->stdeqp->generateLayoutPreview($sequip);
        }
        $nameLayout = $study->ID_STUDY.'-'.preg_replace('/[^A-Za-z0-9\-]/', '', $checkStuname).'-StdeqpLayout-';
        $idComArr = [];
        $idElmArr = [];
        $comprelease = [];

        $packings = [];
        $cryogenPipeline = [];
        $calModeHeadBalance = [];
        $calModeHbMax = [];
        $graphicSizing = [];

        $shapeCode = $proElmt->SHAPECODE;
        foreach ($product->productElmts as $productElmt) {
            $idComArr[] = $productElmt->ID_COMP;
            $idElmArr[] = $productElmt->ID_PRODUCT_ELMT;
            $comprelease[] = $productElmt->component->COMP_RELEASE;
        }

        if ($study->packings != null) {
            $packings = $this->reportserv->getStudyPackingLayers($study->ID_STUDY);
        }
        
        if ($shapeCode <= 9) {
            $shapeName = Translation::where('TRANS_TYPE', 4)->where('ID_TRANSLATION', $shapeCode)->where('CODE_LANGUE', $study->user->CODE_LANGUE)->orderBy('LABEL', 'ASC')->first();
        } else {
            $shapeName = $this->reportserv->getShapeName3D($shapeCode);
        }

        $componentName = ProductElmt::select('LABEL','ID_COMP', 'ID_PRODUCT_ELMT', 'PROD_ELMT_ISO', 'PROD_ELMT_NAME', 'PROD_ELMT_REALWEIGHT', 'SHAPE_PARAM2', 'INSERT_LINE_ORDER')
        ->join('TRANSLATION', 'ID_COMP', '=', 'TRANSLATION.ID_TRANSLATION')->whereIn('ID_PRODUCT_ELMT', $idElmArr)
        ->where('TRANS_TYPE', 1)->whereIn('ID_TRANSLATION', $idComArr)
        ->where('CODE_LANGUE', $study->user->CODE_LANGUE)->orderBy('SHAPE_POS2', 'DESC')->get();
        
        $listParentChaining = $this->reportserv->getParentIdChaining($study->PARENT_ID);

        $arrIdComp = [];
        if (!empty($listParentChaining)) {
            foreach ($listParentChaining as $key => $prChainIdStudy) {
                $arrIdComp[$prChainIdStudy] = [];
                $productPrChain = Product::Where('ID_STUDY', $prChainIdStudy)->first();
                if ($productPrChain) {
                    foreach ($productPrChain->productElmts as $productElmt) {
                        $arrIdComp[$prChainIdStudy][] = $productElmt->ID_COMP;
                    }
                }
            }
        }
        
        $productComps = [];
        foreach ($componentName as $key => $value) {
            $componentStatus = Translation::select('LABEL')->where('TRANS_TYPE', 100)->whereIn('ID_TRANSLATION', $comprelease)->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->orderBy('LABEL', 'ASC')->first();
            $productComps[] = $value;
            $productComps[$key]['display_name'] = $value->LABEL . ' - ' . $productElmt->component->COMP_VERSION . '(' . $componentStatus->LABEL . ' )';
            $productComps[$key]['mass'] = $this->convert->mass($value->PROD_ELMT_REALWEIGHT);
            $productComps[$key]['dim'] = $this->convert->prodDimension($value->SHAPE_PARAM2);
            $studyNumber = [];
            if (!empty($arrIdComp)) {
                foreach ($arrIdComp as $k => $arrId) {
                    if (in_array($value->ID_COMP, $arrId)) {
                        $studyNumber[] = $k;
                    }
                }
            }

            $productComps[$key]['studyNumber'] = count($arrIdComp) - count($studyNumber) + 1;
        }

        if ($PROD_LIST == 1) {
            $progress .= "\nProduct";
            $this->writeProgressFile($progressFile, $progress);
        }
        
        $equipData = $this->stdeqp->findStudyEquipmentsByStudy($study);

        if ($EQUIP_LIST == 1) {
            $progress .= "\nEquipment";
            $this->writeProgressFile($progressFile, $progress);
        }
        
        $symbol = $this->reportserv->getSymbol($study->ID_STUDY);
        $infoReport = $study->reports;
        $photoPath = $infoReport[0]->PHOTO_PATH;
        $photoNameUrl = '';
        if (!empty($photoPath)) {
            $photoPathInfo = pathinfo($photoPath);
            $baseNamePh = $photoPathInfo['basename'];
            $photoNameUrl = $public_path . '/uploads/' . $baseNamePh;
        }

        if ($PIPELINE == 1) {
            if ($study->OPTION_CRYOPIPELINE == 1) {
                $cryogenPipeline = $this->pipelines->loadPipeline($study->ID_STUDY, ['report' => true]);
                $progress .= "\nPipeline Elements";
                $this->writeProgressFile($progressFile, $progress);
            }
        }
        
        $consumptions = $this->reportserv->getAnalyticalConsumption($study->ID_STUDY);
        $economic = $this->reportserv->getAnalyticalEconomic($study->ID_STUDY);
        if ($CONS_OVERALL == 1 || $CONS_TOTAL ==1 || $CONS_SPECIFIC  == 1 || $CONS_HOUR ==1 || $CONS_DAY == 1||
            $CONS_WEEK == 1 || $CONS_MONTH == 1 || $CONS_YEAR ==1 || $CONS_EQUIP ==1 || $CONS_PIPE == 1 || $CONS_TANK ==1) {
            $progress .= "\nConsumption Results";
            $this->writeProgressFile($progressFile, $progress);
        }
        
        if ($isSizingValuesChosen == 1 || $isSizingValuesMax == 16 || $SIZING_GRAPHE == 1) {
            if ($study->CALCULATION_MODE == 3) {
                $calModeHeadBalance = $this->reportserv->getOptimumHeadBalance($study->ID_STUDY);
                $calModeHbMax = $this->reportserv->getOptimumHeadBalanceMax($study->ID_STUDY);
                $graphicSizing = $this->reportserv->sizingOptimumResult($study->ID_STUDY);
                
            } else if ($study->CALCULATION_MODE == 1) {
                $calModeHeadBalance = $this->reportserv->getEstimationHeadBalance($study->ID_STUDY, 1);
                $graphicSizing = $this->reportserv->sizingEstimationResult($study->ID_STUDY);
            }
            $progress .= "\nSizing";
            $this->writeProgressFile($progressFile, $progress);
        }

        if ($REP_CONS_PIE == 1) {
            if (!empty($consumptions)) {
                foreach ($consumptions as $consumption) {
                    $idStudyEquipment = $consumption['id'];

                    $percentProduct = $consumption['percentProduct'];
                    $percentEquipmentPerm = $consumption['percentEquipmentPerm'];
                    $percentEquipmentDown = $consumption['percentEquipmentDown'];
                    $percentLine = $consumption['percentLine'];
                    if ($percentProduct != 0 && $percentEquipmentPerm != 0  && $percentEquipmentDown != 0) {
                        $percentProductLabel = 'Product';
                        $percentEquipmentPermLabel = 'Equipment(permanent)';
                        $percentEquipmentDownLabel = 'Equipment(cool down)';
                        $percentLineLabel = 'Line';
                        $f = fopen("/tmp/consumptionPie.inp", "w");
                        fputs($f, 'name percent' . "\n");
                        fputs($f, '"'. $percentProductLabel .'" '. $percentProduct .'' . "\n");
                        fputs($f, '"'. $percentEquipmentPermLabel .'" '. $percentEquipmentPerm .'' . "\n");
                        fputs($f, '"'. $percentEquipmentDownLabel .'" '. $percentEquipmentDown .'' . "\n");
                        if ($percentLine > 0) {
                            fputs($f, '"'. $percentLineLabel .'" '. $percentLine .'' . "\n");
                        }
                        
                        fclose($f);

                        $folder = $this->output->public_path('consumption');

                        $userName = $study->USERNAM;
                        $idStudy = $study->ID_STUDY;
                        if (!is_dir($folder)) {
                            mkdir($folder, 0777);
                        }

                        if (!is_dir($folder . '/' . $userName)) {
                            mkdir($folder . '/' . $userName, 0777);
                        }

                        if (!is_dir($folder . '/' . $userName . '/' . $idStudy)) {
                            mkdir($folder . '/' . $userName . '/' . $idStudy, 0777);
                        }

                        $outPutFolder = $folder . '/' . $userName . '/' . $idStudy;
                        $outPutFileName = $idStudyEquipment;
                        
                        system('gnuplot -c '. $this->plotFolder .'/consumptions.plot "/tmp/consumptionPie.inp" "'. $outPutFolder . '" "'. $outPutFileName .'" ');
                    }
                }
            }

            $progress .= "\nConsumption pies";
            $this->writeProgressFile($progressFile, $progress);
        }
        
        $proInfoStudy = $this->reportserv->getProInfoStudy($study->ID_STUDY);
        $heatexchange = [];
        $proSections = [];
        $pro2Dchart = [];
        $timeBase = [];
        
        foreach ($study->studyEquipments as $key=> $idstudyequips) {
            if ($idstudyequips->BRAIN_TYPE == 4) {
                if ($ENTHALPY_V == 1 || $ENTHALPY_G == 1) {
                    $heatexchange[] = $this->reportserv->heatExchange($ENTHALPY_SAMPLE, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS);
                    $progress .= "\nEnthalpies";
                    $this->writeProgressFile($progressFile, $progress);
                } 

                if ($ISOVALUE_V == 1 || $ISOVALUE_G == 1) {
                    $timeBase[] = $this->reportserv->timeBased($ISOVALUE_SAMPLE, $axisTemp, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS);
                    $progress .= "\nTime Based";
                    $this->writeProgressFile($progressFile, $progress);
                }
                
                switch ($shapeCode) {
                    case 1:
                        if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                            $progress .= "\nProduct Section";
                            $this->writeProgressFile($progressFile, $progress);
                        }
                        break;

                    case 2:
                        if ($equipData[$key]['ORIENTATION'] == 1) {
                            if ($CONTOUR2D_G == 1) {
                                $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                                $progress .= "\nContour";
                                $this->writeProgressFile($progressFile, $progress);
                            } 

                            if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                                $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                                $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3);
                                $progress .= "\nProduct Section";
                                $this->writeProgressFile($progressFile, $progress);
                            }
                        } else {
                            if ($CONTOUR2D_G == 1) {
                                $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                                $progress .= "\nContour";
                                $this->writeProgressFile($progressFile, $progress);
                            }

                            if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                                $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                                $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                                $progress .= "\nProduct Section";
                                $this->writeProgressFile($progressFile, $progress);
                            }
                        }
                        break;

                    case 4:
                    case 5:
                    case 7:
                    case 8:
                        if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                            $progress .= "\nProduct Section";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        if ($CONTOUR2D_G == 1) {
                            $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                            $progress .= "\nContour";
                            $this->writeProgressFile($progressFile, $progress);
                        }
                        break;

                    case 6:
                        if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                            $progress .= "\nProduct Section";
                            $this->writeProgressFile($progressFile, $progress);
                        }
                        break;

                    case 9:
                        if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3);
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                            $progress .= "\nProduct Section";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        if ($equipData[$key]['ORIENTATION'] == 1) {
                            if ($CONTOUR2D_G == 1) {
                                $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                                $progress .= "\nContour";
                                $this->writeProgressFile($progressFile, $progress);
                            }
                        } else {
                            if ($CONTOUR2D_G == 1) {
                                $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                                $progress .= "\nContour";
                                $this->writeProgressFile($progressFile, $progress);
                            }
                        }
                        break;

                    case 3: 
                        if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                            $progress .= "\nProduct Section";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        if ($CONTOUR2D_G == 1) {
                            $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                            $progress .= "\nContour";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        break;

                    case CYLINDER_STANDING_3D:
                    case CYLINDER_LAYING_3D:
                    case CYLINDER_CONCENTRIC_STANDING_3D:
                    case CYLINDER_CONCENTRIC_LAYING_3D:
                    case OVAL_STANDING_3D:
                    case OVAL_LAYING_3D:
                        if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3);
                            $progress .= "\nProduct Section";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        if ($CONTOUR2D_G == 1) {
                            $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                            $progress .= "\nContour";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        break;

                    default:
                        if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3);
                            $progress .= "\nProduct Section";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        if ($CONTOUR2D_G == 1) {
                            $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                            $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                            $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                            $progress .= "\nContour";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        break;
                }
            } 
        }
        
        $progress .= "\nReport translation";
        $progress .= "\nFINISH";

        $customerPath = $infoReport[0]->CUSTOMER_LOGO;
        
        $customerNameUrl = '';
        if (!empty($customerPath)) {
            $customPathInfo = pathinfo($customerPath);
            $baseName = $customPathInfo['basename'];
            $customerNameUrl = $public_path . '/uploads/' . $baseName;
        }

        $chainingStudies = $this->reportserv->getChainingStudy($id);
        

        $this->writeProgressFile($progressFile, $progress);

        // set document information
        PDF::SetTitle('Cryosoft Report');
        
        PDF::SetFooterMargin(15);
        PDF::setHeaderMargin(5);
        
        // set default header data
        PDF::setPageOrientation('L', 'A4');
        // set margins
        PDF::SetMargins(10, 15, 10, true);
        PDF::setHeaderFont(Array('helvetica', '', 10));
        // set default monospaced font
        PDF::SetDefaultMonospacedFont('courier');
        // set auto page breaks
        PDF::SetAutoPageBreak(TRUE, 15);
        // set image scale factor
        PDF::setImageScale(1.25);
        PDF::setHeaderCallback(function($pdf) use($study, $host, $public_path, $customerNameUrl){
            // Set font
            $pdf->SetTextColor(173,173,173);
            $pdf->SetFont('helvetica', '', 10);
            // Title
            $pdf->Cell(0, 10, $study->STUDY_NAME.'-'. date("d/m/Y"), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            PDF::SetMargins(10, 20, 10, true);

            $pdf->Image($public_path.'/images/logo_cryosoft.png',90, 5, 40, '', 'PNG', '', 'T', false, 300, 'R', false, false, 0, false, false, false);

            if (!empty($customerNameUrl) && file_exists($customerNameUrl)) {
                $pdf->Image($customerNameUrl, 90, 5, 20, '', 'PNG', '', 'T', false, 300, 'L', false, false, 0, false, false, false);
            }

            $pdf->Image($public_path.'/images/logo_cryosoft.png', 90, 5, 40, '', 'PNG', '', 'T', false, 300, 'R', false, false, 0, false, false, false);
    
        });

        PDF::setFooterCallback(function($pdf) {
            $pdf->SetTextColor(173,173,173);
            // Position at 15 mm from bottom
            $pdf->SetY(-15);
            // Set font
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(0,10,'Air Liquide confidential information',0,0,'L');
            $pdf->SetX(11.5);
            $pdf->Cell(0,10,'Page '.$pdf->getAliasNumPage().'/'.$pdf->getAliasNbPages(),0,0,'C');
            $pdf->SetX(11.5);
            $pdf->Cell( 0, 10, 'Air Liquide solutions provider for the food industry ', 0, 0, 'R' ); 
        });
        
        PDF::AddPage();
        PDF::SetTextColor(0,0,0);
        PDF::Bookmark('CONTENT ', 0, 0, '', 'B', array(0,64,128));
        $html = '';
        $html .= '
            <div align="center">
                    <img style="max-width: 640px" src="'.$public_path.'/images/banner_cryosoft.png">
            </div>
                <table border="1" cellpadding="3">
                    <tr>
                        <th colspan="6">Customer</th>
                    </tr>
                    <tr>
                        <td colspan="4">Company name </td>
                        <td colspan="2"> '. $DEST_SURNAME .' </td>
                    </tr>
                    <tr>
                        <td colspan="4">Surname/Name</td>
                        <td colspan="2">'. $DEST_NAME .' </td>
                    </tr>
                    <tr>
                        <td colspan="4">Function</td>
                        <td colspan="2">'. $DEST_FUNCTION .' </td>
                    </tr>
                    <tr>
                        <td colspan="4">Contact</td>
                        <td colspan="2"> '. $DEST_COORD .' </td>
                    </tr>
                    <tr>
                        <td colspan="4">Date of the redivort generation</td>
                        <td colspan="2">'. date("d/m/Y") .' </td>
                    </tr>
                </table>
            <div style="text-align:center">
                <p>';
                if (!empty($photoNameUrl) && file_exists($photoNameUrl)) {
                    $html .= '<img src="'. $photoNameUrl.'" style="height:280px">';
                } else {
                    $html .= '<img src="'. $public_path.'/images/globe_food.gif">';
                }
                $html .= '</p>
            <table class ="table table-bordered" border="1" cellpadding="3" style="color:red">
                <tr>
                    <th align="center" colspan="3"><h3>Study of the product:</h3> <h4>'. $study['STUDY_NAME'] .'</h4></th>
                </tr>
                <tr>
                    <td >Calculation mode :</td>
                    <td align="center" colspan="2">'. ($study['CALCULATION_MODE'] == 3 ? 'Optimum equipment' : 'Estimation' ) .'</td>
                </tr>
                <tr>
                    <td >Economic :</td>
                    <td align="center" colspan="2">'.( $study['OPTION_ECO'] == 1 ? 'YES' : 'NO') .' </td>
                </tr>';
                $html .= '<tr>
                    <td >Cryogenic Pipeline :</td>
                    <td align="center" colspan="2">'.  ($study['OPTION_CRYOPIPELINE'] != null && !($study['OPTION_CRYOPIPELINE'] == 0) ? 'YES' : 'NO') .' </td>
                </tr>';
                if ($study['CHAINING_CONTROLS'] == 1) {
                    $html .= '<tr>
                        <td >Chaining :</td>
                        <td align="center">YES</td>
                        <td align="center">'.  (($study['HAS_CHILD'] != 0) && ($study['PARENT_ID'] != 0) ? 'This study is a child' : '') .' </td>
                    </tr>';
                }
            $html .= '</table>';
        PDF::writeHTML($html, true, false, true, false, '');
        PDF::AddPage();
        if (($study['CHAINING_CONTROLS'] == 1) && ($study['PARENT_ID'] != 0)) {
            if (!empty($chainingStudies)) {
                PDF::SetFont('helvetica', 'B', 16);
                PDF::Bookmark('CHAINING SYNTHESIS', 0, 0, '', 'B', array(0,64,128));
                PDF::SetFillColor(38, 142, 226);
                PDF::SetTextColor(0,0,0);
                $content ='Chaining synthesis';
                PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
                PDF::SetFont('helvetica', '', 10);
                $html = '<br><div class="chaining">
                        <table border="1" cellpadding="3">
                            <tr>
                                <th colspan="2" align="center">Study Name</th>
                                <th colspan="2" align="center">Equipment</th>
                                <th align="center">Control temperature<br>('. $symbol['temperatureSymbol'] .') </th>
                                <th align="center">Residence/ Dwell time<br>( '. $symbol['timeSymbol'] .' )</th>
                                <th align="center">Convection Setting (Hz)</th>
                                <th align="center">Initial Average Product tempeture<br>('. $symbol['temperatureSymbol'] .')  </th>
                                <th align="center">Final Average Product temperature<br>('. $symbol['temperatureSymbol'] .') </th>
                                <th align="center">Product Heat Load<br>('. $symbol['enthalpySymbol'] .') </th>
                            </tr>';
                            foreach ($chainingStudies as $key => $resoptHeads) { 
                            $html .= '<tr>
                                <td colspan="2" align="center"> '. $resoptHeads['stuName'] .' </td>
                                <td colspan="2" align="center"> '. $resoptHeads['equipName'] .'</td>
                                <td align="center"> '. $resoptHeads['tr'] .' </td>
                                <td align="center"> '. $resoptHeads['ts'] .' </td>
                                <td align="center"> '. $resoptHeads['vc'] .' </td>
                                <td align="center"> '. $resoptHeads['proInfoStudy']['avgTInitial'] .' </td>
                                <td align="center"> '. $resoptHeads['tfp'] .' </td>
                                <td align="center"> '. $resoptHeads['vep'] .' </td>
                            </tr>';
                            }
                        $html .= '</table></div>';
                PDF::writeHTML($html, true, false, true, false, '');
                PDF::AddPage();
            }
        }
        
        if ($REP_CUSTOMER == 1)  {
            if (!empty($production)) {
                PDF::SetFont('helvetica', 'B', 16);
                PDF::Bookmark('PRODUCTION DATA', 0, 0, '', 'B', array(0,64,128));
                PDF::SetFillColor(38, 142, 226);
                PDF::SetTextColor(0,0,0);
                $content ='Production Data';
                PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
                PDF::SetFont('helvetica', '', 10);
                $html = '';
                $html .= '<br><div class="production">
                        <table border="1" cellpadding="5">
                        <tr>
                            <td>Daily production</td>
                            <td align="center"> '. $production->DAILY_PROD .'</td>
                            <td>Hours/Day</td>
                        </tr>
                        <tr>
                            <td>Weekly production</td>
                            <td align="center"> '. $production->WEEKLY_PROD .'</td>
                            <td>Days/Week</td>
                        </tr>
                        <tr>
                            <td>Annual production</td>
                            <td align="center"> '. $production->NB_PROD_WEEK_PER_YEAR .'</td>
                            <td>Weeks/Year</td>
                        </tr>
                        <tr>
                            <td>Number of equipment cooldowns</td>
                            <td align="center"> '. $production->DAILY_STARTUP .'</td>
                            <td>per day</td>
                        </tr>
                        <tr>
                            <td>Factory Air temperature</td>
                            <td align="center"> '. $this->convert->prodTemperature($production->AMBIENT_TEMP) .'</td>
                            <td>( '. $symbol['temperatureSymbol'] . ' )</td>
                        </tr>
                        <tr>
                            <td>Relative Humidity of Factory Air</td>
                            <td align="center"> '. $production->AMBIENT_HUM .'</td>
                            <td>(%)</td>
                        </tr>
                        <tr>
                            <td>Required Average temperature</td>
                            <td align="center"> '. $this->convert->prodTemperature($production->AVG_T_DESIRED) .'</td>
                            <td>( '. $symbol['temperatureSymbol'] . ' )</td>
                        </tr>
                        <tr>
                            <td>Required Production Rate</td>
                            <td align="center"> '. $this->convert->productFlow($production->PROD_FLOW_RATE) .'</td>
                            <td>( '. $symbol['productFlowSymbol'] .' )</td>
                        </tr>
                        </table>
                </div>';
                PDF::writeHTML($html, true, false, true, false, '');
                PDF::AddPage();
            }
        }
        
        if ($PROD_LIST == 1) {
            $shapeNameDisplay = ($shapeCode <= 9) ? $shapeName->LABEL : $shapeName;
            PDF::SetFont('helvetica', 'B', 16);
            PDF::Bookmark('PRODUCT DATA', 0, 0, '', 'B', array(0,64,128));
            PDF::SetFillColor(38, 142, 226);
            PDF::SetTextColor(0,0,0);
            $content ='Product Data';
            PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
            PDF::SetFont('helvetica', '', 10);
            $html = '<br><h3>Composition of the product and its components</h3>
            <div class="pro-data">
                <table border="0.5" cellpadding="5">
                    <tr style="font-weight:bold">
                        <th align="center">Product name</th>
                        <th align="center">Shape</th>';
                        if ($shapeCode != SLAB && $shapeCode != SPHERE && $shapeCode != SPHERE_3D && $shapeCode != TRAPEZOID_3D && $shapeCode != TRAPEZOID_3D && $shapeCode != OVAL_STANDING_3D && $shapeCode != OVAL_LAYING_3D) {
                            if ($shapeCode == PARALLELEPIPED_STANDING || $shapeCode == PARALLELEPIPED_BREADED || $shapeCode == CYLINDER_CONCENTRIC_LAYING || $shapeCode == PARALLELEPIPED_STANDING_3D || $shapeCode == CYLINDER_CONCENTRIC_LAYING_3D || $shapeCode == PARALLELEPIPED_BREADED_3D){
                                $prodDim1Name = 'Length';
                            } 
                            if ($shapeCode == CYLINDER_LAYING || $shapeCode == CYLINDER_STANDING || $shapeCode == CYLINDER_STANDING_3D || $shapeCode == CYLINDER_LAYING_3D){
                                $prodDim1Name = 'Diameter';
                            } 
                            if ($shapeCode == PARALLELEPIPED_LAYING || $shapeCode == CYLINDER_CONCENTRIC_STANDING || $shapeCode == PARALLELEPIPED_LAYING_3D || $shapeCode == CYLINDER_CONCENTRIC_STANDING_3D){
                                $prodDim1Name = 'Height';
                            }                                 
                        }

                        if ($shapeCode == TRAPEZOID_3D || $shapeCode == OVAL_STANDING_3D || $shapeCode == OVAL_LAYING_3D) {

                            if ($shapeCode == TRAPEZOID_3D) {
                                $prodDim1Name = 'Base Length';
                            } 

                            if ($shapeCode == OVAL_STANDING_3D || $shapeCode == OVAL_LAYING_3D){
                                $prodDim1Name = 'Major Diameter';
                            } 
                                
                        } 

                        if (isset($prodDim1Name)) {
                            $html .= '<th align="center">'. $prodDim1Name .'<br>('. $symbol['prodDimensionSymbol'] . ' )</th>';
                        }

                        if ($shapeCode == PARALLELEPIPED_STANDING || $shapeCode == PARALLELEPIPED_LAYING || $shapeCode == PARALLELEPIPED_BREADED || $shapeCode == PARALLELEPIPED_STANDING_3D || $shapeCode == PARALLELEPIPED_LAYING_3D || $shapeCode == PARALLELEPIPED_BREADED_3D) {
                                $prodDim3Name = 'Width';
                        }
                        

                        if ($shapeCode == TRAPEZOID_3D || $shapeCode == OVAL_STANDING_3D || $shapeCode == OVAL_LAYING_3D) {

                            if ($shapeCode == TRAPEZOID_3D) {
                                $prodDim3Name = 'Base Width';
                            } 

                            if ($shapeCode == OVAL_STANDING_3D || $shapeCode == OVAL_LAYING_3D){
                                $prodDim3Name = 'Minor Diameter';
                            } 
                                
                        }

                        if (isset($prodDim3Name)) {
                            $html .= '<th align="center">'. $prodDim3Name .'<br>('. $symbol['prodDimensionSymbol'] . ' )</th>';
                        }

                        if ($shapeCode == PARALLELEPIPED_STANDING || $shapeCode == PARALLELEPIPED_BREADED || $shapeCode == CYLINDER_STANDING || $shapeCode == PARALLELEPIPED_STANDING_3D || $shapeCode == CYLINDER_STANDING_3D || $shapeCode == PARALLELEPIPED_BREADED_3D || $shapeCode == TRAPEZOID_3D || $shapeCode == PARALLELEPIPED_BREADED_3D || $shapeCode == OVAL_STANDING_3D) {
                            $prodDim2Name = 'Height';
                        }

                        if ($shapeCode == SLAB) {
                            $prodDim2Name = 'Thickness';
                        }

                        if (isset($prodDim2Name)) {
                            $html .= '<th align="center">'. $prodDim2Name .'<br>('. $symbol['prodDimensionSymbol'] . ' )</th>';
                        }
                        
                        $html .= '
                        <th align="center">Real product mass per unit<br>('. $symbol['massSymbol'] .')</th>
                        <th align="center">Same temperature throughout product.</th>
                        <th align="center">Initial temperature<br>(' . $symbol['temperatureSymbol'] . ') </th>
                    </tr>
                    <tr>
                        <td align="center">'. $product->PRODNAME .' </td>
                        <td align="center">'. $shapeNameDisplay .' </td>';
                        if (isset($prodDim1Name)) {
                            $html .='<td align="center">'. $this->convert->prodDimension($proElmt->SHAPE_PARAM1) .'</td>';
                        }

                        if (isset($prodDim3Name)) {
                            $html .='<td align="center">'. $this->convert->prodDimension($proElmt->SHAPE_PARAM3) .' </td>';
                        }

                        if (isset($prodDim2Name)) {
                            $html .='<td align="center">'. $this->convert->prodDimension($proElmt->SHAPE_PARAM2) .' </td>';
                        }
                        
                        $html .='
                        <td align="center">'. $this->convert->mass($product->PROD_REALWEIGHT) .' </td>
                        <td align="center">'. ($product->PROD_ISO == 1 ? 'YES' : 'NO') .' </td>
                        <td align="center">'. $proInfoStudy['avgTInitial'] .' </td>
                    </tr>
                </table>
            </div>
            <div class="pro-components">
                <div class="table table-bordered">
                    <table border="0.5" cellpadding="5">
                        <tr style="font-weight:bold">
                            <th align="center">Component list</th>
                            <th align="center">Description</th>
                            <th align="center">Product dimension<br>('. $symbol['prodDimensionSymbol'] .')</th>
                            <th align="center">Real mass<br>('. $symbol['massSymbol'] .') </th>
                            <th align="center">Same temperature throughout product.</th>
                            <th align="center">Added to product in study number</th>
                            <th align="center">Initial temperature<br>('. $symbol['temperatureSymbol'] .')</th>
                        </tr>';
                        foreach($productComps as $key => $resproductComps) { 
                            $prodElmIso = '';
                            $sameTemperature = 'NO';
                            $studyNumber = '';

                            if (!($study['CHAINING_CONTROLS'] && $study['PARENT_ID'] != 0 && $resproductComps['INSERT_LINE_ORDER'] != $study['ID_STUDY'])) {
                                if ($resproductComps['PROD_ELMT_ISO'] == 1 && empty($meshView['productElmtInitTemp'][$key])) {
                                    $prodElmIso = 'Undefined';
                                    $sameTemperature = 'NO';
                                }

                                if ($resproductComps['PROD_ELMT_ISO'] == 1 && !empty($meshView['productElmtInitTemp'][$key])) {
                                    $prodElmIso = $meshView['productElmtInitTemp'][$key][0];
                                    $sameTemperature = 'YES';
                                }

                                if ($resproductComps['PROD_ELMT_ISO'] != 1) {
                                    $prodElmIso = 'Non-isothermal';
                                    $sameTemperature = 'NO';
                                }
                            } else {
                                $prodElmIso = 'Non-isothermal';
                                $sameTemperature = 'NO';
                            }

                            if ($study['CHAINING_CONTROLS']) {
                                $studyNumber = $resproductComps['studyNumber'];
                            } else {
                                $studyNumber = '';
                            }
                            
                        $html .= '
                        <tr>
                            <td>'. $resproductComps['display_name'] .'</td>
                            <td align="center">'. $resproductComps['PROD_ELMT_NAME'] .'</td>
                            <td align="center">'. $this->convert->prodDimension($resproductComps['SHAPE_PARAM2']) .'</td>
                            <td align="center">'. $this->convert->mass($resproductComps['PROD_ELMT_REALWEIGHT']) .'</td>
                            <td align="center">'. $sameTemperature .'</td>
                            <td align="center">'. $studyNumber .'</td>
                            <td align="center">'. $prodElmIso .'</td>
                        </tr>';
                        }
                    $html .= '
                    </table>
                </div>
            </div>';
            PDF::writeHTML($html, true, false, true, false, '');
            PDF::AddPage();
        }
        
        if ($PROD_3D == 1) {
            PDF::SetFont('helvetica', 'B', 16);
            PDF::Bookmark('PRODUCT 3D', 0, 0, '', 'B', array(0,64,128));
            PDF::SetFillColor(38, 142, 226);
            PDF::SetTextColor(0,0,0);
            $content ='Product 3D';
            PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
            PDF::SetFont('helvetica', '', 10);
            $html = '';
            if ($PACKING == 1) {
                PDF::SetFont('helvetica', 'B', 16);
                PDF::SetFillColor(38, 142, 226);
                PDF::SetTextColor(0,0,0);
                $content ='Packing Data';
                PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
                PDF::SetFont('helvetica', '', 10);
            }
            $html .='<div class="pro-data">
            <div class="table table-bordered">
            <table border="0.5" align="center" cellpadding="5">
                <tr class="lineHeight20">
                    <td colspan="5" class="colCenter">Packing</td>
                    <td class="colCenter">3D view of the product</td>
                </tr>
                <tr class="lineHeight20">
                    <td rowspan="2" class="colCenter">Side</td>
                    <td rowspan="2" class="colCenter">Number of layers</td>
                    <td colspan="2" class="colCenter">Packing data</td>
                    <td rowspan="2" class="colCenter">Thickness ()</td>';
                    if ($PACKING == 1) {    
                    $html .='   
                        <td rowspan="'. ($packings['count'] + 2).'" class="colCenter"> </td>';
                    } else {
                        $html .='  
                        <td rowspan="2" class="colCenter"> </td>';
                    }
                    $html .=' 
                    </tr>
                    <tr class="lineHeight15">
                        <td class="colCenter">Order</td>
                        <td class="colCenter">Name</td>
                    </tr>';
                        if ($PACKING == 1) {
                            if (!empty($packings['packingLayerData']['1'])) {
                                foreach ($packings['packingLayerData']['1'] as $key => $top) {
                                    $html .= '<tr class="lineHeight15">';
                                    if ($key == 0) {
                                        $html .= '
                                            <td rowspan="'. count($packings['packingLayerData']['1']) .'" class="colCenter"> Top</td>
                                            <td rowspan="'. count($packings['packingLayerData']['1']) .'" class="colCenter"> '. count($packings['packingLayerData']['1']) .'</td>';
                                    }
                            
                                    $html .= '
                                        <td class="colCenter">'. ($top['PACKING_LAYER_ORDER'] + 1 ) .'</td>
                                        <td class="colCenter">'. $top['LABEL'] .'</td>
                                        <td class="colCenter">'. $top['THICKNESS'] .'</td>
                                    </tr>';
                                }
                            }
                            if (!empty($packings['packingLayerData']['2'])) {
                                foreach ($packings['packingLayerData']['2'] as $key => $bottom) {
                                    $html .= '<tr class="lineHeight15">';
                                    if ($key == 0) {
                                        $html .= '
                                            <td rowspan="'. count($packings['packingLayerData']['2']) .'" class="colCenter"> Bottom</td>
                                            <td rowspan="'. count($packings['packingLayerData']['2']) .'" class="colCenter"> '. count($packings['packingLayerData']['2']) .'</td>';
                                    }
                            
                                    $html .= '
                                        <td class="colCenter">'. ($bottom['PACKING_LAYER_ORDER'] + 1 ) .'</td>
                                        <td class="colCenter">'. $bottom['LABEL'] .'</td>
                                        <td class="colCenter">'. $bottom['THICKNESS'] .'</td>
                                    </tr>';
                                }
                            }
                            if (!empty($packings['packingLayerData']['3'])) {
                                foreach ($packings['packingLayerData']['3'] as $key => $sides) {
                                    $html .= '<tr class="lineHeight15">';
                                    if ($key == 0) {
                                        $html .= '
                                            <td rowspan="'. count($packings['packingLayerData']['3']) .'" class="colCenter"> 4 Sides</td>
                                            <td rowspan="'. count($packings['packingLayerData']['3']) .'" class="colCenter"> '. count($packings['packingLayerData']['3']) .'</td>';
                                    }
                            
                                    $html .= '
                                        <td class="colCenter">'. ($sides['PACKING_LAYER_ORDER'] + 1 ) .'</td>
                                        <td class="colCenter"> '. $sides['LABEL'] .'</td>
                                        <td class="colCenter">'. $sides['THICKNESS'] .'</td>
                                    </tr>';
                                }
                            }
                            $html .='
                            </table>
                            </div>
                        </div>';
                        } else {
                            $html .='
                            </table>
                            </div>
                        </div>';
                        }
                    PDF::writeHTML($html, true, false, true, false, '');
                    PDF::AddPage();
                }
                            
        if ($EQUIP_LIST == 1) {
            if (!empty($equipData)) {
                PDF::SetFont('helvetica', 'B', 16);
                PDF::Bookmark('EQUIPMENT DATA', 0, 0, '', 'B', array(0,64,128));
                PDF::SetFillColor(38, 142, 226);
                PDF::SetTextColor(0,0,0);
                $content ='Equipment Data';
                PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
                PDF::SetFont('helvetica', '', 10);
                $html = '
                <div class="equipment-data">
                    <div class="table table-bordered">
                        <table border="0.5" cellpadding="5">
                            <tr>
                                <th align="center">No.</th>
                                <th align="center">Name</th>
                                <th align="center">Residence / Dwell time  ( '. $symbol['timeSymbol'] .' )</th>
                                <th align="center">Control temperature( '. $symbol['temperatureSymbol'] .' )</th>
                                <th align="center">Convection Setting(Hz)</th>
                                <th align="center">Product orientation</th>
                                <th align="center">Conveyor coverage or quantity of product per batch</th>
                            </tr>';
                            foreach($equipData as $key => $resequipDatas) {
                                $html .='
                                <tr>
                                    <td align="center"> '. ($key + 1) .'</td>
                                    <td align="center"> '. $resequipDatas['displayName'] .'</td>
                                    <td align="center"> '. $resequipDatas['ts'][0] .'</td>
                                    <td align="center"> '. $resequipDatas['tr'][0] .'</td>
                                    <td align="center"> '. $resequipDatas['vc'][0] .'</td>
                                    <td align="center"> '. ($resequipDatas['ORIENTATION'] == 1 ? 'Parallel' : 'Perpendicular') .'</td>
                                    <td align="center"> '. $resequipDatas['top_or_QperBatch'] .'</td>
                                </tr>';
                            }
                    $html .='
                        </table>
                    </div>
                </div>';
                PDF::writeHTML($html, true, false, true, false, '');
                PDF::AddPage();
            } 
        }
        
        if ($ASSES_ECO == 1) {
            PDF::SetFont('helvetica', 'B', 16);
            PDF::Bookmark('BELT OR SHELVES LAYOUT', 0, 0, '', 'B', array(0,64,128));
            PDF::SetFillColor(38, 142, 226);
            PDF::SetTextColor(0,0,0);
            $content ='Belt or Shelves Layout';
            PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
            PDF::SetFont('helvetica', '', 10);
            $html ='';
            foreach ($equipData as $resequipDatas) {
                PDF::Bookmark($resequipDatas['displayName'], 1, 0, '', '', array(128,0,0));
                // PDF::Cell(0, 10, '', 0, 1, 'L');
                $html .=   '<br><h3>'. $resequipDatas['displayName'] .'</h3>';
                if ($this->equip->getCapability($resequipDatas['CAPABILITIES'], 8192)) {
                    $html .=  '<table border="0.5" cellpadding="5">
                            <tr>
                                <th colspan="2" align="center">Inputs</th>
                                <th align="center">Image</th>
                            </tr>
                            <tr>
                                <td>Space (length) ( '. $symbol['prodDimensionSymbol'] .' )</td>
                                <td align="center"> User not define </td>
                                <td rowspan="8" align="center"><img style="width: 340px; height: 460px"  src="'. $public_path . "/reports/" . $study->USERNAM ."/". $nameLayout.$resequipDatas['ID_STUDY_EQUIPMENTS'].".jpg".'"></td>
                            </tr>
                            <tr>
                                <td>Space (width) ( '. $symbol['prodDimensionSymbol'] .' )</td>
                                <td align="center"> User not define </td>
                            </tr>
                            <tr>
                                <td>Orientation</td>
                                <td align="center"> '. ($resequipDatas['ORIENTATION'] == 1 ? 'Parallel' : 'Perpendicular') .' </td>
                            </tr>
                            <tr>
                                <td colspan="2" align="center">Outputs</td>
                            </tr>
                            <tr>
                                <td>Space in width ( '. $symbol['prodDimensionSymbol'] .' )</td>
                                <td align="center"> '. $resequipDatas['layoutResults']['LEFT_RIGHT_INTERVAL'] .' </td>
                            </tr>
                            <tr>
                                <td>Number per meter</td>
                                <td align="center"> '. $resequipDatas['layoutResults']['NUMBER_PER_M'] .' </td>
                            </tr>
                            <tr>
                                <td>Number in width</td>
                                <td align="center"> '. $resequipDatas['layoutResults']['NUMBER_IN_WIDTH'] .' </td>
                            </tr>
                            <tr>
                                <td>Conveyor coverage or quantity of product per batch</td>
                                <td align="center"> '. $resequipDatas['top_or_QperBatch'] .' </td>
                            </tr>
                        </table>
                <br></br><br></br><br></br>';
                } else {
                    $html .= '<table border="0.5" cellpadding="5">
                        <tr>
                            <th colspan="2" align="center">Inputs</th>
                        </tr>
                        <tr>
                            <td>Orientation</td><td>'. ($resequipDatas['ORIENTATION'] == 1 ? 'Parallel' : 'Perpendicular') .'</td>
                        </tr>
                        <tr><td>Conveyor coverage or quantity of product per batch</td><td>'. $resequipDatas['top_or_QperBatch'] .'</td></tr>
                    </table>
                <br></br><br></br><br></br>';
                }
                
            }
            PDF::writeHTML($html, true, false, true, false, '');
            PDF::AddPage();
        }
        
        if ($PIPELINE == 1) {
            if (!empty($cryogenPipeline)) {
                if ($study->OPTION_CRYOPIPELINE == 1) {
                    PDF::SetFont('helvetica', 'B', 16);
                    PDF::Bookmark('CRYOGENIC PIPELINE', 0, 0, '', 'B', array(0,64,128));
                    PDF::SetFillColor(38, 142, 226);
                    PDF::SetTextColor(0,0,0);
                    $content ='Cryogenic Pipeline';
                    PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
                    PDF::SetFont('helvetica', '', 10);
                    $html = '';
                    $html .= '
                    <div class="consum-esti">
                        <div class="table table-bordered">
                            <table border="0.5" cellpadding="5">
                                <tr>
                                    <th colspan="2" align="center">Type</th>
                                    <th colspan="4" align="center">Name</th>
                                    <th colspan="2" align="center">Number</th>
                                </tr>
                                <tr>
                                    <td colspan="2">Insulated line</td>
                                    <td colspan="4" align="center">'. ($cryogenPipeline['dataResultExist']['insulLabel']) .'</td>
                                    <td colspan="2" align="center">'. ($cryogenPipeline['dataResultExist']['insulllenght']) .' ('.$symbol['lineDimensionSymbol'].')</td>
                                </tr>
                                <tr>
                                    <td colspan="2">Insulated valves</td>
                                    <td colspan="4" align="center">'. ($cryogenPipeline['dataResultExist']['insulvalLabel']) .'</td>
                                    <td colspan="2" align="center">'. ($cryogenPipeline['dataResultExist']['insulvallenght']) .'</td>
                                </tr>
                                <tr>
                                    <td colspan="2">Elbows</td>
                                    <td colspan="4" align="center">'. ($cryogenPipeline['dataResultExist']['elbowLabel']) .'</td>
                                    <td colspan="2" align="center">'. ($cryogenPipeline['dataResultExist']['elbowsnumber']) .'</td>
                                </tr>
                                <tr>
                                    <td colspan="2">Tees</td>
                                    <td colspan="4" align="center">'. ($cryogenPipeline['dataResultExist']['teeLabel']) .'</td>
                                    <td colspan="2" align="center">'. ($cryogenPipeline['dataResultExist']['teenumber']) .'</td>
                                </tr>
                                <tr>
                                    <td colspan="2">Non-insulated line</td>
                                    <td colspan="4" align="center">'. ($cryogenPipeline['dataResultExist']['noninsulLabel']) .'</td>
                                    <td colspan="2" align="center">'. ($cryogenPipeline['dataResultExist']['noninsullenght']) .' ('.$symbol['lineDimensionSymbol'].')</td>
                                </tr>
                                <tr>
                                    <td colspan="2">Non-insulated valves</td>
                                    <td colspan="4" align="center">'. ($cryogenPipeline['dataResultExist']['noninsulvalLabel']) .'</td>
                                    <td colspan="2"align="center">'. ($cryogenPipeline['dataResultExist']['noninsulatevallenght']) .'</td>
                                </tr>
                                <tr>
                                    <td colspan="2">Storage tank</td>
                                    <td colspan="4" align="center">'. ($cryogenPipeline['dataResultExist']['storageTankName']) .'</td>
                                    <td colspan="2" align="center"></td>
                                </tr>
                                <tr>
                                    <td colspan="6"><strong>Tank pressure :</strong> </td>
                                    <td colspan="2" align="center">'. ($cryogenPipeline['dataResultExist']['pressuer']) .' ('.$symbol['pressureSymbol'].')</td>
                                </tr>
                                <tr>
                                    <td colspan="6"><strong>Equipment elevation above tank outlet. :</strong></td>
                                    <td colspan="2" align="center">'. ($cryogenPipeline['dataResultExist']['height']) .' ('.$symbol['materialRiseSymbol'].')</td>
                                </tr>
                            </table>
                        </div>
                    </div>';
                    PDF::writeHTML($html, true, false, true, false, '');
                    PDF::AddPage();
                }
            }
        }

        if ($PROD_3D != 1 && $PACKING == 1) {

            PDF::SetFont('helvetica', 'B', 16);
            PDF::Bookmark('PACKING DATA', 0, 0, '', 'B', array(0,64,128));
            PDF::SetFillColor(38, 142, 226);
            PDF::SetTextColor(0,0,0);
            $content ='Packing Data';
            PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
            PDF::SetFont('helvetica', '', 10);
            $html ='
            <div class="pro-data">
            <div class="table table-bordered">
                <table border="0.5" align="center" cellpadding="5">
                    <tr class="lineHeight20">
                        <td colspan="5" class="colCenter">Packing</td>
                        <td class="colCenter">3D view of the product</td>
                    </tr>
                    <tr class="lineHeight20">
                        <td rowspan="2" class="colCenter">Side</td>
                        <td rowspan="2" class="colCenter">Number of layers</td>
                        <td colspan="2" class="colCenter">Packing data</td>
                        <td rowspan="2" class="colCenter">Thickness ()</td>
                        <td rowspan="'. ($packings['count'] + 2).'" class="colCenter"> </td>
                    </tr>
                    <tr class="lineHeight15">
                        <td class="colCenter">Order</td>
                        <td class="colCenter">Name</td>
                    </tr>';
                        if (!empty($packings['packingLayerData']['1'])) {
                            foreach ($packings['packingLayerData']['1'] as $key => $top) {
                                $html .= '<tr class="lineHeight15">';
                                if ($key == 0) {
                                    $html .= '
                                        <td rowspan="'. count($packings['packingLayerData']['1']) .'" class="colCenter"> Top</td>
                                        <td rowspan="'. count($packings['packingLayerData']['1']) .'" class="colCenter"> '. count($packings['packingLayerData']['1']) .'</td>';
                                }
                        
                                $html .= '
                                    <td class="colCenter">'. ($top['PACKING_LAYER_ORDER'] + 1 ) .'</td>
                                    <td class="colCenter">'. $top['LABEL'] .'</td>
                                    <td class="colCenter">'. $top['THICKNESS'] .'</td>
                                </tr>';
                            }
                        }
                        if (!empty($packings['packingLayerData']['2'])) {
                            foreach ($packings['packingLayerData']['2'] as $key => $bottom) {
                                $html .= '<tr class="lineHeight15">';
                                if ($key == 0) {
                                    $html .= '
                                        <td rowspan="'. count($packings['packingLayerData']['2']) .'" class="colCenter"> Bottom</td>
                                        <td rowspan="'. count($packings['packingLayerData']['2']) .'" class="colCenter"> '. count($packings['packingLayerData']['2']) .'</td>';
                                }
                        
                                $html .= '
                                    <td class="colCenter">'. ($bottom['PACKING_LAYER_ORDER'] + 1 ) .'</td>
                                    <td class="colCenter">'. $bottom['LABEL'] .'</td>
                                    <td class="colCenter">'. $bottom['THICKNESS'] .'</td>
                                </tr>';
                            }
                        }
                        if (!empty($packings['packingLayerData']['3'])) {
                            foreach ($packings['packingLayerData']['3'] as $key => $sides) {
                                $html .= '<tr class="lineHeight15">';
                                if ($key == 0) {
                                    $html .= '
                                        <td rowspan="'. count($packings['packingLayerData']['3']) .'" class="colCenter"> 4 Sides</td>
                                        <td rowspan="'. count($packings['packingLayerData']['3']) .'" class="colCenter"> '. count($packings['packingLayerData']['3']) .'</td>';
                                }
                        
                                $html .= '
                                    <td class="colCenter">'. ($sides['PACKING_LAYER_ORDER'] + 1 ) .'</td>
                                    <td class="colCenter"> '. $sides['LABEL'] .'</td>
                                    <td class="colCenter">'. $sides['THICKNESS'] .'</td>
                                </tr>';
                            }
                        }
                            $html .='
                        </table>
                        </div>
                    </div>';
                    PDF::writeHTML($html, true, false, true, false, '');
                    PDF::AddPage();
                }
        
        if ($CONS_OVERALL == 1 || $CONS_TOTAL ==1 || $CONS_SPECIFIC  == 1 || $CONS_HOUR ==1 || $CONS_DAY == 1||
        $CONS_WEEK == 1 || $CONS_MONTH == 1 || $CONS_YEAR ==1 || $CONS_EQUIP ==1 || $CONS_PIPE == 1 || $CONS_TANK ==1) {
            if (!empty($consumptions )) {
                PDF::SetFont('helvetica', 'B', 16);
                PDF::Bookmark('CONSUMPTIONS / ECONOMICS ASSESSMENTS', 0, 0, '', 'B', array(0,64,128));
                PDF::SetFillColor(38, 142, 226);
                PDF::SetTextColor(0,0,0);
                $content ='Consumptions / Economics assessments';
                PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
                PDF::SetFont('helvetica', '', 9);
                $rowspan = ($CONS_PIPE == 1 || $CONS_EQUIP == 1) ? ' rowspan="2"' : '';
                $nameWidth = ($CONS_PIPE == 1 || $CONS_EQUIP == 1) ? ' width="10%"' : '';
                $overallWidth = ($CONS_PIPE == 1 || $CONS_EQUIP == 1) ? ' width="9%"' : '';
                $totalWidth = ($CONS_PIPE == 1 || $CONS_EQUIP == 1) ? ' width="7%"' : '';
                $specificWidth = ($CONS_PIPE == 1 || $CONS_EQUIP == 1) ? ' width="8%"' : '';
                $html ='
                <br><h3>Values</h3>
                <div class="consum-esti">
                    <div class="table table-bordered">
                    <table border="1" cellpadding="3" width="100%">
                        <tr style="font-size:10px"><td'. $rowspan .' colspan="2" align="center"'. $nameWidth .'>Equipment</td>';
                            if ($CONS_OVERALL == 1) { 
                            $html .='
                                <td'. $rowspan .' align="center"'. $overallWidth .'>Overall Cryogen Consumption Ratio (product + equipment and pipeline losses) Unit of Cryogen, per piece of product.<br>( '. $symbol['consumSymbol'] .' ) </td>';
                            }
                            if ($CONS_TOTAL == 1) { 
                            $html .=' 
                                <td'. $rowspan .' align="center"'. $totalWidth .'>Total Cryogen Consumption (product + equipment and pipeline losses).<br>( '. $symbol['consumMaintienSymbol'] .')  / '. $symbol['perUnitOfMassSymbol'] .'  </td>';
                            } 
                            if ($CONS_SPECIFIC == 1) { 
                            $html .=' 
                                <td'. $rowspan .' align="center"'. $specificWidth .'>Specific Cryogen Consumption Ratio (product only) Unit of Cryogen, per unit weight of product.<br>( '. $symbol['consumMaintienSymbol'] .')  / '. $symbol['perUnitOfMassSymbol'] .' </td>';
                            }
                            if ($CONS_HOUR == 1) {
                            $html .=' 
                                <td'. $rowspan .' align="center">Total Cryogen Consumption per hour<br>( '.$symbol['consumSymbol'] .' ) </td>';
                            }
                            if ($CONS_DAY == 1) {
                            $html .=' 
                                <td'. $rowspan .' align="center">Total Cryogen Consumption per day<br>( '. $symbol['consumSymbol'] .' ) </td>';
                            }
                            if ($CONS_WEEK == 1) { 
                            $html .=' 
                                <td'. $rowspan .' align="center">Total Cryogen Consumption per week<br>( '. $symbol['consumSymbol'] .' ) </td>';
                            }
                            if ($CONS_MONTH == 1) { 
                            $html .=' 
                                <td'. $rowspan .' align="center">Total Cryogen Consumption per month<br>( '. $symbol['consumSymbol'] .' ) </td>';
                            }
                            if ($CONS_YEAR == 1) { 
                            $html .=' 
                                <td'. $rowspan .' align="center">Total Cryogen Consumption per year<br>( '. $symbol['consumSymbol'] .' ) </td>';
                            }
                            if ($CONS_EQUIP == 1) {
                            $html .=' 
                                <td colspan="2" align="center">Equipment Cryogen Consumption</td>';
                            }
                            if ($CONS_PIPE == 1) { 
                            $html .=' 
                                <td colspan="2" align="center">Pipeline consumption</td>';
                            }
                            if ($CONS_TANK == 1) { 
                            $html .=' 
                                <td'. $rowspan .' align="center">Tank losses<br>( '. $symbol['consumSymbol'] .' ) </td>';
                            }
                            $html .=' 
                        </tr>';

                        if ($CONS_PIPE == 1 || $CONS_EQUIP == 1){
                        $html .=' 
                        <tr style="font-size:9px">';
                            if ($CONS_EQUIP == 1) { 
                            $html .=' 
                                <td align="center">Heat losses per hour<br>( '. $symbol['consumMaintienSymbol'] .' ) </td>
                                <td align="center">Cooldown<br>( '. $symbol['consumSymbol'] .' ) </td>';
                            }
                            if ($CONS_PIPE == 1) { 
                            $html .=' 
                                <td align="center">Heat losses per hour<br>( '. $symbol['consumMaintienSymbol'] .' ) </td>
                                <td align="center">Cooldown<br>( '. $symbol['consumSymbol'] .' ) </td>';
                            }
                        $html .=' 
                        </tr>';
                        }

                        foreach($consumptions as $key => $resconsumptions) { 
                        $html .=' 
                        <tr style="font-size:10px">';
                        $html .='
                            <td rowspan="2"> '. $resconsumptions['equipName'] .' </td>
                            <td align="center">( '. $symbol['consumSymbol'] .' )</td>';
                            if ($CONS_OVERALL == 1) {
                            $html .=' 
                                <td align="center"> '. $resconsumptions['tc'] .' </td>';
                            }
                            if ($CONS_TOTAL == 1) {
                            $html .='
                                <td align="center"> '. $resconsumptions['kgProduct'] .' </td>';
                            }
                            if ($CONS_SPECIFIC == 1) {
                            $html .='
                                <td align="center"> '. $resconsumptions['product'] .' </td>
                            ';
                            }
                            if ($CONS_HOUR == 1) {
                            $html .='
                                <td align="center"> '. $resconsumptions['hour'] .' </td>';
                            }
                            if ($CONS_DAY == 1) {
                            $html .='
                                <td align="center"> '. $resconsumptions['day'] .'</td>';
                            }
                            if ($CONS_WEEK == 1) {
                            $html .='
                                <td align="center"> '. $resconsumptions['week'] .' </td>';
                            }
                            if ($CONS_MONTH == 1) {
                            $html .='
                                <td align="center"> '. $resconsumptions['month'] .' </td>';
                            }
                            if ($CONS_YEAR == 1) {
                            $html .='
                                <td align="center"> '. $resconsumptions['year'] .' </td>';
                            }
                            if ($CONS_EQUIP == 1) { 
                            $html .='
                                <td align="center"> '. $resconsumptions['eqptPerm'] .' </td>
                                <td align="center"> '. $resconsumptions['eqptCold'] .' </td>';
                            }
                            if ($CONS_PIPE == 1) {
                            $html .='
                                <td align="center"> '. $resconsumptions['linePerm'] .' </td>
                                <td align="center"> '. $resconsumptions['lineCold'] .' </td>';
                            }
                            if ($CONS_TANK == 1) {
                            $html .='
                            <td align="center"> '. $resconsumptions['tank'] .' </td>';
                            }
                        $html .='</tr>';
                        $html .='<tr style="font-size:10px">
                            <td align="center">( '. $symbol['monetarySymbol'] .' )</td>';
                            if ($study->OPTION_ECO != 1) {
                                if ($CONS_OVERALL == 1) {
                                $html .=' 
                                    <td align="center"> -- </td>';
                                }
                                if ($CONS_TOTAL == 1) { 
                                $html .='
                                    <td align="center"> -- </td>';
                                }
                                if ($CONS_SPECIFIC == 1) { 
                                    $html .='
                                    <td align="center"> -- </td>';
                                }
                                if ($CONS_HOUR == 1) { 
                                $html .='
                                    <td align="center"> -- </td>';
                                }
                                if ($CONS_DAY == 1) { 
                                $html .='
                                    <td align="center"> -- </td>';
                                }
                                if ($CONS_WEEK == 1) { 
                                $html .='
                                    <td align="center"> -- </td>';
                                }
                                if ($CONS_MONTH == 1) { 
                                $html .='
                                    <td align="center"> -- </td>';
                                }
                                if ($CONS_YEAR == 1) { 
                                $html .='
                                    <td align="center"> -- </td>';
                                }
                                if ($CONS_EQUIP == 1) { 
                                $html .='
                                    <td align="center"> -- </td>
                                    <td align="center"> -- </td>';
                                }
                                if ($CONS_PIPE == 1) { 
                                $html .='
                                    <td align="center"> -- </td>
                                    <td align="center"> -- </td>';
                                }
                                if ($CONS_TANK == 1) { 
                                $html .='
                                    <td align="center"> -- </td>';
                                }
                            } else {
                                if ($CONS_OVERALL == 1) {
                                    $html .=' 
                                        <td align="center"> '. $economic[$key]['tc'] .' </td>';
                                    }
                                    if ($CONS_TOTAL == 1) { 
                                    $html .='
                                        <td align="center"> '. $economic[$key]['kgProduct'] .' </td>';
                                    }
                                    if ($CONS_SPECIFIC == 1) { 
                                        $html .='
                                        <td align="center"> '. $economic[$key]['product'] .' </td>';
                                    }
                                    if ($CONS_HOUR == 1) { 
                                    $html .='
                                        <td align="center"> '. $economic[$key]['hour'] .' </td>';
                                    }
                                    if ($CONS_DAY == 1) { 
                                    $html .='
                                        <td align="center"> '. $economic[$key]['day'] .' </td>';
                                    }
                                    if ($CONS_WEEK == 1) { 
                                    $html .='
                                        <td align="center"> '. $economic[$key]['week'] .' </td>';
                                    }
                                    if ($CONS_MONTH == 1) { 
                                    $html .='
                                        <td align="center">'. $economic[$key]['month'] .'</td>';
                                    }
                                    if ($CONS_YEAR == 1) { 
                                    $html .='
                                        <td align="center"> '. $economic[$key]['year'] .' </td>';
                                    }
                                    if ($CONS_EQUIP == 1) { 
                                    $html .='
                                        <td align="center"> '. $economic[$key]['eqptPerm'] .' </td>
                                        <td align="center"> '. $economic[$key]['eqptCold'] .' </td>';
                                    }
                                    if ($CONS_PIPE == 1) { 
                                    $html .='
                                        <td align="center"> '. $economic[$key]['linePerm'] .' </td>
                                        <td align="center"> '. $economic[$key]['lineCold'] .' </td>';
                                    }
                                    if ($CONS_TANK == 1) { 
                                    $html .='
                                        <td align="center"> '. $economic[$key]['tank'] .' </td>';
                                    }
                            }
                            $html .=' </tr>';
                        }
                        $html .='
                        </table>
                    </div>
                </div>';
                PDF::writeHTML($html, true, false, true, false, '');
                PDF::AddPage();
            }
        }

        if ($REP_CONS_PIE == 1) {
            if (!empty($consumptions )) {
                PDF::SetFont('helvetica', 'B', 16);
                PDF::Bookmark('Consumption pies', 0, 0, '', 'B', array(0,64,128));
                PDF::SetFillColor(38, 142, 226);
                PDF::SetTextColor(0,0,0);
                $content ='Consumption pies';
                PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
                PDF::SetFont('helvetica', '', 9);
                $html='';
                foreach ($consumptions as $key => $consumption) {
                    $percentProduct = $consumption['percentProduct'];
                    $percentEquipmentPerm = $consumption['percentEquipmentPerm'];
                    $percentEquipmentDown = $consumption['percentEquipmentDown'];
                    $percentLine = $consumption['percentLine'];
                    if ($percentProduct != 0 && $percentEquipmentPerm != 0  && $percentEquipmentDown != 0) {
                        $height = $key > 0 ? 550 : 530;
                        PDF::Bookmark($consumption['equipName'] , 1, 0, '', '', array(128,0,0));
                        $html .='<br><h3>'. $consumption['equipName'] .'</h3>';
                        $html .= '<div style="text-align:center"><img height="'. $height .'" src="'. $public_path .'/consumption/'. $study['USERNAM'] .'/'. $study['ID_STUDY'] .'/'. $consumption['id'] .'.png"></div>';
                    }
                }

                PDF::writeHTML($html, true, false, true, false, '');
            }
        }

        if (($isSizingValuesChosen == 1) || ($isSizingValuesMax == 16) || ($SIZING_GRAPHE == 1)) {
            PDF::SetFont('helvetica', 'B', 16);
            PDF::Bookmark('HEAT BALANCE / SIZING RESULTS', 0, 0, '', 'B', array(0,64,128));
            PDF::SetFillColor(38, 142, 226);
            PDF::SetTextColor(0,0,0);
            $content ='Heat balance / sizing results';
            PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
            PDF::SetFont('helvetica', '', 10);
            $html='';
            if ($isSizingValuesChosen == 1) {
                PDF::Bookmark('Chosen product flowrate', 1, 0, '', '', array(128,0,0));
                PDF::Cell(0, 10, 'Chosen product flowrate', 0, 1, 'L');
                $html .='
                <div class="heat-balance-sizing">
                    <div class="table table-bordered">
                        <table border="0.5" cellpadding="5">
                            <tr>
                                <th colspan="2" rowspan="2" align="center">Equipment</th>
                                <th rowspan="2" align="center">Average initial temperature ('. $symbol['temperatureSymbol'] .')</th>
                                <th rowspan="2" align="center">Final Average Product temperature ('. $symbol['temperatureSymbol'] .')</th>
                                <th rowspan="2" align="center">Control temperature ('. $symbol['temperatureSymbol'] .')</th>
                                <th rowspan="2" align="center">Residence / Dwell time   ('. $symbol['timeSymbol'] .')</th>
                                <th rowspan="2" align="center">Product Heat Load ('. $symbol['enthalpySymbol'] .')</th>
                                <th colspan="3" align="center">Chosen product flowrate</th>
                                <th rowspan="2" align="center">Precision of the high level calculation. (%)</th>
                            </tr>
                            <tr>
                                <th align="center">Hourly production capacity ('. $symbol['productFlowSymbol'] .')</th>
                                <th align="center">Cryogen consumption (product + equipment heat load) ( '. $symbol['consumMaintienSymbol'] .')  / '. $symbol['perUnitOfMassSymbol'] .' </th>
                                <th align="center">Conveyor coverage or quantity of product per batch</th>
                            </tr>';
                            if (!empty($calModeHeadBalance)) {
                                foreach( $calModeHeadBalance as $resoptHeads) { 
                                    if ($resoptHeads['conso_warning'] == 'warning_fluid') {
                                        $conso = '<img src="'. $public_path .'/images/output/warning_fluid_overflow.gif" width="30">';  
                                    } else if ($resoptHeads['conso_warning'] == 'warning_dhp') {
                                        $conso = '<img src="'. $public_path .'/images/output/warning_fluid_overflow.gif" width="30"><img src="'. $public_path .'/images/output/warning_dhp_overflow.gif" width="30">';  
                                    } else if ($resoptHeads['conso_warning'] == 'warning_dhp_value') {
                                        $conso = '<div>'. $resoptHeads['conso'] .'</div><img src="'. $public_path .'/images/output/warning_dhp_overflow.gif" width="30">';
                                    } else if ($resoptHeads['conso_warning'] != 'warning_fluid' && $resoptHeads['conso_warning'] != 'warning_dhp' && $resoptHeads['conso_warning'] != 'warning_dhp_value') {
                                        $conso = $resoptHeads['conso'];
                                    }
                                $html .='
                                    <tr>
                                        <td align="center" colspan="2"> '. $resoptHeads['equipName'] .' </td>
                                        <td align="center"> '. $proInfoStudy['avgTInitial'] .' </td>
                                        <td align="center"> '. $resoptHeads['tfp'] .' </td>
                                        <td align="center"> '. $resoptHeads['tr'] .' </td>
                                        <td align="center"> '. $resoptHeads['ts'] .' </td>
                                        <td align="center"> '. $resoptHeads['vep'] .' </td>
                                        <td align="center"> '. $resoptHeads['dhp'] .' </td>
                                        <td align="center"> '. $conso .' </td>
                                        <td align="center"> '. $resoptHeads['toc'] .' </td>
                                        <td align="center"> '. $resoptHeads['precision'] .' </td>
                                    </tr>';
                                }
                            }
                            $html .='
                        </table>
                    </div>
                </div>';
                PDF::writeHTML($html, true, false, true, false, '');
                PDF::AddPage();
            }
            if (!empty($calModeHbMax)) {
                if ($isSizingValuesMax == 16) {
                    PDF::Bookmark(' Maximum product flowrate', 1, 0, '', '', array(128,0,0));
                    PDF::Cell(0, 10, 'Maximum product flowrate', 0, 1, 'L');
                    $html = '
                    <div class="Max-prod-flowrate">
                        <div class="table table-bordered">
                            <table border="0.5" cellpadding="5">
                                <tr>
                                    <th colspan="2" rowspan="2" align="center">Equipment</th>
                                    <th rowspan="2" align="center">Average initial temperature ( '. $symbol['temperatureSymbol'] .' ) </th>
                                    <th rowspan="2" align="center">Final Average Product temperature ( '. $symbol['temperatureSymbol'] .' ) </th>
                                    <th rowspan="2" align="center">Control temperature ( '. $symbol['temperatureSymbol'] .' ) </th>
                                    <th rowspan="2" align="center">Residence / Dwell time   ( '. $symbol['timeSymbol'] .' ) </th>
                                    <th rowspan="2" align="center">Product Heat Load ( '. $symbol['enthalpySymbol'] .' ) </th>
                                    <th colspan="3" align="center">Maximum product flowrate </th>
                                    <th rowspan="2" align="center">Precision of the high level calculation. (%)</th>
                                </tr>
                                <tr>
                                    <th align="center">Hourly production capacity ( '. $symbol['productFlowSymbol'] .' ) </th>
                                    <th align="center">Cryogen consumption (product + equipment heat load) ( '. $symbol['consumMaintienSymbol'] .')  / '. $symbol['perUnitOfMassSymbol'] .'  </th>
                                    <th align="center">Conveyor coverage or quantity of product per batch</th>
                                </tr>';
                                if (!empty($calModeHbMax)) {
                                    foreach($calModeHbMax  as $resoptimumHbMax) {
                                        if ($resoptimumHbMax['conso_warning'] == 'warning_fluid') {
                                            $conso = '<img src="'. $public_path .'/images/output/warning_fluid_overflow.gif" width="30">';  
                                        } else if ($resoptimumHbMax['conso_warning'] == 'warning_dhp') {
                                            $conso = '<img src="'. $public_path .'/images/output/warning_fluid_overflow.gif" width="30"><img src="'. $public_path .'/images/output/warning_dhp_overflow.gif" width="30">';  
                                        } else if ($resoptimumHbMax['conso_warning'] == 'warning_dhp_value') {
                                            $conso = '<div>'. $resoptimumHbMax['conso'] .'</div><img src="'. $public_path .'/images/output/warning_dhp_overflow.gif" width="30">';
                                        } else if ($resoptimumHbMax['conso_warning'] != 'warning_fluid' && $resoptimumHbMax['conso_warning'] != 'warning_dhp' && $resoptimumHbMax['conso_warning'] != 'warning_dhp_value') {
                                            $conso = $resoptimumHbMax['conso'];
                                        }
                                        $html .='<tr>
                                            <td align="center" colspan="2"> '. $resoptimumHbMax['equipName'] .' </td>
                                            <td align="center"> '. $proInfoStudy['avgTInitial'] .' </td>
                                            <td align="center">'. $resoptimumHbMax['tfp'] .' </td>
                                            <td align="center">'. $resoptimumHbMax['tr']  .'</td>
                                            <td align="center">'. $resoptimumHbMax['ts']  .'</td>
                                            <td align="center">'. $resoptimumHbMax['vep'] .' </td>
                                            <td align="center">'. $resoptimumHbMax['dhp'] .' </td>
                                            <td align="center"> '. $conso .' </td>
                                            <td align="center"> '. $resoptimumHbMax['toc'] .'</td>
                                            <td align="center"> '. $resoptimumHbMax['precision'] .' </td>
                                        </tr>';
                                        }
                                }
                            $html .='</table>
                        </div>
                    </div>';
                    PDF::writeHTML($html, true, false, true, false, '');
                    PDF::AddPage();
                }
            }

            if ($SIZING_GRAPHE == 1) {
                PDF::Bookmark(' Graphic', 1, 0, '', '', array(128,0,0));
                PDF::Cell(0, 10, 'Graphic', 0, 1, 'L');
                if ($study['CALCULATION_MODE'] == 3) {
                    $html = '
                    <div align="center">
                        <img  width="640" height="450" src="'. $public_path .'/sizing/'. $study['USERNAM'].'/'. $study['ID_STUDY'].'/'. $study['ID_STUDY'] .'.png">
                    </div>';
                    PDF::writeHTML($html, true, false, true, false, '');
                    PDF::AddPage();
                } else if($study['CALCULATION_MODE'] == 1) {
                    if (!empty($calModeHeadBalance)) {
                        foreach ($calModeHeadBalance as $resoptHeads) {
                            $html = '
                            <div align="center">
                                <img  width="640" height="450" src="'. $public_path .'/sizing/'. $study['USERNAM'].'/'. $study['ID_STUDY'].'/'. $study['ID_STUDY'] ."-".$resoptHeads['id'] .'.png">
                            </div>';
                            PDF::writeHTML($html, true, false, true, false, '');
                            PDF::AddPage();
                        }
                    }
                }
            }
        }

        if (($ENTHALPY_V == 1) || ($ENTHALPY_G ==1)) {
            if (!empty($heatexchange)) {
                PDF::SetFont('helvetica', 'B', 16);
                PDF::Bookmark('HEAT EXCHANGE', 0, 0, '', 'B', array(0,64,128));
                PDF::SetFillColor(38, 142, 226);
                PDF::SetTextColor(0,0,0);
                $content ='Heat Exchange';
                PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
                PDF::SetFont('helvetica', '', 10);
                $html='';
                foreach ($heatexchange as $resheatexchanges) {
                    $html ='';
                    PDF::Bookmark($resheatexchanges['equipName'] , 1, 0, '', '', array(128,0,0));
                    PDF::Cell(0, 10, $resheatexchanges['equipName'], 0, 1, 'L');
                    if ($ENTHALPY_V == 1) {
                        $html ='<h3>Values</h3>
                        <div class="heat-exchange">
                            <table border="0.5" cellpadding="5">
                                <tr>
                                    <th colspan="2">Equipment</th>';
                                    foreach($resheatexchanges['result'] as $result) { 
                                        $html .='<th align="center"> '. $result['x'] . ' '. $symbol['timeSymbol']. '</th>';
                                    }
                                $html .='    
                                </tr>
                                <tr>
                                    <td colspan="2">'. $resheatexchanges['equipName'] . ' - (' . $symbol['enthalpySymbol'] . ')' .'</td>';
                                    foreach($resheatexchanges['result'] as $result) {
                                        $html .=' <th align="center"> '. $result['y'] .'</th>';
                                    }
                                    $html .='     
                                </tr>
                            </table>
                        </div>';
                        PDF::writeHTML($html, true, false, true, false, '');
                    }

                    if ($ENTHALPY_G == 1) {
                        $html ='<h3>Graphic</h3>
                        <div align="center">
                        <img width="640" height="450" src="'. $public_path .'/heatExchange/'. $study['USERNAM'] .'/'. $resheatexchanges['idStudyEquipment'] .'.png">
                        </div>';
                        PDF::writeHTML($html, true, false, true, false, '');
                    }
                    
                }
                PDF::AddPage();
            }
        }

        if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
            if (!empty($proSections)) {
                PDF::Bookmark('PRODUCT SECTION', 0, 0, '', 'B', array(0,64,128));
                PDF::SetFont('helvetica', 'B', 16);
                PDF::SetFillColor(38, 142, 226);
                PDF::SetTextColor(0,0,0);
                $content ='Product Section';
                PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
                PDF::SetFont('helvetica', '', 10);
                $html='';
                foreach ($proSections as $resproSections) {
                    $html ='';
                    PDF::Bookmark($resproSections['equipName'] , 1, 0, '', '', array(128,0,0));
                    // PDF::Cell(0, 10, '' , 0, 1, 'L');
                    $html .='<br><h3>'. $resproSections['equipName'] .'</h3>';
                    if ($ISOCHRONE_V == 1) {
                        if ($resproSections['selectedAxe'] == 1) {
                            PDF::Bookmark('Values - Dimension' . $resproSections['selectedAxe'] . '(' . '*,' . $resproSections['axeTemp'][0] . ',' . $resproSections['axeTemp'][1] . ')' . '(' . $resproSections['prodchartDimensionSymbol'] . ')' , 2, 0, '', 'I', array(0,128,0));
                            // PDF::Cell(0, 10, '', 0, 1, 'L');
                            $html .='<h3> Values - Dimension'. $resproSections['selectedAxe'] . '(' . '*,' . $resproSections['axeTemp'][0] . ',' . $resproSections['axeTemp'][1] . ')' . '(' . $resproSections['prodchartDimensionSymbol'] .')</h3>';
                        } else if ($resproSections['selectedAxe'] == 2) {
                            PDF::Bookmark('Values - Dimension' . $resproSections['selectedAxe'] . '(' . $resproSections['axeTemp'][0] . ',*,' . $resproSections['axeTemp'][1] . ')' . '(' . $resproSections['prodchartDimensionSymbol'] . ')' , 2, 0, '', 'I', array(0,128,0));
                            // PDF::Cell(0, 10, '' , 0, 1, 'L');
                            $html .='<h3> Values - Dimension'. $resproSections['selectedAxe'] . '(' . $resproSections['axeTemp'][0] . ',*,' . $resproSections['axeTemp'][1] . ')' . '(' . $resproSections['prodchartDimensionSymbol'] .')</h3>';
                        } else if ($resproSections['selectedAxe'] == 3) {
                            PDF::Bookmark('Values - Dimension' . $resproSections['selectedAxe'] . '(' . $resproSections['axeTemp'][0] . ',' . $resproSections['axeTemp'][1] . ',*' . ')' . '(' . $resproSections['prodchartDimensionSymbol'] . ')' , 2, 0, '', 'I', array(0,128,0));
                            // PDF::Cell(0, 10, '', 0, 1, 'L');
                            $html .= '<h3> Values - Dimension'. $resproSections['selectedAxe'] . '(' . $resproSections['axeTemp'][0] . ',' . $resproSections['axeTemp'][1] . ',*' . ')' . '(' . $resproSections['prodchartDimensionSymbol'] .')</h3>';
                        }
                        $html .='
                        <div class="values-dim2">
                            <div class="table table-bordered">
                                <table border="0.5" cellpadding="5">
                                    <tr>
                                        <th align="center">Node number</th>
                                        <th align="center">Position Axis 1  ( '. $resproSections['prodchartDimensionSymbol'] .' ) </th>';
                                        $i = 0;
                                        foreach ($resproSections['resultLabel'] as $labelTemp) { 
                                            $html .='<th align="center">T° at '. $labelTemp .' '. $resproSections['timeSymbol'] .' ( '. $resproSections['temperatureSymbol'] .' ) </th>';
                                            $i++;
                                        }
                                        $html .='</tr>';
                                    foreach ($resproSections['result']['resultValue'] as $key=> $node) {
                                    $html .='<tr>
                                        <td align="center"> '. $key .'</td>
                                        <td align="center"> '. $resproSections['result']['mesAxis'][$key] .'</td>';
                                        $i = 0;
                                        foreach ($node as $dbchart) { 
                                            if ($i < 20) $html .='<td align="center"> '. $dbchart .' </td>';
                                            $i++;
                                        }
                                    $html .='</tr>';
                                    }
                                $html .='</table>
                            </div>
                        </div>';
                        PDF::writeHTML($html, true, false, true, false, '');
                    }
                    if ($ISOCHRONE_G == 1) {
                        if ($resproSections['selectedAxe'] == 1) {
                            PDF::Bookmark('Graphic - Dimension' . $resproSections['selectedAxe'] . '(' . '*,' . $resproSections['axeTemp'][0] . ',' . $resproSections['axeTemp'][1] . ')' . '(' . $resproSections['prodchartDimensionSymbol'] . ')' , 2, 0, '', 'I', array(0,128,0));
                            // PDF::Cell(0, 10, '', 0, 1, 'L');
                            $html ='<h3> Graphic - Dimension'. $resproSections['selectedAxe'] . '(' . '*,' . $resproSections['axeTemp'][0] . ',' . $resproSections['axeTemp'][1] . ')' . '(' . $resproSections['prodchartDimensionSymbol'] .')</h3>
                            <div align="center">
                            <img width="640" height="450" src="'. $public_path .'/productSection/'. $study['USERNAM'] .'/'. $resproSections['idStudyEquipment'] .'-'. $resproSections['selectedAxe'] .'.png"></div>';
                            PDF::writeHTML($html, true, false, true, false, '');
                        } else if ($resproSections['selectedAxe'] == 2) {
                            PDF::Bookmark('Graphic - Dimension' . $resproSections['selectedAxe'] . '(' . $resproSections['axeTemp'][0] . ',*,' . $resproSections['axeTemp'][1] . ')' . '(' . $resproSections['prodchartDimensionSymbol'] . ')' , 2, 0, '', 'I', array(0,128,0));
                            // PDF::Cell(0, 10, '', 0, 1, 'L');
                            $html ='<h3> Graphic - Dimension'. $resproSections['selectedAxe'] . '(' . $resproSections['axeTemp'][0] . ',*,' . $resproSections['axeTemp'][1] . ')' . '(' . $resproSections['prodchartDimensionSymbol'] .')</h3>
                            <div align="center">
                            <img width="640" height="450" src="'. $public_path .'/productSection/'. $study['USERNAM'] .'/'. $resproSections['idStudyEquipment'] .'-'. $resproSections['selectedAxe'] .'.png"></div>';
                            PDF::writeHTML($html, true, false, true, false, '');
                        } else if ($resproSections['selectedAxe'] == 3) {
                            PDF::Bookmark('Graphic - Dimension' . $resproSections['selectedAxe'] . '(' . $resproSections['axeTemp'][0] . ',' . $resproSections['axeTemp'][1] . ',*' . ')' . '(' . $resproSections['prodchartDimensionSymbol'] . ')' , 2, 0, '', 'I', array(0,128,0));
                            // PDF::Cell(0, 10, '', 0, 1, 'L');
                            $html ='<h3> Graphic - Dimension'. $resproSections['selectedAxe'] . '(' . $resproSections['axeTemp'][0] . ',' . $resproSections['axeTemp'][1] . ',*' . ')' . '(' . $resproSections['prodchartDimensionSymbol'] .')</h3>
                            <div align="center">
                            <img width="640" height="450" src="'. $public_path .'/productSection/'. $study['USERNAM'] .'/'. $resproSections['idStudyEquipment'] .'-'. $resproSections['selectedAxe'] .'.png"></div>';
                            PDF::writeHTML($html, true, false, true, false, '');
                        }
                    }
                }
                PDF::AddPage();
            }
        }

        if ($ISOVALUE_V == 1 || $ISOVALUE_G == 1) {
            if (!empty($timeBase)) {
                PDF::SetFont('helvetica', 'B', 16);
                PDF::Bookmark('PRODUCT GRAPH - TIME BASED', 0, 0, '', 'B', array(0,64,128));
                PDF::SetFillColor(38, 142, 226);
                PDF::SetTextColor(0,0,0);
                $content ='Product Graph - Time Based';
                PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
                PDF::SetFont('helvetica', '', 10);
                $html='';
                foreach($timeBase as $timeBases) {
                    $html = '';
                    PDF::Bookmark($timeBases['equipName'] , 1, 0, '', '', array(128,0,0));
                    // PDF::Cell(0, 10, '', 0, 1, 'L');
                    $html .='<h3>'. $timeBases['equipName'] .'</h3>';
                    if ($ISOVALUE_V == 1) {
                        $html .='
                        <h3>Values</h3>
                        <div class="values-graphic"> 
                            <div class="table table-bordered">
                            <table border="0.5" cellpadding="5">
                                <tr>
                                    <th align="center">Points</th>
                                    <th align="center">('. $timeBases['timeSymbol'] .')</th>';
                                    foreach ($timeBases['result'] as $points) {
                                        $html .='<th align="center"> '. $points['points'] .'</th>';
                                    }
                                    $html .='</tr>
                                <tr>
                                    <td align="center"> "Top" . ( '. $timeBases['label']['top'] .' ) </td>
                                    <td align="center">( '. $timeBases['temperatureSymbol'] .' )</td>';
                                    foreach ($timeBases['result'] as $tops) {
                                        $html .='<td align="center"> '. $tops['top'] .'</td>';
                                    }
                                    $html .='</tr>
                                <tr>
                                    <td align="center"> "Internal" . ( '. $timeBases['label']['int'] .' )</td>
                                    <td align="center">( '. $timeBases['temperatureSymbol'] .' )</td>';
                                    foreach ($timeBases['result'] as $internals) {
                                        $html .='<td align="center"> '. $internals['int'] .'</td>';
                                    }
                                    $html .='</tr>
                                <tr>
                                    <td align="center"> "Bottom" . ( '. $timeBases['label']['bot'] .' )</td>
                                    <td align="center">( '. $timeBases['temperatureSymbol'] .' )</td>';
                                    foreach ($timeBases['result'] as $bottoms) {
                                        $html .='<td align="center"> '. $bottoms['bot'] .'</td>';
                                    }
                                    $html .='</tr>
                                <tr>
                                    <td align="center">Avg. Temp.</td>
                                    <td align="center">( '. $timeBases['temperatureSymbol'] .' )</td>';
                                    foreach ($timeBases['result'] as $avgs) {
                                        $html .='<td align="center"> '. $avgs['average'] .'</td>';
                                    }
                                    $html .='</tr>
                            </table>
                            </div>
                        </div>';
                        PDF::writeHTML($html, true, false, true, false, '');
                    }
                    if ($ISOVALUE_G == 1) {
                        $html ='<h3>Graphic</h3>
                        <div align="center">
                            <img width="640" height="450" src="'. $public_path .'/timeBased/'.$study['USERNAM'] .'/'.$timeBases['idStudyEquipment'].'.png"></div>';
                        PDF::writeHTML($html, true, false, true, false, '');
                    }
                }
                PDF::AddPage();
            }
        }
        
        if ($CONTOUR2D_G == 1) {
            if (!empty($pro2Dchart)) {
                PDF::SetFont('helvetica', 'B', 16);
                PDF::Bookmark('2D OUTLINES', 0, 0, '', 'B', array(0,64,128));
                PDF::SetFillColor(38, 142, 226);
                PDF::SetTextColor(0,0,0);
                $content ='2D Outlines';
                PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
                PDF::SetFont('helvetica', '', 10);
                $html='';
                $html = '<h3 style ="background-color:#268EE2">2D Outlines</h3>';
                foreach ($pro2Dchart as $key => $pro2Dcharts) {
                    $html = '';
                    if ($shapeCode < 10) {
                        if ($shapeCode == 2 || $shapeCode == 9) {
                            if ($equipData[$key]['ORIENTATION'] == 1) {
                                PDF::Bookmark($pro2Dcharts['equipName'] . ' Slice 23 @' . $pro2Dcharts['lfDwellingTime'] . '(' . $symbol['timeSymbol'] . ')' , 1, 0, '', '', array(128,0,0));
                                PDF::Cell(0, 10, '' , 0, 1, 'L');
                                $html .='<h3>'. $pro2Dcharts['equipName'] . ' Slice 23 @' . $pro2Dcharts['lfDwellingTime'] . '(' . $symbol['timeSymbol'].')</h3>';
                                $html .= '
                                <div align="center"> 
                                    <img width="640" height="450" src="'. $public_path.'/heatmap/'.$study['USERNAM'].'/'.$pro2Dcharts['idStudyEquipment'].'/'. $pro2Dcharts['lfDwellingTime'].'-'.$pro2Dcharts['chartTempInterval'][0].'-'. $pro2Dcharts['chartTempInterval'][1].'-'.$pro2Dcharts['chartTempInterval'][2].'.png">
                                </div>';
                                PDF::writeHTML($html, true, false, true, false, '');
                            } else {
                                PDF::Bookmark($pro2Dcharts['equipName'] . ' Slice 12 @' . $pro2Dcharts['lfDwellingTime'] . '(' . $symbol['timeSymbol'] . ')' , 1, 0, '', '', array(128,0,0));
                                // PDF::Cell(0, 10, '', 0, 1, 'L');
                                $html .='<h3>'. $pro2Dcharts['equipName'] . ' Slice 12 @' . $pro2Dcharts['lfDwellingTime'] . '(' . $symbol['timeSymbol'].')</h3>';
                                $html .= '
                                <div align="center">
                                <img width="640" height="450" src="'. $public_path.'/heatmap/'.$study['USERNAM'].'/'.$pro2Dcharts['idStudyEquipment'].'/'. $pro2Dcharts['lfDwellingTime'].'-'.$pro2Dcharts['chartTempInterval'][0].'-'. $pro2Dcharts['chartTempInterval'][1].'-'.$pro2Dcharts['chartTempInterval'][2].'.png">
                                </div>';
                                PDF::writeHTML($html, true, false, true, false, '');
                            }
                        } else if ($shapeCode != 1 || $shapeCode != 6) {
                            PDF::Bookmark($pro2Dcharts['equipName'] . ' Slice 12 @' . $pro2Dcharts['lfDwellingTime'] . '(' . $symbol['timeSymbol'] . ')' , 1, 0, '', '', array(128,0,0));
                            // PDF::Cell(0, 10, '', 0, 1, 'L');
                            $html .='<h3>'. $pro2Dcharts['equipName'] . ' Slice 12 @' . $pro2Dcharts['lfDwellingTime'] . '(' . $symbol['timeSymbol'].')</h3>';
                            $html .= '
                            <div align="center">
                            <img width="640" height="450" src="'. $public_path.'/heatmap/'.$study['USERNAM'].'/'.$pro2Dcharts['idStudyEquipment'].'/'. $pro2Dcharts['lfDwellingTime'].'-'.$pro2Dcharts['chartTempInterval'][0].'-'. $pro2Dcharts['chartTempInterval'][1].'-'.$pro2Dcharts['chartTempInterval'][2].'.png">
                            </div>';
                            PDF::writeHTML($html, true, false, true, false, '');
                        }
                    } else {
                        switch ($pro2Dcharts['selectedPlan']) {
                            case 1:
                                $selectedPlanName = 23;
                                break;

                            case 2:
                                $selectedPlanName = 13;
                                break;

                            case 3:
                                $selectedPlanName = 12;
                                break;
                            
                        }
                       PDF::Bookmark($pro2Dcharts['equipName'] . ' Slice '. $selectedPlanName . ' @' . $pro2Dcharts['lfDwellingTime'] . '(' . $symbol['timeSymbol'] . ')' , 1, 0, '', '', array(128,0,0));
                        // PDF::Cell(0, 10, '', 0, 1, 'L');
                        $html .='<h3>'. $pro2Dcharts['equipName'] . ' Slice '. $selectedPlanName . ' @' . $pro2Dcharts['lfDwellingTime'] . '(' . $symbol['timeSymbol'].')</h3>';
                        $html .= '
                        <div align="center">
                        <img width="640" height="450" src="'. $public_path.'/heatmap/'.$study['USERNAM'].'/'.$pro2Dcharts['idStudyEquipment'].'/'. $pro2Dcharts['lfDwellingTime'].'-'.$pro2Dcharts['chartTempInterval'][0].'-'. $pro2Dcharts['chartTempInterval'][1].'-'.$pro2Dcharts['chartTempInterval'][2].'-'. $pro2Dcharts['selectedPlan'] .'.png">
                        </div>';
                        PDF::writeHTML($html, true, false, true, false, '');
                    }
                }
                PDF::AddPage();
            }
        }

        $html ='';
        PDF::SetFont('helvetica', 'B', 16);
        PDF::Bookmark('COMMENTS ', 0, 0, '', 'B', array(0,64,128));
        PDF::SetFillColor(38, 142, 226);
        PDF::SetTextColor(0,0,0);
        $content ='Comments';
        PDF::Cell(0, 10, $content, 0, 1, 'L', 1, 0);
        PDF::SetFont('helvetica', '', 10);
        $html .= '
        <div class="comment">
             <p>
                <textarea rows="5"> '. $REPORT_COMMENT .' </textarea>
            </p>
        </div>

        <div class="info-writer">
            <div style="text-align:center">
                <p>';
                if (!empty($photoNameUrl) && file_exists($photoNameUrl)) {
                    $html .= '<img src="'. $photoNameUrl.'" style="height:280px">';
                } else {
                    $html .= '<img src="'. $public_path.'/images/globe_food.gif">';
                }
                $html .= '</p>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th colspan="6">Study realized by</th>
                    </tr>
                    <tr>
                        <td colspan="4">Company name</td>
                        <td colspan="2"> '. $WRITER_SURNAME .' </td>
                    </tr>
                    <tr>
                        <td colspan="4">Surname/Name</td>
                        <td colspan="2"> '. $WRITER_NAME .' </td>
                    </tr>
                    <tr>
                        <td colspan="4">Function</td>
                        <td colspan="2"> '. $WRITER_FUNCTION .' </td>
                    </tr>
                    <tr>
                        <td colspan="4">Contact</td>
                        <td colspan="2"> '. $WRITER_COORD .' </td>
                    </tr>
                </table>
            </div>
        </div>';
        PDF::writeHTML($html, true, false, true, false, '');
        
        // add a new page for TOC
        PDF::addTOCPage();
        PDF::setCellMargins(1, 1, 1, 1);

        PDF::MultiCell(0, 0, 'Table Of Content', 0, 'C', 0, 1, '', '', true, 0);
        PDF::Ln();
       
        // add table of content at page 1
        PDF::addTOC(1, 'courier', '.', 'INDEX', 'B', array(128, 0, 0));;
        
        // end of TOC page
        PDF::endTOCPage();
        PDF::Output($public_path . "/reports/" . $study->USERNAM."/" . $name_report, 'F');
        return ["url" => $host . "reports/$study->USERNAM/$name_report?time=". time() .""];
    }
    
    function backgroundGenerationHTML($params)
    {
        set_time_limit(600);
        $id = $params['studyId'];
        $input = $params['input'];
        $DEST_SURNAME = $input['DEST_SURNAME'];
        $DEST_NAME = $input['DEST_NAME'];
        $DEST_FUNCTION = $input['DEST_FUNCTION'];
        $DEST_COORD = $input['DEST_COORD'];
        $PHOTO_PATH = $input['PHOTO_PATH'];
        $CUSTOMER_LOGO = $input['CUSTOMER_LOGO'];
        $REPORT_COMMENT = $input['REPORT_COMMENT'];
        $WRITER_SURNAME = $input['WRITER_SURNAME'];
        $WRITER_NAME = $input['WRITER_NAME'];
        $WRITER_FUNCTION = $input['WRITER_FUNCTION'];
        $WRITER_COORD = $input['WRITER_COORD'];
        $PROD_LIST = $input['PROD_LIST'];
        $PROD_3D = $input['PROD_3D'];
        $EQUIP_LIST = $input['EQUIP_LIST'];
        $REP_CUSTOMER = $input['REP_CUSTOMER'];
        $PACKING = $input['PACKING'];
        $ASSES_ECO = $input['ASSES_ECO'];
        $PIPELINE = $input['PIPELINE'];
        $CONS_OVERALL = $input['CONS_OVERALL'];
        $CONS_TOTAL = $input['CONS_TOTAL'];
        $CONS_SPECIFIC = $input['CONS_SPECIFIC'];
        $CONS_HOUR = $input['CONS_HOUR'];
        $CONS_DAY = $input['CONS_DAY'];
        $CONS_WEEK = $input['CONS_WEEK'];
        $CONS_MONTH = $input['CONS_MONTH'];
        $CONS_YEAR = $input['CONS_YEAR'];
        $CONS_EQUIP = $input['CONS_EQUIP'];
        $CONS_PIPE = $input['CONS_PIPE'];
        $CONS_TANK = $input['CONS_TANK'];
        $REP_CONS_PIE = $input['REP_CONS_PIE'];
        $isSizingValuesChosen = $input['isSizingValuesChosen'];
        $isSizingValuesMax = $input['isSizingValuesMax'];
        $SIZING_GRAPHE = $input['SIZING_GRAPHE'];
        $ENTHALPY_V = $input['ENTHALPY_V'];
        $ENTHALPY_G = $input['ENTHALPY_G'];
        $ENTHALPY_SAMPLE = $input['ENTHALPY_SAMPLE'];
        $ISOCHRONE_V = $input['ISOCHRONE_V'];
        $ISOCHRONE_G = $input['ISOCHRONE_G'];
        $ISOCHRONE_SAMPLE = $input['ISOCHRONE_SAMPLE'];
        $ISOVALUE_V = $input['ISOVALUE_V'];
        $ISOVALUE_G = $input['ISOVALUE_G'];
        $ISOVALUE_SAMPLE = $input['ISOVALUE_SAMPLE'];
        $CONTOUR2D_G = $input['CONTOUR2D_G'];
        $CONTOUR2D_TEMP_MIN = $input['CONTOUR2D_TEMP_MIN'];
        $CONTOUR2D_TEMP_MAX = $input['CONTOUR2D_TEMP_MAX'];
        $CONTOUR2D_TEMP_STEP = $input['CONTOUR2D_TEMP_STEP'];
        $axeTempRecordData = [
            [-1.0, $input['AXE3_Y'], $input['AXE3_Z']],
            [$input['AXE2_X'], -1.0, $input['AXE2_Z']],
            [$input['AXE1_X'], $input['AXE1_Y'], -1.0]
        ];

        $axisTemp = [
            'POINT1_X' => $input['POINT1_X'],
            'POINT1_Y' => $input['POINT1_Y'],
            'POINT1_Z' => $input['POINT1_Z'],
            'POINT2_X' => $input['POINT2_X'],
            'POINT2_Y' => $input['POINT2_Y'],
            'POINT2_Z' => $input['POINT2_Z'],
            'POINT3_X' => $input['POINT3_X'],
            'POINT3_Y' => $input['POINT3_Y'],
            'POINT3_Z' => $input['POINT3_Z'],
        ];

        $study = Study::find($id);
        $host = getenv('APP_URL');
        $checkStuname = str_replace(' ', '', $study->STUDY_NAME);

        $public_path = rtrim(app()->basePath("public/"), '/');

        $name_report = "$study->ID_STUDY-".preg_replace('/[^A-Za-z0-9\-]/', '', $checkStuname)."-Report.html";
        $progressFile = $public_path. "/reports/" . $study->USERNAM. "/" ."$study->ID_STUDY-".preg_replace('/[^A-Za-z0-9\-]/', '', $checkStuname)."-Report.progess";

        if (!is_dir( $public_path. "/reports/"  . $study->USERNAM)) {
            mkdir( $public_path. "/reports/" . $study->USERNAM, 0777, true);
        }

        if (file_exists($public_path . "/reports/" . $study->USERNAM."/" . $name_report)) {
            @unlink($public_path . "/reports/" . $study->USERNAM."/" . $name_report);
        }
        
        $progress = "";
        $production = Production::Where('ID_STUDY', $id)->first();

        if ($REP_CUSTOMER == 1) {
            $progress .= "Production";
            $this->writeProgressFile($progressFile, $progress);
        }
        
        $product = Product::Where('ID_STUDY', $id)->first();
        $meshView = $this->reportserv->getMeshView($product);

        $products = ProductElmt::where('ID_PROD', $product->ID_PROD)->orderBy('SHAPE_POS2', 'DESC')->get();


        $specificDimension = 0.0;
        $count = count($products);
        foreach ($products as $key => $pr) {
            $elements[] = $pr;
            if ($pr->ID_SHAPE == $this->values->SPHERE || $pr->ID_SHAPE == $this->values->CYLINDER_CONCENTRIC_STANDING || $pr->ID_SHAPE == $this->values->CYLINDER_CONCENTRIC_LAYING || $pr->ID_SHAPE == $this->values->PARALLELEPIPED_BREADED) {
                if ($key < $count - 1) {
                    $specificDimension += $pr->SHAPE_PARAM2 * 2;
                } else {
                    $specificDimension += $pr->SHAPE_PARAM2;
                }
            } else {
                $specificDimension += $pr->SHAPE_PARAM2;
            }
        }
        $specificDimension = $this->convert->prodDimension($specificDimension);
        $proElmt = ProductElmt::Where('ID_PROD', $product->ID_PROD)->first();
        foreach ($study->studyEquipments as $sequip) {
            $layout = $this->stdeqp->generateLayoutPreview($sequip);
        }

        $stuNameLayout = preg_replace('/[^A-Za-z0-9\-]/', '', $checkStuname);
        $idComArr = [];
        $idElmArr = [];
        $comprelease = [];
        $cryogenPipeline = [];
        $packings = [];
        $calModeHeadBalance = [];
        $calModeHbMax = [];
        $graphicSizing = [];

        $shapeCode = $proElmt->SHAPECODE;

        foreach ($product->productElmts as $productElmt) {
            $idComArr[] = $productElmt->ID_COMP;
            $idElmArr[] = $productElmt->ID_PRODUCT_ELMT;
            $comprelease[] = $productElmt->component->COMP_RELEASE;
        }

        if ($study->packings != null) {
            $packings = $this->reportserv->getStudyPackingLayers($study->ID_STUDY);
            $progress .= "\nPacking data";
        }

        if ($shapeCode <= 9) {
            $shapeName = Translation::where('TRANS_TYPE', 4)->where('ID_TRANSLATION', $shapeCode)->where('CODE_LANGUE', $study->user->CODE_LANGUE)->orderBy('LABEL', 'ASC')->first();
        } else {
            $shapeName = $this->reportserv->getShapeName3D($shapeCode);
        }
        
        $componentName = ProductElmt::select('LABEL','ID_COMP', 'ID_PRODUCT_ELMT', 'PROD_ELMT_ISO', 'PROD_ELMT_NAME', 'PROD_ELMT_REALWEIGHT', 'SHAPE_PARAM2', 'INSERT_LINE_ORDER')
        ->join('TRANSLATION', 'ID_COMP', '=', 'TRANSLATION.ID_TRANSLATION')->whereIn('ID_PRODUCT_ELMT', $idElmArr)
        ->where('TRANS_TYPE', 1)->whereIn('ID_TRANSLATION', $idComArr)
        ->where('CODE_LANGUE', $study->user->CODE_LANGUE)->orderBy('SHAPE_POS2', 'DESC')->get();


        $listParentChaining = $this->reportserv->getParentIdChaining($study->PARENT_ID);

        $arrIdComp = [];
        if (!empty($listParentChaining)) {
            foreach ($listParentChaining as $key => $prChainIdStudy) {
                $arrIdComp[$prChainIdStudy] = [];
                $productPrChain = Product::Where('ID_STUDY', $prChainIdStudy)->first();
                if ($productPrChain) {
                    foreach ($productPrChain->productElmts as $productElmt) {
                        $arrIdComp[$prChainIdStudy][] = $productElmt->ID_COMP;
                    }
                }
            }
        }
        
        $productComps = [];
        foreach ($componentName as $key => $value) {
            $componentStatus = Translation::select('LABEL')->where('TRANS_TYPE', 100)->whereIn('ID_TRANSLATION', $comprelease)->where('CODE_LANGUE', $this->auth->user()->CODE_LANGUE)->orderBy('LABEL', 'ASC')->first();
            $productComps[] = $value;
            $productComps[$key]['display_name'] = $value->LABEL . ' - ' . $productElmt->component->COMP_VERSION . '(' . $componentStatus->LABEL . ' )';
            $productComps[$key]['mass'] = $this->convert->mass($value->PROD_ELMT_REALWEIGHT);
            $productComps[$key]['dim'] = $this->convert->prodDimension($value->SHAPE_PARAM2);
            $studyNumber = [];
            if (!empty($arrIdComp)) {
                foreach ($arrIdComp as $k => $arrId) {
                    if (in_array($value->ID_COMP, $arrId)) {
                        $studyNumber[] = $k;
                    }
                }
            }

            $productComps[$key]['studyNumber'] = count($arrIdComp) - count($studyNumber) + 1;
        }

        if ($PROD_LIST == 1) {
            $progress .= "\nProduct";
            $this->writeProgressFile($progressFile, $progress);
        }

        
        $equipData = $this->stdeqp->findStudyEquipmentsByStudy($study);
        if (count($equipData) > 0) {
            foreach ($equipData as $key => $equip) {
                $equipData[$key]['hasLayout'] = $this->equip->getCapability($equip['CAPABILITIES'], 8192);
            }
        }

        if ($EQUIP_LIST == 1) {
            $progress .= "\nEquipment";
            $this->writeProgressFile($progressFile, $progress);
        }
        
        $symbol = $this->reportserv->getSymbol($study->ID_STUDY);
        $infoReport = $study->reports;

        if ($PIPELINE == 1) {
            if ($study->OPTION_CRYOPIPELINE == 1) {
                $cryogenPipeline = $this->pipelines->loadPipeline($study->ID_STUDY, ['report' => true]);
                $progress .= "\nPipeline Elements";
                $this->writeProgressFile($progressFile, $progress);
            }
        }
        
        $consumptions = $this->reportserv->getAnalyticalConsumption($study->ID_STUDY);
        $economic = [];        
        $economic = $this->reportserv->getAnalyticalEconomic($study->ID_STUDY);

        if ($CONS_OVERALL == 1 || $CONS_TOTAL ==1 || $CONS_SPECIFIC  == 1 || $CONS_HOUR ==1 || $CONS_DAY == 1||
            $CONS_WEEK == 1 || $CONS_MONTH == 1 || $CONS_YEAR ==1 || $CONS_EQUIP ==1 || $CONS_PIPE == 1 || $CONS_TANK ==1) {
            $progress .= "\nConsumption Results";
            $this->writeProgressFile($progressFile, $progress);
        }
        
        if ($isSizingValuesChosen == 1 || $isSizingValuesMax == 16 || $SIZING_GRAPHE == 1) {
            if ($study->CALCULATION_MODE == 3) {
                $calModeHeadBalance = $this->reportserv->getOptimumHeadBalance($study->ID_STUDY);
                $calModeHbMax = $this->reportserv->getOptimumHeadBalanceMax($study->ID_STUDY);
                $graphicSizing = $this->reportserv->sizingOptimumResult($study->ID_STUDY);
            } else if ($study->CALCULATION_MODE == 1) {
                $calModeHeadBalance = $this->reportserv->getEstimationHeadBalance($study->ID_STUDY, 1);
                $graphicSizing = $this->reportserv->sizingEstimationResult($study->ID_STUDY);
            }
            $progress .= "\nHeat balance";
            $progress .= "\nSizing";
            $this->writeProgressFile($progressFile, $progress);
        }

        if ($REP_CONS_PIE == 1) {
            $progress .="\nConsumption pies";
            if (!empty($consumptions)) {
                foreach ($consumptions as $consumption) {
                    $idStudyEquipment = $consumption['id'];

                    $percentProduct = $consumption['percentProduct'];
                    $percentEquipmentPerm = $consumption['percentEquipmentPerm'];
                    $percentEquipmentDown = $consumption['percentEquipmentDown'];
                    $percentLine = $consumption['percentLine'];
                    if ($percentProduct != 0 && $percentEquipmentPerm != 0  && $percentEquipmentDown != 0) {
                        $percentProductLabel = 'Product';
                        $percentEquipmentPermLabel = 'Equipment(permanent)';
                        $percentEquipmentDownLabel = 'Equipment(cool down)';
                        $percentLineLabel = 'Line';
                        $f = fopen("/tmp/consumptionPie.inp", "w");
                        fputs($f, 'name percent' . "\n");
                        fputs($f, '"'. $percentProductLabel .'" '. $percentProduct .'' . "\n");
                        fputs($f, '"'. $percentEquipmentPermLabel .'" '. $percentEquipmentPerm .'' . "\n");
                        fputs($f, '"'. $percentEquipmentDownLabel .'" '. $percentEquipmentDown .'' . "\n");
                        if ($percentLine > 0) {
                            fputs($f, '"'. $percentLineLabel .'" '. $percentLine .'' . "\n");
                        }
                        
                        fclose($f);

                        $folder = $this->output->public_path('consumption');

                        $userName = $study->USERNAM;
                        $idStudy = $study->ID_STUDY;
                        if (!is_dir($folder)) {
                            mkdir($folder, 0777);
                        }

                        if (!is_dir($folder . '/' . $userName)) {
                            mkdir($folder . '/' . $userName, 0777);
                        }

                        if (!is_dir($folder . '/' . $userName . '/' . $idStudy)) {
                            mkdir($folder . '/' . $userName . '/' . $idStudy, 0777);
                        }

                        $outPutFolder = $folder . '/' . $userName . '/' . $idStudy;
                        $outPutFileName = $idStudyEquipment;
                        
                        system('gnuplot -c '. $this->plotFolder .'/consumptions.plot "/tmp/consumptionPie.inp" "'. $outPutFolder . '" "'. $outPutFileName .'" ');
                    }
                }
            }
            
            $this->writeProgressFile($progressFile, $progress);
        }
        
        $proInfoStudy = $this->reportserv->getProInfoStudy($study->ID_STUDY);
        $proSections = [];
        $pro2Dchart = [];
        $heatexchange = [];
        $timeBase = [];
     
        foreach ($study->studyEquipments as $key=> $idstudyequips) {
            if ($idstudyequips->BRAIN_TYPE == 4) {
                if ($ENTHALPY_V == 1 || $ENTHALPY_G == 1) {
                    $heatexchange[] = $this->reportserv->heatExchange($ENTHALPY_SAMPLE, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS);
                    $progress .= "\nEnthalpies";
                    $this->writeProgressFile($progressFile, $progress);
                } 

                if ($ISOVALUE_V == 1 || $ISOVALUE_G == 1) {
                    $timeBase[] = $this->reportserv->timeBased($ISOVALUE_SAMPLE, $axisTemp, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS);
                    $progress .= "\nTime Based";
                    $this->writeProgressFile($progressFile, $progress);
                }
                
                switch ($shapeCode) {
                    case 1:
                        if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                            $progress .= "\nProduct Section";
                            $this->writeProgressFile($progressFile, $progress);
                        }
                        break;


                    case 2:
                        if ($equipData[$key]['ORIENTATION'] == 1) {
                            if ($CONTOUR2D_G == 1) {
                                $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                                $progress .= "\nContour";
                                $this->writeProgressFile($progressFile, $progress);
                            } 

                            if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                                $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                                $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3);
                                $progress .= "\nProduct Section";
                                $this->writeProgressFile($progressFile, $progress);
                            }
                        } else {
                            if ($CONTOUR2D_G == 1) {
                                $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                                $progress .= "\nContour";
                                $this->writeProgressFile($progressFile, $progress);
                            }

                            if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                                $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                                $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                                $progress .= "\nProduct Section";
                                $this->writeProgressFile($progressFile, $progress);
                            }
                        }
                        break;

                    case 4:
                    case 5:
                    case 7:
                    case 8:
                        if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                            $progress .= "\nProduct Section";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        if ($CONTOUR2D_G == 1) {
                            $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                            $progress .= "\nContour";
                            $this->writeProgressFile($progressFile, $progress);
                        }
                        break;

                    case 6:
                        if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                            $progress .= "\nProduct Section";
                            $this->writeProgressFile($progressFile, $progress);
                        }
                        break;

                    case 9:
                        if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3);
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                            $progress .= "\nProduct Section";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        if ($equipData[$key]['ORIENTATION'] == 1) {
                            if ($CONTOUR2D_G == 1) {
                                $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                                $progress .= "\nContour";
                                $this->writeProgressFile($progressFile, $progress);
                            }
                        } else {
                            if ($CONTOUR2D_G == 1) {
                                $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                                $progress .= "\nContour";
                                $this->writeProgressFile($progressFile, $progress);
                            }
                        }

                        break;

                    case 3: 
                        if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                            $progress .= "\nProduct Section";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        if ($CONTOUR2D_G == 1) {
                            $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                            $progress .= "\nContour";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        break;

                    case CYLINDER_STANDING_3D:
                    case CYLINDER_LAYING_3D:
                    case CYLINDER_CONCENTRIC_STANDING_3D:
                    case CYLINDER_CONCENTRIC_LAYING_3D:
                    case OVAL_STANDING_3D:
                    case OVAL_LAYING_3D:
                        if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3);
                            $progress .= "\nProduct Section";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        if ($CONTOUR2D_G == 1) {
                            $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                            $progress .= "\nContour";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        break;

                    default:
                        if ($ISOCHRONE_V == 1 || $ISOCHRONE_G == 1) {
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1);
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2);
                            $proSections[] = $this->reportserv->productSection($ISOCHRONE_SAMPLE, $axeTempRecordData, $study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3);
                            $progress .= "\nProduct Section";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        if ($CONTOUR2D_G == 1) {
                            $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 1, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                            $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 2, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                            $pro2Dchart[] = $this->reportserv->productChart2DStatic($study->ID_STUDY, $idstudyequips->ID_STUDY_EQUIPMENTS, 3, $CONTOUR2D_TEMP_STEP, $CONTOUR2D_TEMP_MIN, $CONTOUR2D_TEMP_MAX);
                            $progress .= "\nContour";
                            $this->writeProgressFile($progressFile, $progress);
                        }

                        break;
                }
            } 
        }

        $progress .= "\nReport translation";
        $progress .= "\nFINISH";
        $this->writeProgressFile($progressFile, $progress);

        $chainingStudies = $this->reportserv->getChainingStudy($id);

        $myfile = fopen( $public_path. "/reports/" . "/" . $study->USERNAM."/" . $name_report, "w") or die("Unable to open file!");
        $html = $this->viewHtml($study ,$production, $product, $proElmt, $shapeName, 
        $productComps, $equipData, $cryogenPipeline, $consumptions, $proInfoStudy,
        $calModeHbMax, $calModeHeadBalance, $heatexchange, $proSections, $timeBase, 
        $symbol, $host, $pro2Dchart, $params, $shapeCode, $economic, $stuNameLayout, $specificDimension, $chainingStudies, $meshView);
        fwrite($myfile, $html);
        fclose($myfile);
        $url = ["url" => $host . "reports/$study->USERNAM/$name_report?time=". time() .""];
        return $url;
    }

    function _downLoadPDF($studyId)
    {
        $input = $this->request->all();
        $params['studyId'] = $studyId;
        $params['input'] = $input;
        ignore_user_abort(true);
        set_time_limit(600);
        $bgProcess = function($obj, $fn, $id) {
            flush();
            call_user_func_array([$obj, $fn], [$id]);
        };
        register_shutdown_function($bgProcess, $this, 'backgroundGenerationPDF', $params);
        header('Connection: close');
        header('Content-length: 19');
        header('Content-type: application/json');
        
        exit("{'processing':true}");
        return ['processing' => true];        
    }

    function downLoadPDF($studyId)
    {
        $input = $this->request->all();
        $params['studyId'] = $studyId;
        $params['input'] = $input;
        set_time_limit(600);
        $data = $this->backgroundGenerationPDF($params);

        return $data;
    }
    
    public function _downLoadHtmlToPDF($studyId)
    {
        $input = $this->request->all();
        $params['studyId'] = $studyId;
        $params['input'] = $input;
        if ($input['countRunInterval'] == 0) {
            ignore_user_abort(true);
            set_time_limit(600);
            $bgProcess = function($obj, $fn, $id) {
                // ob_flush();
                flush();
                call_user_func_array([$obj, $fn], [$id]);
            };
            register_shutdown_function($bgProcess, $this, 'backgroundGenerationHTML', $params);
            header('Connection: close');
            header('Content-length: 19');
            // header('Access-Control-Allow-Origin: *'); 
            header('Content-type: application/json');
            
            exit("{'processing':true}");
            return ['processing' => true]; 
        } else {
            return 1;
        }
    }

    public function downLoadHtmlToPDF($studyId)
    {
        $input = $this->request->all();
        $params['studyId'] = $studyId;
        $params['input'] = $input;
        
        set_time_limit(600);
        $data = $this->backgroundGenerationHTML($params);

        return $data;
    }

    public function viewHtml($study ,$production, $product, $proElmt, $shapeName, 
    $productComps, $equipData, $cryogenPipeline, $consumptions, $proInfoStudy,
    $calModeHbMax, $calModeHeadBalance, $heatexchange, $proSections, $timeBase , 
    $symbol, $host, $pro2Dchart, $params, $shapeCode, $economic, $stuNameLayout, $specificDimension, $chainingStudies, $meshView)
    {
        $arrayParam = [
            'study' => $study,
            'production' => $production,
            'productionINTL' => $this->convert->prodTemperature($production->AVG_T_INITIAL),
            'product' => $product,
            'productRealW' =>  $this->convert->mass($product->PROD_REALWEIGHT),
            'proElmt' => $proElmt,
            'proElmtParam1' => $this->convert->prodDimension($proElmt->SHAPE_PARAM1),
            'proElmtParam2' => $specificDimension,
            'proElmtParam3' => $this->convert->prodDimension($proElmt->SHAPE_PARAM3),
            'shapeName' => $shapeName,
            'proInfoStudy' => $proInfoStudy,
            'symbol' => $symbol,
            'host' => $host,
            'params' => $params['input'],
            'shapeCode' => $shapeCode,
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
            'economic' => $economic,
            'stuNameLayout' => $stuNameLayout,
            'chainingStudies' => $chainingStudies,
            'meshView' => $meshView
        ];
        return view('report.viewHtmlToPDF', $param);
    }

    function processingReport($id)
    {
        $study = Study::find($id);

        $public_path = rtrim(app()->basePath("public"), '/');
        $checkStuname = str_replace(' ', '', $study->STUDY_NAME);

        $fileName = preg_replace('/[^A-Za-z0-9\-]/', '', $checkStuname);
        $progressFile = "$study->ID_STUDY-" . $fileName . "-Report.progess";

        $progressFileHtmlPath = $public_path . '/report/' . $study->USERNAM . '/' . $study->ID_STUDY . '-' . $fileName . '-Report.html';

        $progressFilePdfPath = $public_path . '/report/' . $study->USERNAM . '/' . $study->ID_STUDY . '-' . $fileName . '-Report.pdf';

        $progressFileHtml = getenv('APP_URL') . '/reports/' . $study->USERNAM . '/' . $study->ID_STUDY . '-' . $fileName . '-Report.html?time=' . time();
        $progressFilePdf = getenv('APP_URL') . '/reports/' . $study->USERNAM . '/' . $study->ID_STUDY . '-' . $fileName . '-Report.pdf?time=' . time();

        $progressfilePath = $public_path . "/reports/" . $study->USERNAM . "/" . $progressFile;
        
        $progress = [];
        if (file_exists($progressfilePath)) {
            $file = file_get_contents($progressfilePath);
            $progress = explode("\n", $file);
        }
        
        return compact('progressFileHtml', 'progressFilePdf', 'progress');
    }

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

        $url = getenv('APP_URL') . '/uploads/'.$media->filename.'.'.$media->extension;
        
        return $url;
    }
}