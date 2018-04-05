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
    <body>
    <div class ="container-fluid">
        <div class="logo">
            <div class="row">
                <div class="col-md-6">
                    <?php if (!empty($arrayParam['params']['CUSTOMER_PATH'])) { ?>
                    <p>
                        <img src="<?php echo $arrayParam['params']['CUSTOMER_PATH']?>">
                    </p>
                    <?php } ?>
                </div>
                <div class="col-md-6">
                    <div class="pull-right text-right">
                        <img src="<?php echo  $arrayParam['host'] . "/images/logo_cryosoft.png"?>">
                    </div>
                </div>
            </div>
        </div>
        <div class="info-company">
            <div class="text-center">
                <img src="<?php echo  $arrayParam['host'] . "/images/banner_cryosoft.png"?>">
            </div>
            <div class="table-responsive">
                <table class="table table-bordered" border="1">
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
                    <img src="<?php echo !empty($arrayParam['params']['PHOTO_PATH']) ? $arrayParam['params']['PHOTO_PATH'] : $arrayParam['host'] . "/images/globe_food.gif"?>">
                </p>
            </div>
            <div class="table-responsive" style="color:red">
                <table class ="table table-bordered" border="1">
                <tr>
                    <th align="center" colspan="3"><h3>Study of the product:</h3> <?php echo $arrayParam['study']['STUDY_NAME'] ?></th>
                </tr>
                <tr>
                    <td >Calculation mode :</td>
                    <td align="center" colspan="2"><?php echo $arrayParam['study']['CALCULATION_MODE'] == 3 ? "Optimum equipment" : "Estimation" ?></td>
                </tr>
                <tr>
                    <td >Economic :</td>
                    <td align="center" colspan="2"><?php echo $arrayParam['study']['OPTION_ECONO'] == 1 ? "YES" : "NO" ?></td>
                </tr>
                <tr>
                    <td >Cryogenic Pipeline :</td>
                    <td align="center" colspan="2"><?php echo !empty($cryogenPipeline) ? "YES" : "NO" ?></td>
                </tr>
                <tr>
                    <td >Chaining :</td>
                    <td align="center"><?php echo $arrayParam['study']['CHAINING_CONTROLS'] == 1 ? "YES" : "NO" ?></td>
                    <td align="center"><?php echo ($arrayParam['study']['CHAINING_CONTROLS'] == 1) && ($arrayParam['study']['HAS_CHILD'] != 0) && ($arrayParam['study']['PARENT_ID'] != 0) ? "This study is a child" : "" ?></td>
                </tr>
                </table>
            </div>
        </div>
        <?php if (!empty($calModeHeadBalance)) { ?>
        <?php if (($arrayParam['study']['CHAINING_CONTROLS'] == 1) && ($arrayParam['study']['PARENT_ID'] != 0)) { ?>
        <h3 style ="background-color:#268EE2">Chaining synthesis</h3>
        <div class="table-responsive">
            <table class ="table table-bordered" border="1">
                <table border="0.5">
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
                    <?php foreach($calModeHeadBalance as $key => $resoptHeads) { ?>
                        <tr>
                            <td colspan="2" align="center"><?php echo $resoptHeads['stuName'] ?></td>
                            <td colspan="2" align="center"><?php echo $resoptHeads['equipName'] ?></td>
                            <td align="center"><?php echo $resoptHeads['tr'] ?></td>
                            <td align="center"><?php echo $resoptHeads['ts'] ?></td>
                            <td align="center"><?php echo $equipData[$key]['tr'][0] ?></td>
                            <td align="center"><?php echo $arrayParam['proInfoStudy']['avgTInitial'] ?></td>
                            <td align="center"><?php echo $resoptHeads['tfp'] ?></td>
                            <td align="center"><?php echo $resoptHeads['vep'] ?></td>
                        </tr>
                    <?php }?>
                    </table>
                </div>
            </div>
        <?php } ?>
        <?php } ?>

        
        <?php if ($arrayParam['params']['REP_CUSTOMER'] == 1) { ?>                 
        <div class="production">
        <h3 style ="background-color:#268EE2">Production Data</h3>
            <div class="table table-responsive">
                <table class ="table table-bordered" border="1">
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
                    <td ><?php echo $arrayParam['production']->AVG_T_INITIAL ?></td>
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
        <h3 style ="background-color:#268EE2">Composition of the product and its components</h3>
        <div class="pro-data">
            <div class="table-responsive">
                <table class ="table table-bordered" border="1">
                    <tr>
                        <th align="center">Product name</th>
                        <th align="center">Shape</th>
                        <th align="center">Height <?php echo "(" . $arrayParam['symbol']['prodDimensionSymbol'] . " )" ?></th>
                        <th align="center">Length <?php echo "(" . $arrayParam['symbol']['prodDimensionSymbol'] . " )" ?></th>
                        <th align="center">Width <?php echo "(" . $arrayParam['symbol']['prodDimensionSymbol'] . " )" ?></th>
                        <th align="center">Real product mass per unit <?php echo "(" . $arrayParam['symbol']['massSymbol'] . " )" ?></th>
                        <th align="center">Same temperature throughout product.</th>
                        <th align="center">Initial temperature <?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></th>
                    </tr>
                    <tr>
                        <td align="center"><?php echo $arrayParam['product']->PRODNAME ?></td>
                        <td align="center"><?php echo $arrayParam['shapeName']->LABEL ?></td>
                        <td align="center"><?php echo $arrayParam['proElmt']->SHAPE_PARAM1 ?></td>
                        <td align="center"><?php echo $arrayParam['proElmt']->SHAPE_PARAM2 ?></td>
                        <td align="center"><?php echo $arrayParam['proElmt']->SHAPE_PARAM3 ?></td>
                        <td align="center"><?php echo $arrayParam['product']->PROD_REALWEIGHT ?></td>
                        <td align="center"><?php echo $arrayParam['product']->PROD_ISO == 1 ? "YES" : "NO" ?></td>
                        <td align="center"><?php echo $arrayParam['production']->AVG_T_INITIAL ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="pro-components">
            <div class="table-responsive">
                <table class ="table table-bordered" border="1">
                    <tr>
                        <th align="center">Component list</th>
                        <th align="center">Description</th>
                        <th align="center">Product dimension <?php echo "(" . $arrayParam['symbol']['prodDimensionSymbol'] . " )" ?></th>
                        <th align="center">Real mass <?php echo "(" . $arrayParam['symbol']['massSymbol'] . " )" ?></th>
                        <th align="center">Same temperature throughout product.</th>
                        <th align="center">Added to product in study number</th>
                        <th align="center">Initial temperature <?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></th>
                    </tr>
                    <?php foreach($productComps as $resproductComps) { ?>
                    <tr>
                        <td align="center"><?php echo $resproductComps['display_name'] ?></td>
                        <td align="center"><?php echo $resproductComps['PROD_ELMT_NAME'] ?></td>
                        <td align="center"><?php echo $resproductComps['SHAPE_PARAM2'] ?></td>
                        <td align="center"><?php echo $resproductComps['PROD_ELMT_REALWEIGHT'] ?></td>
                        <td align="center"><?php echo ($resproductComps['PROD_ELMT_ISO'] == 1) ? "YES" : "NO" ?></td>
                        <td align="center"><?php echo "" ?></td>
                        <td align="center"><?php echo ($resproductComps['PROD_ELMT_ISO'] == 1 )|| ($resproductComps['PROD_ELMT_ISO'] == 2) ? '' : "non isothermal" ?></td>
                    </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
        <?php } ?>

        <?php if ($arrayParam['params']['PROD_3D'] == 1) { ?>               
        <h3 style ="background-color:#268EE2">Product 3D</h3>
        <?php if ($arrayParam['params']['PACKING'] == 1) { ?>
        <h3 style ="background-color:#268EE2">&& Packing Data</h3>
        <?php } ?>
        <div class="product3d">
            <div class="table-responsive">
            <table class ="table table-bordered" border="1">
                <tr>
                    <th colspan="5" align="center">Packing</th>
                    <th align="center">3D view of the product</th>
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
        <h3 style ="background-color:#268EE2">Equipment data</h3>
        <div class="equipment-data">
            <div class="table-responsive">
            <table class ="table table-bordered" border="1">
                <tr>
                    <th align="center">No.</th>
                    <th align="center">Name</th>
                    <th align="center">Residence / Dwell time  <?php echo "(" . $arrayParam['symbol']['timeSymbol'] . " )" ?></th>
                    <th align="center">Control temperature<?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></th>
                    <th align="center">Convection Setting(Hz)</th>
                    <th align="center">Product orientation</th>
                    <th align="center">Conveyor coverage or quantity of product per batch</th>
                </tr>
                <?php foreach($equipData as $key => $resequipDatas) { ?>
                <tr>
                    <td align="center"><?php echo $key+1 ?></td>
                    <td align="center"><?php echo $resequipDatas['displayName'] ?></td>
                    <td align="center"><?php echo $resequipDatas['ORIENTATION'] == 1 ? 'Parallel' : 'Perpendicular' ?></td>
                    <td align="center"><?php echo $resequipDatas['tr'][0] ?></td>
                    <td align="center"><?php echo $resequipDatas['ts'][0] ?></td>
                    <td align="center"><?php echo $resequipDatas['vc'][0] ?></td>
                    <td align="center"><?php echo $resequipDatas['top_or_QperBatch'] ?></td>
                </tr>
                <?php } ?>
            </table>
            </div>
        </div>
        <?php } ?>

        <?php if ($arrayParam['params']['ASSES_ECO'] == 1) { ?>
        <h3 style ="background-color:#268EE2">Belt or shelves layout</h3>
        <?php foreach($equipData as $key => $resequipDatas) { ?>
        <h4><?php echo $resequipDatas['displayName'] ?></h4>
        <div class="layout">
            <div class = "row">
                <div class="col-md-7">
                    <div class="table-responsive">
                    <table class ="table table-bordered" border="1">
                        <tr>
                            <th colspan="2" align="center">Inputs</th>
                        </tr>
                        <tr>
                            <td>Space (length) <?php echo "(" . $arrayParam['symbol']['prodDimensionSymbol'] . " )" ?></td>
                            <td align="center"><?php echo "User not define" ?></td>
                        </tr>
                        <tr>
                            <td>Space (width) <?php echo "(" . $arrayParam['symbol']['prodDimensionSymbol'] . " )" ?></td>
                            <td align="center"><?php echo "User not define" ?></td>
                        </tr>
                        <tr>
                            <td>Orientation</td>
                            <td align="center"><?php echo $resequipDatas['ORIENTATION'] == 1 ? 'Parallel' : 'Perpendicular' ?></td>
                        </tr>
                        <tr>
                            <td colspan="2" align="center">Outputs</td>
                        </tr>
                        <tr>
                            <td>Space in width <?php echo "(" . $arrayParam['symbol']['prodDimensionSymbol'] . " )" ?></td>
                            <td align="center"><?php echo $resequipDatas['layoutResults']['LEFT_RIGHT_INTERVAL'] ?></td>
                        </tr>
                        <tr>
                            <td>Number per meter</td>
                            <td align="center"><?php echo $resequipDatas['layoutResults']['NUMBER_PER_M'] ?></td>
                        </tr>
                        <tr>
                            <td>Number in width</td>
                            <td align="center"><?php echo $resequipDatas['layoutResults']['NUMBER_IN_WIDTH'] ?></td>
                        </tr>
                        <tr>
                            <td>Conveyor coverage or quantity of product per batch</td>
                            <td align="center"><?php echo $resequipDatas['top_or_QperBatch'] ?></td>
                        </tr>
                    </table>
                    </div>
                </div>
                <div class="col-md-2">
                </div>
                <div class="col-md-3">
                    image
                </div>
            </div>
        </div>
        <?php } ?>
        <?php } ?>

        <?php if (!empty($cryogenPipeline)) { ?> 
        <?php if ($arrayParam['params']['PIPELINE'] == 1) { ?>
        <h3 style ="background-color:#268EE2">Cryogenic Pipeline</h3>
        <div class="consum-esti">
            <div class="table-responsive">
            <table class ="table table-bordered" border="1">
                <tr>
                    <th colspan="2" align="center">Type</th>
                    <th colspan="4" align="center">Name</th>
                    <th colspan="2" align="center">Number</th>
                </tr>
                <tr>
                    <td colspan="2">Insulated line</td>
                    <td colspan="4" align="center"><?php echo $cryogenPipeline['dataResultExist']['insulatedline'] ?? "" ?></td>
                    <td colspan="2" align="center"><?php echo $cryogenPipeline['dataResultExist']['insulllenght'] ?? "" ?></td>
                </tr>
                <tr>
                    <td colspan="2">Insulated valves</td>
                    <td colspan="4" align="center"><?php echo $cryogenPipeline['dataResultExist']['insulatedlineval'] ?? "" ?></td>
                    <td colspan="2" align="center"><?php echo $cryogenPipeline['dataResultExist']['insulvallenght'] ?? "" ?></td>
                </tr>
                <tr>
                    <td colspan="2">Elbows</td>
                    <td colspan="4" align="center"><?php echo $cryogenPipeline['dataResultExist']['elbows'] ?? "" ?></td>
                    <td colspan="2" align="center"><?php echo $cryogenPipeline['dataResultExist']['elbowsnumber'] ?? "" ?></td>
                </tr>
                <tr>
                    <td colspan="2">Tees</td>
                    <td colspan="4" align="center"><?php echo $cryogenPipeline['dataResultExist']['tee'] ?? "" ?></td>
                    <td colspan="2" align="center"><?php echo $cryogenPipeline['dataResultExist']['teenumber'] ?? "" ?></td>
                </tr>
                <tr>
                    <td colspan="2">Non-insulated line</td>
                    <td colspan="4" align="center"><?php echo $cryogenPipeline['dataResultExist']['non_insulated_line'] ?? "" ?></td>
                    <td colspan="2" align="center"><?php echo $cryogenPipeline['dataResultExist']['noninsullenght'] ?? "" ?></td>
                </tr>
                <tr>
                    <td colspan="2">Non-insulated valves</td>
                    <td colspan="4" align="center"><?php echo $cryogenPipeline['dataResultExist']['non_insulated_valves'] ?? "" ?></td>
                    <td colspan="2"align="center"><?php echo $cryogenPipeline['dataResultExist']['noninsulatevallenght'] ?? "" ?></td>
                </tr>
                <tr>
                    <td colspan="2">Storage tank</td>
                    <td colspan="4" align="center"><?php echo $cryogenPipeline['dataResultExist']['storageTankName'] ?? "" ?></td>
                    <td colspan="2" align="center"><?php echo "" ?></td>
                </tr>
            </table>
            <div id="pressuer"><b>Tank pressure :</b> <?php echo $cryogenPipeline['dataResultExist']['pressuer'] ?? "" ?> (Bar)</div>
            <div id="height"><b>Equipment elevation above tank outlet. :</b><?php echo $cryogenPipeline['dataResultExist']['height'] ?? "" ?> (m)</div>
            </div>
        </div>
        <?php } ?>
        <?php } ?>

        <?php if ($arrayParam['params']['PACKING'] == 1 && $arrayParam['params']['PROD_3D'] != 1) { ?>
        <div class= "Packing">
        <div class="table-responsive">
        <table class ="table table-bordered" border="1">
                <tr>
                    <th colspan="5" align="center">Packing</th>
                    <th align="center">3D view of the product</th>
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
        $arrayParam['params']['CONS_WEEK'] == 1 || $arrayParam['params']['CONS_MONTH'] == 1 || $arrayParam['params']['CONS_YEAR'] ==1 || $arrayParam['params']['CONS_EQUIP'] ==1 || $arrayParam['params']['CONS_PIPE'] == 1 || $arrayParam['params']['CONS_TANK'] ==1)  { ?>
        <h3 style ="background-color:#268EE2">Consumptions / Economics assessments</h3>
        <h4>Values</h4>
        <div class="consum-esti">
            <div class="table-responsive">
            <table class ="table table-bordered" border="1">
                <tr>
                <th colspan="2" align="center" rowspan="2">Equipment</th>
                <?php if ($arrayParam['params']['CONS_OVERALL'] == 1) { ?>
                <th rowspan="2" align="center">Overall Cryogen Consumption Ratio (product + equipment and pipeline losses) Unit of Cryogen, per piece of product.  <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                <?php } ?>
                <?php if ($arrayParam['params']['CONS_TOTAL'] == 1) { ?>
                <th rowspan="2" align="center">Total Cryogen Consumption (product + equipment and pipeline losses). <?php echo "(" . $arrayParam['symbol']['consumMaintienSymbol'] . " )" . "/" . $arrayParam['symbol']['perUnitOfMassSymbol']  ?></th>
                <?php } ?>
                <?php if ($arrayParam['params']['CONS_SPECIFIC'] == 1) { ?>
                <th rowspan="2" align="center">Specific Cryogen Consumption Ratio (product only) Unit of Cryogen, per unit weight of product. <?php echo "(" . $arrayParam['symbol']['consumMaintienSymbol'] . " )" . "/" . $arrayParam['symbol']['perUnitOfMassSymbol']  ?></th>
                <?php } ?>
                <?php if ($arrayParam['params']['CONS_HOUR'] == 1) { ?>
                <th rowspan="2" align="center">Total Cryogen Consumption per hour <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                <?php } ?>
                <?php if ($arrayParam['params']['CONS_DAY'] == 1) { ?>
                <th rowspan="2" align="center">Total Cryogen Consumption per day <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                <?php } ?>
                <?php if ($arrayParam['params']['CONS_WEEK'] == 1) { ?>
                <th rowspan="2" align="center">Total Cryogen Consumption per week <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                <?php } ?>
                <?php if ($arrayParam['params']['CONS_MONTH'] == 1) { ?>
                <th rowspan="2" align="center">Total Cryogen Consumption per month <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                <?php } ?>
                <?php if ($arrayParam['params']['CONS_YEAR'] == 1) { ?>
                <th rowspan="2" align="center">Total Cryogen Consumption per year <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                <?php } ?>
                <?php if ($arrayParam['params']['CONS_EQUIP'] == 1) { ?>
                <th colspan="2" align="center">Equipment Cryogen Consumption</th>
                <?php } ?>
                <?php if ($arrayParam['params']['CONS_PIPE'] == 1) { ?>
                <th colspan="2" align="center">Pipeline consumption</th>
                <?php } ?>
                <?php if ($arrayParam['params']['CONS_TANK'] == 1) { ?>
                <th rowspan="2">Tank losses <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></th>
                <?php } ?>
                </tr>
                <?php if (($arrayParam['params']['CONS_PIPE'] == 1) || ($arrayParam['params']['CONS_EQUIP'] == 1) ){ ?>
                    <tr>
                        <?php if ($arrayParam['params']['CONS_EQUIP'] == 1) { ?>
                        <td align="center">Heat losses per hour <?php echo "(" . $arrayParam['symbol']['consumMaintienSymbol'] . " )" ?></td>
                        <td align="center">Cooldown <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_PIPE'] == 1) { ?>
                        <td align="center">Heat losses per hour <?php echo "(" . $arrayParam['symbol']['consumMaintienSymbol'] . " )" ?></td>
                        <td align="center">Cooldown <?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
                <?php foreach($consumptions as $resconsumptions) { ?>
                    <tr>
                        <td align="center" rowspan="2"><?php echo $resconsumptions['equipName'] ?></td>
                        <td align="center" ><?php echo "(" . $arrayParam['symbol']['consumSymbol'] . " )" ?></td>
                        <?php if ($arrayParam['params']['CONS_OVERALL'] == 1) { ?>
                        <td align="center"><?php echo $resconsumptions['tc'] ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_TOTAL'] == 1) { ?>
                        <td align="center"><?php echo $resconsumptions['kgProduct'] ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_SPECIFIC'] == 1) { ?>
                        <td align="center"><?php echo $resconsumptions['product'] ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_HOUR'] == 1) { ?>
                        <td align="center"><?php echo $resconsumptions['hour'] ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_DAY'] == 1) { ?>
                        <td align="center"><?php echo $resconsumptions['day'] ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_WEEK'] == 1) { ?>
                        <td align="center"><?php echo $resconsumptions['week'] ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_MONTH'] == 1) { ?>
                        <td align="center"><?php echo $resconsumptions['month'] ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_YEAR'] == 1) { ?>
                        <td align="center"><?php echo $resconsumptions['year'] ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_EQUIP'] == 1) { ?>
                        <td align="center"><?php echo $resconsumptions['eqptPerm'] ?></td>
                        <td align="center"><?php echo $resconsumptions['eqptCold'] ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_PIPE'] == 1) { ?>
                        <td align="center"><?php echo $resconsumptions['linePerm'] ?></td>
                        <td align="center"><?php echo $resconsumptions['lineCold'] ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_TANK'] == 1) { ?>
                        <td align="center"><?php echo $resconsumptions['tank'] ?></td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td align="center"><?php echo "(" . $arrayParam['symbol']['monetarySymbol'] . " )" ?></td>
                        <?php if ($arrayParam['params']['CONS_OVERALL'] == 1) { ?>
                        <td align="center"><?php echo "--" ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_TOTAL'] == 1) { ?>
                        <td align="center"><?php echo "--" ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_SPECIFIC'] == 1) { ?>
                        <td align="center"><?php echo "--" ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_HOUR'] == 1) { ?>
                        <td align="center"><?php echo "--" ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_DAY'] == 1) { ?>
                        <td align="center"><?php echo "--" ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_WEEK'] == 1) { ?>
                        <td align="center"><?php echo "--" ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_MONTH'] == 1) { ?>
                        <td align="center"><?php echo "--" ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_YEAR'] == 1) { ?>
                        <td align="center"><?php echo "--" ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_EQUIP'] == 1) { ?>
                        <td align="center"><?php echo "--" ?></td>
                        <td align="center"><?php echo "--" ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_PIPE'] == 1) { ?>
                        <td align="center"><?php echo "--" ?></td>
                        <td align="center"><?php echo "--" ?></td>
                        <?php } ?>
                        <?php if ($arrayParam['params']['CONS_TANK'] == 1) { ?>
                        <td align="center"><?php echo "--" ?></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </table>
            </div>
        </div>
        <?php } ?>
        <?php } ?>

        <?php if (($arrayParam['params']['isSizingValuesChosen'] == 1) || ($arrayParam['params']['isSizingValuesMax'] == 1) || ($arrayParam['params']['SIZING_GRAPHE'] == 1))  { ?>
        <h3 style ="background-color:#268EE2">Heat balance / sizing results</h3>
        <?php } ?>
        <?php if ($arrayParam['params']['isSizingValuesChosen'] == 1) { ?>
        <h4>Chosen product flowrate</h4>
        <div class="heat-balance-sizing">
            <div class="table-responsive">
            <table class ="table table-bordered" border="1">
                <tr>
                    <th colspan="2" rowspan="2" align="center">Equipment</th>
                    <th rowspan="2" align="center">Average initial temperature <?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></th>
                    <th rowspan="2" align="center">Final Average Product temperature <?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></th>
                    <th rowspan="2" align="center">Control temperature <?php echo "(" . $arrayParam['symbol']['temperatureSymbol'] . " )" ?></th>
                    <th rowspan="2" align="center">Residence / Dwell time   <?php echo "(" . $arrayParam['symbol']['timeSymbol'] . " )" ?></th>
                    <th rowspan="2" align="center">Product Heat Load <?php echo "(" . $arrayParam['symbol']['enthalpySymbol'] . " )" ?></th>
                    <th colspan="4" align="center">Chosen product flowrate</th>
                    <th rowspan="2" align="center">Precision of the high level calculation. (%)</th>
                </tr>
                <tr>
                    <td align="center">Hourly production capacity <?php echo "(" . $arrayParam['symbol']['productFlowSymbol'] . " )" ?></td>
                    <td colspan="2" align="center">Cryogen consumption (product + equipment heat load) <?php echo "(" . $arrayParam['symbol']['consumMaintienSymbol'] . " )" . "/" . $arrayParam['symbol']['perUnitOfMassSymbol']  ?></td>
                    <td align="center">Conveyor coverage or quantity of product per batch</td>
                </tr>
                <?php foreach($calModeHeadBalance as $resoptHeads) { ?>
                <tr>
                    <td align="center" colspan="2"><?php echo $resoptHeads['equipName'] ?></td>
                    <td align="center"><?php echo $arrayParam['proInfoStudy']['avgTInitial'] ?></td>
                    <td align="center"><?php echo $resoptHeads['tfp'] ?></td>
                    <td align="center"><?php echo $resoptHeads['tr'] ?></td>
                    <td align="center"><?php echo $resoptHeads['ts'] ?></td>
                    <td align="center"><?php echo $resoptHeads['vep'] ?></td>
                    <td align="center"><?php echo $resoptHeads['dhp'] ?></td>
                    <td align="center"><?php echo $resoptHeads['conso'] ?></td>
                    <td align="center" colspan="2"><?php echo $resoptHeads['toc'] ?></td>
                    <td align="center"><?php echo $resoptHeads['precision'] ?></td>
                </tr>
                <?php } ?>
            </table>
            </div>
        </div>
        <?php } ?>

        <?php if ($arrayParam['params']['isSizingValuesMax'] == 16) { ?> 
        <?php if (!empty($calModeHbMax)) { ?>
        <h4>Maximum product flowrate</h4>
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
                    <th colspan="4">Maximum product flowrate </th>
                    <th rowspan="2">Precision of the high level calculation. (%)</th>
                </tr>
                <tr>
                    <td>Hourly production capacity <?php echo "(" . $arrayParam['symbol']['productFlowSymbol'] . " )" ?></td>
                    <td colspan="2">Cryogen consumption (product + equipment heat load) <?php echo "(" . $arrayParam['symbol']['consumMaintienSymbol'] . " )" . "/" . $arrayParam['symbol']['perUnitOfMassSymbol']  ?></td>
                    <td>Conveyor coverage or quantity of product per batch</td>
                </tr>
                <?php foreach($calModeHbMax  as $resoptimumHbMax) { ?>
                <tr>
                    <td align="center" colspan="2"><?php echo $resoptimumHbMax['equipName'] ?></td>
                    <td align="center" ><?php echo $arrayParam['proInfoStudy']['avgTInitial'] ?></td>
                    <td align="center"><?php echo $resoptimumHbMax['tfp'] ?></td>
                    <td align="center"><?php echo $resoptimumHbMax['tr'] ?></td>
                    <td align="center"><?php echo $resoptimumHbMax['ts'] ?></td>
                    <td align="center"><?php echo $resoptimumHbMax['vep'] ?></td>
                    <td align="center"><?php echo $resoptimumHbMax['dhp'] ?></td>
                    <td align="center"><?php echo $resoptimumHbMax['conso'] ?></td>
                    <td align="center" colspan="2"><?php echo $resoptimumHbMax['toc'] ?></td>
                    <td align="center"><?php echo $resoptimumHbMax['precision'] ?></td>
                </tr>
                <?php } ?>
            </table>
            </div>
        </div>
        <?php }?>

        <?php if ($arrayParam['params']['SIZING_GRAPHE'] == 1) { ?> 
        <h4>Graphic</h4>
        <div class ="graphic" align="center">
            <img src="<?php echo $arrayParam['host'] . "/sizing/" . $arrayParam['study']['USERNAM'] . "/" .  $arrayParam['study']['ID_STUDY'] . ".png" ?>" style="max-width: 640px">
        </div>
        <?php } ?>
        <?php } ?>


        <?php if (!empty($heatexchange)) { ?>
        <?php if (($arrayParam['params']['ENTHALPY_V'] == 1) || ($arrayParam['params']['ENTHALPY_G'] == 1)) { ?>
        <h3 style ="background-color:#268EE2">Heat Exchange</h3>
        <?php } ?>
            <!-- <h4>get first Equipment </h4> -->
            <?php foreach($heatexchange as $key=> $resheatexchanges) { ?>
            <?php if ($arrayParam['params']['ENTHALPY_V'] == 1) { ?>
            <div class="heat-exchange">
                <div class="table-responsive">
                <table class ="table table-bordered" border="1">
                    <tr>
                        <th colspan="2">Equipment</th>
                        <th align="center"> <?php echo $resheatexchanges['result'][0]['x']?></th>
                        <th align="center"> <?php echo $resheatexchanges['result'][1]['x'] ?></th>
                        <th align="center"> <?php echo $resheatexchanges['result'][2]['x'] ?></th>
                        <th align="center"> <?php echo $resheatexchanges['result'][3]['x'] ?></th>
                        <th align="center"> <?php echo $resheatexchanges['result'][4]['x'] ?></th>
                        <th align="center"> <?php echo $resheatexchanges['result'][5]['x'] ?></th>
                        <th align="center"> <?php echo $resheatexchanges['result'][6]['x'] ?></th>
                        <th align="center"> <?php echo $resheatexchanges['result'][7]['x'] ?></th>
                        <th align="center"> <?php echo $resheatexchanges['result'][8]['x'] ?></th>
                        <th align="center"> <?php echo $resheatexchanges['result'][9]['x'] ?></th>
                    </tr>
                    <tr>
                        <td colspan="2"><?php echo $resheatexchanges['equipName'] . " - (v1.0)"  ?></td>
                        <td align="center"><?php echo $resheatexchanges['result'][0]['y'] ?></td>
                        <td align="center"><?php echo $resheatexchanges['result'][1]['y'] ?></td>
                        <td align="center"><?php echo $resheatexchanges['result'][2]['y'] ?></td>
                        <td align="center"><?php echo $resheatexchanges['result'][3]['y'] ?></td>
                        <td align="center"><?php echo $resheatexchanges['result'][4]['y'] ?></td>
                        <td align="center"><?php echo $resheatexchanges['result'][5]['y'] ?></td>
                        <td align="center"><?php echo $resheatexchanges['result'][6]['y'] ?></td>
                        <td align="center"><?php echo $resheatexchanges['result'][7]['y'] ?></td>
                        <td align="center"><?php echo $resheatexchanges['result'][8]['y'] ?></td>
                        <td align="center"><?php echo $resheatexchanges['result'][9]['y'] ?></td>
                    </tr>
                </table>
                </div>
                <?php } ?> 
                
                <?php if ($arrayParam['params']['ENTHALPY_G'] == 1) { ?>
                <div id="hexchGraphic" align="center">
                    <img src="<?php echo $arrayParam['host'] . "/heatExchange/" . $arrayParam['study']['USERNAM'] . "/" .  $resheatexchanges['idStudyEquipment'] . ".png" ?>" style="max-width :640px">
                </div>
                <?php } ?>
                <?php } ?>
            </div>
            <?php } ?> 

         
        <?php if (!empty($proSections)) { ?>
        <?php if (($arrayParam['params']['ISOCHRONE_V'] == 1) || ($arrayParam['params']['ISOCHRONE_G'] == 1)) { ?> 
        <h3 style ="background-color:#268EE2">Product Section</h3>
        <?php } ?> 
        <!-- <h4>get first Equipment </h4> -->
            <?php foreach ($proSections as $resproSections) {?>
            <?php if ($arrayParam['params']['ISOCHRONE_V'] == 1) { ?>  
            <h4><?php echo $resproSections['equipName'] ?></h4>
                <?php if ($resproSections['selectedAxe'] == 1) {?> 
                Values - Dimension <?php echo $resproSections['selectedAxe'] . "(" . "*," . $resproSections['axeTemp'][0] . "," . $resproSections['axeTemp'][0] . ")" . "(" . $resproSections['prodchartDimensionSymbol'] . ")" ?>  
                <?php } else if ($resproSections['selectedAxe'] == 2) { ?>
                Values - Dimension <?php echo $resproSections['selectedAxe'] . "(" . $resproSections['axeTemp'][0] . ",*," . $resproSections['axeTemp'][0] . ")" . "(" . $resproSections['prodchartDimensionSymbol'] . ")" ?>  
                <?php } else if ($resproSections['selectedAxe'] == 3) {?>
                Values - Dimension <?php echo $resproSections['selectedAxe'] . "(" . $resproSections['axeTemp'][0] . "," . $resproSections['axeTemp'][0] . ",*" . ")" . "(" . $resproSections['prodchartDimensionSymbol'] . ")" ?>  
                <?php } ?>
                <div class="values-dim2">
                    <div class="table-responsive">
                    <table class ="table table-bordered" border="1">
                        <tr>
                            <th align="center">Node number</th>
                            <th align="center">Position Axis 1 <?php echo "(" . $resproSections['prodchartDimensionSymbol'] . ")" ?></th>
                            <?php foreach ($resproSections['resultLabel'] as $index => $labelTemp) { ?>
                                <th align="center">T° at <?php echo $resproSections['resultLabel'][$index] . $resproSections['timeSymbol'] . "(" . $resproSections['temperatureSymbol'] . ")" ?></th>
                            <?php }?>
                        </tr>
                        <?php foreach ($resproSections['result']['recAxis'] as $key=> $node) {?>
                        <tr>
                            <td align="center"> <?php echo $key?></td>
                            <td align="center"> <?php echo $resproSections['dataChart'][0][$key]['y']?></td>
                            <?php foreach ($resproSections['dataChart'] as $index => $dbchart) { ?>
                            <td align="center"> <?php echo $resproSections['dataChart'][$index][$key]['x'] ?></td>
                            <?php }?>
                        </tr>
                        <?php }?>
                    </table>
                    </div>
                </div>
                <?php }?>


                <div class="graphic-dim2" align="center"> 
                <?php if ($arrayParam['params']['ISOCHRONE_G'] == 1) { ?> 
                <?php if ($resproSections['selectedAxe'] == 1) {?> 
                Graphic - Dimension <?php echo $resproSections['selectedAxe'] . "(" . "*," . $resproSections['axeTemp'][0] . "," . $resproSections['axeTemp'][0] . ")" . "(" . $resproSections['prodchartDimensionSymbol'] . ")" ?> 
                <p> 
                    <img src="<?php echo $arrayParam['host'] . "/productSection/" . $arrayParam['study']['USERNAM'] . "/" .  $resproSections['idStudyEquipment'] . "-" . $resproSections['selectedAxe'] . ".png" ?>" style="max-width: 640px">
                    <?php } else if ($resproSections['selectedAxe'] == 2) { ?>
                </p>
                Graphic - Dimension <?php echo $resproSections['selectedAxe'] . "(" . $resproSections['axeTemp'][0] . ",*," . $resproSections['axeTemp'][0] . ")" . "(" . $resproSections['prodchartDimensionSymbol'] . ")" ?>  
                <p>
                    <img src="<?php echo $arrayParam['host'] . "/productSection/" . $arrayParam['study']['USERNAM'] . "/" .  $resproSections['idStudyEquipment'] . "-" . $resproSections['selectedAxe'] . ".png" ?>" style="max-width: 640px">
                    <?php } else if ($resproSections['selectedAxe'] == 3) {?>
                </p>
                Graphic - Dimension <?php echo $resproSections['selectedAxe'] . "(" . $resproSections['axeTemp'][0] . "," . $resproSections['axeTemp'][0] . ",*" . ")" . "(" . $resproSections['prodchartDimensionSymbol'] . ")" ?>  
                <p>
                    <img src="<?php echo $arrayParam['host'] . "/productSection/" . $arrayParam['study']['USERNAM'] . "/" .  $resproSections['idStudyEquipment'] . "-" . $resproSections['selectedAxe'] . ".png" ?>" style="max-width: 640px">
                    <?php } ?>
                </p>
                </div>
                <?php } ?>
            <?php } ?>
        <?php } ?>   
        
        <?php if (!empty($timeBase)) { ?>
        <?php if (($arrayParam['params']['ISOVALUE_V'] == 1) || ($arrayParam['params']['ISOVALUE_G'] == 1)) { ?> 
        <h3 style ="background-color:#268EE2">Product Graph - Time Based</h3>
        <?php } ?>   
            <?php if ($arrayParam['params']['ISOVALUE_V'] == 1) { ?> 
            <?php foreach ($timeBase as $timeBases) { ?>
            <h4><?php echo $timeBases['equipName'] ?></h4>
            <div class="values-graphic"> 
                <div class="table-responsive">
                <table class ="table table-bordered" border="1">
                    <tr>
                        <th align="center">Points</th>
                        <th align="center"><?php echo "(" . $timeBases['timeSymbol'] . " )" ?></th>
                        <?php foreach ($timeBases['result'] as $points) { ?>
                        <th align="center"><?php echo $points['points']?></th>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td align="center"><?php echo "Top" . "(" . $timeBases['label']['top'] . ")" ?> </td>
                        <td align="center"><?php echo "(" . $timeBases['temperatureSymbol'] . " )" ?></td>
                        <?php foreach ($timeBases['result'] as $tops) { ?>
                        <td align="center"><?php echo $tops['top']?></td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td align="center"><?php echo "Internal" . "(" . $timeBases['label']['int'] . ")" ?></td>
                        <td align="center"><?php echo "(" . $timeBases['temperatureSymbol'] . " )" ?></td>
                        <?php foreach ($timeBases['result'] as $internals) { ?>
                        <td align="center"><?php echo $internals['int']?></td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td align="center"><?php echo "Bottom" . "(" . $timeBases['label']['bot'] . ")" ?></td>
                        <td align="center"><?php echo "(" . $timeBases['temperatureSymbol'] . " )" ?></td>
                        <?php foreach ($timeBases['result'] as $bottoms) { ?>
                        <td align="center"><?php echo $bottoms['bot']?></td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td align="center">Avg. Temp.</td>
                        <td align="center"><?php echo "(" . $timeBases['temperatureSymbol'] . " )" ?></td>
                        <?php foreach ($timeBases['result'] as $avgs) { ?>
                        <td align="center"><?php echo $avgs['average']?></td>
                        <?php } ?>
                    </tr>
                </table>
                </div>
            </div>
            <?php } ?>
            <?php if ($arrayParam['params']['ISOVALUE_G'] == 1) { ?>                
            <div class="pro-graphic" align="center">
            <img src="<?php echo $arrayParam['host'] . "/timeBased/" . $arrayParam['study']['USERNAM'] . "/" .  $timeBases['idStudyEquipment'] . ".png" ?>" style="max-width: 640px">
            </div>
            <?php } ?>
        <?php } ?>
        <?php } ?>

        <?php if (!empty($pro2Dchart)) { ?>
            <?php if ($arrayParam['params']['CONTOUR2D_G'] == 1) { ?> 
            <h3 style ="background-color:#268EE2">2D Outlines</h3>
                <?php foreach ($pro2Dchart as $pro2Dcharts) {?>
                <h3><?php echo $pro2Dcharts['equipName'] ?></h3>
                    <div class="outlines" align="center"> 
                    <img src="<?php echo $arrayParam['host'] . "/heatmap/" . $arrayParam['study']['USERNAM'] . "/" .  $pro2Dcharts['idStudyEquipment'] . "/" . $pro2Dcharts['lfDwellingTime'] . "-" 
                    . $pro2Dcharts['chartTempInterval'][0] . "-" . $pro2Dcharts['chartTempInterval'][1] . "-" . $pro2Dcharts['chartTempInterval'][2] . ".png" ?>" style="max-width: 640px">
                    </div>
                <?php } ?>
        <?php } ?>
        <?php } ?>

        <h3 style ="background-color:#268EE2">Comments</h3>
        
        <div class="comment">
            <p>
                <textarea disabled class="form-control" rows="5"><?php echo $arrayParam['params']['REPORT_COMMENT'] ?></textarea>
            </p>
        </div>

        <div class="info-company">
            <div class="text-center">
                <p>
                    <img src="<?php echo (!empty($arrayParam['study']['reports'][0]['PHOTO_PATH'])) ? $arrayParam['study']['reports'][0]['PHOTO_PATH'] : $arrayParam['host'] . "/images/globe_food.gif"?>">
                </p>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered">
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
    </body>
</html>