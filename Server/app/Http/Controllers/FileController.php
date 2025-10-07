<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileRequest;
use App\Models\File;
use App\Models\Task;
use ZipArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FileController
{
    public function addFile(FileRequest $request)
    {
        try {
            $validated = $request->validated();
            $file = $validated["file"];
            $user_id = Auth::id();
            $zipPath = $this->compressFiles($file, $request->name);
            $new_file = File::create([
                'filename' => $request->filename,
                'file' => $zipPath,
                'user_id' => $user_id,
                'task_id' => $request->task_id
            ]);

            return response()->json([
                'message' => 'Files compressed and uploaded successfully',
                'file' => $new_file,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add file', 'message' => $e->getMessage()], 500);
        }
    }
    public function getSingleFile(int $file_id)
    {
        try {
            $file = File::find($file_id)->first();
            if (!$file) {
                return response()->json(['message' => 'file not found'], 404);
            }
            return response()->json(['file' => $file], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get file', 'message' => $e->getMessage()], 500);
        }
    }
    public function getTaskFiles(int $task_id)
    {
        try {
            $task = Task::find($task_id)->first();
            if (!$task) {
                return response()->json(['message' => 'task not found'], 404);
            }
            $files = $task->files;
            if (!$files) {
                return response()->json(['message' => 'there is no files for this task'], 404);
            }
            return response()->json(['task' => $task], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get files', 'message' => $e->getMessage()], 500);
        }
    }
    public function deleteFile(int $file_id)
    {
       try {
            $file = File::find($file_id);

            if (!$file) {
                return response()->json([
                    'message' => 'file not found'
                ], 404);
            }

            if ($file->file && Storage::disk('public')->exists($file->file)) {
                Storage::disk('public')->delete($file->file);
            }

            $file->delete();

            return response()->json([
                'message' => 'file deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete file',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function editFile(int $file_id, FileRequest $request)
    {
         try{
            $validated = $request->validated();
            $user_id = Auth::id();
            $find_file = File::find($file_id)->first();
            if(!$find_file){
                return response()->json([
                    'message' => 'file not found'
                ], 404);
            }
            $data = $request->only(['filename','task_id']);
            if ($request->hasFile('file')) {

                if ($find_file->file && Storage::disk('public')->exists($find_file->file)) {
                    Storage::disk('public')->delete($find_file->file);
                }

                $file = $request->file('file');
                $zipPath = $this->compressFiles($file, $find_file->name);
                $data['file'] = $zipPath;
                $data['user_id'] = $user_id;
            }
            $find_file->update($data);

            return response()->json([

                'message' => 'file updated successfully',
                'file' => $find_file
            ], 200);

         }catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to edit file',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    private function compressFiles($file, $baseName): string
    {
        $zip = new ZipArchive();
        $zipFileName = 'compressed_' . $baseName . '_' . time() . '.zip';
        $zipPath = 'files/' . $zipFileName;
        $fullPath = storage_path('app/public/' . $zipPath);

        Storage::disk('public')->makeDirectory('files');

        if ($zip->open($fullPath, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($file->getRealPath(), $file->getClientOriginalName());
            $zip->close();
            return $zipPath;
        } else {
            throw new \Exception('Failed to create ZIP file');
        }
    }
}
