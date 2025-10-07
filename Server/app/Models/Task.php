<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable=["title","description","status","priority","project_id","creator_id","user_id","info","progress"];
    protected $table = "tasks";

    public function comments(){
        return $this->hasMany(Comment::class);
    }
    public function project(){
        return $this->belongsTo(Project::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function files(){
        return $this->hasMany(File::class);
    }
}
