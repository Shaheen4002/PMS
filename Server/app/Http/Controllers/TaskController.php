<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController
{
    public function addTask(TaskRequest $request)
    {
        try {
            $validated = $request->validated();
            $creator = Auth::id();
            $validated['creator_id'] = $creator;
            $task = Task::create($validated);
            return response()->json(['message' => 'task created','task' => $task],201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add task', 'message' => $e->getMessage()], 500);
        }
    }
    public function deleteTask(int $task_id)
    {
        try{
            $task = Task::find($task_id);
            if(!$task){
                return response()->json(['error' => 'Task not found'], 404);
            }
            $task->delete();
            return response()->json(['message' => 'Task deleted successfully'], 200);
        }catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete the task', 'message' => $e->getMessage()], 500);
        }
    }
    public function editTask(TaskRequest $request, int $task_id)
    {
        try{
            $task = Task::find($task_id);
            if(!$task){
                return response()->json(['error' => 'Task not found'], 404);
            }
            $validated = $request->validated();
            $task->update($validated);
            return response()->json(['message' => 'Task updated successfully','task' => $task], 200);
        }catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update the task', 'message' => $e->getMessage()], 500);
        }
    }
    public function getUserTasks(int $user_id)
    {
        try{
            $tasks = Task::where('user_id', $user_id)->get();
            return response()->json(['tasks' => $tasks], 200);
        }catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve tasks', 'message' => $e->getMessage()], 500);
        }
    }
    public function getSingleTask(int $task_id)
    {
        try{
            $task = Task::find($task_id);
            if(!$task){
                return response()->json(['error' => 'Task not found'], 404);
            }
            return response()->json(['task' => $task], 200);
        }catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve the task', 'message' => $e->getMessage()], 500);
        }
    }
    public function editTaskStatus(Request $request, int $task_id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,in_progress,completed'
        ]);
        try{
            $task = Task::find($task_id);
            if(!$task){
                return response()->json(['error' => 'Task not found'], 404);
            }
            $task->status = $request['status'];
            $task->save();
            return response()->json(['message' => 'Task status updated successfully','task' => $task], 200);
        }catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update task status', 'message' => $e->getMessage()], 500);
        }
    }
    public function editTaskProgress(Request $request,int $task_id)
    {
        $request->validate([
            'progress' => 'required|integer|min:0|max:100'
        ]);
        try{
            $task = Task::find($task_id);
            if(!$task){
                return response()->json(['error' => 'Task not found'], 404);
            }
            $task->progress = $request['progress'];
            $task->save();
            return response()->json(['message' => 'Task progress updated successfully','task' => $task], 200);
        }catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update task progress', 'message' => $e->getMessage()], 500);
        }
    }
    public function editTaskInfo(Request $request,int $task_id)
    {
        $request->validate([
            'info' => 'required|string'
        ]);
        try{
            $task = Task::find($task_id);
            if(!$task){
                return response()->json(['error' => 'Task not found'], 404);
            }
            $task->info = $request['info'];
            $task->save();
        }catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update task info', 'message' => $e->getMessage()], 500);
        }
    }
}
