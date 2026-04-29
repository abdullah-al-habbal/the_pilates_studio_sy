<?php
// filePath: app/Traits/ApiResponseTrait.php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\Api\ErrorCodeEnum;
use App\Enums\Api\SuccessCodeEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

trait ApiResponseTrait
{
    public function paginated(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        SuccessCodeEnum|string $code = SuccessCodeEnum::SUCCESS,
        ?string $message = null
    ): JsonResponse {
        return $this->success(
            data: $resourceClass::collection($paginator->items()),
            code: $code,
            message: $message,
            meta: [
                'pagination' => [
                    'total'        => $paginator->total(),
                    'count'        => $paginator->count(),
                    'per_page'     => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'total_pages'  => $paginator->lastPage(),
                ],
            ]
        );
    }

    protected function success(
        mixed $data = null,
        SuccessCodeEnum|string $code = SuccessCodeEnum::SUCCESS,
        ?string $message = null,
        int $status = 200,
        array $meta = []
    ): JsonResponse {
        if ($code instanceof SuccessCodeEnum) {
            $message = $message ?? $code->getMessage();
            $status = $status === 200 ? $code->getStatusCode() : $status;
            $codeString = $code->value;
        } else {
            $codeString = $code;
            $message = $message ?? 'Success';
        }

        $response = [
            'success' => true,
            'code' => $codeString,
            'message' => $message,
            'data' => $data,
            'timestamp' => Carbon::now()->toISOString(),
            'status_code' => $status,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }

    protected function created(
        mixed $data = null,
        SuccessCodeEnum|string $code = SuccessCodeEnum::CREATED,
        ?string $message = null,
        array $meta = []
    ): JsonResponse {
        return $this->success($data, $code, $message, 201, $meta);
    }

    protected function updated(
        mixed $data = null,
        SuccessCodeEnum|string $code = SuccessCodeEnum::UPDATED,
        ?string $message = null,
        array $meta = []
    ): JsonResponse {
        return $this->success($data, $code, $message, 200, $meta);
    }

    protected function deleted(
        mixed $data = null,
        SuccessCodeEnum|string $code = SuccessCodeEnum::DELETED,
        ?string $message = null
    ): JsonResponse {
        return $this->success($data, $code, $message, 200);
    }

    protected function noContent(
        SuccessCodeEnum|string $code = SuccessCodeEnum::SUCCESS,
        ?string $message = null
    ): JsonResponse {
        return $this->success(null, $code, $message, 200);
    }

    protected function error(
        ErrorCodeEnum|string $code = ErrorCodeEnum::BAD_REQUEST,
        ?string $message = null,
        int $status = 400,
        mixed $errors = null,
        array $headers = []
    ): JsonResponse {
        if ($code instanceof ErrorCodeEnum) {
            $message = $message ?? $code->getMessage();
            $status = $status === 400 ? $code->getStatusCode() : $status;
            $codeString = $code->value;
        } else {
            $codeString = $code;
            $message = $message ?? 'An error occurred';
        }

        $response = [
            'success' => false,
            'code' => $codeString,
            'message' => $message,
            'timestamp' => Carbon::now()->toISOString(),
            'status_code' => $status,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status, $headers);
    }

    protected function validationError(
        mixed $errors = null,
        string $message = 'Validation failed',
        ErrorCodeEnum|string $code = ErrorCodeEnum::VALIDATION_FAILED
    ): JsonResponse {
        return $this->error($code, $message, 422, $errors);
    }

    protected function notFound(
        string $message = 'Resource not found',
        ErrorCodeEnum|string $code = ErrorCodeEnum::RESOURCE_NOT_FOUND
    ): JsonResponse {
        return $this->error($code, $message, 404);
    }

    protected function unauthorized(
        string $message = 'Unauthorized',
        ErrorCodeEnum|string $code = ErrorCodeEnum::UNAUTHORIZED
    ): JsonResponse {
        return $this->error($code, $message, 401);
    }

    protected function forbidden(
        string $message = 'Forbidden',
        ErrorCodeEnum|string $code = ErrorCodeEnum::FORBIDDEN
    ): JsonResponse {
        return $this->error($code, $message, 403);
    }

    protected function unprocessable(
        string $message = 'Unprocessable entity',
        mixed $errors = null,
        ErrorCodeEnum|string $code = ErrorCodeEnum::UNPROCESSABLE_ENTITY
    ): JsonResponse {
        return $this->error($code, $message, 422, $errors);
    }

    protected function tooManyRequests(
        string $message = 'Too many requests',
        ErrorCodeEnum|string $code = ErrorCodeEnum::TOO_MANY_REQUESTS
    ): JsonResponse {
        return $this->error($code, $message, 429);
    }

    protected function businessError(
        ErrorCodeEnum $code,
        ?string $message = null,
        mixed $errors = null
    ): JsonResponse {
        return $this->error($code, $message, $code->getStatusCode(), $errors);
    }
}
