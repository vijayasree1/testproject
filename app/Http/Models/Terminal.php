<?php

namespace App\Http\Models;

use OwenIt\Auditing\Auditable;
use Sofa\Eloquence\Eloquence;
use Sofa\Eloquence\Mappable;

/**
 * @SWG\Definition(required={"name", "Terminal"}, @SWG\Xml(name="Terminal"))
 */
class Terminal extends DBManModel
{
    use Auditable;
    /**
     * @SWG\Property(example=1329)
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(example="SBB")
     * @var string
     */
    public $category;

    /**
     * @SWG\Property(example="2010-11-17 00:00:00")
     * @var datetime
     */
    public $activationDate;

    /**
     * @SWG\Property(example="INMARSAT")
     * @var string
     */
    public $satelliteSystem;

    /**
     * @SWG\Property(example="901112115108364")
     * @var string
     */
    public $terminalIdentifier;
    
    /**
     * @SWG\Property(example="GoDirect Filter Tier 0")
     * @var string
     */
    public $goDirectFilter;

    /**
     * @SWG\Property(example="IMSI")
     * @var string
     */
    public $primaryId;

    use Eloquence, Mappable;

    protected $primaryKey = 'Idx';

    protected $table = 'terminal';

    public $timestamps = false;

    protected $maps = [
        'id' => 'Idx',
        'category' => 'Category',
        'systemId' => 'System_Idx',
        'activationDate' => 'Activation_Date',
        'activatedBy' => 'Activated_By',
        'satelliteSystem' => 'Satellite_System',
        'terminalIdentifier' => 'terminal_identifier',
        'primaryId' => 'Primary_ID',
        'billingEntity' => 'Billing_Entity',
        'psa' => 'psa',
        'comments' => 'Comments',
        'imsi' => 'IMSI',
        'icao' => 'ICAO',
        'serial' => 'Serial',
        'fwdID' => 'FwdID',
        'otherId' => 'other_ID',
        'deactivationDate' => 'Deactivation_Date',
        'qoServiceId' => 'QoService_Idx',
        'iccId' => 'ICC_ID',
        'leso' => 'lesoCompany.Idx',
        'userGroupId' => 'User_Group_Id',
        'firewallFilterId' => 'Firewall_Filter_Idx',
        'goDirectFilter' => 'goDirectFilterService.Firewall_Filter_Name',
        'qos' => 'serviceQos.Service_Idx',
        'status' => 'Status',
        'simUserId' => 'Sim_User_Idx',
        'goDirectAccess' => 'cabinbilling.Status',
        'bssOrderNumber' => 'bssOrder.BSS_Order_Number',
        'lastUpdatedBy' => 'Last_Updated_By',
        'updatedOn' => 'Updated_On',
        'packageDetails' => 'subscription',
        'xiplink' => 'Xiplink',
        'trafficShaping' => 'trafficTerminals'
    ];

    protected $hidden = ['billingEntity', 'psa'];

    protected $visible = ['networks', 'apn', 'services', 'bssOrderNumber', 'comments','notificationContacts'];

    protected $fillable = ['satelliteSystem', 'category', 'systemId', 'primaryId', 'imsi', 'iccId', 'leso', 
                            'billingEntity', 'psa', 'primaryId', 'comments', 'activationDate', 'activatedBy', 'status',
                            'simUserId', 'lastUpdatedBy', 'updatedOn'];

    protected $appends = [];

    public function services()
    {
        return $this->hasMany('App\Http\Models\Service', 'Terminal_Idx');
    }

    public function networks()
    {
        return $this->belongsToMany('App\Http\Models\Network', 'terminal_network_mapping', 'Terminal_Idx', 'Network_Idx')
                    ->withPivot('IP_Allocation_Type', 'Active_YN_Flag', 'PDP_Allowed_YN_Flag')
                    ->where('network.Active_YN_Flag', '=', 'Yes');
    }

    public function apn()
    {
        return $this->belongsToMany('App\Http\Models\APN', 'terminal_apn_mapping', 'Terminal_Idx', 'APN_Idx');
    }

    public function lesoCompany()
    {
        return $this->belongsTo('App\Http\Models\Leso', 'Leso_Mapping_Idx');
    }

    public function goDirectFilterService()
    {
        return $this->belongsTo('App\Http\Models\RadminFirewallTier', 'Firewall_Filter_Idx');
    }

    public function subscription()
    {
        return $this->belongsTo('App\Http\Models\JxSubscription', 'TPK_DID','msisdn')
                    ->where('jx_subscription_raw.status', '=', 'ACTIVE');
    }
    
    public function serviceQos()
    {
        return $this->belongsTo('App\Http\Models\ServicesMaster', 'QoService_Idx');
    }

    public function getTerminalIdentifierAttribute()
    {
        if( !is_null($this->attributes['Primary_ID']) ) 
        {
            return $this->attributes[$this->attributes['Primary_ID']];
        }

        return null;
    }

    public function bssOrder()
    {
        return $this->hasOne('App\Http\Models\BSSOrderNumber', 'Terminal_Idx');
    }

    public function cabinbilling()
    {
        return $this->hasOne('App\Http\Models\Cabinbilling','Terminal_Idx');
    }
    
    public function trafficTerminals()
    {
        return $this->hasMany('App\Http\Models\TrafficShapingTerminal', 'Terminal_Idx')
                    ->whereRaw('Start_Date <= NOW() AND IFNULL(End_Date, NOW()) >= NOW()')
                    ->where('Status','=','ACTIVE');
    }
    
    public function notificationContacts()
    {
        return $this->hasMany('App\Http\Models\TrafficShapingNotification', 'Terminal_Idx')
                    ->whereRaw('Start_Date <= NOW() AND IFNULL(End_Date, NOW()) >= NOW()');
        
    }
}

