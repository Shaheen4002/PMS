<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController
{
    public function addProject(ProjectRequest $request)
    {
        try {
            $validated = $request->validated();
            $validated['manager_id'] = Auth::id();
            $project = Project::create($validated);
            Auth::user()->projects()->attach($project->id, [
            'role' => 'manager'
        ]);
            return response()->json(['message' => 'Project created successfully', 'project' => $project], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create project', 'message' => $e->getMessage()], 500);
        }
    }
    public function deleteProject(int $project_id)
    {
        try {
            $project = Project::find($project_id);
            $current_user = Auth::user();
            $current_user_id = Auth::id();

            if (!$project) {
                return response()->json(['status' => 404, 'message' => 'Project not found'], 404);
            }

            if ($current_user->role === "manager" && $current_user_id !== $project->manager_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $project->delete();
            return response()->json(['message' => 'Project deleted successfully', 'project' => $project], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete project', 'message' => $e->getMessage()], 500);
        }
    }
    public function editProject(ProjectRequest $request, int $project_id)
    {
        $project = Project::find($project_id);
        $validated = $request->validated();
        $current_user_id = Auth::id();
        $project_manager = $project->manager_id;

        if (!$project) {
            return response()->json(['status' => 404, 'message' => 'Project not found'], 404);
        }
        if ($current_user_id !== $project_manager) {
            return response()->json(['message' => 'unAuthorized'], 403);
        }

        try {
            $project->update($validated->all());
            return response()->json(['message' => 'Project updated successfully', 'project' => $project], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update project', 'message' => $e->getMessage()], 500);
        }
    }
    public function getSingleProject(int $project_id)
    {
        try {
            $project = Project::find($project_id);

            if (!$project) {
                return response()->json(['status' => 404, 'message' => 'Project not found'], 404);
            }

            return response()->json(['project' => $project], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve project', 'message' => $e->getMessage()], 500);
        }
    }
    public function getUserProjects(int $user_id)
    {
        try {
            $projects = User::find($user_id)->projects;
            if (!$projects) {
                return response()->json(['status' => 404, 'message' => 'No projects found for this user'], 404);
            }
            return response()->json(['projects' => $projects], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve projects', 'message' => $e->getMessage()], 500);
        }
    }
    public function getYourProjects()
    {
        try {
            $current_user_id = Auth::id();
            $user = User::find($current_user_id);

            if (!$user || $user->projects->isEmpty()) {
                return response()->json(['status' => 404, 'message' => 'No projects found for this user'], 404);
            }

            return response()->json(['projects' => $user->projects], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve projects', 'message' => $e->getMessage()], 500);
        }
    }
    public function getProjectMembers(int $project_id)
    {
        try {
            $project = Project::find($project_id);

            if (!$project) {
                return response()->json(['status' => 404, 'message' => 'Project not found'], 404);
            }

            $members = $project->users;

            return response()->json(['members' => $members], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve members', 'message' => $e->getMessage()], 500);
        }
    }
    public function getProjectTasks(int $project_id)
    {
        try {
            $project = Project::find($project_id);

            if (!$project) {
                return response()->json(['status' => 404, 'message' => 'Project not found'], 404);
            }

            $tasks = $project->tasks;

            return response()->json(['tasks' => $tasks], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve tasks', 'message' => $e->getMessage()], 500);
        }
    }
    public function editProjectStatus(Request $request, int $project_id)
    {
        $request->validate([
            'status' => 'required|string|in:planned,active,completed,cancelled',
        ]);

        $project = Project::find($project_id);
        $current_user_id = Auth::id();
        $project_manager = $project->manager_id;

        if (!$project) {
            return response()->json(['status' => 404, 'message' => 'Project not found'], 404);
        }
        if ($current_user_id !== $project_manager) {
            return response()->json(['message' => 'unAuthorized'], 403);
        }

        try {
            $project->status = $request['status'];
            $project->save();
            return response()->json(['message' => 'Project status updated successfully', 'project' => $project], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update project status', 'message' => $e->getMessage()], 500);
        }
    }
}
