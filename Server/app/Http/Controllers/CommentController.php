<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController
{
    public function addComment(CommentRequest $request)
    {
        try {
            $validated = $request->validated();
            $user_id = Auth::id();
            $validated['user_id'] = $user_id;
            $comment = Comment::create($validated);
            return response()->json(['message' => 'comment added', 'comment' => $comment], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'failed to add comment', 'error' => $e], 500);
        }
    }
    public function getTaskComments(int $task_id)
{
    try {
        $task = Task::find($task_id);
        if(!$task){
            return response()->json(['message' => 'Task not found'], 404);
        }

        $comments = Comment::where('task_id', $task_id)->get();

        $userIds = $comments->pluck('user_id')->unique()->filter()->toArray();
        // pluck('user_id'): Extracts just the user_id values from all comments

        // SELECT * FROM users WHERE id IN (1, 2, 3)
        $users = User::whereIn('id', $userIds)
            ->select('id', 'name', 'email')
            ->get()
            ->keyBy('id');

        $formattedComments = $comments->map(function($comment) use ($users) {
            // use ($users): Makes the $users dictionary available inside the function
            $user = $users->get($comment->user_id);

            return [
                'id' => $comment->id,
                'content' => $comment->content,
                'task_id' => $comment->task_id,
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
                'user' => $user ? [
                    'name' => $user->name,
                    'email' => $user->email
                ] : null
            ];
        });

        return response()->json([
            'message' => 'Comments fetched successfully',
            'comments' => $formattedComments
        ], 200);

    } catch(\Exception $e) {
        return response()->json([
            'message' => 'Failed to get comments',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function deleteComment(int $comment_id)
    {
        try {
            $comment = Comment::find($comment_id);
            if (!$comment) {
                return response()->json(['message' => 'comment not found'], 404);
            }
            $comment->delete();
            return response()->json(['message' => 'comment deleted'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'failed to delete comment', 'error' => $e], 500);
        }
    }
}
