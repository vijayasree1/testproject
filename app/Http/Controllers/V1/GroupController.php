<?php

namespace App\Http\Controllers\V1;

use App\Http\Models\ContactGroupMapping;
use App\Http\Models\GroupLocationMapping;
use App\Http\Controllers\Controller;
use App\Http\Models\Contact;
use App\Http\Models\Group;
use App\Http\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Response;
use Validator;

class GroupController extends Controller
{

    /**
    * @SWG\Get(
    *     path="/groups",
    *     summary="/groups resource",
    *     tags={"groups"},
    *     description="This resource is dedicated to querying data around Groups.",
    *     operationId="listGroups",
    *     consumes={"application/json"},
    *     produces={"application/json"},
    *     @SWG\Parameter(
    *         name="page",
    *         in="query",
    *         description="Filter by Page Number",
    *         required=false,
    *         type="integer",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Parameter(
    *         name="page_size",
    *         in="query",
    *         description="Number of results Per Page",
    *         required=false,
    *         type="integer",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Parameter(
    *         name="customerId",
    *         in="query",
    *         description="Filter Groups by Customer Id",
    *         required=false,
    *         type="integer",
    *         @SWG\Items(type="integer"),
    *         collectionFormat="csv"
    *     ),
    *     @SWG\Parameter(
    *         name="hwContactId",
    *         in="query",
    *         description="Filter Groups by Honeywell Id",
    *         required=false,
    *         type="integer",
    *         @SWG\Items(type="string"),
    *         collectionFormat="csv"
    *     ),
    *     @SWG\Parameter(
    *         name="contactId",
    *         in="query",
    *         description="Filter Groups by Contact Id",
    *         required=false,
    *         type="integer",
    *         @SWG\Items(type="string"),
    *         collectionFormat="csv"
    *     ),
    *     @SWG\Parameter(
    *         name="locationId",
    *         in="query",
    *         description="Filter Groups by Location Id",
    *         required=false,
    *         type="integer",
    *         @SWG\Items(type="string"),
    *         collectionFormat="csv"
    *     ),
    *     @SWG\Response(
    *         response=200,
    *         description="Success",
    *         @SWG\Schema(
    *             type="array",
    *             @SWG\Items(ref="#/definitions/Group")
    *         ),
    *     ),
    *     @SWG\Response(
    *         response="400",
    *         description="Invalid tag value",
    *     ),
    *     @SWG\Response(
    *         response="401",
    *         description="Unauthorized access",
    *     )
    *     
    * ),
    */

    /*DB::listen(function($sql){
        echo $sql->sql;
    });*/
        

    public function listGroups(Request $request)
    {

        $contactQuery = Group::query();
        $contactQuery->distinct()->select('groups.*');
        $contactQuery->leftJoin('contact_groups', 'groups.Group_Idx', '=', 'contact_groups.Group_Idx');
        $contactQuery->leftJoin('contact', 'contact_groups.Contact_Idx', '=', 'contact.Idx');
        $contactQuery->leftJoin('group_locations', 'groups.Group_Idx', '=', 'group_locations.Group_Idx');
        $contactQuery->leftJoin('location', 'location.Idx', '=', 'group_locations.Location_Idx');
        $contactQuery->join('customer', 'customer.Idx', '=', 'groups.Customer_Idx');

         $searchParams = [
            'customerId' => [
                'columnName' => 'customer.Idx',
                'operator' => '='
            ],
            'contactId' => [
                'columnName' => 'contact.Idx',
                'operator' => '='
            ],
            'locationId' => [
                'columnName' => 'location.Idx',
                'operator' => '='
            ],
            'hwContactId' => [
                'columnName' => 'contact.Honeywell_Id',
                'operator' => '='
            ]
        ];

        foreach ($searchParams as $searchParam => $column) {
            if( !is_null($request->input($searchParam)) ) {
                $contactQuery->where($column['columnName'], $column['operator'], $request->input($searchParam));
            }
        }

        if( $contactQuery->count() < 1 ) {
            //return response('Group not found', 200);
            return response()->json(
                 'Group Not Found', 404
            );
        }

        return $contactQuery->paginate(intval(Input::input('page_size', 50))); //->toSql()
    }


    /**
    * @SWG\Get(
    *     path="/groups/{groupId}",
    *     summary="/groups with Id resource",
    *     tags={"groups"},
    *     description="This resource is dedicated to querying data around Groups.",
    *     operationId="listGroupsWithId",
    *     consumes={"application/json"},
    *     produces={"application/json"},
    *     @SWG\Parameter(
    *         name="page",
    *         in="query",
    *         description="Filter by Page Number",
    *         required=false,
    *         type="integer",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Parameter(
    *         name="page_size",
    *         in="query",
    *         description="Number of results Per Page",
    *         required=false,
    *         type="integer",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Parameter(
    *         name="groupId",
    *         in="path",
    *         description="Filter Groups by Group Id",
    *         required=true,
    *         type="integer",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Response(
    *         response=200,
    *         description="Success",
    *         @SWG\Schema(
    *             type="array",
    *             @SWG\Items(ref="#/definitions/Group")
    *         ),
    *     ),
    *     @SWG\Response(
    *         response="400",
    *         description="Invalid tag value",
    *     ),
    *     @SWG\Response(
    *         response="401",
    *         description="Unauthorized access",
    *     )
    *     
    * ),
    */

    public function listGroupsWithId(Request $request, $id)
    {

        /*DB::listen(function($sql){
            echo $sql->sql;
        });*/

        $contactQuery = Group::query();
        $contactQuery->distinct()->select('groups.*');
        $contactQuery->leftJoin('contact_groups', 'groups.Group_Idx', '=', 'contact_groups.Group_Idx');
        $contactQuery->leftJoin('contact', 'contact_groups.Contact_Idx', '=', 'contact.Idx');
        $contactQuery->leftJoin('group_locations', 'groups.Group_Idx', '=', 'group_locations.Group_Idx');
        $contactQuery->leftJoin('location', 'location.Idx', '=', 'group_locations.Location_Idx');
        $contactQuery->join('customer', 'customer.Idx', '=', 'groups.Customer_Idx');
        $contactQuery->where('groups.Group_Idx', '=', $id);
   
        $contactQuery->with('contacts')->with('locations')->get();

        if( $contactQuery->count() < 1 ) {
            return response('Group not found', 200);
        }

        return $contactQuery->paginate(intval(Input::input('page_size', 50))); //->toSql()
    }


    /**
    * @SWG\Post(
    *     path="/groups",
    *     summary="/create a group, select contacts + locations",
    *     tags={"groups"},
    *     description="This resource is dedicated to posting data to Groups.",
    *     operationId="createGroup",
    *     consumes={"application/json"},
    *     produces={"application/json"},
    *     @SWG\Parameter(
    *         name="groupName",
    *         in="formData",
    *         description="Group Name",
    *         required=true,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="groupDescription",
    *         in="formData",
    *         description="Group Description",
    *         required=true,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="customerId",
    *         in="formData",
    *         description="Customer Id",
    *         required=true,
    *         type="integer",
    *     ),
    *     @SWG\Parameter(
    *         name="Start_Date",
    *         in="formData",
    *         description="Start_Date",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="End_Date",
    *         in="formData",
    *         description="End_Date",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Response(
    *         response=200,
    *         description="Success",
    *         @SWG\Schema(
    *             type="array",
    *             @SWG\Items(ref="#/definitions/Group")
    *         ),
    *     ),
    *     @SWG\Response(
    *         response="400",
    *         description="Invalid tag value",
    *     ),
    *     @SWG\Response(
    *         response="401",
    *         description="Unauthorized access",
    *     )
    *     
    * ),
    */

    public function createGroup(Request $request) {

        /*DB::listen(function($sql){
            echo $sql->sql;
        });*/

        try {

            \DB::beginTransaction();
            $group = new Group([
                'groupName' => $request['groupName'],
                'groupDescription' => $request['groupDescription'],
                'customerId' => $request['customerId'],
            ]);

            if (!$group->validate($request->method())) {
                return response($group->errors(), 400);
            }
            
            $group->save();
       
            if (empty($request['startDate'])) { $group->startDate = \Carbon::now(); } else { $group->startDate = $request['startDate']; }
            if (empty($request['endDate'])) { $group->endDate = null; } else { $group->endDate = $request['endDate']; }
            //$group->endDate = $request['End_Date'];

            $contacts = Contact::find($request['contact']); //Contact is Contact[] from the HTML Form name input.
            if (!empty($contacts)) {
            foreach ($contacts as $contact)
                {
                    if ($contact->customerId != $group->customerId) { throw new \Exception("A contact specified does not belong to the customer", 301); }
                    //$group->contacts()->attach($contact->id, ['Start_Date' => \Carbon::now()]);
                    
                    $group_contact = new ContactGroupMapping(['Contact_Idx' => $contact->id,'Group_Idx' => $group->groupId, 'Start_Date' => \Carbon::now()]);
                    $group_contact->save();
                }
            }

            $locations = Location::find($request['location']); //Location is Location[] from the HTML Form name input.
            if (!empty($locations)) {
                foreach ($request['location'] as $location) { //location idx  

                    $locationIds = DB::table('location')->select('location.Idx as LocationIdx', 'customer.Idx as CustomerIdx')
                    ->join('system_location_mapping','system_location_mapping.Location_Idx', '=', 'location.Idx' )
                    ->join('system','system_location_mapping.System_Idx', '=', 'system.Idx' )
                    ->join('system_customer_mapping','system_customer_mapping.System_Idx', '=', 'system.Idx' )
                    ->join('customer','system_customer_mapping.Customer_Idx', '=', 'customer.Idx' )
                    ->whereRaw('(((now() >= system_customer_mapping.Start_Date and system_customer_mapping.End_Date is NULL) OR (now() > system_customer_mapping.Start_Date and now() < system_customer_mapping.End_Date))
                        AND ((now() >= system_location_mapping.Start_Date and system_location_mapping.End_Date is NULL) OR (now() > system_location_mapping.Start_Date and now() < system_location_mapping.End_Date))) ')
                    ->where('location.Idx', '=', $location)
                    ->where('customer.Idx', '=', $group->customerId)
                    ->groupBy('location.Idx')->get();

                    if (count($locationIds) == 0) {  throw new \Exception("A location specified does not belong to the customer", 1); };

                    //$group->locations()->attach($location, ['Group_Idx' => $group->groupId, 'Start_Date' => \Carbon::now(), 'Updated_On' => \Carbon::now(), 'Last_Updated_By' => 'API REQUEST']);
                    $group_location = new GroupLocationMapping(['Location_Idx' => $location,'Group_Idx' => $group->groupId, 'Start_Date' => \Carbon::now(), 'Updated_On' => \Carbon::now(), 'Last_Updated_By' => 'API REQUEST']);
                    $group_location->save();
                }
            }

            if($group->save()) {
                \DB::commit();
                return response(array('groupId' => $group->groupId), 200);
            } else {
                \DB::rollback();
                return response(301);
            }

        }
        catch (\Exception $e) {
            $error = $e->getMessage();
            \DB::rollback();
            if($error){
                return response($error, 301);
            }
        }


    }


    /**
    * @SWG\Put(
    *     path="/groups/{groupId}",
    *     summary="/edit a group, add/remove contacts/locations",
    *     tags={"groups"},
    *     description="This resource is dedicated to edit Groups.",
    *     operationId="updateGroup",
    *     consumes={"application/x-www-form-urlencoded"},
    *     produces={"application/json"},
    *     @SWG\Parameter(
    *         name="groupId",
    *         in="path",
    *         description="Filter Groups by Group Id",
    *         required=true,
    *         type="integer",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Parameter(
    *         name="groupName",
    *         in="formData",
    *         description="Group Name",
    *         required=true,
    *         type="string",
    *         @SWG\Items(type="string"),
    *     ),
    *     @SWG\Parameter(
    *         name="groupDescription",
    *         in="formData",
    *         description="Group Description",
    *         required=true,
    *         type="string",
    *         @SWG\Items(type="string"),
    *     ),
    *     @SWG\Parameter(
    *         name="contact",
    *         in="formData",
    *         description="Add/Remove Contacts",
    *         required=false,
    *         type="array",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Parameter(
    *         name="location",
    *         in="formData",
    *         description="Add/Remove Locations",
    *         required=false,
    *         type="array",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Response(
    *         response=200,
    *         description="Success",
    *         @SWG\Schema(
    *             type="array",
    *             @SWG\Items(ref="#/definitions/Group")
    *         ),
    *     ),
    *     @SWG\Response(
    *         response="400",
    *         description="Invalid tag value",
    *     ),
    *     @SWG\Response(
    *         response="401",
    *         description="Unauthorized access",
    *     )
    *     
    * ),
    */

    public function updateGroup(Request $request, $id) {

        /*DB::listen(function($sql){
            echo $sql->sql;
        });*/

        try {

            \DB::beginTransaction();

            $group = Group::find($id); //find the group based on the ID passed into the function

            $data_contacts = $group->contacts()->lists('id')->toArray(); //cast this to an array based on the contact ids currently in the group
            $data_locations = $group->locations()->lists('id')->toArray(); //cast this to an array based on the contact ids currently in the group
            //print_r($data_locations);

            $contacts = array();
            $locations = array();

            if ($request->has('contact')) { $contacts = $request['contact']; }//get the new contacts posted, this is an array
            if ($request->has('location')) { $locations = $request['location']; } //get the new locations posted, this is an array
            $group->groupName = $request['groupName']; 
            $group->groupDescription = $request['groupDescription'];
            if (empty($request['startDate'])) { $group->startDate = \Carbon::now(); } else { $group->startDate = $request['startDate']; }
            if (empty($request['endDate'])) { $group->endDate = null; } else { $group->endDate = $request['endDate']; }

            if (!$group->validate($request->method())) {
                return response($group->errors(), 400);
            }

            //if (!empty($contacts)) { 
                
                $contactsArray = $contacts; //explode(',', $contacts); 
                $contactsNoChanges = array_intersect($contactsArray, $data_contacts); //compare the 2 arrays, get the data which does not contain any changes.
                //var_dump($contactsNoChanges);
                $contactsToAdd = array_diff($contactsArray, $contactsNoChanges); //add contacts to group based on the new data and the data with no changes.
                //var_dump($toadd);
                $contactsToRemove = array_diff($data_contacts, $contactsArray); //remove contacts from group based on the current group data and the data posted
            //}


            //if (!empty($locations)) { 

                $locationsArray = $locations; // explode(',', $locations);
                $locationsNoChanges = array_intersect($locationsArray, $data_locations); //compare the 2 arrays, get the data which does not contain any changes.
                //var_dump($locationsNoChanges);
                $locationsToAdd = array_diff($locationsArray, $locationsNoChanges); //add locations to group based on the new data and the data with no changes.
                //print_r($locationsToAdd);
                $locationsToRemove = array_diff($data_locations, $locationsArray); //remove locations from group based on the current group data and the data posted

            //}

            //only loop is toremove is not empty !empty()
            if (!empty($contactsToRemove)) {
                foreach ($contactsToRemove as $key => $value) {
                    //echo "removed " .$value ." from group:";
                    //$group->contacts()->updateExistingPivot($value, ['End_Date' => \Carbon::now(), 'Active_YN_Flag' => 'N']);
                    
                    $group_contact_remove = ContactGroupMapping::where('Contact_Idx', '=', $value)->where('Group_Idx', '=', $id)->whereRaw('End_Date IS NULL OR End_Date > now()')->first();
                    $group_contact_remove->End_Date = \Carbon::now();
                    //$service->Active_YN_Flag = 'N';
                    $group_contact_remove->save();
                }
            }

            //add contacts
            if (!empty($contactsToAdd)) {
                foreach ($contactsToAdd as $key => $value) {
                    //echo "added " .$value ." to group:";
                    //$group->contacts()->attach($value, ['Start_Date' => \Carbon::now()]);
                    $group_contact_add = new ContactGroupMapping(['Contact_Idx' => $value,'Group_Idx' => $id, 'Start_Date' => \Carbon::now()]);
                    $group_contact_add->save();
                }
            }

            //do the same for locations
            if (!empty($locationsToRemove)) {
                foreach ($locationsToRemove as $key => $value) {
                    //echo "removed location" .$value ." from group:";
                    //$group->locations()->updateExistingPivot($value, ['End_Date' => \Carbon::now(), 'Updated_On' => \Carbon::now(), 'Last_Updated_By' => 'API REQUEST']);
                   
                    $group_location_remove = GroupLocationMapping::where('Location_Idx', '=', $value)->where('Group_Idx', '=', $id)->whereRaw('End_Date IS NULL OR End_Date > now()')->first();
                    $group_location_remove->End_Date = \Carbon::now();
                    $group_location_remove->Updated_On = \Carbon::now();
                    $group_location_remove->Last_Updated_By = 'API REQUEST';
                    $group_location_remove->save();
                    
                    /*GroupLocationMapping::where( 'Location_Idx', '=', $value)
                    ->where('Group_Idx', '=', $id)->whereRaw('End_Date IS NULL OR End_Date > now()')->first()
                    ->update(['End_Date' => \Carbon::now(), 'Updated_On' => \Carbon::now(), 'Last_Updated_By' => 'API REQUEST']);*/
                  
                }
            }

            //do the same for locations
            if (!empty($locationsToAdd)) {
                foreach ($locationsToAdd as $key => $value) {
                    //echo "added location" .$value ." to group:";
                    //$group->locations()->attach($value, ['Start_Date' => \Carbon::now(), 'Updated_On' => \Carbon::now(), 'Last_Updated_By' => 'API REQUEST']);
                    $group_location_add = new GroupLocationMapping(['Location_Idx' => $value,'Group_Idx' => $id, 'Start_Date' => \Carbon::now(), 'Updated_On' => \Carbon::now(), 'Last_Updated_By' => 'API REQUEST']);
                    $group_location_add->save();
                }
            }

            if($group->save()) {
                \DB::commit();
                return response(array('groupId' => $group->groupId),200);
            } else {
                \DB::rollback();
                return response(301);
            }

        }
        catch (\Exception $e) {
            $error = $e->getMessage();
            \DB::rollback();
            if($error){
                return response($error, 301);
            }
        }

    } //function

    /**
    * @SWG\Delete(
    *     path="/groups/{groupId}/locations/{locationId}",
    *     summary="/removes location from a group",
    *     tags={"groups"},
    *     description="This resource is dedicated to removing a Location from a Group.",
    *     operationId="deleteLocationFromGroup",
    *     consumes={"application/x-www-form-urlencoded"},
    *     produces={"application/json"},
    *     @SWG\Parameter(
    *         name="groupId",
    *         in="path",
    *         description="Group Id",
    *         required=true,
    *         type="integer",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Parameter(
    *         name="locationId",
    *         in="path",
    *         description="Location Id to soft delete.",
    *         required=true,
    *         type="integer",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Response(
    *         response=200,
    *         description="Success",
    *         @SWG\Schema(
    *             type="array",
    *             @SWG\Items(ref="#/definitions/")
    *         ),
    *     ),
    *     @SWG\Response(
    *         response="400",
    *         description="Invalid tag value",
    *     ),
    *     @SWG\Response(
    *         response="401",
    *         description="Unauthorized access",
    *     )
    *     
    * ),
    */

    public function deleteLocationFromGroup(Request $request, $id, $locationId)
    {

        \DB::beginTransaction();
        
        $groupQuery = Group::query();
        $groupQuery->distinct()->select('groups.*');
        $groupQuery->leftJoin('group_locations', 'groups.Group_Idx', '=', 'group_locations.Group_Idx');
        $groupQuery->leftJoin('location', 'location.Idx', '=', 'group_locations.Location_Idx');
        $groupQuery->where('groups.Group_Idx', '=', $id);
        $groupQuery->where('Location_Idx', '=', $locationId);
        $groupQuery->where('group_locations.End_Date', '=', null);
   
        $groupQuery->with('locations')->get();

        if( $groupQuery->count() < 1 ) {
            return response('No active Group/Location combination found', 200);
        }

        //$group = Group::find($id);
        //$group->locations()->updateExistingPivot($locationId, ['End_Date' => \Carbon::now(), 'Updated_On' => \Carbon::now(), 'Last_Updated_By' => 'API REQUEST']);

        $group = GroupLocationMapping::where('Location_Idx', '=', $locationId)->where('Group_Idx', '=', $id)->whereRaw('End_Date IS NULL OR End_Date > now()')->first();
        $group->End_Date = \Carbon::now();
        $group->Updated_On = \Carbon::now();
        $group->Last_Updated_By = 'API REQUEST';
        
        if($group->save()) {
            \DB::commit();
            return response(array('groupId' => $group->groupId, 'locationId' => $locationId), 200);
        } else {
            \DB::rollback();
            return response(301);
        }
    }

    /**
    * @SWG\Post(
    *     path="/groups/{groupId}/locations",
    *     summary="/adds locations to a group (pass locations[] name array with locationIds)",
    *     tags={"groups"},
    *     description="This resource is dedicated to adding Locations to a Group.",
    *     operationId="addLocationToGroup",
    *     consumes={"application/x-www-form-urlencoded"},
    *     produces={"application/json"},
    *     @SWG\Parameter(
    *         name="groupId",
    *         in="path",
    *         description="Group Id",
    *         required=true,
    *         type="integer",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Parameter(
    *         name="locations[]",
    *         in="formData",
    *         description="Locations to add to group.",
    *         required=true,
    *         type="array",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Response(
    *         response=200,
    *         description="Success",
    *         @SWG\Schema(
    *             type="array",
    *             @SWG\Items(ref="#/definitions/")
    *         ),
    *     ),
    *     @SWG\Response(
    *         response="400",
    *         description="Invalid tag value",
    *     ),
    *     @SWG\Response(
    *         response="401",
    *         description="Unauthorized access",
    *     )
    *     
    * ),
    */

    public function addLocationToGroup(Request $request, $id)
    {

        \DB::beginTransaction();

        /*DB::listen(function($sql){
            echo $sql->sql;
        });*/

        $locations = array();
        if ($request->has('locations')) { $locations = $request['locations']; } //get the new locations posted, this is an array

        $group = Group::find($id);

        if (!empty($locations)) {
            foreach ($locations as $key => $value) {

                $groupQuery = Group::query();
                $groupQuery->distinct()->select('groups.*');
                $groupQuery->leftJoin('group_locations', 'groups.Group_Idx', '=', 'group_locations.Group_Idx');
                $groupQuery->leftJoin('location', 'location.Idx', '=', 'group_locations.Location_Idx');
                $groupQuery->where('groups.Group_Idx', '=', $id);
                $groupQuery->where('Location_Idx', '=', $value);
                $groupQuery->where('group_locations.End_Date', '=', null);
           
                $groupQuery->with('locations')->get();

                if( $groupQuery->count() >= 1 ) {
                    return response('Location ' . $value . ' and Group '. $id . ' combination is already active.', 200);
                }

                //$group->locations()->attach($value, ['Start_Date' => \Carbon::now(), 'Updated_On' => \Carbon::now(), 'Last_Updated_By' => 'API REQUEST']);
            
                $group_location = new GroupLocationMapping();
                $group_location->Group_Idx = $id;
                $group_location->Location_Idx = $value;
                $group_location->Last_Updated_By = 'API REQUEST';
                $group_location->Start_Date = \Carbon::now();
                $group_location->Updated_On = \Carbon::now();
                $group_location->save();
            }
            
        }

        if($group->save()) {
            \DB::commit();
            return response(array('groupId' => $group->groupId), 200);
        } else {
            \DB::rollback();
            return response(301);
        }
    }
        
}

