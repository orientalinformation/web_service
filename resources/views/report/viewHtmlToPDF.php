<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>View Report</title>

    <!-- Bootstrap -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <style type="text/css">
        body{font-size: 0.95rem}
        .block-title {
            background: #268EE2;line-height: 45px;color: #fff;padding: 0 15px;font-size: 20px;text-transform: uppercase;font-weight: 600;margin-bottom: 15px
        }
        .bold {font-weight: bold}
        .layout{padding-bottom: 15px}
        img {max-width: 100%}
        .table td, .table th{padding: .55rem;font-size: 13px;vertical-align: middle;}
    </style>
    <body>
    <div class ="box-report-frame container-fluid">
        <div class="logo">
            <div class="row">
                <div class="col-md-6">
                    <?php if (!empty($arrayParam['params']['CUSTOMER_LOGO'])) { ?>
                    <p>
                        <img src="<?php echo $arrayParam['params']['CUSTOMER_LOGO']?>" style="max-height: 50px">
                    </p>
                    <?php } ?>
                </div>
                <div class="col-md-6">
                    <div class="pull-right text-right">
                        <img src="<?php echo  $arrayParam['host'] . "images/logo_cryosoft.png"?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="info-company">
            <div class="text-center">
                <img src="<?php echo  $arrayParam['host'] . "images/banner_cryosoft.png"?>">
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" border="1">
                    <tr>
                        <th colspan="6">Customer</th>
                    </tr>
                    <tr>
                        <td colspan="4">Company name</td>
                        <td colspan="2"> <?php echo $arrayParam['params']['DEST_SURNAME'] ?> </td>
                    </tr>
                    <tr>
                        <td colspan="4">Surname / Name</td>
                        <td colspan="2"><?php echo $arrayParam['params']['DEST_NAME'] ?></td>
                    </tr>
                    <tr>
                        <td colspan="4">Function</td>
                        <td colspan="2"><?php echo $arrayParam['params']['DEST_FUNCTION'] ?></td>
                    </tr>
                    <tr>
                        <td colspan="4">Contact</td>
                        <td colspan="2"> <?php echo $arrayParam['params']['DEST_COORD'] ?></td>
                    </tr>
                    <tr>
                        <td colspan="4">Date of the redivort generation</td>
                        <td colspan="2"><?php echo date("d/m/Y") ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="text-center">
                <p>
                    <img src="<?php echo !empty($arrayParam['params']['PHOTO_PATH']) ? $arrayParam['params']['PHOTO_PATH'] : $arrayParam['host'] . "images/globe_food.gif"?>" style="max-height: 280px">
                </p>
            </div>
            <div class="table-responsive">
                <table class ="table table-bordered table-hover" border="1">
                <tr>
                    <th class="text-center" colspan="3"><h5>Study of the product: <b style="color:#f00"><?php echo $arrayParam['study']['STUDY_NAME'] ?></b></h5></th>
                </tr>
                <tr>
                    <td >Calculation mode :</td>
                    <td class="text-center" colspan="2"><?php echo $arrayParam['study']['CALCULATION_MODE'] == 3 ? "Optimum equipment" : "Estimation" ?></td>
                </tr>
                <tr>
                    <td >Economic :</td>
                    <td class="text-center" colspan="2"><?php echo $arrayParam['study']['OPTION_ECO'] == 1 ? "YES" : "NO" ?></td>
                </tr>
                <tr>
                    <td >Cryogenic Pipeline :</td>
                    <td class="text-center" colspan="2"><?php echo ($arrayParam['study']['OPTION_CRYOPIPELINE'] != null && !($arrayParam['study']['OPTION_CRYOPIPELINE'] == 0)) ? "YES" : "NO" ?></td>
                </tr>
                <?php if ($arrayParam['study']['CHAINING_CONTROLS'] == 1){ ?>
                <tr>
                    <td >Chaining :</td>
                    <td class="text-center">YES</td>
                    <td class="text-center"><?php echo (($arrayParam['study']['HAS_CHILD'] != 0) && ($arrayParam['study']['PARENT_ID'] != 0)) ? "This study is a child" : "" ?></td>
                </tr>
                <?php } ?>
                </table>
            </div>
        </div>
        <?php if (!empty($chainingStudies)) { ?>
        <?php if (($arrayParam['study']['CHAINING_CONTROLS'] == 1) && ($arrayParam['study']['PARENT_ID'] != 0)) { ?>
        <div class="block-title">Chaining synthesis</div>
        <div class="table-responsive">
            <table class ="table table-bordered table-hover table-striped">
                <tr>
                    <th colspan="2">Study Name</th>
                    <th colspan="2">Equipment</th>
                    <th>Control temperature (C)</th>
                    <th>Residence/ Dwell time (s)</th>
                    <th>Convection Setting (Hz)</th>
                    <th>Initial Average Product tempeture (C) </th>
                    <th>Final Average Product temperature (C)</th>
                    <th>Product Heat Load (kj/kg)</th>
                </tr>
                <?php foreach($chainingStudies as $key => $resoptHeads) { ?>
                    <tr>
                        <td colspan="2" class="text-center"><?php echo $resoptHeads['stuName'] ?></td>
                        <td colspan="2" class="text-center"><?php echo $resoptHeads['equipName'] ?></td>
                        <td class="text-center"><?php echo $resoptHeads['tr'] ?></td>
                        <td class="text-center"><?php echo $resoptHeads['ts'] ?></td>
                        <td class="text-center"><?php echo $resoptHeads['vc'] ?></td>
                        <td class="text-center"><?php echo $resoptHeads['proInfoStudy']['avgTInitial'] ?></td>
                        <td class="text-center"><?php echo $resoptHeads['tfp'] ?></td>
                        <td class="text-center"><?php echo $resoptHeads['vep'] ?></td>
                    </tr>
                <?php }?>
            </table>
        </div>
        <?php } ?>
        <?php } ?>

        
        <?php if ($arrayParam['params']['REP_CUSTOMER'] == 1) { ?>                 
        <div class="production">
            <div class="block-title">Production Data</div>
            <div class="table table-responsive">
                <table class ="table table-bordered table-hover table-striped" border="1">
                <tr>
                    <th>Daily production</th>
                    <th><?php echo $arrayParam['production']->DAILY_PROD ?></th>
                    <th>Hours/Day</th>
                </tr>
                <tr>
                    <td>Weekly production</td>
                    <td><?php echo $arrayParam['production']->WEEKLY_PROD ?></td>
                    <td>Days/Week</td>
                </tr>
                <tr style="height: 10px;">
                    <td>Annual production</td>
                    <td><?php echo $arrayParam['production']->NB_PROD_WEEK_PER_YEAR ?></td>
                    <td>Weeks/Year</td>
                </tr>
                <tr>
                    <td>Number of equipment cooldowns</td>
                    <td><?php echo $arrayParam['production']->DAILY_STARTUP ?></td>
                    <td>per day</td>
                </tr>
                <tr>
                    <td>Factory Air temperature</td>
                    <td><?php echo $arrayParam['production']->AMBIENT_TEMP ?></td>
                    <td><?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></td>
                </tr>
                <tr>
                    <td>Relative Humidity of Factory Air</td>
                    <td><?php echo $arrayParam['production']->AMBIENT_HUM ?></td>
                    <td>(%)</td>
                </tr>
                <tr>
                    <td>Required Average temperature</td>
                    <td ><?php echo $arrayParam['production']->AVG_T_DESIRED ?></td>
                    <td><?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></td>
                </tr>
                <tr>
                    <td>Required Production Rate</td>
                    <td ><?php echo $arrayParam['production']->PROD_FLOW_RATE ?></td>
                    <td><?php echo "(" . $arrayParam['symbol']['productFlowSymbol'] . " )" ?></td>
                </tr>
                </table>
            </div>
        </div>
        <?php } ?>

        <?php if ($arrayParam['params']['PROD_LIST'] == 1) { ?>
        <div class="block-title">Product Data</div>
        <h5 class="bold">Composition of the product and its components</h5>
        <div class="pro-data">
            <div class="table-responsive">
                <table class ="table table-bordered table-hover table-striped" border="1">
                    <tr>
                        <th class="text-center">Product name</th>
                        <th class="text-center">Shape</th>
                        <?php 
                            if ($arrayParam['shapeCode'] != SLAB && $arrayParam['shapeCode'] != SPHERE && $arrayParam['shapeCode'] != SPHERE_3D && $arrayParam['shapeCode'] != TRAPEZOID_3D && $arrayParam['shapeCode'] != TRAPEZOID_3D && $arrayParam['shapeCode'] != OVAL_STANDING_3D && $arrayParam['shapeCode'] != OVAL_LAYING_3D) {?>

                            <?php if ($arrayParam['shapeCode'] == PARALLELEPIPED_STANDING || $arrayParam['shapeCode'] == PARALLELEPIPED_BREADED || $arrayParam['shapeCode'] == CYLINDER_CONCENTRIC_LAYING || $arrayParam['shapeCode'] == PARALLELEPIPED_STANDING_3D || $arrayParam['shapeCode'] == CYLINDER_CONCENTRIC_LAYING_3D || $arrayParam['shapeCode'] == PARALLELEPIPED_BREADED_3D){
                                $prodDim1Name = 'Length';
                            } ?>

                            <?php if ($arrayParam['shapeCode'] == CYLINDER_LAYING || $arrayParam['shapeCode'] == CYLINDER_STANDING || $arrayParam['shapeCode'] == CYLINDER_STANDING_3D || $arrayParam['shapeCode'] == CYLINDER_LAYING_3D){
                                $prodDim1Name = 'Diameter';
                            } ?>

                            <?php if ($arrayParam['shapeCode'] == PARALLELEPIPED_LAYING || $arrayParam['shapeCode'] == CYLINDER_CONCENTRIC_STANDING || $arrayParam['shapeCode'] == PARALLELEPIPED_LAYING_3D || $arrayParam['shapeCode'] == CYLINDER_CONCENTRIC_STANDING_3D){
                                $prodDim1Name = 'Height';
                            } ?>
                                
                        <?php } ?>

                        <?php if ($arrayParam['shapeCode'] == TRAPEZOID_3D || $arrayParam['shapeCode'] == OVAL_STANDING_3D || $arrayParam['shapeCode'] == OVAL_LAYING_3D) {?>

                            <?php if ($arrayParam['shapeCode'] == TRAPEZOID_3D) {
                                $prodDim1Name = 'Base Length';
                            } ?>

                            <?php if ($arrayParam['shapeCode'] == OVAL_STANDING_3D || $arrayParam['shapeCode'] == OVAL_LAYING_3D){
                                $prodDim1Name = 'Major Diameter';
                            } ?>
                                
                        <?php } ?>
                        <?php if (isset($prodDim1Name)): ?>
                        <th class="text-center"><?php echo $prodDim1Name ?><br><?php echo "(" . $arrayParam['symbol']['prodDimensionSymbol'] . ")" ?></th>
                        <?php endif ?>
                        <?php if ($arrayParam['shapeCode'] == PARALLELEPIPED_STANDING || $arrayParam['shapeCode'] == PARALLELEPIPED_LAYING || $arrayParam['shapeCode'] == PARALLELEPIPED_BREADED || $arrayParam['shapeCode'] == PARALLELEPIPED_STANDING_3D || $arrayParam['shapeCode'] == PARALLELEPIPED_LAYING_3D || $arrayParam['shapeCode'] == PARALLELEPIPED_BREADED_3D) {
                                $prodDim3Name = 'Width';
                            }
                        ?>

                        <?php if ($arrayParam['shapeCode'] == TRAPEZOID_3D || $arrayParam['shapeCode'] == OVAL_STANDING_3D || $arrayParam['shapeCode'] == OVAL_LAYING_3D) {?>

                            <?php if ($arrayParam['shapeCode'] == TRAPEZOID_3D) {
                                $prodDim3Name = 'Base Width';
                            } ?>

                            <?php if ($arrayParam['shapeCode'] == OVAL_STANDING_3D || $arrayParam['shapeCode'] == OVAL_LAYING_3D){
                                $prodDim3Name = 'Minor Diameter';
                            } ?>
                                
                        <?php } ?>
                        <?php if (isset($prodDim3Name)): ?>
                        <th class="text-center"><?php echo $prodDim3Name ?><br><?php echo "(" . $arrayParam['symbol']['prodDimensionSymbol'] . ")" ?></th>
                        <?php endif ?>
                        <?php if ($arrayParam['shapeCode'] == PARALLELEPIPED_STANDING || $arrayParam['shapeCode'] == PARALLELEPIPED_BREADED || $arrayParam['shapeCode'] == CYLINDER_STANDING || $arrayParam['shapeCode'] == PARALLELEPIPED_STANDING_3D || $arrayParam['shapeCode'] == CYLINDER_STANDING_3D || $arrayParam['shapeCode'] == PARALLELEPIPED_BREADED_3D || $arrayParam['shapeCode'] == TRAPEZOID_3D || $arrayParam['shapeCode'] == PARALLELEPIPED_BREADED_3D || $arrayParam['shapeCode'] == OVAL_STANDING_3D) {
                                $prodDim2Name = 'Height';
                            }
                        ?>

                        <?php if ($arrayParam['shapeCode'] == SLAB) {
                                $prodDim2Name = 'Thickness';
                            }
                        ?>

                        <?php if ($arrayParam['shapeCode'] == PARALLELEPIPED_LAYING || $arrayParam['shapeCode'] == CYLINDER_LAYING || $arrayParam['shapeCode'] == PARALLELEPIPED_LAYING_3D || $arrayParam['shapeCode'] == CYLINDER_LAYING_3D || $arrayParam['shapeCode'] == OVAL_LAYING_3D) {
                                $prodDim2Name = 'Length';
                            }
                        ?>

                        <?php if ($arrayParam['shapeCode'] == SPHERE || $arrayParam['shapeCode'] == CYLINDER_CONCENTRIC_STANDING || $arrayParam['shapeCode'] == CYLINDER_CONCENTRIC_LAYING || $arrayParam['shapeCode'] == SPHERE_3D || $arrayParam['shapeCode'] == CYLINDER_CONCENTRIC_STANDING_3D || $arrayParam['shapeCode'] == CYLINDER_CONCENTRIC_LAYING_3D) {
                                $prodDim2Name = 'Diameter';
                            }
                        ?>
                        <?php if (isset($prodDim2Name)): ?>
                        <th class="text-center"><?php echo $prodDim2Name ?><br><?php echo "(" . $arrayParam['symbol']['prodDimensionSymbol'] . ")" ?></th>
                        <?php endif ?>
                        <th class="text-center">Real product mass per unit<br><?php echo "(" . $arrayParam['symbol']['massSymbol'] . ")" ?></th>
                        <th class="text-center">Same temperature throughout product.</th>
                        <th class="text-center">Initial temperature<br><?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . ")" ?></th>
                    </tr>
                    <tr>
                        <td class="text-center"><?php echo $arrayParam['product']->PRODNAME ?></td>
                        <td class="text-center"><?php echo ($arrayParam['shapeCode'] <= 9) ? $arrayParam['shapeName']->LABEL : $arrayParam['shapeName'] ?></td>
                        <?php if (isset($prodDim1Name)): ?>
                        <td class="text-center"><?php echo $arrayParam['proElmtParam1'] ?></td>
                        <?php endif ?>
                        <?php if (isset($prodDim3Name)): ?>
                        <td class="text-center"><?php echo $arrayParam['proElmtParam3'] ?></td>
                        <?php endif ?>
                        <?php if (isset($prodDim2Name)): ?>
                        <td class="text-center"><?php echo $arrayParam['proElmtParam2'] ?></td>
                        <?php endif ?>
                        <td class="text-center"><?php echo $arrayParam['productRealW'] ?></td>
                        <td class="text-center"><?php echo $arrayParam['product']->PROD_ISO == 1 ? "YES" : "NO" ?></td>
                        <td class="text-center">
                           <?php echo $arrayParam['proInfoStudy']['avgTInitial'] ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="pro-components">
            <div class="table-responsive">
                <table class ="table table-bordered table-hover table-striped" border="1">
                    <tr>
                        <th class="text-center">Component list</th>
                        <th class="text-center">Description</th>
                        <th class="text-center">Product dimension<br><?php echo "(" . $arrayParam['symbol']['prodDimensionSymbol'] . " )" ?></th>
                        <th class="text-center">Real mass<br><?php echo "(" . $arrayParam['symbol']['massSymbol'] . " )" ?></th>
                        <th class="text-center">Same temperature throughout product.</th>
                        <th class="text-center">Added to product in study number</th>
                        <th class="text-center">Initial temperature<br><?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></th>
                    </tr>
                    <?php foreach($productComps as $key => $resproductComps) { 
                        $prodElmIso = '';
                        $sameTemperature = 'NO';
                        if (!($arrayParam['study']['CHAINING_CONTROLS'] && $arrayParam['study']['PARENT_ID'] != 0 && $resproductComps['INSERT_LINE_ORDER'] != $arrayParam['study']['ID_STUDY'])) {
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
                    ?>
                    <tr>
                        <td><?php echo $resproductComps['display_name'] ?></td>
                        <td class="text-center"><?php echo $resproductComps['PROD_ELMT_NAME'] ?></td>
                        <td class="text-center"><?php echo $resproductComps['dim'] ?></td>
                        <td class="text-center"><?php echo $resproductComps['mass'] ?></td>
                        <td class="text-center"><?php echo $sameTemperature ?></td>
                        <td class="text-center"><?php if ($arrayParam['study']['CHAINING_CONTROLS']) echo $resproductComps['studyNumber'] ?></td>
                        <td class="text-center"><?php echo $prodElmIso ?></td>
                    </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
        <?php } ?>

        <?php if ($arrayParam['params']['PROD_3D'] == 1) { 
            $title = 'Product 3D';
            if ($arrayParam['params']['PACKING'] == 1) {
                $title .= '&& Packing Data';
            }
        ?>               
        <div class="block-title"><?php echo $title; ?></div>
        <div class="product3d">
            <div class="table-responsive">
            <table class ="table table-bordered" border="1">
                <tr>
                    <th colspan="5" class="text-center">Packing</th>
                    <th class="text-center">3D view of the product</th>
                </tr>
                <tr>
                    <td rowspan="2">Side</td>
                    <td rowspan="2">Number of layers</td>
                    <td colspan="2">Packing data</td>
                    <td rowspan="2">Thickness ()</td>
                    <?php if ($arrayParam['params']['PACKING'] == 1) { ?>
                        <td rowspan="<?php echo count($packings['count'] + 2) ?>"></td>
                    <?php } else { ?>
                        <td rowspan="2"></td>
                    <?php } ?>
                </tr>
                <tr>
                    <td>Order</td>
                    <td>Name</td>
                </tr>
                <?php if ($arrayParam['params']['PACKING'] == 1) { ?>
                    <?php if (!empty($packings['packingLayerData']['1'])) { ?>
                        <?php foreach ($packings['packingLayerData']['1'] as $key => $top) { ?>
                            <tr>
                                <?php if ($key == 0) { ?>
                                    <td rowspan="<?php echo count($packings['packingLayerData']['1'])?>">Top</td>
                                    <td rowspan="<?php echo count($packings['packingLayerData']['1'])?>"><?php echo count($packings['packingLayerData']['1'])?></td>
                                <?php } ?>
                                    <td><?php echo $top['PACKING_LAYER_ORDER'] + 1 ?></td>
                                    <td><?php echo $top['LABEL'] ?></td>
                                    <td><?php echo $top['THICKNESS'] ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                    <?php if (!empty($packings['packingLayerData']['2'])) { ?>
                        <?php foreach ($packings['packingLayerData']['2'] as $key => $bottom) { ?>
                            <tr>
                                <?php if ($key == 0) { ?>
                                    <td rowspan="<?php echo count($packings['packingLayerData']['2'])?>">Bottom</td>
                                    <td rowspan="<?php echo count($packings['packingLayerData']['2'])?>"><?php echo count($packings['packingLayerData']['2'])?></td>
                                <?php } ?>
                                    <td><?php echo $bottom['PACKING_LAYER_ORDER'] + 1 ?></td>
                                    <td><?php echo $bottom['LABEL'] ?></td>
                                    <td><?php echo $bottom['THICKNESS'] ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                    <?php if (!empty($packings['packingLayerData']['3'])) { ?>
                        <?php foreach ($packings['packingLayerData']['3'] as $key => $sides) { ?>
                            <tr>
                                <?php if ($key == 0) { ?>
                                    <td rowspan="<?php echo count($packings['packingLayerData']['3'])?>">4 Sides</td>
                                    <td rowspan="<?php echo count($packings['packingLayerData']['3'])?>"><?php echo count($packings['packingLayerData']['3'])?></td>
                                <?php } ?>
                                    <td><?php echo $sides['PACKING_LAYER_ORDER'] + 1 ?></td>
                                    <td><?php echo $sides['LABEL'] ?></td>
                                    <td><?php echo $sides['THICKNESS'] ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                    </table>
                </div>
            </div>
                <?php } else { ?>
            </table>
            </div>
        </div>
        <?php } ?>
        <?php } ?>

        <?php if ($arrayParam['params']['EQUIP_LIST'] == 1) { ?>
        <div class="block-title">Equipment data</div>
        <div class="equipment-data">
            <div class="table-responsive">
            <table class ="table table-bordered table-hover table-striped" border="1">
                <tr>
                    <th class="text-center">No.</th>
                    <th class="text-center">Name</th>
                    <th class="text-center">Residence / Dwell time  <?php echo "(" . $arrayParam['symbol']['timeSymbol'] . " )" ?></th>
                    <th class="text-center">Control temperature<?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></th>
                    <th class="text-center">Convection Setting(Hz)</th>
                    <th class="text-center">Product orientation</th>
                    <th class="text-center">Conveyor coverage or quantity of product per batch</th>
                </tr>
                <?php foreach($equipData as $key => $resequipDatas) { ?>
                <tr>
                    <td class="text-center"><?php echo $key+1 ?></td>
                    <td class="text-center"><?php echo $resequipDatas['displayName'] ?></td>
                    <td class="text-center"><?php echo $resequipDatas['ts'][0] ?></td>
                    <td class="text-center"><?php echo $resequipDatas['tr'][0] ?></td>
                    <td class="text-center"><?php echo $resequipDatas['vc'][0] ?></td>
                    <td class="text-center"><?php echo $resequipDatas['ORIENTATION'] == 1 ? 'Parallel' : 'Perpendicular' ?></td>
                    <td class="text-center"><?php echo $resequipDatas['top_or_QperBatch'] ?></td>
                </tr>
                <?php } ?>
            </table>
            </div>
        </div>
        <?php } ?>

        <?php if ($arrayParam['params']['ASSES_ECO'] == 1) { ?>
        <div class="block-title">Belt or shelves layout</div>
        <?php foreach($equipData as $key => $resequipDatas) { ?>
        <h5 class="bold"><?php echo $resequipDatas['displayName'] ?></h5>
        <div class="layout">
            <div class = "row">
                <?php if ($resequipDatas['hasLayout']) { ?>
                <div class="col-md-8">
                    <div class="table-responsive">
                    <table class ="table table-bordered table-hover table-striped">
                        <tr>
                            <th colspan="2" class="text-center">Inputs</th>
                        </tr>
                        <tr>
                            <td>Space (length) <?php echo "(" . $arrayParam['symbol']['prodDimensionSymbol'] . " )" ?></td>
                            <td class="text-center"><?php echo "User not define" ?></td>
                        </tr>
                        <tr>
                            <td>Space (width) <?php echo "(" . $arrayParam['symbol']['prodDimensionSymbol'] . " )" ?></td>
                            <td class="text-center"><?php echo "User not define" ?></td>
                        </tr>
                        <tr>
                            <td>Orientation</td>
                            <td class="text-center"><?php echo $resequipDatas['ORIENTATION'] == 1 ? 'Parallel' : 'Perpendicular' ?></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="text-center">Outputs</td>
                        </tr>
                        <tr>
                            <td>Space in width <?php echo "(" . $arrayParam['symbol']['prodDimensionSymbol'] . " )" ?></td>
                            <td class="text-center"><?php echo $resequipDatas['layoutResults']['LEFT_RIGHT_INTERVAL'] ?></td>
                        </tr>
                        <tr>
                            <td>Number per meter</td>
                            <td class="text-center"><?php echo $resequipDatas['layoutResults']['NUMBER_PER_M'] ?></td>
                        </tr>
                        <tr>
                            <td>Number in width</td>
                            <td class="text-center"><?php echo $resequipDatas['layoutResults']['NUMBER_IN_WIDTH'] ?></td>
                        </tr>
                        <tr>
                            <td>Conveyor coverage or quantity of product per batch</td>
                            <td class="text-center"><?php echo $resequipDatas['top_or_QperBatch'] ?></td>
                        </tr>
                    </table>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <img src="<?php echo  $arrayParam['host'] . "reports/".$arrayParam['study']['USERNAM']."/".$arrayParam['study']['ID_STUDY']."-".$stuNameLayout."-StdeqpLayout-".$resequipDatas['ID_STUDY_EQUIPMENTS'].".jpg"?>">
                    </div>
                </div>
                <?php } else {?>
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class ="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <td colspan="2" class="text-center">Inputs</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Orientation</td>
                                    <td><?php echo $resequipDatas['ORIENTATION'] == 1 ? 'Parallel' : 'Perpendicular' ?></td>
                                </tr>
                                <tr>
                                    <td>Conveyor coverage or quantity of product per batch</td>
                                    <td><?php echo $resequipDatas['top_or_QperBatch'] ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
        <?php } ?>

        <?php if (!empty($cryogenPipeline)) { ?> 
        <?php if ($arrayParam['params']['PIPELINE'] == 1) { ?>
        <div class="block-title">Cryogenic Pipeline</div>
        <div class="consum-esti">
            <div class="table-responsive">
            <table class ="table table-bordered table-hover" border="1">
                <tr>
                    <th colspan="2" class="text-center">Type</th>
                    <th colspan="4" class="text-center">Name</th>
                    <th colspan="2" class="text-center">Number</th>
                </tr>
                <tr>
                    <td colspan="2">Insulated line</td>
                    <td colspan="4" class="text-center"><?php echo $cryogenPipeline['dataResultExist']['insulLabel'] ?></td>
                    <td colspan="2" class="text-center"><?php echo $cryogenPipeline['dataResultExist']['insulllenght'] ?></td>
                </tr>
                <tr>
                    <td colspan="2">Insulated valves</td>
                    <td colspan="4" class="text-center"><?php echo $cryogenPipeline['dataResultExist']['insulvalLabel'] ?></td>
                    <td colspan="2" class="text-center"><?php echo $cryogenPipeline['dataResultExist']['insulvallenght'] ?></td>
                </tr>
                <tr>
                    <td colspan="2">Elbows</td>
                    <td colspan="4" class="text-center"><?php echo $cryogenPipeline['dataResultExist']['elbowLabel'] ?></td>
                    <td colspan="2" class="text-center"><?php echo $cryogenPipeline['dataResultExist']['elbowsnumber'] ?></td>
                </tr>
                <tr>
                    <td colspan="2">Tees</td>
                    <td colspan="4" class="text-center"><?php echo $cryogenPipeline['dataResultExist']['teeLabel'] ?></td>
                    <td colspan="2" class="text-center"><?php echo $cryogenPipeline['dataResultExist']['teenumber'] ?></td>
                </tr>
                <tr>
                    <td colspan="2">Non-insulated line</td>
                    <td colspan="4" class="text-center"><?php echo $cryogenPipeline['dataResultExist']['noninsulLabel'] ?></td>
                    <td colspan="2" class="text-center"><?php echo $cryogenPipeline['dataResultExist']['noninsullenght'] ?></td>
                </tr>
                <tr>
                    <td colspan="2">Non-insulated valves</td>
                    <td colspan="4" class="text-center"><?php echo $cryogenPipeline['dataResultExist']['noninsulvalLabel'] ?></td>
                    <td colspan="2"class="text-center"><?php echo $cryogenPipeline['dataResultExist']['noninsulatevallenght'] ?></td>
                </tr>
                <tr>
                    <td colspan="2">Storage tank</td>
                    <td colspan="4" class="text-center"><?php echo $cryogenPipeline['dataResultExist']['storageTankName'] ?></td>
                    <td colspan="2" class="text-center"><?php echo "" ?></td>
                </tr>
                <tr>
                    <td colspan="6"><b>Tank pressure :</b> </td>
                    <td colspan="2" class="text-center"><?php echo $cryogenPipeline['dataResultExist']['pressuer'] ?> <?php echo "(" . $arrayParam['symbol']['pressureSymbol'] . " )" ?></td>
                </tr>
                <tr>
                    <td colspan="6"><b>Equipment elevation above tank outlet. :</b></td>
                    <td colspan="2" class="text-center"><?php echo $cryogenPipeline['dataResultExist']['height'] ?> <?php echo "(" . $arrayParam['symbol']['materialRiseSymbol'] . " )" ?></td>
                </tr>

            </table>
            </div>
        </div>
        <?php } ?>
        <?php } ?>

        <?php if ($arrayParam['params']['PACKING'] == 1 && $arrayParam['params']['PROD_3D'] != 1) { ?>
        <div class= "Packing">
        <div class="table-responsive">
        <table class ="table table-bordered table-hover" border="1">
                <tr>
                    <th colspan="5" class="text-center">Packing</th>
                    <th class="text-center">3D view of the product</th>
                </tr>
                <tr>
                    <td rowspan="2">Side</td>
                    <td rowspan="2">Number of layers</td>
                    <td colspan="2">Packing data</td>
                    <td rowspan="2">Thickness ()</td>
                    <td rowspan="<?php echo count($packings['count'] + 2) ?>"></td>
                    
                </tr>
                <tr>
                    <td>Order</td>
                    <td>Name</td>
                </tr>
                    <?php if (!empty($packings['packingLayerData']['1'])) { ?>
                        <?php foreach ($packings['packingLayerData']['1'] as $key => $top) { ?>
                            <tr>
                                <?php if ($key == 0) { ?>
                                    <td rowspan="<?php echo count($packings['packingLayerData']['1'])?>">Top</td>
                                    <td rowspan="<?php echo count($packings['packingLayerData']['1'])?>"><?php echo count($packings['packingLayerData']['1'])?></td>
                                <?php } ?>
                                    <td><?php echo $top['PACKING_LAYER_ORDER'] + 1 ?></td>
                                    <td><?php echo $top['LABEL'] ?></td>
                                    <td><?php echo $top['THICKNESS'] ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                    <?php if (!empty($packings['packingLayerData']['2'])) { ?>
                        <?php foreach ($packings['packingLayerData']['2'] as $key => $bottom) { ?>
                            <tr>
                                <?php if ($key == 0) { ?>
                                    <td rowspan="<?php echo count($packings['packingLayerData']['2'])?>">Bottom</td>
                                    <td rowspan="<?php echo count($packings['packingLayerData']['2'])?>"><?php echo count($packings['packingLayerData']['2'])?></td>
                                <?php } ?>
                                    <td><?php echo $bottom['PACKING_LAYER_ORDER'] + 1 ?></td>
                                    <td><?php echo $bottom['LABEL'] ?></td>
                                    <td><?php echo $bottom['THICKNESS'] ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                    <?php if (!empty($packings['packingLayerData']['3'])) { ?>
                        <?php foreach ($packings['packingLayerData']['3'] as $key => $sides) { ?>
                            <tr>
                                <?php if ($key == 0) { ?>
                                    <td rowspan="<?php echo count($packings['packingLayerData']['3'])?>">4 Sides</td>
                                    <td rowspan="<?php echo count($packings['packingLayerData']['3'])?>"><?php echo count($packings['packingLayerData']['3'])?></td>
                                <?php } ?>
                                    <td><?php echo $sides['PACKING_LAYER_ORDER'] + 1 ?></td>
                                    <td><?php echo $sides['LABEL'] ?></td>
                                    <td><?php echo $sides['THICKNESS'] ?></td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                    </table>
                </div>
            </div>
        <?php } ?>

        <?php if (!empty($consumptions)) { ?>
            <?php if ($arrayParam['params']['CONS_OVERALL'] == 1 || $arrayParam['params']['CONS_TOTAL'] ==1 || $arrayParam['params']['CONS_SPECIFIC']  == 1 || $arrayParam['params']['CONS_HOUR'] ==1 || $arrayParam['params']['CONS_DAY'] == 1||
            $arrayParam['params']['CONS_WEEK'] == 1 || $arrayParam['params']['CONS_MONTH'] == 1 || $arrayParam['params']['CONS_YEAR'] ==1 || $arrayParam['params']['CONS_EQUIP'] ==1 || $arrayParam['params']['CONS_PIPE'] == 1 || $arrayParam['params']['CONS_TANK'] ==1)  { 
                $countColspan = 2;
            ?>
            <div class="block-title">Consumptions / Economics assessments</div>
            <h5 class="bold">Values</h5>
            <div class="consum-esti">
                <div class="table-responsive">
                <table class ="table table-bordered table-hover" border="1">
                    <thead>
                        <tr>
                            <th colspan="2" class="text-center" rowspan="2" width="20%">Equipment</th>
                            <?php if ($arrayParam['params']['CONS_OVERALL'] == 1) { $countColspan++; ?>
                            <th rowspan="2" class="text-center">Overall Cryogen Consumption Ratio (product + equipment and pipeline losses) Unit of Cryogen, per piece of product.  <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                            <?php } ?>
                            <?php if ($arrayParam['params']['CONS_TOTAL'] == 1) { $countColspan++; ?>
                            <th rowspan="2" class="text-center">Total Cryogen Consumption (product + equipment and pipeline losses). <?php echo "(" . $arrayParam['symbol']['consumMaintienSymbol'] . " )" . "/" . $arrayParam['symbol']['perUnitOfMassSymbol']  ?></th>
                            <?php } ?>
                            <?php if ($arrayParam['params']['CONS_SPECIFIC'] == 1) { $countColspan++; ?>
                            <th rowspan="2" class="text-center">Specific Cryogen Consumption Ratio (product only) Unit of Cryogen, per unit weight of product. <?php echo "(" . $arrayParam['symbol']['consumMaintienSymbol'] . " )" . "/" . $arrayParam['symbol']['perUnitOfMassSymbol']  ?></th>
                            <?php } ?>
                            <?php if ($arrayParam['params']['CONS_HOUR'] == 1) { $countColspan++; ?>
                            <th rowspan="2" class="text-center">Total Cryogen Consumption per hour <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                            <?php } ?>
                            <?php if ($arrayParam['params']['CONS_DAY'] == 1) { $countColspan++; ?>
                            <th rowspan="2" class="text-center">Total Cryogen Consumption per day <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                            <?php } ?>
                            <?php if ($arrayParam['params']['CONS_WEEK'] == 1) { $countColspan++; ?>
                            <th rowspan="2" class="text-center">Total Cryogen Consumption per week <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                            <?php } ?>
                            <?php if ($arrayParam['params']['CONS_MONTH'] == 1) { $countColspan++; ?>
                            <th rowspan="2" class="text-center">Total Cryogen Consumption per month <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                            <?php } ?>
                            <?php if ($arrayParam['params']['CONS_YEAR'] == 1) { $countColspan++; ?>
                            <th rowspan="2" class="text-center">Total Cryogen Consumption per year <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                            <?php } ?>
                            <?php if ($arrayParam['params']['CONS_EQUIP'] == 1) { ?>
                            <th colspan="2" class="text-center">Equipment Cryogen Consumption</th>
                            <?php } ?>
                            <?php if ($arrayParam['params']['CONS_PIPE'] == 1) { ?>
                            <th colspan="2" class="text-center">Pipeline consumption</th>
                            <?php } ?>
                            <?php if ($arrayParam['params']['CONS_TANK'] == 1) { $countColspan++; ?>
                            <th rowspan="2">Tank losses <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                            <?php } ?>
                        </tr>
                        <?php if ($arrayParam['params']['CONS_EQUIP'] == 1 || $arrayParam['params']['CONS_PIPE'] == 1) { ?>
                        <tr>
                            <th colspan="<?php echo $countColspan; ?>"></th>
                            <?php if ($arrayParam['params']['CONS_EQUIP'] == 1) { ?>
                            <th class="text-center">Heat losses per hour <?php echo "(" . $arrayParam['symbol']['consumMaintienSymbol'] . " )" ?></th>
                            <th class="text-center">Cooldown <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                            <?php } ?>
                            <?php if ($arrayParam['params']['CONS_PIPE'] == 1) { ?>
                            <th class="text-center">Heat losses per hour <?php echo "(" . $arrayParam['symbol']['consumMaintienSymbol'] . " )" ?></th>
                            <th class="text-center">Cooldown <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                            <?php } ?>
                        </tr>
                        <?php } ?>
                    </thead>
                    <tbody>
                        <?php foreach($consumptions as $key => $resconsumptions) { ?>
                            <tr>
                                <td class="text-center" rowspan="2"><?php echo $resconsumptions['equipName'] ?></td>
                                <td class="text-center" ><?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></td>
                                <?php if ($arrayParam['params']['CONS_OVERALL'] == 1) { ?>
                                <td class="text-center"><?php echo $resconsumptions['tc'] ?></td>
                                <?php } ?>
                                <?php if ($arrayParam['params']['CONS_TOTAL'] == 1) { ?>
                                <td class="text-center"><?php echo $resconsumptions['kgProduct'] ?></td>
                                <?php } ?>
                                <?php if ($arrayParam['params']['CONS_SPECIFIC'] == 1) { ?>
                                <td class="text-center"><?php echo $resconsumptions['product'] ?></td>
                                <?php } ?>
                                <?php if ($arrayParam['params']['CONS_HOUR'] == 1) { ?>
                                <td class="text-center"><?php echo $resconsumptions['hour'] ?></td>
                                <?php } ?>
                                <?php if ($arrayParam['params']['CONS_DAY'] == 1) { ?>
                                <td class="text-center"><?php echo $resconsumptions['day'] ?></td>
                                <?php } ?>
                                <?php if ($arrayParam['params']['CONS_WEEK'] == 1) { ?>
                                <td class="text-center"><?php echo $resconsumptions['week'] ?></td>
                                <?php } ?>
                                <?php if ($arrayParam['params']['CONS_MONTH'] == 1) { ?>
                                <td class="text-center"><?php echo $resconsumptions['month'] ?></td>
                                <?php } ?>
                                <?php if ($arrayParam['params']['CONS_YEAR'] == 1) { ?>
                                <td class="text-center"><?php echo $resconsumptions['year'] ?></td>
                                <?php } ?>
                                <?php if ($arrayParam['params']['CONS_EQUIP'] == 1) { ?>
                                <td class="text-center"><?php echo $resconsumptions['eqptPerm'] ?></td>
                                <td class="text-center"><?php echo $resconsumptions['eqptCold'] ?></td>
                                <?php } ?>
                                <?php if ($arrayParam['params']['CONS_PIPE'] == 1) { ?>
                                <td class="text-center"><?php echo $resconsumptions['linePerm'] ?></td>
                                <td class="text-center"><?php echo $resconsumptions['lineCold'] ?></td>
                                <?php } ?>
                                <?php if ($arrayParam['params']['CONS_TANK'] == 1) { ?>
                                <td class="text-center"><?php echo $resconsumptions['tank'] ?></td>
                                <?php } ?>
                            </tr>
                            <tr>
                                <td class="text-center"><?php echo "(" . $arrayParam['symbol']['monetarySymbol'] . " )" ?></td>
                                <?php if ($arrayParam['study']['OPTION_ECO'] != 1) { ?>
                                    <?php if ($arrayParam['params']['CONS_OVERALL'] == 1) { ?>
                                    <td class="text-center"><?php echo "--" ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_TOTAL'] == 1) { ?>
                                    <td class="text-center"><?php echo "--" ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_SPECIFIC'] == 1) { ?>
                                    <td class="text-center"><?php echo "--" ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_HOUR'] == 1) { ?>
                                    <td class="text-center"><?php echo "--" ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_DAY'] == 1) { ?>
                                    <td class="text-center"><?php echo "--" ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_WEEK'] == 1) { ?>
                                    <td class="text-center"><?php echo "--" ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_MONTH'] == 1) { ?>
                                    <td class="text-center"><?php echo "--" ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_YEAR'] == 1) { ?>
                                    <td class="text-center"><?php echo "--" ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_EQUIP'] == 1) { ?>
                                    <td class="text-center"><?php echo "--" ?></td>
                                    <td class="text-center"><?php echo "--" ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_PIPE'] == 1) { ?>
                                    <td class="text-center"><?php echo "--" ?></td>
                                    <td class="text-center"><?php echo "--" ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_TANK'] == 1) { ?>
                                    <td class="text-center"><?php echo "--" ?></td>
                                    <?php } ?>
                                <?php } else { ?>
                                    <?php if ($arrayParam['params']['CONS_OVERALL'] == 1) { ?>
                                    <td class="text-center"><?php echo $economic[$key]['tc'] ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_TOTAL'] == 1) { ?>
                                    <td class="text-center"><?php echo $economic[$key]['kgProduct'] ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_SPECIFIC'] == 1) { ?>
                                    <td class="text-center"><?php echo $economic[$key]['product'] ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_HOUR'] == 1) { ?>
                                    <td class="text-center"><?php echo $economic[$key]['hour'] ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_DAY'] == 1) { ?>
                                    <td class="text-center"><?php echo $economic[$key]['day'] ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_WEEK'] == 1) { ?>
                                    <td class="text-center"><?php echo $economic[$key]['week'] ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_MONTH'] == 1) { ?>
                                    <td class="text-center"><?php echo $economic[$key]['month'] ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_YEAR'] == 1) { ?>
                                    <td class="text-center"><?php echo $economic[$key]['year'] ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_EQUIP'] == 1) { ?>
                                    <td class="text-center"><?php echo $economic[$key]['eqptPerm'] ?></td>
                                    <td class="text-center"><?php echo $economic[$key]['eqptCold'] ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_PIPE'] == 1) { ?>
                                    <td class="text-center"><?php echo $economic[$key]['linePerm'] ?></td>
                                    <td class="text-center"><?php echo $economic[$key]['lineCold'] ?></td>
                                    <?php } ?>
                                    <?php if ($arrayParam['params']['CONS_TANK'] == 1) { ?>
                                    <td class="text-center"><?php echo $economic[$key]['tank'] ?></td>
                                    <?php } ?>
                                <?php } ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php } ?>
            <?php if ($arrayParam['params']['REP_CONS_PIE'] == 1) {?>
            <div class="block-title">Consumptions Pies</div>
            <?php foreach ($consumptions as $key => $consumption) { 
                $percentProduct = $consumption['percentProduct'];
                $percentEquipmentPerm = $consumption['percentEquipmentPerm'];
                $percentEquipmentDown = $consumption['percentEquipmentDown'];
                $percentLine = $consumption['percentLine'];
                if ($percentProduct != 0 && $percentEquipmentPerm != 0  && $percentEquipmentDown != 0) {
            ?>
                <h5 class="bold"><?php echo $consumption['equipName'] ?></h5>
                <div class="form-group text-center">
                    <img src="<?php echo $arrayParam['host'] . "consumption/" . $arrayParam['study']['USERNAM'] . "/" . $arrayParam['study']['ID_STUDY'] . "/" . $consumption['id'] . ".png" ?>" style="max-width: 640px" />
                </div>
            <?php }}?>
            <?php } ?>
        <?php } ?>

        <?php if (($arrayParam['params']['isSizingValuesChosen'] == 1) || ($arrayParam['params']['isSizingValuesMax'] == 16) || ($arrayParam['params']['SIZING_GRAPHE'] == 1))  { ?>
        <div class="block-title">Heat balance / sizing results</div>
        <?php } ?>
        <?php if ($arrayParam['params']['isSizingValuesChosen'] == 1) { ?>
        <h5 class="bold">Chosen product flowrate</h5>
        <div class="heat-balance-sizing">
            <div class="table-responsive">
            <table class ="table table-bordered" border="1">
                <tr>
                    <th colspan="2" rowspan="2" class="text-center">Equipment</th>
                    <th rowspan="2" class="text-center">Average initial temperature <?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></th>
                    <th rowspan="2" class="text-center">Final Average Product temperature <?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></th>
                    <th rowspan="2" class="text-center">Control temperature <?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></th>
                    <th rowspan="2" class="text-center">Residence / Dwell time   <?php echo "(" . $arrayParam['symbol']['timeSymbol'] . " )" ?></th>
                    <th rowspan="2" class="text-center">Product Heat Load <?php echo "(" . $arrayParam['symbol']['enthalpySymbol'] . " )" ?></th>
                    <th colspan="3" class="text-center" class="text-center">Chosen product flowrate</th>
                    <th rowspan="2" class="text-center">Precision of the high level calculation. (%)</th>
                </tr>
                <tr>
                    <th class="text-center">Hourly production capacity <?php echo "(" . $arrayParam['symbol']['productFlowSymbol'] . " )" ?></th>
                    <th class="text-center">Cryogen consumption (product + equipment heat load) <?php echo "(" . $arrayParam['symbol']['consumMaintienSymbol'] . " )" . "/" . $arrayParam['symbol']['perUnitOfMassSymbol']  ?></th>
                    <th class="text-center">Conveyor coverage or quantity of product per batch</th>
                </tr>
                <?php foreach($calModeHeadBalance as $resoptHeads) { ?>
                <tr>
                    <td class="text-center" colspan="2"><?php echo $resoptHeads['equipName'] ?></td>
                    <td class="text-center"><?php echo $arrayParam['proInfoStudy']['avgTInitial'] ?></td>
                    <td class="text-center"><?php echo $resoptHeads['tfp'] ?></td>
                    <td class="text-center"><?php echo $resoptHeads['tr'] ?></td>
                    <td class="text-center"><?php echo $resoptHeads['ts'] ?></td>
                    <td class="text-center"><?php echo $resoptHeads['vep'] ?></td>
                    <td class="text-center"><?php echo $resoptHeads['dhp'] ?></td>
                    <td class="text-center">
                    <?php 
                    if ($resoptHeads['conso_warning'] == 'warning_fluid') {
                        echo '<img src="'. $arrayParam['host'] .'images/output/warning_fluid_overflow.gif" width="30">';  
                    } else if ($resoptHeads['conso_warning'] == 'warning_dhp') {
                        echo '<img src="'. $arrayParam['host'] .'images/output/warning_fluid_overflow.gif" width="30"><img src="'. $arrayParam['host'] .'images/output/warning_dhp_overflow.gif" width="30">';  
                    } else if ($resoptHeads['conso_warning'] == 'warning_dhp_value') {
                        echo '<div>'. $resoptHeads['conso'] .'</div><img src="'. $arrayParam['host'] .'images/output/warning_dhp_overflow.gif" width="30">';
                    } else if ($resoptHeads['conso_warning'] != 'warning_fluid' && $resoptHeads['conso_warning'] != 'warning_dhp' && $resoptHeads['conso_warning'] != 'warning_dhp_value') {
                        echo $resoptHeads['conso'];
                    }
                    ?>
                    </td>
                    <td class="text-center"><?php echo $resoptHeads['toc'] ?></td>
                    <td class="text-center"><?php echo $resoptHeads['precision'] ?></td>
                </tr>
                <?php } ?>
            </table>
            </div>
        </div>
        <?php } ?>

        <?php if ($arrayParam['params']['isSizingValuesMax'] == 16) { ?> 
        <?php if (!empty($calModeHbMax)) { ?>
        <h5 class="bold">Maximum product flowrate</h5>
        <div class="Max-prod-flowrate">
            <div class="table-responsive">
            <table class ="table table-bordered" border="1">
                <tr>
                    <th colspan="2" rowspan="2">Equipment</th>
                    <th rowspan="2">Average initial temperature <?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></th>
                    <th rowspan="2">Final Average Product temperature <?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></th>
                    <th rowspan="2">Control temperature <?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></th>
                    <th rowspan="2">Residence / Dwell time   <?php echo "(" . $arrayParam['symbol']['timeSymbol'] . " )" ?></th>
                    <th rowspan="2">Product Heat Load <?php echo "(" . $arrayParam['symbol']['enthalpySymbol'] . " )" ?></th>
                    <th colspan="3" class="text-center">Maximum product flowrate </th>
                    <th rowspan="2">Precision of the high level calculation. (%)</th>
                </tr>
                <tr>
                    <th>Hourly production capacity <?php echo "(" . $arrayParam['symbol']['productFlowSymbol'] . " )" ?></th>
                    <th>Cryogen consumption (product + equipment heat load) <?php echo "(" . $arrayParam['symbol']['consumMaintienSymbol'] . " )" . "/" . $arrayParam['symbol']['perUnitOfMassSymbol']  ?></th>
                    <th>Conveyor coverage or quantity of product per batch</th>
                </tr>
                <?php foreach($calModeHbMax  as $resoptimumHbMax) { ?>
                <tr>
                    <td class="text-center" colspan="2"><?php echo $resoptimumHbMax['equipName'] ?></td>
                    <td class="text-center" ><?php echo $arrayParam['proInfoStudy']['avgTInitial'] ?></td>
                    <td class="text-center"><?php echo $resoptimumHbMax['tfp'] ?></td>
                    <td class="text-center"><?php echo $resoptimumHbMax['tr'] ?></td>
                    <td class="text-center"><?php echo $resoptimumHbMax['ts'] ?></td>
                    <td class="text-center"><?php echo $resoptimumHbMax['vep'] ?></td>
                    <td class="text-center"><?php echo $resoptimumHbMax['dhp'] ?></td>
                    <td class="text-center"> 
                    <?php 
                        if ($resoptimumHbMax['conso_warning'] == 'warning_fluid') {
                            echo '<img src="'. $arrayParam['host'] .'images/output/warning_fluid_overflow.gif" width="30">';  
                        } else if ($resoptimumHbMax['conso_warning'] == 'warning_dhp') {
                            echo '<img src="'. $arrayParam['host'] .'images/output/warning_fluid_overflow.gif" width="30"><img src="'. $arrayParam['host'] .'images/output/warning_dhp_overflow.gif" width="30">';  
                        } else if ($resoptimumHbMax['conso_warning'] == 'warning_dhp_value') {
                            echo '<div>'. $resoptimumHbMax['conso'] .'</div><img src="'. $arrayParam['host'] .'images/output/warning_dhp_overflow.gif" width="30">';
                        } else if ($resoptimumHbMax['conso_warning'] != 'warning_fluid' && $resoptimumHbMax['conso_warning'] != 'warning_dhp' && $resoptimumHbMax['conso_warning'] != 'warning_dhp_value') {
                            echo $resoptimumHbMax['conso'];
                        }
                    ?>
                    </td>
                    <td class="text-center"><?php echo $resoptimumHbMax['toc'] ?></td>
                    <td class="text-center"><?php echo $resoptimumHbMax['precision'] ?></td>
                </tr>
                <?php } ?>
            </table>
            </div>
        </div>
        <?php }?>
        <?php } ?>

        <?php if ($arrayParam['params']['SIZING_GRAPHE'] == 1) { ?> 
        <h5 class="bold">Graphic</h5>
        <div class ="graphic text-center">
            <img src="<?php echo $arrayParam['host'] . "sizing/" . $arrayParam['study']['USERNAM'] . "/" .  $arrayParam['study']['ID_STUDY'] . "/" .  $arrayParam['study']['ID_STUDY'].".png" ?>" style="max-width: 640px">
        </div>
        <?php } ?>


        <?php if (!empty($heatexchange)) { ?>
        <?php if (($arrayParam['params']['ENTHALPY_V'] == 1) || ($arrayParam['params']['ENTHALPY_G'] == 1)) { ?>
        <div class="block-title">Heat Exchange</div>
        <?php } ?>
            <?php foreach($heatexchange as $key=> $resheatexchanges) { ?>
            <?php if ($arrayParam['params']['ENTHALPY_V'] == 1) { ?>
            <div class="heat-exchange">
                <h4 class="bold"><?php echo $resheatexchanges['equipName'] ?></h4>
                <div class="table-responsive">
                <table class ="table table-bordered table-hover table-striped" border="1">
                    <tr>
                        <th colspan="2">Equipment</th>
                        <?php foreach($resheatexchanges['result'] as $result) { ?>
                            <th class="text-center"> <?php echo $result['x'] . $arrayParam['symbol']['timeSymbol'] ?></th>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td colspan="2"><?php echo $resheatexchanges['equipName'] . ' - (' . $arrayParam['symbol']['enthalpySymbol'] . ')'; ?></td>
                        <?php foreach($resheatexchanges['result'] as $result) { ?>
                            <th class="text-center"> <?php echo $result['y']?></th>
                        <?php } ?>
                    </tr>
                </table>
                </div>
                <?php } ?> 
                
                <?php if ($arrayParam['params']['ENTHALPY_G'] == 1) { ?>
                <div id="hexchGraphic" class="text-center">
                    <img src="<?php echo $arrayParam['host'] . "heatExchange/" . $arrayParam['study']['USERNAM'] . "/" .  $resheatexchanges['idStudyEquipment'] . ".png" ?>" style="max-width :640px">
                </div>
                <?php } ?>
                <?php } ?>
            </div>
            <?php } ?> 

         
        <?php if (!empty($proSections)) { ?>
        <?php if (($arrayParam['params']['ISOCHRONE_V'] == 1) || ($arrayParam['params']['ISOCHRONE_G'] == 1)) { ?> 
        <div class="block-title">Product Section</div>
        <?php } ?> 
            <?php foreach ($proSections as $resproSections) {?>
            <?php if ($arrayParam['params']['ISOCHRONE_V'] == 1) { ?>  
            <h4 class="bold"><?php echo $resproSections['equipName'] ?></h4>
                <?php if ($resproSections['selectedAxe'] == 1) {?> 
                Values - Dimension <?php echo $resproSections['selectedAxe'] . "(" . "*," . $resproSections['axeTemp'][0] . "," . $resproSections['axeTemp'][1] . ")" . "(" . $resproSections['prodchartDimensionSymbol'] . ")" ?>  
                <?php } else if ($resproSections['selectedAxe'] == 2) { ?>
                Values - Dimension <?php echo $resproSections['selectedAxe'] . "(" . $resproSections['axeTemp'][0] . ",*," . $resproSections['axeTemp'][1] . ")" . "(" . $resproSections['prodchartDimensionSymbol'] . ")" ?>  
                <?php } else if ($resproSections['selectedAxe'] == 3) {?>
                Values - Dimension <?php echo $resproSections['selectedAxe'] . "(" . $resproSections['axeTemp'][0] . "," . $resproSections['axeTemp'][1] . ",*" . ")" . "(" . $resproSections['prodchartDimensionSymbol'] . ")" ?>  
                <?php } ?>
                <div class="values-dim2">
                    <div class="table-responsive">
                    <table class ="table table-bordered table-hover table-striped" border="1">
                        <tr>
                            <th class="text-center">Node number</th>
                            <th class="text-center">Position Axis 1 <?php echo "(" . $resproSections['prodchartDimensionSymbol'] . ")" ?></th>
                            <?php foreach ($resproSections['resultLabel'] as $index => $labelTemp) { ?>
                                <th class="text-center">T° at <?php echo $resproSections['resultLabel'][$index] . $resproSections['timeSymbol'] . "(" . $resproSections['temperatureSymbol'] . ")" ?></th>
                            <?php }?>
                        </tr>
                        <?php foreach ($resproSections['result']['resultValue'] as $key => $node) {?>
                        <tr>
                            <td class="text-center"> <?php echo $key?></td>
                            <td class="text-center"> <?php echo $resproSections['result']['mesAxis'][$key]?></td>
                            <?php foreach ($node as $index => $dbchart) { ?>
                            <td class="text-center"> <?php echo $dbchart ?></td>
                            <?php }?>
                        </tr>
                        <?php }?>
                    </table>
                    </div>
                </div>
                <?php }?>


                <div class="graphic-dim2 text-center"> 
                <?php if ($arrayParam['params']['ISOCHRONE_G'] == 1) { ?> 
                <?php if ($resproSections['selectedAxe'] == 1) {?> 
                Graphic - Dimension <?php echo $resproSections['selectedAxe'] . "(" . "*," . $resproSections['axeTemp'][0] . "," . $resproSections['axeTemp'][1] . ")" . "(" . $resproSections['prodchartDimensionSymbol'] . ")" ?> 
                <p> 
                    <img src="<?php echo $arrayParam['host'] . "productSection/" . $arrayParam['study']['USERNAM'] . "/" .  $resproSections['idStudyEquipment'] . "-" . $resproSections['selectedAxe'] . ".png" ?>" style="max-width: 640px">
                    <?php } else if ($resproSections['selectedAxe'] == 2) { ?>
                </p>
                Graphic - Dimension <?php echo $resproSections['selectedAxe'] . "(" . $resproSections['axeTemp'][0] . ",*," . $resproSections['axeTemp'][1] . ")" . "(" . $resproSections['prodchartDimensionSymbol'] . ")" ?>  
                <p>
                    <img src="<?php echo $arrayParam['host'] . "productSection/" . $arrayParam['study']['USERNAM'] . "/" .  $resproSections['idStudyEquipment'] . "-" . $resproSections['selectedAxe'] . ".png" ?>" style="max-width: 640px">
                    <?php } else if ($resproSections['selectedAxe'] == 3) {?>
                </p>
                Graphic - Dimension <?php echo $resproSections['selectedAxe'] . "(" . $resproSections['axeTemp'][0] . "," . $resproSections['axeTemp'][1] . ",*" . ")" . "(" . $resproSections['prodchartDimensionSymbol'] . ")" ?>  
                <p>
                    <img src="<?php echo $arrayParam['host'] . "productSection/" . $arrayParam['study']['USERNAM'] . "/" .  $resproSections['idStudyEquipment'] . "-" . $resproSections['selectedAxe'] . ".png" ?>" style="max-width: 640px">
                    <?php } ?>
                </p>
                </div>
                <?php } ?>
            <?php } ?>
        <?php } ?>   
        
        <?php if (!empty($timeBase)) { ?>
        <?php if (($arrayParam['params']['ISOVALUE_V'] == 1) || ($arrayParam['params']['ISOVALUE_G'] == 1)) 
        { ?> 
            <div class="block-title">Product Graph - Time Based</div>
        <?php } ?>
            <?php foreach ($timeBase as $timeBases) { ?>
            <?php if ($arrayParam['params']['ISOVALUE_V'] == 1) { ?> 
            <h5 class="bold"><?php echo $timeBases['equipName'] ?></h5>
            <div class="values-graphic"> 
                <div class="table-responsive">
                <table class ="table table-bordered table-hover table-striped" border="1">
                    <tr>
                        <th class="text-center">Points</th>
                        <th class="text-center"><?php echo "(" . $timeBases['timeSymbol'] . " )" ?></th>
                        <?php foreach ($timeBases['result'] as $points) { ?>
                        <th class="text-center"><?php echo $points['points']?></th>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td class="text-center"><?php echo "Top" . "(" . $timeBases['label']['top'] . ")" ?> </td>
                        <td class="text-center"><?php echo "(" . $timeBases['temperatureSymbol'] . " )" ?></td>
                        <?php foreach ($timeBases['result'] as $tops) { ?>
                        <td class="text-center"><?php echo $tops['top']?></td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td class="text-center"><?php echo "Internal" . "(" . $timeBases['label']['int'] . ")" ?></td>
                        <td class="text-center"><?php echo "(" . $timeBases['temperatureSymbol'] . " )" ?></td>
                        <?php foreach ($timeBases['result'] as $internals) { ?>
                        <td class="text-center"><?php echo $internals['int']?></td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td class="text-center"><?php echo "Bottom" . "(" . $timeBases['label']['bot'] . ")" ?></td>
                        <td class="text-center"><?php echo "(" . $timeBases['temperatureSymbol'] . " )" ?></td>
                        <?php foreach ($timeBases['result'] as $bottoms) { ?>
                        <td class="text-center"><?php echo $bottoms['bot']?></td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td class="text-center">Avg. Temp.</td>
                        <td class="text-center"><?php echo "(" . $timeBases['temperatureSymbol'] . " )" ?></td>
                        <?php foreach ($timeBases['result'] as $avgs) { ?>
                        <td class="text-center"><?php echo $avgs['average']?></td>
                        <?php } ?>
                    </tr>
                </table>
                </div>
            </div>
        <?php } ?>
            <?php if ($arrayParam['params']['ISOVALUE_G'] == 1) { ?>      
                <h5 class="bold">Graphic</h5>        
                <div class="pro-graphic text-center">
                <img src="<?php echo $arrayParam['host'] . "timeBased/" . $arrayParam['study']['USERNAM'] . "/" .  $timeBases['idStudyEquipment'] . ".png" ?>" style="max-width: 640px">
                </div>
                    <?php } ?>
            <?php } ?>
        <?php } ?>

        <?php if (!empty($pro2Dchart)) { ?>
            <?php if ($arrayParam['params']['CONTOUR2D_G'] == 1) { ?> 
            <div class="block-title">2D Outlines</div>
                <?php foreach ($pro2Dchart as $pro2Dcharts) {
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

                    if ($arrayParam['shapeCode'] < 10) {
                        $contourFileName = $arrayParam['host'] . 'heatmap/' . $arrayParam['study']['USERNAM'] . '/' . $pro2Dcharts['idStudyEquipment'] . '/' . $pro2Dcharts['lfDwellingTime'] . '-' . $pro2Dcharts['chartTempInterval'][0] . '-' . $pro2Dcharts['chartTempInterval'][1] . '-' . $pro2Dcharts['chartTempInterval'][2] . '.png';
                    } else {
                        $contourFileName = $arrayParam['host'] . 'heatmap/' . $arrayParam['study']['USERNAM'] . '/' . $pro2Dcharts['idStudyEquipment'] . '/' . $pro2Dcharts['lfDwellingTime'] . '-' . $pro2Dcharts['chartTempInterval'][0] . '-' . $pro2Dcharts['chartTempInterval'][1] . '-' . $pro2Dcharts['chartTempInterval'][2] . '-' . $pro2Dcharts['selectedPlan'] . '.png';
                    }
                ?>
                <h5 class="bold"><?php echo $pro2Dcharts['equipName'] . ' Slice '. $selectedPlanName . ' @' . $pro2Dcharts['lfDwellingTime'] . '(' . $arrayParam['symbol']['timeSymbol'] . ')' ?></h5>
                    <div class="outlines text-center"> 
                    <img src="<?php echo $contourFileName ?>" style="max-width: 640px">
                    </div>
                <?php } ?>
        <?php } ?>
        <?php } ?>

            <div class="block-title">Comments</div>
            <div class="comment">
                <p>
                    <textarea disabled class="form-control" rows="5"><?php echo $arrayParam['params']['REPORT_COMMENT'] ?></textarea>
                </p>
            </div>

            <div class="info-company">
                <div class="text-center">
                    <p>
                        <img src="<?php echo (!empty($arrayParam['study']['reports'][0]['PHOTO_PATH'])) ? $arrayParam['study']['reports'][0]['PHOTO_PATH'] : $arrayParam['host'] . "images/globe_food.gif"?>" style="max-height: 280px">
                    </p>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <tr>
                            <th colspan="6">Study realized by</th>
                        </tr>
                        <tr>
                            <td colspan="4">Company name</td>
                            <td colspan="2"><?php echo $arrayParam['params']['WRITER_SURNAME'] ?></td>
                        </tr>
                        <tr>
                            <td colspan="4">Surname / Name</td>
                            <td colspan="2"><?php echo $arrayParam['params']['WRITER_NAME'] ?></td>
                        </tr>
                        <tr>
                            <td colspan="4">Function</td>
                            <td colspan="2"><?php echo $arrayParam['params']['WRITER_FUNCTION'] ?></td>
                        </tr>
                        <tr>
                            <td colspan="4">Contact</td>
                            <td colspan="2"><?php echo $arrayParam['params']['WRITER_COORD'] ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </body>
</html>