# Student Disciplinary Management System

A comprehensive system for managing student disciplinary cases in educational institutions.

## Features

- User Authentication and Role-based Access Control
- Case Reporting and Management
- Case Review System
- Student Records Management
- Parent Information Management
- Notification System
- Reports Generation

## User Roles

1. **Admin**
   - Manage users
   - Review and process cases
   - Generate reports
   - Full system access

2. **Teachers**
   - Report disciplinary cases
   - View their reported cases
   - Update case information

3. **Parents**
   - View their children's cases
   - Receive notifications
   - Access case updates

## Installation

1. Clone the repository:
```bash
git clone https://github.com/fmusenene/Student-Disciplinary-Management-System.git
```

2. Create the database:
```sql
CREATE DATABASE disciplinary_system;
```

3. Import the database schema:
```bash
mysql -u root disciplinary_system < database/schema.sql
```

4. Configure the database connection:
   - Open `config/database.php`
   - Update the database credentials

5. Set up the web server:
   - Point your web server to the project directory
   - Ensure PHP and MySQL are installed and configured

6. Create an admin user:
   - Run `create_admin.php` in your browser
   - Follow the prompts to create the first admin account

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Security Features

- Password hashing
- Role-based access control
- Input validation and sanitization
- Session management
- SQL injection prevention

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details. 