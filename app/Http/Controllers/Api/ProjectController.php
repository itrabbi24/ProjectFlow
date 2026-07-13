<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Services\ProjectService;
use App\Repositories\ProjectRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    protected ProjectRepositoryInterface $projectRepo;
    protected ProjectService $projectService;

    public function __construct(
        ProjectRepositoryInterface $projectRepo,
        ProjectService $projectService
    ) {
        $this->projectRepo = $projectRepo;
        $this->projectService = $projectService;
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Project::class);

        $query = Project::with('client', 'manager');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->input('client_id'));
        }

        $projects = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $projects
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        Gate::authorize('create', Project::class);

        $project = $this->projectService->createProject($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Project created successfully',
            'data' => $project
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $project = $this->projectRepo->findOrFail($id, ['client', 'manager', 'files.uploader', 'purchases', 'expenses.paidBy', 'incomes']);
        
        Gate::authorize('view', $project);

        $financials = $this->projectService->getProjectFinancials($project);

        return response()->json([
            'status' => 'success',
            'data' => [
                'project' => $project,
                'financials' => $financials
            ]
        ]);
    }

    public function update(UpdateProjectRequest $request, int $id): JsonResponse
    {
        $project = $this->projectRepo->findOrFail($id);
        
        Gate::authorize('update', $project);

        $updatedProject = $this->projectService->updateProject($id, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Project updated successfully',
            'data' => $updatedProject
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $project = $this->projectRepo->findOrFail($id);
        
        Gate::authorize('delete', $project);

        $this->projectService->deleteProject($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Project deleted successfully'
        ]);
    }

    public function uploadFile(Request $request, int $id): JsonResponse
    {
        $project = $this->projectRepo->findOrFail($id);
        
        Gate::authorize('update', $project);

        $request->validate([
            'file' => 'required|file|max:10240' // Max 10MB
        ]);

        $projectFile = $this->projectService->uploadFile($project, $request->file('file'), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'File uploaded successfully',
            'data' => $projectFile
        ]);
    }
}
