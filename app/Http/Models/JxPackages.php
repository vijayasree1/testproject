<?php

namespace App\Http\Models;

use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "js-packages"})
 */
class JxPackages extends DBManModel
{
    /**
     * @SWG\Property(example="100")
     * @var int
     */
    protected $id;

    /**
     * @SWG\Property(example="50010 - SATCOM1_BAS1_Aviation_Test Package")
     * @var string
     */
    protected $templateName;

    /**
     * @SWG\Property(example="20400")
     * @var string
     */
    protected $templateId;

    /**
     * @SWG\Property(example="50010_SATCOM1_BAS-1_TEST_PACKAGE")
     * @var string
     */
    protected $friendlyName;

    /**
     * @SWG\Property(example="10")
     * @var string
     */
    protected $usage;

    /**
     * @SWG\Property(example="1.2")
     * @var string
     */
    protected $downlinkcir;

    /**
     * @SWG\Property(example="1.2")
     * @var string
     */
    protected $uplinkcir;
    
    /**
     * @SWG\Property(example="1.2")
     * @var string
     */
    protected $downlinkmir;
    
    /**
     * @SWG\Property(example="1.2")
     * @var string
     */
    protected $uplinkmir;
    
    /**
     * @SWG\Property(example="active")
     * @var string
     */
    protected $status;
    
    /**
     * @SWG\Property(example="test")
     * @var string
     */
    protected $type;

    use Eloquence, Mappable;

    protected $table = 'jx_packages';
    protected $primaryKey = 'id';

    protected $maps = [
        'id' => 'id',
        'templateName' => 'templatename',
        'templateId' => 'templateid',
        'friendlyName' => 'friendlyname',
        'usage' => 'monthlyusageinGB',
        'downlinkcir' => 'downlinkcir',
        'uplinkcir' => 'uplinkcir',
        'downlinkmir' => 'downlinkmir',
        'uplinkmir' => 'uplinkmir',
        'status' => 'status',
        'type' => 'type'
    ];
}
