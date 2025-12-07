# Task Management API
<img width="1536" height="961" alt="image" src="https://github.com/user-attachments/assets/9c3a8394-6fd7-4e55-b0c4-651d6396ad6e" />


REST API for managing projects, tasks, and tags built with Laravel 12 and PostgreSQL.

## Repository Links

- **Backend (API)**: [https://github.com/andrych17/task_management_be](https://github.com/andrych17/task_management_be)
- **Frontend**: [https://github.com/andrych17/task_management_fe](https://github.com/andrych17/task_management_fe)

## 1. Folder Structure

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── ProjectController.php
│   │   │   └── TaskController.php
│   │   └── Requests/
│   │       ├── StoreTaskRequest.php
│   │       └── UpdateTaskRequest.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Project.php
│   │   ├── Task.php
│   │   └── Tag.php
│   ├── Repositories/
│   │   ├── Interfaces/
│   │   ├── ProjectRepository.php
│   │   └── TaskRepository.php
│   └── Providers/
│       └── AppServiceProvider.php
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── tests/
│   ├── Feature/
│   │   ├── AuthenticationTest.php
│   │   └── TaskApiTest.php
│   └── Unit/
│       └── TaskTest.php
└── phpunit.xml
```

## 2. Design Patterns

**Repository Pattern**
- Separates data access logic from controllers
- Implementation: `TaskRepository`
- Bound via Dependency Injection in `AppServiceProvider`

**Form Request Validation**
- Centralizes validation rules
- Implementation: `StoreTaskRequest`, `UpdateTaskRequest`

**Factory Pattern**
- Generates test data
- Implementation: `TaskFactory`, `ProjectFactory`, `TagFactory`

## 3. Setup Instructions

1. Install dependencies
   ```bash
   composer install
   ```

2. Setup environment
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Configure database in `.env`
   ```env
   DB_CONNECTION=pgsql
   DB_DATABASE=task_management
   DB_USERNAME=postgres
   DB_PASSWORD=your_password
   ```

4. Create databases
   ```sql
   CREATE DATABASE task_management;
   CREATE DATABASE task_management_test;
   ```

5. Run migrations and seed demo data
   ```bash
   # Fresh migration with demo data
   php artisan migrate:fresh --seed

   # Or migration only
   php artisan migrate
   ```

6. Start server
   ```bash
   php artisan serve
   ```

**Access:**
- API: `http://localhost:8000/api`
- Demo Login: `demo@taskmanager.com` / `demo12345`

**Demo Data Seeded:**
- 3 users (1 demo + 2 additional)
- 30 projects (10 per user)
- 90 tasks (30 per user)
- 30 tags (10 per user)

## 4. Assumptions

**Business Logic**
- Tasks can exist without a project (nullable)
- Deleting a project deletes all its tasks (cascade)
- Tag names are unique per user, not globally
- Task status: `todo`, `in-progress`, `done` (no workflow restrictions)
- Users can only access their own data
- Dashboard endpoint provides task counts by status for overview/statistics

**Pagination & Filtering**
- Default: 15 items per page, max: 100
- Search uses case-insensitive partial match (ILIKE)
- Invalid values are capped/ignored
- Prefix `-` for descending sort order

**API Endpoints**
```
Authentication:
- POST /api/register
- POST /api/login
- POST /api/logout
- GET /api/user

Dashboard:
- GET /api/dashboard - Returns task counts by status (total_tasks, todo, in_progress, done)

Projects:
- GET /api/projects?search=keyword - List all user projects (searchable dropdown)

Tags:
- GET /api/tags?search=keyword - List all user tags (searchable dropdown)

Tasks:
- GET /api/tasks - Paginated, filtered, sorted list
- GET /api/tasks/{id} - Single task details
- POST /api/tasks - Create task (auto-creates tags from string array)
- PUT /api/tasks/{id} - Update task
- DELETE /api/tasks/{id} - Delete task

Query Parameters (Tasks):
- per_page (max: 100)
- search (title/description)
- status (todo|in-progress|done)
- project_id
- tags (comma-separated tag names, e.g., "urgent,backend")
- sort (due_date, created_at, title - prefix with - for desc)
```

## 5. Test Execution and Code Coverage

**Test Summary**: 92 tests | 279 assertions | 82.8% coverage

### Run Tests
```bash
# Run all tests
php artisan test

# With coverage detail
php artisan test --coverage --min=70

# Generate HTML report
php artisan test --coverage-html=coverage-report
```

### Latest Test Results
```
Tests:    92 passed (279 assertions)
Duration: 54.10s
```

**Test Breakdown:**
- **Unit Tests** (46 tests):
  - ProjectTest (4 tests)
  - TagTest (6 tests)
  - TaskRepositoryTest (10 tests)
  - TaskTest (8 tests)
  - UserTest (7 tests)

- **Feature Tests** (46 tests):
  - AuthenticationTest (10 tests)
  - ProjectApiTest (8 tests)
  - TagApiTest (7 tests)
  - TaskApiTest (32 tests)

### View Coverage Report
Open `backend/coverage-report/index.html` in browser

### Coverage Breakdown
- Controllers: AuthController (100%), TaskController (71.8%), TagController (66.7%), ProjectController (66.7%)
- Models: 100%
- Repositories: 100%
- Form Requests: 100%
- **Total: 82.8%** (exceeds 70% threshold)

### Demo Data
```bash
php artisan db:seed  # Creates 3 users, 30 projects, 90 tasks, 30 tags
```

**Demo Account**: `demo@taskmanager.com` / `demo12345`
