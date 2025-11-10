<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Criteria;

enum RetrievalMode
{
    case DOCUMENTS;
    case SEARCH;
}
