<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = ['name', 'email', 'password', 'role','contact_info'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function comments(){
        return $this->hasMany(Comment::class);
    }
    public function reports(){
        return $this->hasMany(Report::class);
    }
    public function tasks(){
        return $this->hasMany(Task::class);
    }
    public function projects(){
        return $this->belongsToMany(Project::class,"project_user")->withPivot('role');
    }
    public function files(){
        return $this->hasMany(File::class,'uploaded_by');
    }
}
