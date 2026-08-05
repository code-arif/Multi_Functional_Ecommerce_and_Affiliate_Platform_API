<?php

namespace Modules\Core\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    // Success response
    protected function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    // Error response
    protected function errorResponse(
        string $message = 'Error',
        mixed $errors = null,
        int $statusCode = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $statusCode);
    }

    // Paginated response
    protected function paginatedResponse(
        mixed $data,
        string $message = 'Success'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
        ]);
    }

    // Created response
    protected function createdResponse(
        mixed $data = null,
        string $message = 'Created successfully'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    // No content response
    protected function noContentResponse(
        string $message = 'Deleted successfully'
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], 200);
    }

    // Forbidden response
    protected function forbiddenResponse(
        string $message = 'Forbidden',
        mixed $errors = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], 403);
    }

    // Unauthorized response
    protected function unauthorizedResponse(
        string $message = 'Authentication required.'
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }
}
