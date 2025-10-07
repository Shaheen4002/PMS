<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserInProject
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get the project ID from the route parameters
        $projectId = $request->route('project_id') ?? $request->route('project');

        // If project ID is not found in the route, try to get it from request data
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

        // Check if user belongs to the project
        $userBelongsToProject = Auth::user()
            ->projects()
            ->where('project_id', $projectId)
            ->exists();

        if (!$userBelongsToProject) {
            return response()->json([
                'message' => 'You are not a member of this project',
                'error' => 'not_project_member',
                'project_id' => $projectId,
                'user_id' => Auth::id()
            ], 403);
        }

        return $next($request);
    }
}
