# API Examples — Advanced Filtering System

Base URL: `http://localhost:8000/api/v1`

All list endpoints accept the same query parameters:

| Parameter | Description |
|-----------|-------------|
| `filters[]` | Array of filter objects (`field`, `operator`, `value`) |
| `search` | Global search across configured fields and relations |
| `sort` | Comma-separated fields; prefix with `-` for descending |
| `page` | Page number (default: 1) |
| `per_page` | Items per page (default: 15, max: 100) |

## Supported operators

| Operator | Description |
|----------|-------------|
| `eq` | Exact match |
| `ne` | Not equal |
| `contains` | Text contains (LIKE `%value%`) |
| `starts_with` | Text starts with |
| `ends_with` | Text ends with |
| `gt` / `gte` / `lt` / `lte` | Numeric or date comparison |
| `in` | Multiple values (comma-separated string or array) |
| `not_in` | Exclude multiple values |
| `empty` | NULL or empty string |
| `not_empty` | Has a non-empty value |

---

## Articles

### Text matching on title

```http
GET /api/v1/articles?filters[0][field]=title&filters[0][operator]=contains&filters[0][value]=Forbes
```

### Exact status + view count range

```http
GET /api/v1/articles?filters[0][field]=status&filters[0][operator]=eq&filters[0][value]=published&filters[1][field]=view_count&filters[1][operator]=gte&filters[1][value]=1000
```

### Date comparison

```http
GET /api/v1/articles?filters[0][field]=published_at&filters[0][operator]=gte&filters[0][value]=2025-01-01&filters[1][field]=published_at&filters[1][operator]=lte&filters[1][value]=2025-12-31
```

### Multiple statuses

```http
GET /api/v1/articles?filters[0][field]=status&filters[0][operator]=in&filters[0][value]=draft,published
```

### Empty excerpt

```http
GET /api/v1/articles?filters[0][field]=excerpt&filters[0][operator]=empty
```

### Filter by author name (relation)

```http
GET /api/v1/articles?filters[0][field]=author.name&filters[0][operator]=contains&filters[0][value]=Sarah
```

### Filter by nested category parent (relation)

```http
GET /api/v1/articles?filters[0][field]=category.parent.name&filters[0][operator]=eq&filters[0][value]=Business
```

### Filter by tag name (many-to-many)

```http
GET /api/v1/articles?filters[0][field]=tags.name&filters[0][operator]=eq&filters[0][value]=Technology
```

### Global search + sort + pagination

```http
GET /api/v1/articles?search=market&sort=-view_count,title&page=2&per_page=25
```

---

## Authors

```http
GET /api/v1/authors?filters[0][field]=articles_count&filters[0][operator]=gt&filters[0][value]=100&sort=-rating
```

```http
GET /api/v1/authors?filters[0][field]=user.email&filters[0][operator]=contains&filters[0][value]=@forbes
```

---

## Categories

```http
GET /api/v1/categories?filters[0][field]=is_active&filters[0][operator]=eq&filters[0][value]=1&filters[1][field]=parent.name&filters[1][operator]=not_empty
```

---

## Tags

```http
GET /api/v1/tags?filters[0][field]=usage_count&filters[0][operator]=gte&filters[0][value]=500&sort=-usage_count
```

---

## Comments

```http
GET /api/v1/comments?filters[0][field]=rating&filters[0][operator]=gte&filters[0][value]=4&filters[1][field]=article.title&filters[1][operator]=contains&filters[1][value]=Market
```

```http
GET /api/v1/comments?filters[0][field]=article.author.name&filters[0][operator]=eq&filters[0][value]=Jane Doe&search=insightful
```

---

## JSON POST-style (same params as query string)

```bash
curl -G "http://localhost:8000/api/v1/articles" \
  --data-urlencode "search=energy" \
  --data-urlencode "sort=-published_at" \
  --data-urlencode "filters[0][field]=status" \
  --data-urlencode "filters[0][operator]=eq" \
  --data-urlencode "filters[0][value]=published"
```

## Sample response shape

```json
{
  "data": [
    {
      "id": 1,
      "title": "Sample Article",
      "status": "published",
      "view_count": 1200,
      "author": { "id": 5, "name": "Sarah Johnson" },
      "category": { "id": 2, "name": "Startups", "parent": { "id": 1, "name": "Business" } },
      "tags": [{ "id": 10, "name": "Technology" }]
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "per_page": 15, "total": 842 }
}
```
