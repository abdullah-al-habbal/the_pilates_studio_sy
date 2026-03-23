<?php
// filePath: app/Enums/Api/SuccessCodeEnum.php

declare(strict_types=1);

namespace App\Enums\Api;

enum SuccessCodeEnum: string
{
    // General success codes
    case SUCCESS = 'SUCCESS';
    case CREATED = 'CREATED';
    case UPDATED = 'UPDATED';
    case DELETED = 'DELETED';

    // Auth related success codes
    case LOGIN_SUCCESS = 'LOGIN_SUCCESS';
    case LOGOUT_SUCCESS = 'LOGOUT_SUCCESS';
    case REGISTER_SUCCESS = 'REGISTER_SUCCESS';
    case EMAIL_VERIFIED = 'EMAIL_VERIFIED';
    case VERIFICATION_EMAIL_SENT = 'VERIFICATION_EMAIL_SENT';

    // Resource specific success codes
    case SETTINGS_UPDATED = 'SETTINGS_UPDATED';
    case PROFILE_UPDATED = 'PROFILE_UPDATED';
    case BOOKING_CREATED = 'BOOKING_CREATED';
    case BOOKING_CANCELLED = 'BOOKING_CANCELLED';
    case BOOKING_UPDATED = 'BOOKING_UPDATED';
    case PACKAGE_PURCHASED = 'PACKAGE_PURCHASED';

    // File operations
    case FILE_UPLOADED = 'FILE_UPLOADED';
    case FILE_DELETED = 'FILE_DELETED';

    public function getMessage(): string
    {
        return match($this) {
            self::SUCCESS => 'Operation completed successfully',
            self::CREATED => 'Resource created successfully',
            self::UPDATED => 'Resource updated successfully',
            self::DELETED => 'Resource deleted successfully',
            self::LOGIN_SUCCESS => 'Login successful',
            self::LOGOUT_SUCCESS => 'Logout successful',
            self::REGISTER_SUCCESS => 'Registration successful',
            self::EMAIL_VERIFIED => 'Email verified successfully',
            self::VERIFICATION_EMAIL_SENT => 'Verification email sent',
            self::SETTINGS_UPDATED => 'Settings updated successfully',
            self::PROFILE_UPDATED => 'Profile updated successfully',
            self::BOOKING_CREATED => 'Booking created successfully',
            self::BOOKING_CANCELLED => 'Booking cancelled successfully',
            self::BOOKING_UPDATED => 'Booking updated successfully',
            self::PACKAGE_PURCHASED => 'Package purchased successfully',
            self::FILE_UPLOADED => 'File uploaded successfully',
            self::FILE_DELETED => 'File deleted successfully',
        };
    }

    public function getStatusCode(): int
    {
        return match($this) {
            self::CREATED, self::REGISTER_SUCCESS, self::BOOKING_CREATED, self::PACKAGE_PURCHASED, self::FILE_UPLOADED => 201,
            default => 200,
        };
    }
}
