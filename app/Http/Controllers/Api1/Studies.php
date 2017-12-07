<?php

namespace App\Http\Controllers\Api1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Kernel\KernelService;

class Studies extends Controller
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
     * @var App\Kernel\KernelService
     */
    protected $kernel;
    

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request, Auth $auth, KernelService $kernel)
    {
        $this->request = $request;
        $this->auth = $auth;
        $this->kernel = $kernel;
    }

    //
    public function findStudies()
    {
        $studies = $this->auth->user()->studies;
        return $studies;
    }

    public function deleteStudyById($id)
    {

    }

    public function getStudyById($id)
    {
        $study = \App\Models\Study::find($id);
        return $study;
    }

    public function saveStudyAs($id)
    {

    }

    public function refreshMesh($id) {
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, $id);
        
        return $this->kernel->getKernelObject('MeshBuilder')->MBMeshBuild($conf);
    }

    public function openStudy($id)
    {
        $conf = $this->kernel->getConfig($this->auth->user()->ID_USER, intval($id), -1);

        return $this->kernel->getKernelObject('StudyCleaner')->SCStudyClean($conf, 40);
    }
}
