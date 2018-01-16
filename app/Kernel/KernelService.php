<?php

namespace App\Kernel;

class KernelService
{
    public function __construct()
    {
        $this->ic = null;
    }

    public function getKernelObject($name)
    {        
        if (!$this->ic) {
            $config_path = \config_path('ice.cfg');
            putenv('ICE_CONFIG='.$config_path);
            $this->ic = \Ice\initialize();
        }

        $strProxy = "{$name}:tcp -h ". getenv('KERNEL_HOST') ." -p ". getenv('KERNEL_PORT');
        $base = $this->ic->stringToProxy($strProxy);
        $className = "\\Cryosoft\\$name\\I{$name}PrxHelper";
        $obj = call_user_func( array( $className, 'checkedCast' ),$base );
        if(!$obj) {
            throw new RuntimeException("Invalid proxy");
        }
        return $obj;
    }

    public function getConfig($idUser, $idStudy = 0, $idTmp = 0, $connectToDB = 1, $initTrace = 1, $logPath = null)
    {
        if (!$logPath)
            $logPath = getenv('KERNEL_LOG');
        return new \Cryosoft\stSKConf(
            getenv('KERNEL_ODBC'), 
            getenv('KERNEL_USER'), 
            getenv('KERNEL_PASS'), 
            $logPath,
            $idUser, $idStudy, $idTmp, $connectToDB, $initTrace
        );
    }
    
   function __destruct()
   {
        if($this->ic) {
            $this->ic->destroy(); // Clean up
        }
   }
}
