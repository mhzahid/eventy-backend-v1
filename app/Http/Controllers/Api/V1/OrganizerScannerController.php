<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreScannerRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrganizerScannerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $scanners = $request->user()
            ->managedScanners()
            ->with('scanner:id,name,email,created_at')
            ->latest()
            ->cursorPaginate(10)
            ->through(fn($link) => [
                'id'         => $link->scanner->id,
                'name'       => $link->scanner->name,
                'email'      => $link->scanner->email,
                'created_at' => $link->scanner->created_at->format('F j, Y'),
            ]);

        return response()->json($scanners);
    }

    public function store(StoreScannerRequest $request): JsonResponse
    {
        $organizer = $request->user();
        $data      = $request->validated();

        $scanner = DB::transaction(function () use ($organizer, $data) {
            $scanner = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $data['password'],
            ]);

            $scanner->assignRole('ticket-scanner');

            $organizer->managedScanners()->create(['scanner_id' => $scanner->id]);

            return $scanner;
        });

        return response()->json([
            'data' => [
                'id'         => $scanner->id,
                'name'       => $scanner->name,
                'email'      => $scanner->email,
                'created_at' => $scanner->created_at,
            ],
        ], 201);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'scanner_id' => 'required|exists:users,id',
            'password' => 'required|string|min:8'
        ]);

        $link = $request->user()
            ->managedScanners()
            ->where('scanner_id', $request->scanner_id)
            ->firstOrFail();

        $link->scanner->update(['password' => $request->input('password')]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'scanner_id' => 'required|exists:users,id'
        ]);

        $scannerId = $request->input('scanner_id');

        $link = $request->user()
            ->managedScanners()
            ->where('scanner_id', $scannerId)
            ->firstOrFail();

        DB::transaction(function () use ($link) {
            $scanner = $link->scanner;
            $link->delete();
            $scanner->tokens()->delete();
            $scanner->forceDelete();
        });

        return response()->json(['message' => 'Scanner removed.']);
    }
}