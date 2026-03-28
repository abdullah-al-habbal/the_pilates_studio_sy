<?php
// filePath: app/Enums/Api/ErrorCodeEnum.php

declare(strict_types=1);

namespace App\Enums\Api;

enum ErrorCodeEnum: string
{
    // General errors (400-499)
    case BAD_REQUEST = 'BAD_REQUEST';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
    case UNPROCESSABLE_ENTITY = 'UNPROCESSABLE_ENTITY';
    case TOO_MANY_REQUESTS = 'TOO_MANY_REQUESTS';

    // Validation errors
    case VALIDATION_FAILED = 'VALIDATION_FAILED';
    case INVALID_INPUT = 'INVALID_INPUT';
    case MISSING_REQUIRED_FIELD = 'MISSING_REQUIRED_FIELD';

    // Authentication errors
    case INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    case TOKEN_EXPIRED = 'TOKEN_EXPIRED';
    case TOKEN_INVALID = 'TOKEN_INVALID';
    case TOKEN_MISSING = 'TOKEN_MISSING';
    case EMAIL_NOT_VERIFIED = 'EMAIL_NOT_VERIFIED';
    case EMAIL_ALREADY_VERIFIED = 'EMAIL_ALREADY_VERIFIED';
    case ACCOUNT_LOCKED = 'ACCOUNT_LOCKED';
    case INVALID_VERIFICATION_CODE = 'INVALID_VERIFICATION_CODE';

    // Resource errors
    case RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    case RESOURCE_ALREADY_EXISTS = 'RESOURCE_ALREADY_EXISTS';
    case RESOURCE_CONFLICT = 'RESOURCE_CONFLICT';
    case RESOURCE_LOCKED = 'RESOURCE_LOCKED';

    // Business logic errors
    case INSUFFICIENT_PERMISSIONS = 'INSUFFICIENT_PERMISSIONS';
    case OPERATION_NOT_ALLOWED = 'OPERATION_NOT_ALLOWED';
    case INSUFFICIENT_CREDITS = 'INSUFFICIENT_CREDITS';
    case BOOKING_ALREADY_EXISTS = 'BOOKING_ALREADY_EXISTS';
    case SESSION_FULL = 'SESSION_FULL';
    case SESSION_EXPIRED = 'SESSION_EXPIRED';
    case CLASS_CANCELLED = 'CLASS_CANCELLED';
    case INVALID_BOOKING_STATUS = 'INVALID_BOOKING_STATUS';

    // Mobile app errors
    case APP_VERSION_REQUIRED = 'APP_VERSION_REQUIRED';
    case APP_VERSION_OUTDATED = 'APP_VERSION_OUTDATED';
    case INVALID_PLATFORM = 'INVALID_PLATFORM';

    // Header errors
    case MISSING_REQUIRED_HEADERS = 'MISSING_REQUIRED_HEADERS';
    case INVALID_HEADER_VALUE = 'INVALID_HEADER_VALUE';

    case SERVER_CONFIGURATION_ERROR = 'SERVER_CONFIGURATION_ERROR';
    case INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';

    public function getMessage(): string
    {
        return match($this) {
            // General errors
            self::BAD_REQUEST => 'Invalid request',
            self::UNAUTHORIZED => 'Authentication required',
            self::FORBIDDEN => 'Access denied',
            self::NOT_FOUND => 'Resource not found',
            self::METHOD_NOT_ALLOWED => 'Method not allowed',
            self::UNPROCESSABLE_ENTITY => 'Unable to process the request',
            self::TOO_MANY_REQUESTS => 'Too many requests',

            // Validation errors
            self::VALIDATION_FAILED => 'Validation failed',
            self::INVALID_INPUT => 'Invalid input provided',
            self::MISSING_REQUIRED_FIELD => 'Required field is missing',

            // Authentication errors
            self::INVALID_CREDENTIALS => 'Invalid email or password',
            self::TOKEN_EXPIRED => 'Authentication token has expired',
            self::TOKEN_INVALID => 'Invalid authentication token',
            self::TOKEN_MISSING => 'Authentication token is missing',
            self::EMAIL_NOT_VERIFIED => 'Email address not verified',
            self::EMAIL_ALREADY_VERIFIED => 'Email is already verified.',
            self::ACCOUNT_LOCKED => 'Account has been locked',
            self::INVALID_VERIFICATION_CODE => 'Invalid verification code',

            // Resource errors
            self::RESOURCE_NOT_FOUND => 'Requested resource not found',
            self::RESOURCE_ALREADY_EXISTS => 'Resource already exists',
            self::RESOURCE_CONFLICT => 'Resource conflict detected',
            self::RESOURCE_LOCKED => 'Resource is locked',

            // Business logic errors
            self::INSUFFICIENT_PERMISSIONS => 'Insufficient permissions',
            self::OPERATION_NOT_ALLOWED => 'Operation not allowed',
            self::INSUFFICIENT_CREDITS => 'Insufficient credits',
            self::BOOKING_ALREADY_EXISTS => 'Booking already exists',
            self::SESSION_FULL => 'Class session is full',
            self::SESSION_EXPIRED => 'Session has expired',
            self::CLASS_CANCELLED => 'Class has been cancelled',
            self::INVALID_BOOKING_STATUS => 'Invalid booking status',

            // Mobile app errors
            self::APP_VERSION_REQUIRED => 'App version header is required',
            self::APP_VERSION_OUTDATED => 'App version is outdated',
            self::INVALID_PLATFORM => 'Invalid platform specified',

            // Header errors
            self::MISSING_REQUIRED_HEADERS => 'Required headers are missing',
            self::INVALID_HEADER_VALUE => 'Invalid header value',
            self::SERVER_CONFIGURATION_ERROR => 'Server configuration error',
            self::INTERNAL_SERVER_ERROR => 'Internal server error',
        };
    }

    public function getStatusCode(): int
    {
        return match($this) {
            self::BAD_REQUEST => 400,
            self::UNAUTHORIZED => 401,
            self::FORBIDDEN => 403,
            self::NOT_FOUND => 404,
            self::METHOD_NOT_ALLOWED => 405,
            self::UNPROCESSABLE_ENTITY => 422,
            self::TOO_MANY_REQUESTS => 429,
            self::VALIDATION_FAILED => 422,
            self::INVALID_INPUT => 422,
            self::MISSING_REQUIRED_FIELD => 422,
            self::INVALID_CREDENTIALS => 401,
            self::TOKEN_EXPIRED => 401,
            self::TOKEN_INVALID => 401,
            self::TOKEN_MISSING => 401,
            self::EMAIL_NOT_VERIFIED => 403,
            self::EMAIL_ALREADY_VERIFIED => 400,
            self::ACCOUNT_LOCKED => 403,
            self::INVALID_VERIFICATION_CODE => 422,
            default => 400,
        };
    }
}
