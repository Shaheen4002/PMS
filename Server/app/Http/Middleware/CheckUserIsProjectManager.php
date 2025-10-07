<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserIsProjectManager
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get the project ID from the route parameters
        $projectId = $request->route('project_id')
                   ?? $request->route('project')
                   ?? $request->route('id');

        // If project ID is not found in the route, try request data
        if (!$projectId) {
            $projectId = $request->input('project_id');
        }

        if (!$projectId) {
            return response()->json([
                'message' => 'Project ID is required',
                'error' => 'project_id_missing'
            ], 400);
        }

        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'unauthenticated'
            ], 401);
        }

        $user = Auth::user();

        // Check if user is manager in the project through pivot table
        $isManagerInProject = $user->projects()
            ->where('project_id', $projectId)
            ->wherePivot('role', 'manager')
            ->exists();

        // Also check if user is the project creator (manager_id in projects table)
        $isProjectCreator = \App\Models\Project::where('id', $projectId)
            ->where('manager_id', $user->id)
            ->exists();

        if (!$isManagerInProject && !$isProjectCreator) {
            return response()->json([
                'message' => 'Access denied. You must be a manager of this project.',
                'error' => 'not_project_manager',
                'project_id' => $projectId,
                'user_id' => $user->id,
                'user_role' => $user->role
            ], 403);
        }

        // Add project ID to the request for controller use
        $request->merge(['project_id' => $projectId]);

        return $next($request);
    }
}
