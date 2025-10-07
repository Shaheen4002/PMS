<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\Project;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController
{
    public function addReport(ReportRequest $request,int $project_id)
    {
        try{
            $validated = $request->validated();
            $lead_id = Auth::id();
            $validated['lead_id'] = $lead_id;
            $validated['project_id'] = $project_id;
            $report = Report::create($validated);
            return response()->json(['message' => 'Report created successfully', 'report' => $report], 201);
        }catch(\Exception $e){
            return response()->json(['message' => 'failed to create the report', 'error' => $e->getMessage()],500);
        }
    }
    public function getSingleReport(int $report_id,int $project_id)
    {
        $report = Report::where('id', $report_id)->where('project_id', $project_id)->first();
        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }
        return response()->json(['report' => $report], 200);
    }
    public function getProjectReports(int $project_id)
    {
        try{
            $project = Project::find($project_id);
            if(!$project){
                return response()->json(['message' => 'Project not found'], 404);
            }
            // $reports = Report::where('project_id', $project_id)->get();
            $reports = $project->reports;
            if(!$reports){
                 return response()->json(['message' => 'no reports found'], 404);
            }
            return response()->json(['reports' => $reports], 200);
        }catch(\Exception $e){
            return response()->json(['message' => 'failed to get the reports', 'error' => $e->getMessage()],500);
        }
    }
    public function getUserReports(int $user_id)
    {
        try{
            $reports = Report::where('lead_id', $user_id)->get();
            if(!$reports){
                 return response()->json(['message' => 'no reports found'], 404);
            }
            return response()->json(['reports' => $reports], 200);
        }catch(\Exception $e){
            return response()->json(['message' => 'failed to get the reports', 'error' => $e->getMessage()],500);
        }
    }
    public function deleteReport(int $report_id)
    {
        try{
            $report = Report::find($report_id);
            if(!$report){
                return response()->json(['message' => 'report not found'], 404);
            }
            $report->delete();
            return response()->json(['message' => 'report deleted'],200);
        }catch(\Exception $e){
            return response()->json(['message' => 'failed to delete the report', 'error' => $e->getMessage()],500);
        }
    }
}
