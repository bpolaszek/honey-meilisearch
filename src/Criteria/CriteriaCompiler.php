<?php

declare(strict_types=1);

namespace Honey\ODM\Meilisearch\Criteria;

use Bentools\MeilisearchFilters\Expression;
use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Criteria\Comparison;
use Honey\ODM\Core\Criteria\CompositeExpression;
use Honey\ODM\Core\Criteria\Criteria;
use Honey\ODM\Core\Criteria\ExpressionInterface;
use Honey\ODM\Core\Criteria\LogicalOperator;
use Honey\ODM\Core\Criteria\Negation;
use Honey\ODM\Core\Criteria\Operator;
use Honey\ODM\Core\Criteria\Sort;
use Honey\ODM\Core\Criteria\UnsupportedExpressionException;
use Meilisearch\Contracts\DocumentsQuery;
use Meilisearch\Contracts\SearchQuery;
use SortDirection;

use function array_map;
use function array_shift;
use function array_values;
use function Bentools\MeilisearchFilters\field;
use function Honey\ODM\Meilisearch\index_uid;
use function sprintf;

/**
 * Compiles the platform-agnostic Criteria into Meilisearch queries.
 *
 * Criteria without a search term compile to a DocumentsQuery (documents endpoint);
 * criteria carrying a search term compile to a SearchQuery (search endpoint).
 */
final readonly class CriteriaCompiler
{
    /**
     * @param AsDocument<object> $classMetadata
     */
    public function compile(AsDocument $classMetadata, Criteria $criteria): DocumentsCriteriaWrapper
    {
        return new DocumentsCriteriaWrapper(
            index_uid($classMetadata),
            match ($criteria->search) {
                null => $this->compileDocumentsQuery($classMetadata, $criteria),
                default => $this->compileSearchQuery($classMetadata, $criteria),
            },
        );
    }

    /**
     * @param AsDocument<object> $classMetadata
     */
    public function compileExpression(ExpressionInterface $expression, AsDocument $classMetadata): Expression
    {
        return match (true) {
            $expression instanceof Comparison => self::compileComparison($expression, $classMetadata),
            $expression instanceof CompositeExpression => $this->compileComposite($expression, $classMetadata),
            $expression instanceof Negation => $this->compileExpression($expression->expression, $classMetadata)
                ->group()
                ->negate(),
            default => throw UnsupportedExpressionException::expression($expression),
        };
    }

    /**
     * @param AsDocument<object> $classMetadata
     */
    private function compileDocumentsQuery(AsDocument $classMetadata, Criteria $criteria): DocumentsQuery
    {
        $query = new DocumentsQuery();
        if (null !== $criteria->where) {
            $query = $query->setFilter([(string) $this->compileExpression($criteria->where, $classMetadata)]); // @phpstan-ignore argument.type
        }
        if ([] !== $criteria->orderBy) {
            $query = $query->setSort(self::compileSorts($criteria->orderBy, $classMetadata));
        }
        $query = $query->setOffset($criteria->offset);
        if (null !== $criteria->limit) {
            $query = $query->setLimit($criteria->limit);
        }

        return $query;
    }

    /**
     * @param AsDocument<object> $classMetadata
     */
    private function compileSearchQuery(AsDocument $classMetadata, Criteria $criteria): SearchQuery
    {
        $query = new SearchQuery()->setQuery((string) $criteria->search);
        if (null !== $criteria->where) {
            $query = $query->setFilter([(string) $this->compileExpression($criteria->where, $classMetadata)]); // @phpstan-ignore argument.type
        }
        if ([] !== $criteria->orderBy) {
            $query = $query->setSort(self::compileSorts($criteria->orderBy, $classMetadata));
        }
        $query = $query->setOffset($criteria->offset);
        if (null !== $criteria->limit) {
            $query = $query->setLimit($criteria->limit);
        }

        return $query;
    }

    /**
     * @param AsDocument<object> $classMetadata
     */
    private function compileComposite(CompositeExpression $expression, AsDocument $classMetadata): Expression
    {
        $expressions = array_map(
            fn (ExpressionInterface $e) => $this->compileExpression($e, $classMetadata)->group(),
            $expression->expressions,
        );
        $first = array_shift($expressions);

        return match (true) {
            [] === $expressions => $first,
            LogicalOperator::AND === $expression->operator => $first->and(...$expressions),
            default => $first->or(...$expressions),
        };
    }

    /**
     * @param AsDocument<object> $classMetadata
     */
    private static function compileComparison(Comparison $comparison, AsDocument $classMetadata): Expression
    {
        $field = field($classMetadata->getFieldName($comparison->property));
        $value = $comparison->value;

        return match ($comparison->operator) {
            Operator::EQUALS => $field->equals($value),
            Operator::NOT_EQUALS => $field->notEquals($value),
            Operator::GREATER_THAN => $field->isGreaterThan($value),
            Operator::GREATER_THAN_OR_EQUALS => $field->isGreaterThan($value, includeValue: true),
            Operator::LESS_THAN => $field->isLowerThan($value),
            Operator::LESS_THAN_OR_EQUALS => $field->isLowerThan($value, includeValue: true),
            Operator::IN => $field->isIn(array_values((array) $value)),
            Operator::NOT_IN => $field->isNotIn(array_values((array) $value)),
            Operator::CONTAINS => $field->contains($value),
            Operator::STARTS_WITH => $field->startsWith($value),
            Operator::IS_NULL => $field->isNull(),
            Operator::IS_NOT_NULL => $field->isNotNull(),
        };
    }

    /**
     * @param list<Sort> $sorts
     * @param AsDocument<object> $classMetadata
     *
     * @return list<non-empty-string>
     */
    private static function compileSorts(array $sorts, AsDocument $classMetadata): array
    {
        return array_map(
            fn (Sort $sort) => sprintf(
                '%s:%s',
                $classMetadata->getFieldName($sort->property),
                match ($sort->direction) {
                    SortDirection::Ascending => 'asc',
                    SortDirection::Descending => 'desc',
                },
            ),
            $sorts,
        );
    }
}
