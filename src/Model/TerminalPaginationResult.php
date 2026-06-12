<?php

declare(strict_types=1);

namespace PinVandaag\TmsCcvAPI\Model;

final readonly class TerminalPaginationResult
{
    /**
     * @param array<int, array<string, mixed>|Terminal>|null $items
     */
    public function __construct(
        public ?array $items = null,
        public ?int $currentPage = null,
        public ?int $totalPages = null,
        public ?int $pageSize = null,
        public ?int $totalItems = null,
    ) {
    }
}
