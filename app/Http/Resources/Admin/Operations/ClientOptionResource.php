<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        return [
            'id' => $user->id,
            'fullname' => $user->fullname,
            'phone_number' => $user->phone_number,
        ];
    }
}
