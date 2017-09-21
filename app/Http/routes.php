<?php

/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It's a breeze. Simply tell Laravel the URIs it should respond to
  | and give it the controller to call when that URI is requested.
  |
 */

Route::get('/', function () {
    return redirect('public/vendor/l5-swagger/');
});

Route::get('/updateJxSubscriptions', function () {
     dispatch(new \App\Jobs\JxSubscriptionData());
});

Route::get('/sbbAuthorizationNotification', function () {
     dispatch(new \App\Jobs\SBBAuthorizationNotification());
});

Route::get('/TriggerTrafficNotifications', function () {
    dispatch(new \App\Jobs\TriggerTrafficNotifications());
});


Route::get('/getJxTrafficData', function () {
     dispatch(new \App\Jobs\TrafficData());
});

Route::get('/getJxCDRData', function () {
     dispatch(new \App\Jobs\CDRData());
});

Route::get('/getJxRealTimeData', function () {
     dispatch(new \App\Jobs\RealTimeData());
});

Route::group(['middleware' => [], 'namespace' => 'V1'], function() {
    Route::get('/permissions', [
        'uses' => 'RolePermissionsController@viewPermissions'
    ]);
});

Route::group(['middleware' => ['api','unique'], 'prefix' => 'v1', 'namespace' => 'V1'], function() {
    Route::get('/acsm-users', [
        'middleware' => ['can:acms-users.list'],
        'uses' => 'AcsmUserController@getUserRoles'
    ]);

    Route::get('/sims', [
        'middleware' => ['can:sims.list'],
        'uses' => 'RadminController@listSIMS'
    ]);

    Route::get('/sim-provisioning/{simId}', [
        'middleware' => ['can:sim-provisioning.list'],
        'uses' => 'RadminController@listSIMProvisioning'
    ]);

    Route::get('/ip-addresses', [
        'middleware' => ['can:ips.list'],
        'uses' => 'MasterDataController@listIPS'
    ]);

    Route::put('/sims', [
        'middleware' => ['can:sim.create'],
        'uses' => 'RadminController@addSIM'
    ]);

    Route::get('/companies', [
        'middleware' => ['can:companies.list'],
        'uses' => 'RadminController@listCompanies'
    ]);
    Route::get('/usergroups', [
        'middleware' => ['can:usergroups.list'],
        'uses' => 'RadminController@listUsergroups'
    ]);

    Route::get('simusers/{usergroupId}', [
        'middleware' => ['can:simusers.list'],
        'uses' => 'RadminController@listAvailableSimUsers'
    ]);
    
    Route::get('/cabinbilling', [
        'middleware' => ['can:cabinbilling.usage'],
        'uses' => 'RadminController@cabinBillingUsage'
    ]);
    Route::get('/showsim/{id}', [
    	'middleware' => [],
    	'uses' => 'RadminController@showSim'
    ]);
    Route::get('/locations', [
        'middleware' => ['can:locations.list'],
        'uses' => 'LocationController@listLocations'
    ]);
    Route::get('/locations/{id}/', [
        'middleware' => ['can:locations.details'],
        'uses' => 'LocationController@listLocationWithId'
    ]);

    Route::put('/locations/{id}/cabinbilling', [
        'middleware' => ['can:locations.update-cabin-billing'],
        'uses' => 'LocationController@setCabinBilling'
    ]);

    Route::get('/locations/{id}/cabinbilling/history', [
        'middleware' => ['can:locations.cb-history'],
        'uses' => 'LocationController@listCabinBillingHistory'
    ]);
	
	Route::delete('/location/{locationId}', [
        'middleware' => ['can:locations.delete'],
        'uses' => 'LocationController@deactivateLocation'
    ]);
	
	Route::get('/systems', [
        'middleware' => ['can:systems.list'],
        'uses' => 'SystemController@listSystems'
    ]);
	
	Route::delete('/system/{systemId}', [
        'middleware' => ['can:systems.delete'],
        'uses' => 'SystemController@deactivateSystem'
    ]);
	
    Route::get('/terminals', [
        'middleware' => ['can:tasks.list'], //FIXME: Change Permission 
        'uses' => 'TerminalController@listTerminals'
    ]);

    Route::get('/terminals/{terminalId}', [
        'middleware' => ['can:tasks.list'], //FIXME: Change Permission 
        'uses' => 'TerminalController@getTerminalDetails'
    ]);

    Route::put('/terminals/{terminalId}', [
        'middleware' => ['can:tasks.list'], //FIXME: Change Permission 
        'uses' => 'TerminalController@updateTerminal'
    ]);
	
	Route::Delete('/terminal/{terminalId}', [
        'middleware' => ['can:terminals.delete'],
        'uses' => 'TerminalController@deactivateTerminal'
    ]);

    Route::post('/terminals', [
        'middleware' => ['can:tasks.list'], //FIXME: Change Permission 
        'uses' => 'TerminalController@createTerminal'
    ]);

    Route::get('/terminals', [
        'middleware' => ['can:tasks.list'], //FIXME: Change Permission 
        'uses' => 'TerminalController@listTerminals'
    ]);

    Route::get('/terminals/{terminalId}', [
        'middleware' => ['can:tasks.list'], //FIXME: Change Permission 
        'uses' => 'TerminalController@getTerminalDetails'
    ]);

    Route::get('/subscriptions', [
        'middleware' => ['can:subscriptions.list'],
        'uses' => 'SubscriptionController@listSubscriptions'
    ]);

    Route::get('/subscriptions/{subscriptionId}', [
        'middleware' => ['can:subscriptions.details'],
        'uses' => 'SubscriptionController@getSubscriptionById'
    ]);

    Route::get('/subscriptions/{subscriptionId}/usage', [
        'middleware' => ['can:subscriptions.usage'],
        'uses' => 'SubscriptionController@getSubscriptionUsageDetails'
    ]);
    
    Route::get('/jx-metrics', [
        'middleware' => ['can:subscriptions.details'],
        'uses' => 'JxController@getJxMetrics'
    ]);
    
    Route::get('/jx-metrics/{subscriptionId}', [
        'middleware' => ['can:subscriptions.details'],
        'uses' => 'JxController@getJxMetricsBySubscriptionId'
    ]);
    
    Route::get('/fleet-location', [
        'middleware' => ['can:subscriptions.details'],
        'uses' => 'JxController@getLastLocation'
    ]);
    
    Route::get('/invoices', [
        'middleware' => ['can:invoices.list'],
        'uses' => 'InvoiceController@listInvoices'
    ]);

    Route::get('/invoices/{invoiceNumber}/download', [
        'middleware' => ['can:invoices.download'],
        'uses' => 'InvoiceController@downloadInvoice'
    ]);

    Route::get('/logs', [
        'middleware' => ['can:logs.list'],
        'uses' => 'CdrController@listCdr'
    ]);

    Route::get('/customers', [
        'middleware' => ['can:customers.list'],
        'uses' => 'CustomerController@listCustomers'
    ]);

    Route::get('/customers/{id}/', [
        'middleware' => ['can:customers.details'],
        'uses' => 'CustomerController@listCustomersWithId'
    ]);

    Route::get('/customers/{id}/cabinbilling/purchases', [
        'middleware' => ['can:customers.cb-purchase-list'],
        'uses' => 'CustomerController@listCabinBillingPurchases'
    ]);

    /* Groups */
    Route::get('/groups', [
        'middleware' => ['can:groups.list'],
        'uses' => 'GroupController@listGroups'
    ]);

    Route::get('/groups/{id}', [
        'middleware' => ['can:groups.details'],
        'uses' => 'GroupController@listGroupsWithId'
    ]);

    Route::post('/groups', [
        'middleware' => ['can:groups.create'],
        'uses' => 'GroupController@createGroup'
    ]);

    Route::put('/groups/{id}', [
        'middleware' => ['can:groups.update'],
        'uses' => 'GroupController@updateGroup'
    ]);

    Route::post('/groups/{id}/locations', [
        'middleware' => ['can:groups.locations.list'],
        'uses' => 'GroupController@addLocationToGroup'
    ]);

    Route::delete('/groups/{id}/locations/{locationId}', [
        'middleware' => ['can:groups.locations.delete'],
        'uses' => 'GroupController@deleteLocationFromGroup'
    ]);

    Route::get('/contacts', [
        'middleware' => ['can:contacts.list'],
        'uses' => 'ContactController@listContacts'
    ]);

    Route::get('/contacts/{id}/', [
        'middleware' => ['can:contacts.details'],
        'uses' => 'ContactController@listContactsWithId'
    ]);

    Route::put('/contacts/{id}', [
        'middleware' => ['can:contacts.update'],
        'uses' => 'ContactController@updateContact'
    ]);

    Route::get('/contacts/{id}/groups', [
        'middleware' => ['can:contacts.groups.list'],
        'uses' => 'ContactController@listContactGroups'
    ]);

    Route::post('/contacts', [
        'middleware' => ['can:contacts.create'],
        'uses' => 'ContactController@createContact'
    ]);

    Route::get('/contact-roles', [
        'middleware' => ['can:roles.list'],
        'uses' => 'ContactRoleController@listRoles'
    ]);

    Route::get('/tasks', [
        'middleware' => ['can:tasks.list'],
        'uses' => 'TaskController@listTasks'
    ]);

    // Master Data
    Route::get('/countries', [
        'middleware' => ['can:master-data.list'],
        'uses' => 'MasterDataController@listCountries'
    ]);

    Route::get('/aircraft-makes', [
        'middleware' => ['can:master-data.list'],
        'uses' => 'MasterDataController@listAircraftMakes'
    ]);
    Route::get('/aircraft-makes/{makeId}/models', [
        'middleware' => ['can:master-data.list'],
        'uses' => 'MasterDataController@listAircraftModels'
    ]);

    Route::get('/apn', [
        'middleware' => ['can:master-data.list'],
        'uses' => 'MasterDataController@listApn'
    ]);

    Route::get('/networks', [
        'middleware' => ['can:master-data.list'],
        'uses' => 'MasterDataController@listNetworks'
    ]);

    Route::get('/leso', [
        'middleware' => ['can:master-data.list'],
        'uses' => 'MasterDataController@listLeso'
    ]);

    Route::get('/streaming-modes', [
        'middleware' => ['can:master-data.list'],
        'uses' => 'MasterDataController@listAvailableStreamingModes'
    ]);

    Route::get('/godirect-filter-levels', [
        'middleware' => ['can:master-data.list'],
        'uses' => 'MasterDataController@listFilteringLevels'
    ]);

    //Roles, Permissions APIs
    Route::get('/roles', [
        'middleware' => ['can:roles.list'],
        'uses' => 'RolePermissionsController@getRoles'
    ]);

    Route::put('/roles', [
        'middleware' => ['can:roles.update'],
        'uses' => 'RolePermissionsController@updateRoles'
    ]);

    Route::get('/permissions', [
        'middleware' => ['can:permissions.list'],
        'uses' => 'RolePermissionsController@getPermissions'
    ]);


    Route::post('/tasks', [
        'middleware' => ['can:tasks.list'],
        'uses' => 'JxDataSyncController@createSampleTask'
    ]);

    //Radmin Sync
    Route::get('/radmin-sync', [
    //'middleware' => ['can:radmin.sync'],
        'uses' => 'RadminController@sync'
    ]);

    //Radmin Sync Master Data
    Route::get('/radmin-sync-masterdata', [
        'middleware' => ['can:radmin.sync-masterdata'],
        'uses' => 'RadminController@syncMasterData'
    ]);
    
	Route::get('/audits', [
        'uses' => 'AuditsController@getAudits'
    ]);
    
    Route::get('/audits/{id}', [
        'uses' => 'AuditsController@getAuditDetails'
    ]);
   
    Route::get('/export/aircraft-models', [
            'middleware' => ['can:sync-data.list'], 
            'uses' => 'ExportDataController@listAllAircraftModels'
    ]);
     
    Route::get('/export/locations', [
            'middleware' => ['can:sync-data.list'], 
            'uses' => 'ExportDataController@listAllLocations'
    ]);
     
    Route::get('/export/services', [
            'middleware' => ['can:sync-data.list'],
            'uses' => 'ExportDataController@listServices'
    ]);
    
    Route::get('/export/systems', [
            'middleware' => ['can:sync-data.list'],
            'uses' => 'ExportDataController@listallSystems'
    ]);
    
    Route::get('/export/system-customers', [
            'middleware' => ['can:sync-data.list'],
            'uses' => 'ExportDataController@systemCustomers'
    ]);
    
    Route::get('/export/system-locations', [
            'middleware' => ['can:sync-data.list'],
            'uses' => 'ExportDataController@systemLocations'
    ]);
    
    Route::get('/export/monthly-logs', [
            'middleware' => ['can:sync-data.list'],
            'uses' => 'ExportDataController@monthlyListCdr'
    ]);
    
    Route::get('/export/godirect-access', [
            'middleware' => ['can:sync-data.list'],
            'uses' => 'ExportDataController@goDirectAccess'
    ]);
    
    Route::get('/export/gd-access-activation-logs', [
            'middleware' => ['can:sync-data.list'],
            'uses' => 'ExportDataController@goDirectActivationLogs'
    ]);
    
    Route::get('/export/services-master', [
            'middleware' => ['can:sync-data.list'],
            'uses' => 'ExportDataController@servicesMaster'
    ]);
    
    Route::get('/export/daily-call-logs', [
        'middleware' => ['can:sync-data.list'],
        'uses' => 'ExportDataController@dailyCallLogs'
    ]);
    
    Route::get('/export/management-company', [
        'middleware' => ['can:sync-data.list'],
        'uses' => 'ExportDataController@managementCompany'
    ]);

    Route::get('/list-jx-subscriptions', [
        'middleware' => ['can:jx-subscriptions.list'],
        'uses' => 'MobilewareController@listJxSubscriptions'
    ]);
    
    Route::post('/terminals/{terminalId}', [
        'middleware' => ['can:tasks.list'], //FIXME: Change Permission
        'uses' => 'TerminalController@createJxTerminalTask'
    ]);

    Route::get('/services', [
        'middleware' => ['can:services.list'],
        'uses' => 'MasterDataController@listVAS'
    ]);
    
    Route::get('/location/{locationId}/services', [
        'middleware' => ['can:services.get'],
        'uses' => 'LocationController@getServices'
    ]);
    
    Route::post('/location/{locationId}/service', [
        'middleware' => ['can:services.add'],
        'uses' => 'LocationController@addService'
    ]);
    
    Route::put('/location/{locationId}/serviceMapping/{serviceMappingId}', [
        'middleware' => ['can:services.update'],
        'uses' => 'LocationController@updateService'
    ]);
    
    Route::delete('/location/{locationId}/serviceMapping/{serviceMappingId}', [
        'middleware' => ['can:services.delete'],
        'uses' => 'LocationController@deleteService'
    ]);
    
    Route::post('/traffic-terminals/{terminalId}', [
        'middleware' => ['can:tasks.list'], //FIXME: Change Permission
        'uses' => 'TrafficShapingController@createFlowGuardTerminal'
    ]);
    
    Route::put('/traffic-terminals-reset', [
        'middleware' => ['can:tasks.list'], //FIXME: Change Permission
        'uses' => 'TrafficShapingController@updateFlowGuardTerminal'
    ]);
    
    Route::get('/traffic-terminals/{terminalId}/usage', [
        'middleware' => ['can:tasks.list'], //FIXME: Change Permission
        'uses' => 'TrafficShapingController@trafficTerminalUsuage'
    ]);
    
    Route::post('/traffic-terminals/{terminalId}/notifications', [
        'middleware' => ['can:tasks.list'], //FIXME: Change Permission
        'uses' => 'TrafficShapingController@trafficTerminalNotifications'
    ]);
    
    Route::get('/traffic-terminals/{terminalId}/notifications', [
        'middleware' => ['can:tasks.list'], //FIXME: Change Permission
        'uses' => 'TrafficShapingController@getTrafficTerminalNotifications'
    ]);
    
    Route::get('/traffic-shaping-notification', [
        'uses' => 'TrafficShapingController@trafficShapingNotificationsSync'
    ]);
    
    /* Route::get('/sims-sync', [
        'middleware' => ['can:sync-data.list'],
        'uses' => 'SimsSyncController@syncSimsData'
    ]); */
});

Route::get('auth/login', 'Auth\AuthController@getLogin');
Route::post('auth/login', 'Auth\AuthController@postLogin');
Route::get('auth/logout', 'Auth\AuthController@getLogout');
