<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserIsProjectLeadOrHigher
{
    public function handle(Request $request, Closure $next): Response
    {
        $projectId = $request->route('project_id') ?? $request->route('project');

        if (!$projectId) {
            return response()->json(['message' => 'Project ID is required'], 400);
        }

        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = Auth::user();

        // Check for lead or manager roles in pivot table
        $hasLeadOrHigherRole = $user->projects()
            ->where('project_id', $projectId)
            ->whereIn('project_user.role', ['lead', 'manager'])
            ->exists();

        // Check if user is project creator
        $isProjectCreator = \App\Models\Project::where('id', $projectId)
            ->where('manager_id', $user->id)
            ->exists();

        if (!$hasLeadOrHigherRole && !$isProjectCreator) {
            return response()->json([
                'message' => 'Access denied. Lead role or higher required.',
                'error' => 'insufficient_project_role'
            ], 403);
        }

        return $next($request);
    }
}
