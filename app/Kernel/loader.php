<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '/opt/Ice-3.7.0/php');
require_once 'Ice.php';

require_once __DIR__ . '/defines.php';
require_once __DIR__ . '/lib/IBrainCalculator.php';
require_once __DIR__ . '/lib/IComponentBuilder.php';
require_once __DIR__ . '/lib/IConsumptionCalculator.php';
require_once __DIR__ . '/lib/ICryoRunning.php';
require_once __DIR__ . '/lib/IDimMatCalculator.php';
require_once __DIR__ . '/lib/IEconomicCalculator.php';
require_once __DIR__ . '/lib/IFreezeCalculator.php';
require_once __DIR__ . '/lib/IKernelToolCalculator.php';
require_once __DIR__ . '/lib/ILayoutCalculator.php';
require_once __DIR__ . '/lib/IMeshBuilder.php';
require_once __DIR__ . '/lib/IPhamCastCalculator.php';
require_once __DIR__ . '/lib/IPipelineCalculator.php';
require_once __DIR__ . '/lib/IProfitCalculator.php';
require_once __DIR__ . '/lib/IStudyCleaner.php';
require_once __DIR__ . '/lib/IWeightCalculator.php';
require_once __DIR__ . '/lib/IEquipmentBuilder.php';
require_once __DIR__ . '/KernelService.php';