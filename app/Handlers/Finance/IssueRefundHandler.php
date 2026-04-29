<?php
declare(strict_types=1);
namespace App\Handlers\Finance;
use App\Models\Booking;
use App\Models\MerchandiseOrder;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
class IssueRefundHandler
{
    /**
     * Issue a refund for a booking or merchandise order
     *
     * @param array{
     *   user_id: int,
     *   refundable_type: 'bookings'|'merchandise_orders',
     *   refundable_id: int,
     *   amount: int,
     *   reason: ?string,
     *   refunded_by: int
     * } $data
     */
    public function handle(array $data): Refund
    {
        return DB::transaction(function () use ($data) {
            $refundable = $this->resolveRefundable($data['refundable_type'], $data['refundable_id']);

            if ($refundable->user_id !== $data['user_id']) {
                throw new InvalidArgumentException('Refundable item does not belong to specified user.');
            }
            return Refund::create([
                'refundable_type' => $data['refundable_type'],
                'refundable_id' => $data['refundable_id'],
                'user_id' => $data['user_id'],
                'amount' => (int) $data['amount'],
                'reason' => $data['reason'] ?? null,
                'refunded_by' => $data['refunded_by'],
                'refunded_at' => now(),
            ]);
        });
    }
    protected function resolveRefundable(string $type, int $id): Model
    {
        return match ($type) {
            'bookings' => Booking::findOrFail($id),
            'merchandise_orders' => MerchandiseOrder::findOrFail($id),
            default => throw new InvalidArgumentException("Invalid refundable type: {$type}"),
        };
    }
}
