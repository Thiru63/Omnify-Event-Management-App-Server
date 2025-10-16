# Event Management System - Backend

A robust Laravel-based Event Management System API with comprehensive features for event creation, attendee registration, and management.

## 🚀 Live Deployment

- **API Base URL**: `https://omnify-event-management-app-server.onrender.com/api`
- **Swagger Documentation**: [View API Docs](https://omnify-event-management-app-server.onrender.com/swagger-live)


## 📋 Features Implemented

### Core Features
✅ **Event Management**
- Create events with name, location, timing, and capacity
- List all upcoming events with advanced filtering
- Get unique event locations
- Timezone-aware event handling (default: Asia/Kolkata)

✅ **Attendee Management**
- Register attendees for events
- Prevent overbooking (max capacity enforcement)
- Prevent duplicate email registrations per event
- View paginated attendee lists with search

✅ **Advanced Features**
- Pagination for both events and attendees
- Advanced search and filtering capabilities
- Comprehensive input validation
- Structured error responses
- API request logging
- Rate limiting for security

### Bonus Features
✅ **Swagger/OpenAPI Documentation** - Complete API documentation
✅ **Unit Tests** - Comprehensive test coverage for events and attendees
✅ **Timezone Management** - All events stored in IST with timezone conversion
✅ **Rate Limiting** - Protection against abuse
✅ **CORS Support** - Frontend-backend communication ready

## 🏗️ Architecture & Design

### Database Schema

```sql
Events Table:
- id (Primary Key)
- name (string)
- location (string) 
- start_time (timestamp)
- end_time (timestamp)
- max_capacity (integer)
- current_attendees (integer)
- created_at, updated_at

Attendees Table:
- id (Primary Key)
- event_id (Foreign Key)
- name (string)
- email (string)
- created_at, updated_at
- Unique constraint: (event_id, email)
```

### Models

**Event Model** (`app/Models/Event.php`)
- Handles business logic for event management
- Scopes for upcoming events, available seats, location filtering
- Timezone conversion methods
- Search capabilities across multiple fields

**Attendee Model** (`app/Models/Attendee.php`)
- Manages attendee registrations
- Scopes for event-specific queries and search
- Relationship with Event model

### Controllers

**EventController** (`app/Http/Controllers/EventController.php`)
- `store()` - Create new events
- `index()` - List events with advanced filtering
- `getLocations()` - Get unique locations

**AttendeeController** (`app/Http/Controllers/AttendeeController.php`)
- `register()` - Register attendees with validation
- `index()` - Get event attendees with pagination

### Request Validation

**CreateEventRequest** (`app/Http/Requests/CreateEventRequest.php`)
- Validates event creation data
- Converts timestamps to IST timezone
- Ensures start_time is in future, end_time after start_time

**RegisterAttendeeRequest** (`app/Http/Requests/RegisterAttendeeRequest.php`)
- Validates attendee registration
- Prevents duplicate email registrations per event

## 🔧 API Endpoints

### Events

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/events` | Create a new event |
| `GET` | `/api/events` | List upcoming events with filters |
| `GET` | `/api/events/locations` | Get unique event locations |

### Attendees

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/events/{id}/register` | Register attendee for event |
| `GET` | `/api/events/{id}/attendees` | Get event attendees |

## 🎯 Advanced Filtering & Search

### Event Listing Features
- **Pagination**: Customizable page size (1-100)
- **Sorting**: By name, location, timing, capacity
- **Search**: Across multiple fields (name, location, timing)
- **Location Filtering**: Single or multiple locations
- **Capacity Filtering**: Events with available seats
- **Timezone Support**: Display events in any timezone

### Query Parameters Examples

```bash
# Get events sorted by start time
GET /api/events?sort_by=start_time&sort_order=asc

# Search events in Bangalore with available seats
GET /api/events?search_for=conference&filter_by_location=Bangalore&seat_available_events=true

# Get events with custom pagination
GET /api/events?per_page=20&page=2&timezone=America/New_York
```

## 🛡️ Security & Validation

### Input Validation
- Event timing validation (future dates, logical ranges)
- Capacity limits (1-10,000 attendees)
- Email format validation
- Unique email enforcement per event

### Rate Limiting
- **General API**: 60 requests per minute
- **Event Registration**: 5 requests per minute
- **Burst Protection**: 10 requests per second

### Error Handling
Structured error responses with consistent format:
```json
{
  "success": false,
  "message": "Error description",
  "errors": { /* validation errors */ }
}
```

## 📊 Testing

### Test Coverage
- **Event Creation**: Valid and invalid scenarios
- **Attendee Registration**: Success, duplicates, capacity limits
- **Filtering & Search**: All query parameters tested
- **Edge Cases**: Past dates, zero capacity, non-existent events

Run tests:
```bash
php artisan test
```

## 🚀 Deployment

### Production Setup
- **Platform**: Render.com
- **Database**: PostgreSQL (Production) / SQLite (Development)
- **PHP Version**: 8.2
- **Laravel Version**: 12.33.0

### Environment Configuration
```env
APP_URL=https://omnify-event-management-app-server.onrender.com
DB_CONNECTION=pgsql
APP_TIMEZONE=Asia/Kolkata
```

## 📚 API Documentation

### Interactive Documentation
Visit [Swagger UI](https://omnify-event-management-app-server.onrender.com/swagger-live) for:
- Complete API reference
- Interactive endpoint testing
- Request/response schemas
- Error code documentation

### Sample Requests

**Create Event:**
```bash
curl -X POST "https://omnify-event-management-app-server.onrender.com/api/events" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Tech Conference 2024",
    "location": "Bangalore Convention Center",
    "start_time": "2024-12-20 10:00:00",
    "end_time": "2024-12-20 17:00:00",
    "max_capacity": 100
  }'
```

**Register Attendee:**
```bash
curl -X POST "https://omnify-event-management-app-server.onrender.com/api/events/1/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com"
  }'
```

## 🔄 Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { /* response data */ }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": { /* validation errors */ }
}
```

## 🎯 Design Principles

### Clean Architecture
- **Separation of Concerns**: Models, Controllers, Requests, Traits
- **DRY Principle**: Reusable ApiResponse trait
- **Single Responsibility**: Each class has focused purpose

### Scalability
- Database indexing for performance
- Pagination for large datasets
- Efficient query building with scopes

### Maintainability
- Comprehensive documentation
- Unit test coverage
- Consistent coding standards
- Structured error handling

## 📦 Dependencies

- **Laravel Framework**: 12.33.0
- **L5-Swagger**: API documentation
- **PHPUnit**: Testing framework
- **Carbon**: Date/time handling

---

**Next Steps**: Frontend integration with Next.js using the provided API endpoints and Swagger documentation.Explore [here](https://github.com/Thiru63/Omnify-Event-Management-App-UI) and  [Mange Your Events](https://omnify-event-management-app.vercel.app/dashboard)