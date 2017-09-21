<?php

namespace App\Http\Controllers\V1;

use App\Http\Models\ContactRoleMapping;
use App\Http\Models\ContactGroupMapping;
use App\Http\Controllers\Controller;
use App\Http\Models\Contact;
use App\Http\Models\Group;
use App\Http\Models\ContactReceiveBilling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Validator;

class ContactController extends Controller
{

    /**
    * @SWG\Get(
    *     path="/contacts",
    *     summary="/contacts resource",
    *     tags={"contacts"},
    *     description="This resource is dedicated to querying data around Contacts.",
    *     operationId="listContacts",
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
    *         name="hwContactId",
    *         in="query",
    *         description="Filter by Honeywell Contact Id",
    *         required=false,
    *         type="integer",
    *         @SWG\Items(type="string"),
    *     ),
    *     @SWG\Parameter(
    *         name="customerId",
    *         in="query",
    *         description="Filter by Customer Id",
    *         required=false,
    *         type="integer",
    *         @SWG\Items(type="string"),
    *     ),
    *     @SWG\Parameter(
    *         name="lastSyncDate",
    *         in="query",
    *         description="Filter by Last Updated Date",
    *         required=false,
    *         type="string",
    *         format="date-time",
    *     ),
    *     @SWG\Response(
    *         response=200,
    *         description="Success",
    *         @SWG\Schema(
    *             type="array",
    *             @SWG\Items(ref="#/definitions/Contact")
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

    public function listContacts(Request $request)
    {
        /*DB::listen(function($sql){
            echo $sql->sql;
        });*/

        $this->validate($request, [
            'lastSyncDate' => 'date_format:Y-m-d H:i:s|regex:/[0-9]{4}\-/',
        ]);
        
        $contactQuery = Contact::query();
        $contactQuery->distinct()
            ->select(DB::raw('contact.*'));

        if( !is_null( $request->input('hwContactId') ) ) {
                $contactQuery->where('Honeywell_Id', '=', $request->input('hwContactId'));
        }

        if( !is_null( $request->input('customerId') ) ) {
                $contactQuery->where('Customer_Idx', '=', $request->input('customerId'));
        }

        if ($request->has('lastSyncDate')) {
            $contactQuery->where('Updated_On', '>', $request->input('lastSyncDate'));
        }
        
        return $contactQuery->paginate(intval(Input::input('page_size', 50))); //->toSql()
    }

    /**
    * @SWG\Get(
    *     path="/contacts/{contactId}",
    *     summary="/contacts with Id resource",
    *     tags={"contacts"},
    *     description="This resource is dedicated to querying data around Contacts.",
    *     operationId="listContactsWithId",
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
    *
    *     @SWG\Parameter(
    *         name="contactId",
    *         in="path",
    *         description="Filter by Contact Id",
    *         required=false,
    *         type="integer",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Parameter(
    *         name="honeywellId",
    *         in="query",
    *         description="Filter by honeywell Id",
    *         required=false,
    *         type="integer",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Response(
    *         response=200,
    *         description="Success",
    *         @SWG\Schema(
    *             type="array",
    *             @SWG\Items(ref="#/definitions/Contact"),
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

    public function listContactsWithId(Request $request, $contactId)
    {

        /*DB::listen(function($sql){
            echo $sql->sql;
        });*/
 
        $contactQuery = Contact::query();
        $contactQuery->distinct()
            ->select(DB::raw('contact.*'))
            ->where('contact.Idx', '=', $contactId)
            ;

        $contactQuery->with(['roles' => function($query) { $query->where('Active_YN_Flag', '=', 'Y'); } ])->with('receive_billing')->with('groups')->get();

        if ($request->has('honeywellId')) {
            $contactQuery->where('contact.Honeywell_Id', '=', $request->input('honeywellId'));
        }

        if( $contactQuery->count() < 1 ) {
            return response('Contact not found', 200);
        }

        return $contactQuery->paginate(intval(Input::input('page_size', 50))); //->toSql()
    }

    /**
    * @SWG\Post(
    *     path="/contacts",
    *     summary="/create a contact",
    *     tags={"contacts"},
    *     description="This resource is dedicated to creating a Contact.",
    *     operationId="createContact",
    *     consumes={"multipart/form-data"},
    *     produces={"application/json"},
    *     @SWG\Parameter(
    *         name="firstName",
    *         in="formData",
    *         description="First Name",
    *         required=true,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="middleName",
    *         in="formData",
    *         description="Middle Name",
    *         required=false,
    *         type="string",
    *     ),    
    *     @SWG\Parameter(
    *         name="lastName",
    *         in="formData",
    *         description="Last Name",
    *         required=true,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="title",
    *         in="formData",
    *         description="Title.  Mr, etc",
    *         enum={"Mr.", "Mrs.", "Miss.", "Lord."},
    *         required=true,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="position",
    *         in="formData",
    *         description="Position. E.g. Manager",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="address",
    *         in="formData",
    *         description="Address.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="address2",
    *         in="formData",
    *         description="Address 2.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="stateProvince",
    *         in="formData",
    *         description="State.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="zipCode",
    *         in="formData",
    *         description="ZIP Code.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="city",
    *         in="formData",
    *         description="City.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="countryCode",
    *         in="formData",
    *         description="Country Code. E.g DKK",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="fixedPhone",
    *         in="formData",
    *         description="Office Phone No.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="mobilePhone",
    *         in="formData",
    *         description="Mobile Phone No.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="fax",
    *         in="formData",
    *         description="Fax No.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="email",
    *         in="formData",
    *         description="Email.",
    *         required=true,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="customerId",
    *         in="formData",
    *         description="Customer Id.",
    *         required=true,
    *         type="integer",
    *     ),
    *     @SWG\Parameter(
    *         name="honeywellId",
    *         in="formData",
    *         description="Honeywell Id.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="group[]",
    *         in="formData",
    *         description="Group Ids.",
    *         required=false,
    *         type="array",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Parameter(
    *         name="roleId",
    *         in="formData",
    *         description="Role Id.",
    *         required=false,
    *         type="integer",
    *     ),
    *     @SWG\Response(
    *         response=200,
    *         description="Success",
    *     @SWG\Schema(ref="#/definitions/Contact"),
    *     ),
    *     @SWG\Response(
    *         response="400",
    *         description="Invalid tag value",
    *     ),
    *     @SWG\Response(
    *         response="401",
    *         description="Unauthorized access",
    *     )
    * ),
    */

    public function createContact(Request $request)
    {

        /*DB::listen(function($sql){
            echo $sql->sql;
        });*/

        try {

        \DB::beginTransaction();

        $contact = Contact::create([
            'firstName' => $request['firstName'],
            'middleName' => $request['middleName'],
            'lastName' => $request['lastName'],
            'title' => $request['title'],
            'position' => $request['position'],
            'address' => $request['address'],
            'address2' => $request['address2'],
            'stateProvince' => $request['stateProvince'],
            'zipCode' => $request['zipCode'],
            'city' => $request['city'],
            'countryCode' => $request['countryCode'],
            'fixedPhone' => $request['fixedPhone'],
            'mobilePhone' => $request['mobilePhone'],
            'fax' => $request['fax'],
            'email' => $request['email'],
            'customerId' => $request['customerId'],
            'honeywellId' => $request['honeywellId'],
            'receive_billing' => $request['receive_billing'],
            'group' => $request['groups'],
            'roleId' => $request['roleId'],
        ]);

        $contact->save();
        
        $contact_id=$contact->id;
        
        if ($request['roleId'] != null) {
            $roleIds = $request['roleId']; 
            if (!empty($roleIds)) {
                foreach ($roleIds as $key => $value) {
                    //$contact->roles()->attach($value, ['Created_On' => \Carbon::now(), 'Created_By' => 'API REQUEST', 'Updated_On' => \Carbon::now(), 'Last_Updated_By' => 'API REQUEST', 'Active_YN_Flag' => 'Y']);
                    $contact_roles_add = new ContactRoleMapping(['Role_Idx'=>$value,'Contact_Idx'=>$contact_id,'Created_On' => \Carbon::now(), 'Created_By' => 'API REQUEST', 'Updated_On' => \Carbon::now(), 'Last_Updated_By' => 'API REQUEST', 'Active_YN_Flag' => 'Y']);
                    $contact_roles_add->save();
                }
            }
        }

        if ($request['receive_billing'] == 'true') {

            $billing = new ContactReceiveBilling;
            $billing->Customer_Idx = $contact->customerId;
            $billing->Contact_Idx_for_E_mail = $contact->id;
            $billing->Attention_Type = $request['receiveBillingAttention'];
            $billing->Receive_Billing_For = $request['receiveBillingFor'];

            if (!$billing->validate($request->method())) {
                return response($billing->errors(), 400);
            }

            $contact->receive_billing()->save($billing);
        }

        $groups = Group::find($request['groups']);
        if (!empty($groups)) {
            foreach ($groups as $group)
            {
                //$contact->groups()->attach($group, ['Start_Date' => \Carbon::now()]);
                $contact_group_add = new ContactGroupMapping(['Group_Idx' => $group->groupId,'Contact_Idx' => $contact_id, 'Start_Date' => \Carbon::now()]);
                $contact_group_add->save();
            }
        }

        //after taking all the requests, we do the validation
        if (!$contact->validate($request->method())) {
            return response($contact->errors(), 400);
        }

        if($contact->save()) {
            \DB::commit();
            return response(array('contactId' => $contact->id), 200);
        } else {
            \DB::rollback();
            return response(301);
        }

        } //end try
        catch (\Exception $e) {
            $error = $e->getMessage();
            \DB::rollback();
            if($error){
                return response($error, 301);
            }
        } //end catch
    }

    /**
    * @SWG\Put(
    *     path="/contacts",
    *     summary="/edit a contact",
    *     tags={"contacts"},
    *     description="This resource is dedicated to editing a Contact.",
    *     operationId="updateContact",
    *     consumes={"multipart/form-data"},
    *     produces={"application/json"},
    *     @SWG\Parameter(
    *         name="firstName",
    *         in="formData",
    *         description="First Name",
    *         required=true,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="middleName",
    *         in="formData",
    *         description="Middle Name",
    *         required=false,
    *         type="string",
    *     ),    
    *     @SWG\Parameter(
    *         name="lastName",
    *         in="formData",
    *         description="Last Name",
    *         required=true,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="title",
    *         in="formData",
    *         description="Title.  Mr, etc",
    *         enum={"Mr.", "Mrs.", "Miss.", "Lord."},
    *         required=true,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="position",
    *         in="formData",
    *         description="Position. E.g. Manager",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="address",
    *         in="formData",
    *         description="Address.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="address2",
    *         in="formData",
    *         description="Address 2.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="stateProvince",
    *         in="formData",
    *         description="State.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="zipCode",
    *         in="formData",
    *         description="ZIP Code.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="city",
    *         in="formData",
    *         description="City.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="countryCode",
    *         in="formData",
    *         description="Country Code. E.g DKK",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="fixedPhone",
    *         in="formData",
    *         description="Office Phone No.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="mobilePhone",
    *         in="formData",
    *         description="Mobile Phone No.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="fax",
    *         in="formData",
    *         description="Fax No.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="email",
    *         in="formData",
    *         description="Email.",
    *         required=true,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="customerId",
    *         in="formData",
    *         description="Customer Id.",
    *         required=true,
    *         type="integer",
    *     ),
    *     @SWG\Parameter(
    *         name="honeywellId",
    *         in="formData",
    *         description="Honeywell Id.",
    *         required=false,
    *         type="string",
    *     ),
    *     @SWG\Parameter(
    *         name="group[]",
    *         in="formData",
    *         description="Group Ids.",
    *         required=false,
    *         type="array",
    *         @SWG\Items(type="integer"),
    *     ),
    *     @SWG\Parameter(
    *         name="roleId",
    *         in="formData",
    *         description="Role Id.",
    *         required=false,
    *         type="integer",
    *     ),
    *     @SWG\Response(
    *         response=200,
    *         description="Success",
    *     @SWG\Schema(ref="#/definitions/Contact"),
    *     ),
    *     @SWG\Response(
    *         response="400",
    *         description="Invalid tag value",
    *     ),
    *     @SWG\Response(
    *         response="401",
    *         description="Unauthorized access",
    *     )
    * ),
    */
    public function updateContact(Request $request, $id) {

        /*DB::listen(function($sql){
            echo $sql->sql;
        });*/

        try {

            \DB::beginTransaction();

            $contact = Contact::find($id); //find the contact based on the ID passed into the function
            $data_group = $contact->groups()->lists('groups.Group_Idx')->toArray(); //get all the groups of the contact
            $data_roles = $contact->roles()->lists('contact_roles.Role_Idx')->toArray(); //get all the groups of the contact
            $data_roles_flag = $contact->roles()->where('Active_YN_Flag', 'N')->lists('contact_roles.Role_Idx')->toArray();
            $groups = array();

            $contact->First_Name = $request['firstName'];
            $contact->Middle_Name = $request['middleName'];
            $contact->Last_Name = $request['lastName'];
            $contact->Title = $request['title'];
            $contact->Position = $request['position'];
            $contact->Address = $request['address'];
            $contact->Address2 = $request['address2'];
            $contact->State_Province = $request['stateProvince'];
            $contact->ZIP_Code = $request['zipCode'];
            $contact->City = $request['city'];
            $contact->Country_Code = $request['countryCode'];
            $contact->Fixed_Phone = $request['fixedPhone'];
            $contact->Mobile_Phone = $request['mobilePhone'];
            $contact->Fax = $request['fax'];
            $contact->Email = $request['email'];
            $contact->Customer_Idx = $request['customerId'];
            $contact->Honeywell_Id = $request['honeywellId'];

            if ($request['receive_billing'] == 'true') {

                $billing_contact = ContactReceiveBilling::find($id);
                if ($billing_contact != null) {
                    $billing_contact->Customer_Idx = $contact->customerId;
                    $billing_contact->Contact_Idx_for_E_mail = $contact->id;
                    $billing_contact->Attention_Type = $request['receiveBillingAttention'];
                    $billing_contact->Receive_Billing_For = $request['receiveBillingFor'];
                } else {
                    $billing_contact = new ContactReceiveBilling;
                    $billing_contact->Customer_Idx =  $request['customerId'];
                    $billing_contact->Contact_Idx_for_E_mail = $contact->id;
                    $billing_contact->Attention_Type = $request['receiveBillingAttention'];
                    $billing_contact->Receive_Billing_For = $request['receiveBillingFor'];
                }

               if (!$billing_contact->validate($request->method())) {
                    return response($billing_contact->errors(), 400);
                }

                $contact->receive_billing()->save($billing_contact);
            } 

            else {

                $billing_contact = ContactReceiveBilling::find($id);
                if ($billing_contact != null) {
                    $billing_contact->delete();
                }
            }

          //if ($request->has('roleId')) {
            
            if (empty($request['roleId'])) { $roleIds = array(); } else { $roleIds = $request['roleId'];  }
            $roleNoChanges = array_intersect($roleIds, $data_roles); //compare the 2 arrays, get the data which does not contain any changes.
            $rolesToAdd = array_diff($roleIds, $roleNoChanges); //add roles based on the new data and the data with no changes.
            $rolesToRemove = array_diff($data_roles, $roleIds); //remove roles based on the current data and the data posted
            $rolesToActiveAgain = array_intersect($roleIds, $data_roles_flag);

            //only loop is toremove is not empty !empty()
            if (!empty($rolesToRemove)) {
                foreach ($rolesToRemove as $key => $value) {
                    //echo "removed " .$value ." from group:";
                    //$contact->roles()->updateExistingPivot($value, ['Updated_On' => \Carbon::now(), 'Created_By' => 'API REQUEST', 'Last_Updated_By' => 'API REQUEST', 'Active_YN_Flag' => 'N']);
                    $contact_role_remove = ContactRoleMapping::where('Contact_Idx', '=', $id)->where('Role_Idx', '=', $value)->first();
                    $contact_role_remove->Updated_On = \Carbon::now();
                    $contact_role_remove->Created_By = 'API REQUEST';
                    $contact_role_remove->Last_Updated_By = 'API REQUEST';
                    $contact_role_remove->Active_YN_Flag = 'N';
                    $contact_role_remove->save();
                }
            }

            //update existing role status y/n
            if (!empty($rolesToActiveAgain)) {
                foreach ($rolesToActiveAgain as $key => $value) {
                    // $contact->roles()->updateExistingPivot($value, ['Created_On' => \Carbon::now(), 'Updated_On' => \Carbon::now(), 'Created_By' => 'API REQUEST', 'Last_Updated_By' => 'API REQUEST', 'Active_YN_Flag' => 'Y']);
                    $contact_role_active = ContactRoleMapping::where('Contact_Idx', '=', $id)->where('Role_Idx', '=', $value)->first();
                    $contact_role_active->Created_On = \Carbon::now();
                    $contact_role_active->Updated_On = \Carbon::now();
                    $contact_role_active->Last_Updated_By = 'API REQUEST';
                    $contact_role_active->Active_YN_Flag = 'Y';
                    $contact_role_active->save();
                }
            }

            //add contacts
            if (!empty($rolesToAdd)) {
                foreach ($rolesToAdd as $key => $value) {
                    //$contact->roles()->attach($value, ['Created_On' => \Carbon::now(), 'Created_By' => 'API REQUEST', 'Last_Updated_By' => 'API REQUEST', 'Active_YN_Flag' => 'Y']);
                    $contact_roles_add = new ContactRoleMapping(['Role_Idx'=>$value,'Contact_Idx'=>$id,'Created_On' => \Carbon::now(), 'Created_By' => 'API REQUEST', 'Last_Updated_By' => 'API REQUEST', 'Active_YN_Flag' => 'Y']);
                    $contact_roles_add->save();
                }
            }

            //} //else { return response(array('contactId' => $contact->id), 400); } //return Response(400);  //if no role array passed.

            //after taking all the requests, we do the validation
            if (!$contact->validate($request->method())) {
                return response($contact->errors(), 400);
            }

            $groups = empty($request['groups'])? array() : $request['groups']; 
            $groupsNoChanges = array_intersect($groups, $data_group); //compare the 2 arrays, get the data which does not contain any changes.
            $groupsToAdd = array_diff($groups, $groupsNoChanges); //add contacts to group based on the new data and the data with no changes.
            $groupsToRemove = array_diff($data_group, $groups); //remove contacts from group based on the current group data and the data posted

            //only loop is toremove is not empty !empty()
            if (!empty($groupsToRemove)) {
                foreach ($groupsToRemove as $key => $value) {
                    //echo "removed " .$value ." from group:";
                   // $contact->groups()->updateExistingPivot($value, ['End_Date' => \Carbon::now()]);
                    $contact_group_remove = ContactGroupMapping::where('Contact_Idx', '=', $id)->where('Group_Idx', '=', $value)->whereRaw('End_Date IS NULL OR End_Date > now()')->first();
                    $contact_group_remove->End_Date = \Carbon::now();
                    $contact_group_remove->save();
                }
            }

            //add contacts
            if (!empty($groupsToAdd)) {
                foreach ($groupsToAdd as $key => $value) {
                    //echo "added " .$value ." to group:";
                    //$contact->groups()->attach($value, ['Start_Date' => \Carbon::now()]);
                    $contact_group_add = new ContactGroupMapping(['Group_Idx'=>$value,'Contact_Idx'=>$id,'Start_Date' => \Carbon::now()]);
                    $contact_group_add->save();
                }
            }

            if($contact->save()) {
                \DB::commit();
                return response(array('contactId' => $contact->id), 200);
            } else {
                \DB::rollback();
                return response(301);
            }

        }
        catch (\Exception $e) {
            $error = $e->getMessage().$e->getLine();
            \DB::rollback();
            if($error){
                return response($error, 301);
            }
        }

    } //function

}

