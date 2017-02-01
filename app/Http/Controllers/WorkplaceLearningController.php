<?php
/**
 * This file (InternshipController.php) was created on 06/20/2016 at 01:11.
 * (C) Max Cassee
 * This project was commissioned by HU University of Applied Sciences.
 */

namespace app\Http\Controllers;

// Use the PHP native IntlDateFormatter (note: enable .dll in php.ini)

use App\Workplace;
use App\WorkplaceLearningPeriod;
use Illuminate\Support\Collection;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class WorkplaceLearningController extends Controller{

    public function show(){
        return view("pages.internship")
                ->with("period", new WorkplaceLearningPeriod)
                ->with("workplace", new Workplace)
                ->with("categories", new Collection)
                ->with("resource", new Collection);
    }

    public function edit($id){
        $wplp = WorkplaceLearningPeriod::find($id);
        if (is_null($wplp) || $wplp->student_id != Auth::user()->student_id) {
            return redirect('profiel')
                ->with('error', 'Deze stage bestaat niet, of je hebt geen toegang om deze in te zien');
        } else {
            return view('pages.internship')
                ->with('period', $wplp)
                ->with("workplace", Workplace::find($wplp->wp_id))
                ->with("categories", new Collection)
                ->with("resource", new Collection);
        }
    }

    public function create(Request $r){
        // Validate the input
        $validator = Validator::make($r->all(), [
            'companyName'           => 'required|regex:/^[0-9a-zA-Z ()-,.]*$/|max:255|min:3',
            'companyStreet'         => 'required|regex:/^[0-9a-zA-Z ()-,.]*$/|max:45|min:3',
            'companyHousenr'        => 'required|regex:/^[0-9]{1,5}[a-zA-Z]{1}$/|max:4|min:1', //
            'companyPostalcode'     => 'required|regex:/^[0-9a-zA-Z]*$/|max:10|min:6', //TODO: Fix Regex to proper intl format
            'companyLocation'       => 'required|regex:/^[0-9a-zA-Z ()-]*$/|max:255|min:3',
            'contactPerson'         => 'required|regex:/^[0-9a-zA-Z ()-,.]*$/|max:255|min:3',
            'contactPhone'          => 'required|regex:/^[0-9]{2,3}-?[0-9]{7,8}$/',
            'contactEmail'          => 'required|email|max:255',
            'numdays'               => 'required|integer|min:1sx',
            'startdate'             => 'required|date|after:'.date("Y-m-d", strtotime('-6 months')),
            'enddate'               => 'required|date|after:startdate',
            'internshipAssignment'  => 'required|regex:/^[0-9a-zA-Z ()-,.*&:_+=%$@!?;]*$/|min:15|max:500',
            'isActive'              => 'sometimes|required|in:1,0'
        ]);

        if ($validator->fails()) {
            return redirect('stageperiode/create')
                ->withErrors($validator)
                ->withInput();
        }

        // Pass. Create the internship and period.
        $wplp = new WorkplaceLearningPeriod;
        $wp = new Workplace;

        // Save the workplace first
        $wp->wp_name        = $r['companyName'];
        $wp->street         = $r['companyStreet'];
        $wp->housenr        = $r['companyHousenr'];
        $wp->postalcode     = $r['companyPostalcode'];
        $wp->town           = $r['companyLocation'];
        $wp->contact_name   = $r['contactPerson'];
        $wp->contact_email  = $r['contactEmail'];
        $wp->contact_phone  = $r['contactPhone'];
        $wp->numberofemployees = 0;
        $wp->save();

        $wplp->student_id   = Auth::user()->student_id;
        $wplp->wp_id        = $wp->wp_id;
        $wplp->startdate    = $r['startdate'];
        $wplp->enddate      = $r['enddate'];
        $wplp->nrofdays     = $r['numdays'];
        $wplp->description  = $r['internshipAssignment'];
        $wplp->save();

        // Set the user setting to the current Internship ID
        if($r['isActive'] == 1){
            Auth::user()->setUserSetting('active_internship', $wplp->wplp_id);
        }

        return redirect('profiel')->with('success', 'De wijzigingen zijn opgeslagen.');
    }

    public function update(Request $r, $id){
        // Validate the input
        $validator = Validator::make($r->all(), [
            'companyName'           => 'required|regex:/^[0-9a-zA-Z ()-,.]*$/|max:255|min:3',
            'companyStreet'         => 'required|regex:/^[0-9a-zA-Z ()-,.]*$/|max:45|min:3',
            'companyHousenr'        => 'required|regex:/^[0-9]{1,5}[a-zA-Z]{1}$/|max:4|min:1', //
            'companyPostalcode'     => 'required|regex:/^[0-9a-zA-Z]*$/|max:10|min:6', //TODO: Fix Regex to proper intl format
            'companyLocation'       => 'required|regex:/^[0-9a-zA-Z ()-]*$/|max:255|min:3',
            'contactPerson'         => 'required|regex:/^[0-9a-zA-Z ()-,.]*$/|max:255|min:3',
            'contactPhone'          => 'required|regex:/^[0-9]{2,3}-?[0-9]{7,8}$/',
            'contactEmail'          => 'required|email|max:255',
            'numdays'               => 'required|integer|min:1',
            'startdate'             => 'required|date|after:'.date("Y-m-d", strtotime('-6 months')),
            'enddate'               => 'required|date|after:startdate',
            'internshipAssignment'  => 'required|regex:/^[0-9a-zA-Z ()-,.*&:_+=%$@!?;]*$/|min:15|max:500',
            'isActive'              => 'sometimes|required|in:1,0'
        ]);

        if ($validator->fails()) {
            return redirect('stageperiode/edit/'.$id)
                ->withErrors($validator)
                ->withInput();
        }

        // Input is valid. Attempt to fetch the WPLP and validate it belongs to the user
        $wplp = WorkplaceLearningPeriod::find($id);
        if(is_null($wplp) || $wplp->student_id != Auth::user()->student_id){
            redirect('profiel')
                ->with('error', 'Deze stage bestaat niet, of je hebt geen toegang om deze in te zien');
        }

        // Succes. Also fetch the associated Workplace Eloquent object and update
        $wp = Workplace::find($wplp->wp_id);

        // Save the workplace first
        $wp->wp_name        = $r['companyName'];
        $wp->street         = $r['companyStreet'];
        $wp->housenr        = $r['companyHousenr'];
        $wp->postalcode     = $r['companyPostalcode'];
        $wp->town           = $r['companyLocation'];
        $wp->contact_name   = $r['contactPerson'];
        $wp->contact_email  = $r['contactEmail'];
        $wp->contact_phone  = $r['contactPhone'];
        $wp->numberofemployees = 0;
        $wp->save();

        $wplp->student_id   = Auth::user()->student_id;
        $wplp->wp_id        = $wp->wp_id;
        $wplp->startdate    = $r['startdate'];
        $wplp->enddate      = $r['enddate'];
        $wplp->nrofdays     = $r['numdays'];
        $wplp->description  = $r['internshipAssignment'];
        $wplp->save();

        // Set the user setting to the current Internship ID
        if($r['isActive'] == 1){
            Auth::user()->setUserSetting('active_internship', $wplp->wplp_id);
        }

        return redirect('profiel')->with('success', 'De wijzigingen zijn opgeslagen.');
    }
/*
    public function updateCategories(Request $request, $id){
        // Verify the given ID is valid and belongs to the student
        $t = false;
        foreach(Auth::user()->internshipperiods()->get() as $ip){
            if($ip->stud_stid == $id){
                $t = true;
                break;
            }
        }
        if(!$t) return redirect('profiel'); // $id is invalid or does not belong to the student

        // Inject the new item into the request array for processing and validation if it is filled in by the user
        if(!empty($request['newcat']['-1']['cg_value'])){
           $request['cat'] = array_merge(((is_array($request['cat'])) ? $request['cat'] : array()), $request['newcat']);
        }

        $validator = Validator::make($request->all(), [
            'cat.*.cg_id'       => 'required|digits_between:1,5',
            'cat.*.ss_id'       => 'required|digits_between:1,5',
            'cat.*.cg_value'    => 'required|regex:/^[a-zA-Z0-9_() ]*$/|min:3|max:50',
        ]);
        if($validator->fails()){
            // Noes. errors occured. Exit back to profile page with errors
            return redirect('stageperiode/edit/'.$id)
                ->withErrors($validator)
                ->withInput();
        } else {
            // All is well :)
            foreach($request['cat'] as $cat){
                // Either update or create a new row.
                $c = Categorie::find($cat['cg_id']);
                if(is_null($c)){
                    $c = new Categorie;
                    $c->ss_id = $cat['ss_id'];
                }
                $c->cg_value = $cat['cg_value'];
                $c->save();
            }
            // Done, redirect back to profile page
            return redirect('stageperiode/edit/'.$id);
        }
    }

    public function updateCooperations(Request $request, $id){
        // Verify the given ID is valid and belongs to the student
        $t = false;
        foreach(Auth::user()->internshipperiods()->get() as $ip){
            if($ip->stud_stid == $id){
                $t = true;
                break;
            }
        }
        if(!$t) return redirect('profiel'); // $id is invalid or does not belong to the student

        // Inject the new item into the request array for processing and validation if it is filled in by the user
        if(!empty($request['newswv']['-1']['value']) && !empty($request['newswv']['-1']['omschrijving'])){
            $request['swv'] = array_merge($request['swv'], $request['newswv']);
        }

        $validator = Validator::make($request->all(), [
            'swv.*.swv_id'          => 'required|digits_between:1,5',
            'swv.*.ss_id'           => 'required|digits_between:1,5',
            'swv.*.value'           => 'required|regex:/^[a-zA-Z0-9_() ]*$/|min:3|max:50',
            'swv.*.omschrijving'    => 'required|regex:/^[a-zA-Z0-9_() ]*$/|min:3|max:50',
        ]);
        if($validator->fails()){
            // Noes. errors occured. Exit back to profile page with errors
            return redirect('profiel')
                ->withErrors($validator)
                ->withInput();
        } else {
            // All is well :)
            foreach($request['swv'] as $swv){
                // Either update or create a new row.
                $s = Samenwerkingsverband::find($swv['swv_id']);
                if(is_null($s)){
                    $s                  = new Categorie;
                    $s->ss_id           = $swv['ss_id'];
                }
                $s->swv_value           = $swv['value'];
                $s->swv_omschrijving    = $swv['omschrijving'];
                $s->save();
            }
            // Done, redirect back to profile page
            return redirect('profiel');
        }
    }
*/
    public function __construct(){
        $this->middleware('auth');
    }
}