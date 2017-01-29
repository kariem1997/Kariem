<?php
/**
 * This file (CalendarController.php) was created on 06/19/2016 at 16:01.
 * (C) Max Cassee
 * This project was commissioned by HU University of Applied Sciences.
 */

namespace app\Http\Controllers;

use App\Deadline;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller{
    public function show(){
        return view('pages.calendar')
            ->with('deadlines', Auth::user()->deadlines()->orderBy('dl_datetime', 'asc')->get());
    }

    public function create(Request $r){
        $validator = Validator::make($r->all(), [
           'nameDeadline'   => 'required|regex:/^[0-9a-zA-Z ()-]*$/|max:255|min:3',
           'dateDeadline'   => 'required|date|after:'.date('Y-m-d', strtotime("now")),
        ]);
        if($validator->fails()){
            return redirect('deadline')
                ->withErrors($validator->errors())
                ->withInput();
        }
        
        $d = new Deadline;
        $d->student_id      = Auth::user()->student_id;
        $d->dl_value        = $r['nameDeadline'];
        $d->dl_datetime     = date('Y-m-d H:i:s', strtotime($r['dateDeadline']));
        $d->save();
        
        return redirect('deadline')->with('success', "De deadline is opgeslagen.");
    }

    public function update(Request $r){
        $validator = Validator::make($r->all(), [
            'id'                => 'required|exists:deadline,dl_id',
            'action'            => 'required|in:submit,delete',
            'nameDeadline'      => 'required|regex:/^[0-9a-zA-Z ()-]*$/|max:255|min:3',
            'dateDeadline'      => 'required|date_format:d-m-Y H:i'
        ]);
        if($validator->fails()){
            return redirect('deadline')
                ->withErrors($validator)
                ->withInput();
        }

        $d = Deadline::find($r['id']);
        if(is_null($d) || $d->student_id != Auth::user()->student_id){
            return redirect('deadline')->withErrors(['error', "Deze deadline bestaat niet, of je hebt geen rechten om deze te bewerken."]);
        } elseif($r->input('action') === "submit"){
            $d->dl_value        = $r['nameDeadline'];
            $d->dl_datetime     = date('Y-m-d H:i:s', strtotime($r['dateDeadline']));
            $d->save();
            $msg = "De deadline is aangepast.";
        } elseif($r->input('action') === "delete"){
            $d->delete();
            $msg = "De deadline is verwijderd uit het overzicht.";
        } else {
            return redirect('deadline')->withErrors(['error', "Er is een onbekende fout opgetreden."]);
        }
        return redirect('deadline')->with('success', $msg);
    }
    public function __construct(){
        $this->middleware('auth');
    }
}