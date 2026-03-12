<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Standardized API response service for consistent response format
 * Handles both success and error responses with proper logging
 */
class ApiResponseService
{
    /**
     * Success response format
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200, $extra = [])
    {
        $response = array_merge([
            'success' => true,
            'status_code' => $statusCode,
            'message' => $message,
            'data' => $data,
            'timestamp' => Carbon::now()->toIso8601String(),
        ], $extra);


        return response()->json($response, $statusCode);
    }

    /**
     * Error response format
     */
    public static function error($message, $statusCode = 400, $errors = [], $extra = [])
    {
        $response = array_merge([
            'success' => false,
            'status_code' => $statusCode,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => Carbon::now()->toIso8601String(),
        ], $extra);

        // Log error with proper severity
        $logLevel = $statusCode >= 500 ? 'error' : 'warning';
        Log::$logLevel('API Error Response', [
            'status_code' => $statusCode,
            'message' => $message,
            'errors' => $errors,
        ]);

        return response()->json($response, $statusCode);
    }

    /**
     * Validation error response
     */
    public static function validationError($errors, $message = 'Validation failed')
    {
        return self::error($message, 422, $errors);
    }

    /**
     * Not found error response
     */
    public static function notFound($resource = 'Resource')
    {
        return self::error("{$resource} not found", 404);
    }

    /**
     * Unauthorized error response
     */
    public static function unauthorized($message = 'Unauthorized')
    {
        return self::error($message, 401);
    }

    /**
     * Forbidden error response
     */
    public static function forbidden($message = 'Forbidden')
    {
        return self::error($message, 403);
    }

    /**
     * Server error response
     */
    public static function serverError($message = 'Internal server error', $debug = false, $exception = null)
    {
        if ($exception) {
            Log::error('Server Error Exception', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        $errorData = [];
        if ($debug && config('app.debug')) {
            $errorData['debug'] = [
                'message' => $exception ? $exception->getMessage() : null,
                'file' => $exception ? $exception->getFile() : null,
                'line' => $exception ? $exception->getLine() : null,
            ];
        }

        return self::error($message, 500, $errorData);
    }

    /**
     * List response with pagination
     */
    public static function paginated($items, $total, $perPage, $currentPage, $message = 'List retrieved successfully')
    {
        $totalPages = ceil($total / $perPage);

        return self::success(
            $items,
            $message,
            200,
            [
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'total_pages' => $totalPages,
                    'has_more' => $currentPage < $totalPages,
                ],
            ]
        );
    }

    /**
     * Created resource response
     */
    public static function created($data, $message = 'Resource created successfully')
    {
        return self::success($data, $message, 201);
    }

    /**
     * No content response
     */
    public static function noContent()
    {
        return response()->json(null, 204);
    }

    /**
     * Log action for audit trail
     */
    public static function logAction($action, $resourceType, $resourceId = null, $userId = null, $details = [])
    {
    }

    /**
     * Log security event
     */
    public static function logSecurityEvent($event, $severity = 'warning', $details = [])
    {
        Log::$severity('Security Event: ' . $event, array_merge([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ], $details));
    }
}
