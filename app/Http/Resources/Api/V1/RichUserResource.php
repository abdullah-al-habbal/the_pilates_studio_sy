<?php

// app/Http/Resources/Api/V1/RichUserResource.php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\ValueObjects\User\UserProfileWithBooking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RichUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = UserProfileWithBooking::fromUser($this->resource);

        return $profile->toArray();
    }
}
