# Performance Notes — Large Dataset Filtering

This project seeds **110,000+ rows** across core tables (85k articles, 20k comments, plus users, authors, categories, tags, and pivot rows).

## Index strategy

Migrations add indexes on high-cardinality filter and sort columns:

- **articles**: `status`, `published_at`, `view_count`, `rating`, `is_featured`, composite `(author_id, status)`, `(category_id, published_at)`
- **comments**: `article_id`, `user_id`, `is_approved`, `rating`, `created_at`
- **authors / categories / tags**: name and lookup columns

These indexes support direct-field filters and foreign-key joins used by relation filters.

## Query design choices

1. **Pagination first** — All list APIs use Laravel's `paginate()`, which applies `LIMIT/OFFSET` (or cursor-friendly patterns can be added later). Never load the full dataset into memory.
2. **Eager loading** — Default relations are loaded with `with()` to avoid N+1 queries when serializing API resources.
3. **`whereHas` for relations** — Relation filters use existence subqueries. On PostgreSQL this is generally efficient when foreign keys and relation columns are indexed.
4. **BelongsTo sort via JOIN** — Sorting by `author.name` or `category.name` uses a `LEFT JOIN` instead of correlated subqueries where possible.
5. **Bulk seeding** — The seeder inserts articles and comments in 1,000-row chunks via `DB::table()->insert()` to avoid model overhead during seeding.

## Recommended production optimizations

| Technique | When to use |
|-----------|-------------|
| **PostgreSQL** (current setup) | Better planner and index usage for complex filters vs SQLite |
| **`EXPLAIN ANALYZE`** | Inspect slow filter combinations in staging |
| **Partial indexes** | e.g. `WHERE status = 'published'` if most queries target published articles |
| **Covering indexes** | Add composite indexes matching frequent filter + sort pairs |
| **Read replicas** | Offload heavy list/search traffic from the primary |
| **Cursor pagination** | For very deep pages (`page=5000+`), replace offset pagination |

## Example EXPLAIN workflow

```sql
EXPLAIN ANALYZE
SELECT * FROM articles
WHERE status = 'published'
  AND view_count >= 1000
ORDER BY published_at DESC
LIMIT 15 OFFSET 0;
```

For relation filters:

```sql
EXPLAIN ANALYZE
SELECT * FROM articles
WHERE EXISTS (
  SELECT 1 FROM authors
  WHERE authors.id = articles.author_id
    AND authors.name ILIKE '%Sarah%'
)
LIMIT 15;
```

## Expected behavior at 100k+ scale

- **Direct field filters** on indexed columns (`status`, `published_at`, `view_count`) should remain in the low milliseconds on modest hardware.
- **Global search** (`LIKE '%term%'`) on `title`, `content`, and `excerpt` will scan more rows; consider PostgreSQL full-text search (`tsvector`) for production search workloads.
- **Multiple `whereHas` filters** compound subquery cost; monitor query time and add indexes on joined relation columns.
- **Deep offset pagination** degrades linearly; use keyset/cursor pagination for infinite-scroll UIs.

## Running the seeder

```bash
php artisan migrate:fresh --seed
```

Seeding ~110k rows typically takes a few minutes depending on disk and CPU. Progress messages are printed per stage.

## Running tests

Tests use in-memory SQLite and a small factory dataset — they validate filter correctness, not production throughput:

```bash
php artisan test --filter=Filtering
```

For load testing after seeding:

```bash
# Example with Apache Bench (adjust URL/filter as needed)
ab -n 500 -c 10 "http://localhost:8000/api/v1/articles?filters[0][field]=status&filters[0][operator]=eq&filters[0][value]=published&per_page=25"
```

Compare response times with and without indexes (drop an index in a staging DB only) to validate index impact.
