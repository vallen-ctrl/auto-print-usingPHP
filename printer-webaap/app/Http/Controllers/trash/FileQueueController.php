<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FileQueue;

class FileQueueController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string',
            'order_id' => 'required|string',
            'file_id' => 'required|string',
            'computer_id' => 'required|string',
        ]);

        $queue = FileQueue::create([
            'status' => 'pending',
            'path' => $validated['path'],
            'order_id' => hex2bin($validated['order_id']),
            'file_id' => hex2bin($validated['file_id']),
            'computer_id' => hex2bin($validated['computer_id']),
        ]);

        return response()->json($queue, 201);
    }

    public function destroy(int $id)
    {
        $queue = FileQueue::findOrFail($id);
        $queue->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function update(Request $request, int $id)
    {
        $queue = FileQueue::findOrFail($id);
        $queue->update($request->all());

        return response()->json($queue);
    }

    public function getByComputerId(string $computerId)
    {
        $queues = FileQueue::where('file_id', $computerId)->get();
        
        return response()->json($queues);
    }

    public function index()
    {
        return response()->json(FileQueue::all());
    }
}

