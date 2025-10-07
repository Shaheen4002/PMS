<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable=["name","description","start_date","end_date","manager_id","status"];
    protected $table = "projects";

     public function reports(){
        return $this->hasMany(Report::class);
    }
     public function tasks(){
        return $this->hasMany(Task::class);
    }
    public function users(){
        return $this->belongsToMany(User::class,"project_user")->withPivot('role');
    }
}
