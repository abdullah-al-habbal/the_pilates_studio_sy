<?php

// filePath: app/Http/Controllers/Api/BaseApiController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\HeaderParameter;

#[HeaderParameter(
    name: 'X-App-Platform',
    description: 'The mobile platform of the app (android or ios)',
    type: 'string',
    required: true,
    example: 'ios'
)]
#[HeaderParameter(
    name: 'X-App-Version',
    description: 'The version of the app (semantic versioning)',
    type: 'string',
    required: true,
    example: '1.2.3'
)]
#[HeaderParameter(
    name: 'X-App-Name',
    description: 'The app name (customer, instructor, etc.)',
    type: 'string',
    required: false,
    default: 'customer',
    example: 'customer'
)]
abstract class BaseApiController extends Controller
{
    use ApiResponseTrait;
}
