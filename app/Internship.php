<?php
/**
 * This file (Internship.php) was created on 06/06/2016 at 15:22.
 * (C) Max Cassee
 * This project was commissioned by HU University of Applied Sciences.
 */

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;
use App\InternshipPeriod;

class Internship extends Model{
    // Override the table used for the User Model
    protected $table = 'stageplaatsen';
    // Disable using created_at and updated_at columns
    public $timestamps = false;
    // Override the primary key column
    protected $primaryKey = 'stp_id';

    // Default
    protected $fillable = [
        'stp_id',
        'bedrijfsnaam',
        'plaats',
        'postcode',
        'huisnummer',
        'contactpersoon',
        'contactemail',
        'telefoon',
        'aantalwerknemers',
    ];

    public function users(){
        return $this->belongsToMany('App\User', 'student_stages', 'stage_id', 'student_id');
    }

    public function internshipperiod(){
        return $this->belongsTo('App\InternshipPeriod', 'student_stages', 'stageplaats_id', 'stp_id');
    }

    public function getCompanyName(){
        return $this->bedrijfsnaam;
    }
}