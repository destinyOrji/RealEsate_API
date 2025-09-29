# CAM-GD Homes API

A comprehensive REST API for real estate management system built with PHP and MongoDB.

## 🚀 Features

- **Authentication & Authorization**
  - JWT-based authentication
  - Role-based access control (Client, Agent, Admin)
  - User registration and login
  - Password reset functionality

- **Property Management**
  - CRUD operations for properties
  - Property search and filtering
  - Image upload and management
  - Property reviews and ratings

- **User Management**
  - User profiles and settings
  - Agent applications and approvals
  - Admin dashboard and controls

- **Advanced Features**
  - Property tours scheduling
  - Saved properties functionality
  - Performance metrics and analytics
  - Health monitoring endpoints

## 📁 Project Structure

```
CAM-GD HOMES/
├── api/                          # Main API directory
│   ├── config/                   # Configuration files
│   │   └── config.php           # Database and app configuration
│   ├── controllers/             # API controllers
│   │   ├── AuthController.php   # Authentication logic
│   │   ├── PropertyController.php # Property management
│   │   ├── UserController.php   # User management
│   │   ├── AgentController.php  # Agent-specific features
│   │   └── AdminController.php  # Admin functionality
│   ├── models/                  # Data models
│   │   ├── User.php            # User model
│   │   ├── Property.php        # Property model
│   │   └── Agent.php           # Agent model
│   ├── routes/                  # Route definitions
│   │   ├── auth.php            # Authentication routes
│   │   ├── properties.php      # Property routes
│   │   ├── users.php           # User routes
│   │   ├── agent.php           # Agent routes
│   │   └── admin.php           # Admin routes
│   ├── helpers/                 # Helper classes
│   │   ├── Jwt.php             # JWT token handling
│   │   └── JwtHelper.php       # JWT helper alias
│   ├── middleware/              # Middleware classes
│   │   └── AuthMiddleware.php  # Authentication middleware
│   ├── core/                    # Core system files
│   │   └── Router.php          # Custom router
│   └── index.php               # API entry point
├── vendor/                      # Composer dependencies
├── composer.json               # PHP dependencies
├── composer.lock              # Locked dependencies
├── test-api.php              # API testing script
└── README.md                 # This file
```

## 🛠 Installation

### Prerequisites
- PHP 7.4 or higher
- MongoDB 4.0 or higher
- Composer
- Web server (Apache/Nginx)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd CAM-GD HOMES
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   - Copy `.env.example` to `.env` (if available)
   - Update database configuration in `api/config/config.php`

4. **Set up MongoDB**
   - Ensure MongoDB is running
   - Create database: `camgd_homes`
   - Update connection string in config

5. **Configure web server**
   - Point document root to project directory
   - Ensure URL rewriting is enabled
   - API accessible at: `http://your-domain/api/`

## 🔧 Configuration

### Database Configuration
Edit `api/config/config.php`:

```php
// MongoDB Configuration
define('MONGODB_HOST', 'localhost');
define('MONGODB_PORT', 27017);
define('MONGODB_DATABASE', 'camgd_homes');
define('MONGODB_USERNAME', ''); // if auth required
define('MONGODB_PASSWORD', ''); // if auth required

// JWT Configuration
define('JWT_SECRET', 'your-secret-key-here');
define('JWT_EXPIRY', 3600); // 1 hour
```

## 📚 API Documentation

### Base URL
```
http://your-domain/api
```

### Authentication
All protected endpoints require JWT token in header:
```
Authorization: Bearer <your-jwt-token>
```

### Main Endpoints

#### Authentication
- `POST /auth/register` - Register new user
- `POST /auth/login` - User login
- `POST /auth/logout` - User logout
- `POST /auth/refresh` - Refresh JWT token
- `POST /auth/forgot-password` - Request password reset
- `POST /auth/reset-password` - Reset password

#### Properties
- `GET /properties` - Get all properties (with filters)
- `GET /properties/{id}` - Get property details
- `POST /properties` - Create property (Agent/Admin)
- `PUT /properties/{id}` - Update property (Owner/Admin)
- `DELETE /properties/{id}` - Delete property (Owner/Admin)
- `GET /properties/search` - Search properties
- `GET /properties/featured` - Get featured properties

#### Users
- `GET /users/me` - Get current user profile
- `PUT /users/me` - Update current user profile
- `GET /users/me/saved-properties` - Get saved properties
- `GET /users/me/tours` - Get scheduled tours

#### Agent Features
- `GET /agent/dashboard` - Agent dashboard data
- `GET /agent/properties` - Agent's properties
- `GET /agent/tours` - Scheduled tours
- `GET /agent/earnings` - Earnings and commissions
- `POST /agent/apply` - Apply to become agent

#### Admin Features
- `GET /admin/dashboard` - Admin dashboard
- `GET /admin/users` - Manage users
- `GET /admin/properties` - Manage properties
- `GET /admin/agents` - Manage agents
- `GET /admin/statistics` - System statistics

### Health Monitoring
- `GET /health` - API health check
- `GET /status` - System status
- `GET /test/database` - Database connectivity test

## 🧪 Testing

### Using the Test Script
```bash
php test-api.php
```

### Using Postman
1. Import the collection: `CAM-GD_Homes_API.postman_collection.json`
2. Import the environment: `CAM-GD_Homes_Environment.postman_environment.json`
3. Update environment variables with your API base URL
4. Run the collection tests

### Manual Testing
Use the provided Postman collection or test individual endpoints with curl:

```bash
# Health check
curl http://your-domain/api/health

# Register user
curl -X POST http://your-domain/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"fullname":"John Doe","email":"john@example.com","password":"password123","role":"client"}'

# Login
curl -X POST http://your-domain/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'
```

## 🔒 Security Features

- JWT token-based authentication
- Role-based access control
- Password hashing with PHP's `password_hash()`
- Input validation and sanitization
- CORS configuration
- SQL injection prevention (using MongoDB)
- XSS protection

## 🚀 Deployment

### Production Checklist
- [ ] Set strong JWT secret key
- [ ] Enable HTTPS
- [ ] Configure proper CORS settings
- [ ] Set up database backups
- [ ] Configure error logging
- [ ] Set up monitoring
- [ ] Update file permissions
- [ ] Configure rate limiting

### Environment Variables
Set these in production:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `JWT_SECRET=<strong-secret-key>`
- `MONGODB_URI=<production-mongodb-uri>`

## 📝 API Response Format

### Success Response
```json
{
    "status": "success",
    "message": "Operation completed successfully",
    "data": {
        // Response data
    }
}
```

### Error Response
```json
{
    "status": "error",
    "message": "Error description",
    "code": 400
}
```

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the API documentation

## 🔄 Version History

- **v1.0.0** - Initial API release
  - Basic authentication and authorization
  - Property management CRUD operations
  - User management features
  - Agent and admin functionality
  - Health monitoring endpoints
