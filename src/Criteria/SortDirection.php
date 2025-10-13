<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Criteria;

enum SortDirection: string
{
    case ASC = 'asc';
    case DESC = 'desc';
}
