# Salesmen API - Analýza OpenAPI špecifikácie

## Kľúčové entity

### 1. Salesman (hlavná entita)

**Properties:**

-   `id` - UUID (primary key)
-   `first_name` - string (2-50 chars, required)
-   `last_name` - string (2-50 chars, required)
-   `display_name` - computed field (titles_before + first_name + last_name + titles_after)
-   `titles_before` - array of strings (0-10 items, each 2-10 chars)
-   `titles_after` - array of strings (0-10 items, each 2-10 chars)
-   `prosight_id` - string (exactly 5 digits, required, unique)
-   `email` - string (required, unique)
-   `phone` - string (nullable)
-   `gender` - string (required, from codelist)
-   `marital_status` - string (nullable, from codelist)
-   `created_at` - timestamp
-   `updated_at` - timestamp

### 2. Codelists (pre validácie)

**Genders:**

-   m (muž)
-   f (žena)

**Marital Statuses:**

-   single (slobodný/á)
-   married (ženatý/vydatá)
-   divorced (rozvedený/á)
-   widowed (vdovec/vdova)

**Titles Before:**
Bc., Mgr., Ing., JUDr., MVDr., MUDr., PaedDr., prof., doc., dipl., MDDr., Dr., Mgr. art., ThLic., PhDr., PhMr., RNDr., ThDr., RSDr., arch., PharmDr.

**Titles After:**
CSc., DrSc., PhD., ArtD., DiS, DiS.art, FEBO, MPH, BSBA, MBA, DBA, MHA, FCCA, MSc., FEBU, LL.M

## Endpoints potrebné

1. `POST /salesmen` - vytvoriť nového
2. `GET /salesmen` - list s pagination/sorting
3. `GET /salesmen/{uuid}` - detail jedného
4. `PUT /salesmen/{uuid}` - update
5. `DELETE /salesmen/{uuid}` - zmazať
6. `GET /codelists` - vrátiť všetky codelists

## Dôležité validácie

-   prosight_id musí byť unique
-   email musí byť unique
-   gender/marital_status musia byť z codelistov
-   titles_before/after musia byť z codelistov
-   správne error handling (400, 404, 409, 416)

## Response format

-   data wrapping pre všetky responses
-   pagination links (first, last, prev, next)
-   display_name sa počíta dynamicky
