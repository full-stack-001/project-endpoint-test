<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Interfaces\ProjectRepositoryInterface;
use App\Services\ApiResponse;
use App\Services\MediaService;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{

    /**
     * Summary of projectRepository
     * @var ProjectRepositoryInterface
     */
    private ProjectRepositoryInterface $projectRepository;

    /**
     * Summary of mediaService
     * @var MediaService
     */
    protected MediaService $mediaService;

    /**
     * Summary of __construct
     * @param \App\Interfaces\ProjectRepositoryInterface $projectRepository
     * @param \App\Services\MediaService $mediaService
     */
    public function __construct(ProjectRepositoryInterface $projectRepository, MediaService $mediaService)
    {
        $this->projectRepository = $projectRepository;
        $this->mediaService = $mediaService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $data = $this->projectRepository->index();

        return ApiResponse::sendResponse(ProjectResource::collection($data), '', Response::HTTP_OK);
    }


    
    /**
     * Summary of store
     * @param \App\Http\Requests\StoreProjectRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $project = $this->projectRepository->store($request->except('files'));

            if ($request->has('files')) {
                $this->mediaService->upload($request->file('files'), $project);
            }

            DB::commit();
            return ApiResponse::sendResponse(new ProjectResource($project), 'Project created successfully.', Response::HTTP_CREATED);
        } catch (Exception $ex) {
            return ApiResponse::rollback($ex);
        }

    }

    
    /**
     * Summary of show
     * @param \App\Models\Project $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Project $project): JsonResponse
    {
        return ApiResponse::sendResponse(new ProjectResource($project), '', Response::HTTP_OK);
    }

    
    /**
     * Summary of update
     * @param \App\Http\Requests\UpdateProjectRequest $request
     * @param \App\Models\Project $project
     * @return JsonResponse|mixed
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        DB::beginTransaction();
        try {
            $project->update($request->all());
            DB::commit();
            return ApiResponse::sendResponse(new ProjectResource($project), 'Project updated successfully', Response::HTTP_OK);

        } catch (Exception $ex) {
            return ApiResponse::rollback($ex);
        }

    }

   
    /**
     * Summary of destroy
     * @param \App\Models\Project $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->projectRepository->delete($project->id);
        $this->mediaService->deleteMediaFiles($project);
        return ApiResponse::sendResponse('Product Delete Successful', '', Response::HTTP_NO_CONTENT);
    }
}
