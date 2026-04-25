<?php
declare(strict_types=1);

namespace App\Handlers\Admin\Scheduler;

use App\Models\User;
use Illuminate\Support\Collection;

final readonly class GetUsersListHandler
{
    public function handle(?int $sessionId = null): Collection
    {
        $query = User::orderBy('fullname');

        if ($sessionId) {
            $query->whereDoesntHave('bookingSessions', function ($q) use ($sessionId) {
                $q->where('class_session_id', $sessionId);
            });
        }

        return $query->get(['id', 'fullname', 'phone_number'])
            ->map(fn(User $u) => [
                'id' => $u->id,
                'label' => $u->fullname . ($u->phone_number ? ' · ' . $u->phone_number : ''),
            ]);
    }
}
