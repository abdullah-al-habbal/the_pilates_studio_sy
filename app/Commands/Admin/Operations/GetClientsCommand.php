<?php
// app/Commands/Admin/Operations/GetClientsCommand.php
declare(strict_types=1);

namespace App\Commands\Admin\Operations;

final readonly class GetClientsCommand
{
    public function __construct(
        public ?string $search = null,
        public int $page = 1,
        public ?string $filter = null,
        public int $perPage = 15
    ) {}
}