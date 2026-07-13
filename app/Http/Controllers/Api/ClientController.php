<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Repositories\ClientRepositoryInterface;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ClientController extends Controller
{
    protected ClientRepositoryInterface $clientRepo;
    protected ActivityLogService $activityLogService;

    public function __construct(
        ClientRepositoryInterface $clientRepo,
        ActivityLogService $activityLogService
    ) {
        $this->clientRepo = $clientRepo;
        $this->activityLogService = $activityLogService;
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Client::class);

        $query = Client::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $clients = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $clients
        ]);
    }

    public function store(ClientRequest $request): JsonResponse
    {
        Gate::authorize('create', Client::class);

        $client = $this->clientRepo->create($request->validated());
        $this->activityLogService->log('created', "Client '{$client->name}' created", $client);

        return response()->json([
            'status' => 'success',
            'message' => 'Client created successfully',
            'data' => $client
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $client = $this->clientRepo->findOrFail($id, ['projects']);
        Gate::authorize('view', $client);

        return response()->json([
            'status' => 'success',
            'data' => $client
        ]);
    }

    public function update(ClientRequest $request, int $id): JsonResponse
    {
        $client = $this->clientRepo->findOrFail($id);
        Gate::authorize('update', $client);

        $this->clientRepo->update($id, $request->validated());
        $updatedClient = $this->clientRepo->find($id);

        $this->activityLogService->log('updated', "Client '{$updatedClient->name}' updated", $updatedClient);

        return response()->json([
            'status' => 'success',
            'message' => 'Client updated successfully',
            'data' => $updatedClient
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $client = $this->clientRepo->findOrFail($id);
        Gate::authorize('delete', $client);

        $this->activityLogService->log('deleted', "Client '{$client->name}' deleted", $client);
        $this->clientRepo->delete($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Client deleted successfully'
        ]);
    }
}
