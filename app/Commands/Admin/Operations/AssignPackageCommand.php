<?php

declare(strict_types=1);

namespace App\Commands\Admin\Operations;

use Illuminate\Http\Request;

final readonly class AssignPackageCommand
{
    public function __construct(
        public int $userId,
        public int $packageId,
        public ?int $currencyId = null,
        public ?int $clientSentAmount = null,
        public ?int $createdBy = null,
        public ?string $purchasedAt = null,
        public array $attendedSessionIds = [],
        public array $missedSessionIds = [],
        public ?float $exchangeRateOverride = null,
        public ?string $idempotencyKey = null,
    ) {}

    public static function fromRequest(Request $request, int $packageId): self
    {
        $isHistorical = $request->filled('purchased_at');

        return new self(
            userId: $request->integer('user_id'),
            packageId: $packageId,
            currencyId: $request->input('currency_id') !== null
                ? $request->integer('currency_id')
                : null,
            clientSentAmount: $request->input('paid_amount') !== null
                ? $request->integer('paid_amount')
                : null,
            createdBy: (int) auth()->id(),
            purchasedAt: $isHistorical
                ? $request->string('purchased_at')->toString()
                : null,
            attendedSessionIds: self::parseIds($request, 'attended_session_ids'),
            missedSessionIds: self::parseIds($request, 'missed_session_ids'),
            exchangeRateOverride: $request->filled('exchange_rate_snapshot')
                ? (float) $request->input('exchange_rate_snapshot')
                : null,
            idempotencyKey: $isHistorical
                ? $request->string('idempotency_key')->toString()
                : null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'package_id' => $this->packageId,
            'currency_id' => $this->currencyId,
            'paid_amount' => $this->clientSentAmount,
            'created_by' => $this->createdBy,
            'purchased_at' => $this->purchasedAt,
            'attended_session_ids' => $this->attendedSessionIds,
            'missed_session_ids' => $this->missedSessionIds,
            'exchange_rate_snapshot' => $this->exchangeRateOverride,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }

    private static function parseIds(Request $request, string $field): array
    {
        if (! $request->has($field)) {
            return [];
        }

        $ids = $request->input($field);

        return array_values(array_unique(array_map('intval', $ids ?? [])));
    }
}
