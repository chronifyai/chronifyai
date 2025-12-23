# ChronifyAI API Usage Guide

## Authentication

The API uses Moodle's standard web service authentication. You need to:

1. Enable web services in Moodle
2. Create a web service user
3. Generate a token for the user
4. Assign the user the `local/chronifyai:useservice` capability

## Endpoints

### Get Courses List

**Endpoint:** `local_chronifyai_get_courses_list`

**Parameters:**
- `page` (int, optional): Page number starting from 0 (default: 0)
- `perpage` (int, optional): Number of courses per page, max 100 (default: 20)
- `search` (string, optional): Search term for course name
- `categoryid` (int, optional): Filter by category ID

**Example Request:**

```
POST /webservice/rest/server.php wstoken=YOUR_TOKEN&wsfunction=local_chronifyai_get_courses_list&moodlewsrestformat=json&page=0&perpage=10
``` 

**Example Response:**

```
{ "data": [ { "id": 1, "course_name": "10-Level Grammar & Idioms Course", "category": "Computer Science", "start_date": "2025-01-15", "end_date": "2025-06-15", "instructor_name": "John Doe", "students": 125, "activities": 42 } ], "pagination": { "page": 0, "perpage": 10, "total": 50, "totalpages": 5, "hasnext": true, "hasprev": false } }
``` 

## Adding New API Endpoints

To add new API endpoints:

1. Add function definition to `db/services.php`
2. Implement the function in `classes/external/external.php`
3. Define parameters using `external_function_parameters`
4. Define return structure using `external_single_structure` or `external_multiple_structure`
5. Add appropriate capability checks
6. Update language strings if needed

## Error Handling

The API follows Moodle's standard error handling. Common errors:
- Missing capabilities: HTTP 403
- Invalid parameters: HTTP 400
- Server errors: HTTP 500

