<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\Project;

class AddedToProjectNotification extends Notification
{
    use Queueable;

    public $project;
    public $role;

    public function __construct(Project $project, string $role)
    {
        $this->project = $project;
        $this->role = $role;
    }

    public function via($notifiable)
    {
        return ['database'];  //via() method - defines which channels to send the notification through
    }

    public function toArray($notifiable)
    {
        return [
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'role' => $this->role,
            'message' => 'You have been added to the project: ' . $this->project->name,
            'role_message' => 'Your role: ' . ucfirst($this->role),
            'action_url' => '/projects/' . $this->project->id,
            'timestamp' => now()->toDateTimeString()
        ];
    }
}
