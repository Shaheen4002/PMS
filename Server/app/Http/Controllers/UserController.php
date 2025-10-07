<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Notifications\AddedToProjectNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController
{
    public function getAllUsers()
    {
        try {
            $users = User::all();
            if (!$users) {
                return response()->json(["message" => "no users founded"], 404);
            }
            return response()->json($users, 200);
        } catch (\Exception $e) {
            return response()->json(["message" => "failed get users", "error" => $e->getMessage()], 500);
        }
    }
    public function deleteUser(int $user_id)
    {
        try {
            $user = User::find($user_id);
            if (!$user) {
                return response()->json(["message" => "user not found"], 404);
            }
            $user->delete();
            return response()->json(["message" => "user deleted successfully"], 200);
        } catch (\Exception $e) {
            return response()->json(["message" => "failed get users", "error" => $e->getMessage()], 500);
        }
    }

    public function addUserToProject(int $user_id)
    {
        try {
            $user = User::find($user_id);
            if (!$user) {
                return response()->json(["message" => "User not found"], 404);
            }

            $project_id = request()->route('project_id');
            $project = Project::find($project_id);
            if (!$project) {
                return response()->json(["message" => "Project not found"], 404);
            }

            // Check if user is already in project
            if ($project->users()->where('user_id', $user_id)->exists()) {
                return response()->json(["message" => "User is already in this project"], 400);
            }

            $project->users()->attach($user->id, ['role' => 'member']);

            // Send database notification to the user
            $user->notify(new AddedToProjectNotification($project, 'member'));

            return response()->json([
                "message" => "Member added to the project successfully",
                "notification_sent" => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Failed to add member to the project",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function addLeaderToProject(int $user_id)
    {
        try {
            $user = User::find($user_id);
            if (!$user) {
                return response()->json(["message" => "User not found"], 404);
            }

            $project_id = request()->route('project_id');
            $project = Project::find($project_id);
            if (!$project) {
                return response()->json(["message" => "Project not found"], 404);
            }

            // Check if user is already in project
            if ($project->users()->where('user_id', $user_id)->exists()) {
                return response()->json(["message" => "User is already in this project"], 400);
            }

            $project->users()->attach($user->id, ['role' => 'lead']);

            // Send database notification to the user
            $user->notify(new AddedToProjectNotification($project, 'lead'));

            return response()->json([
                "message" => "Leader added to the project successfully",
                "notification_sent" => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Failed to add leader to the project",
                "error" => $e->getMessage()
            ], 500);
        }
    }
    public function removeUserFromProject(int $user_id)
    {
        try {
            $user = User::find($user_id);
            if (!$user) {
                return response()->json(["message" => "User not found"], 404);
            }

            $project_id = request()->route('project_id');
            $project = Project::find($project_id);
            if (!$project) {
                return response()->json(["message" => "Project not found"], 404);
            }

            // Check if user is actually in the project
            $userInProject = $project->users()
                ->where('user_id', $user_id)
                ->exists();

            if (!$userInProject) {
                return response()->json(["message" => "User is not a member of this project"], 400);
            }

            // Remove user from project
            $project->users()->detach($user_id);

            return response()->json([
                "message" => "User removed from project successfully",
                "user_id" => $user_id,
                "project_id" => $project_id
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Failed to remove user from project",
                "error" => $e->getMessage()
            ], 500);
        }
    }
    // In your UserController or NotificationController
public function getMyNotifications()
{
    try {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'time_ago' => $notification->created_at->diffForHumans()
                ];
            });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count()
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to get notifications',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function markAsRead($notificationId = null)
{
    try {
        $user = Auth::user();

        if ($notificationId) {
            // Mark specific notification as read
            $notification = $user->notifications()->find($notificationId);
            if ($notification) {
                $notification->markAsRead();
            }
        } else {
            // Mark all notifications as read
            $user->unreadNotifications->markAsRead();
        }

        return response()->json([
            'message' => $notificationId ? 'Notification marked as read' : 'All notifications marked as read'
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to mark notification as read',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function getUnreadCount()
{
    try {
        $user = Auth::user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count()
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to get unread count',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
