<?php

declare(strict_types=1);

namespace Honey\MeilisearchAdapter\Criteria\Sort;

enum SortDirection: string
{
    case ASC = 'asc';
    case DESC = 'desc';
}
