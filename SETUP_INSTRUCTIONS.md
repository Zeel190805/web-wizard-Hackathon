# 🚀 Student Portal Setup Instructions

## Quick Setup Guide (5 Minutes)

### Prerequisites
- ✅ XAMPP/LAMP/WAMP Server
- ✅ PHP 7.4 or higher
- ✅ MySQL 5.7 or higher
- ✅ Web browser (Chrome/Firefox/Edge)

### Step 1: Extract Project Files
1. Extract the project to your web server directory:
   - **XAMPP**: `C:\xampp\htdocs\student-portal\`
   - **WAMP**: `C:\wamp64\www\student-portal\`
   - **Linux**: `/var/www/html/student-portal/`

### Step 2: Database Setup
1. **Start your server** (Apache + MySQL)
2. **Open phpMyAdmin**: `http://localhost/phpmyadmin`
3. **Create database**:
   - Click "New" on the left sidebar
   - Database name: `student_portal`
   - Collation: `utf8mb4_general_ci`
   - Click "Create"
4. **Import database**:
   - Select `student_portal` database
   - Click "Import" tab
   - Choose file: `sql/database.sql`
   - Click "Go"

### Step 3: Configuration
1. **Database settings** (if needed):
   - Edit `config/database.php`
   - Update database credentials:
   ```php
   private $host = 'localhost';
   private $dbname = 'student_portal';
   private $username = 'root';        // Your DB username
   private $password = '';            // Your DB password
   ```

2. **Base URL** (if needed):
   - Edit `config/config.php`
   - Update the base URL:
   ```php
   define('BASE_URL', 'http://localhost/student-portal/');
   ```

### Step 4: File Permissions
Create upload directories and set permissions:
```bash
mkdir assets/uploads
mkdir assets/uploads/profiles
chmod 755 assets/uploads
chmod 755 assets/uploads/profiles
```

### Step 5: Access the System
1. **Open browser**: `http://localhost/student-portal/`
2. **Login with demo accounts**:

#### Admin Account
- **Email**: `admin@portal.com`
- **Password**: `password`

#### Student Accounts
- **Email**: `john.doe@student.com` | **Password**: `password`
- **Email**: `jane.smith@student.com` | **Password**: `password`
- **Email**: `mike.wilson@student.com` | **Password**: `password`

## 🎯 Testing Checklist

### Authentication Testing
- [ ] Admin login works
- [ ] Student login works
- [ ] Registration works
- [ ] "Remember Me" functionality
- [ ] Logout works
- [ ] Password reset (demo link)

### Student Features Testing
- [ ] Student dashboard loads
- [ ] Profile viewing works
- [ ] Profile editing works
- [ ] Profile picture upload
- [ ] Responsive design on mobile

### Admin Features Testing
- [ ] Admin dashboard loads
- [ ] Student list displays
- [ ] Search functionality works
- [ ] Add new student
- [ ] Edit student details
- [ ] Delete student
- [ ] Bulk operations
- [ ] Export to CSV
- [ ] Activity logs

### Security Testing
- [ ] SQL injection protection
- [ ] XSS protection
- [ ] CSRF protection
- [ ] File upload security
- [ ] Session management

## 🔧 Troubleshooting

### Common Issues

#### Database Connection Error
**Problem**: "Database connection failed"
**Solution**:
1. Check if MySQL is running
2. Verify database credentials in `config/database.php`
3. Ensure database `student_portal` exists

#### File Upload Not Working
**Problem**: Profile pictures not uploading
**Solution**:
1. Check folder permissions: `chmod 755 assets/uploads`
2. Verify upload_max_filesize in php.ini
3. Check if directory exists

#### CSS/JS Not Loading
**Problem**: Styling/JavaScript not working
**Solution**:
1. Check BASE_URL in `config/config.php`
2. Verify file paths
3. Clear browser cache

#### Session Issues
**Problem**: Auto-logout or session problems
**Solution**:
1. Check session.save_path in php.ini
2. Verify session permissions
3. Clear browser cookies

### System Requirements Check

#### PHP Extensions Required
- PDO MySQL
- GD (for image handling)
- Session support
- JSON support

#### Check PHP Configuration
```bash
php -m | grep -E "(pdo|mysql|gd|session|json)"
```

## 📊 Default Data

### Pre-loaded Sample Data
- **1 Admin user**
- **3 Sample students** with profiles
- **Sample courses**: Computer Science, Business Administration, Mechanical Engineering
- **Activity logs** for demonstration
- **System settings** configured

### Database Schema Overview
- **users**: Authentication and user management
- **student_profiles**: Student personal information
- **activity_logs**: System activity tracking
- **academic_records**: Academic performance
- **documents**: File management
- **notifications**: System notifications
- **system_settings**: Application configuration

## 🔒 Security Features Enabled

### Password Security
- bcrypt hashing with salt
- Minimum 8 character requirement
- Password strength validation
- Failed login attempt tracking
- Account lockout after 5 failed attempts

### Session Security
- Secure session configuration
- Session timeout (30 minutes)
- Session ID regeneration
- CSRF token protection

### File Upload Security
- File type validation
- File size limits (5MB)
- Secure file naming
- Upload directory protection

### Database Security
- Prepared statements for all queries
- Input sanitization
- SQL injection prevention
- Foreign key constraints

## 🎨 Customization Options

### Theme Customization
- Edit `assets/css/style.css`
- Modify CSS variables in `:root`
- Update color schemes
- Customize component styles

### Feature Configuration
- Edit `config/config.php` for settings
- Modify upload limits
- Change session timeout
- Update email settings

### Database Customization
- Add custom fields to student_profiles
- Create additional tables
- Modify existing schema
- Add custom indexes

## 📱 Mobile Responsiveness

### Tested Devices
- ✅ iPhone (iOS Safari)
- ✅ Android (Chrome)
- ✅ iPad (Safari)
- ✅ Desktop (Chrome, Firefox, Edge)

### Responsive Breakpoints
- **Desktop**: 1200px+
- **Laptop**: 992px - 1199px
- **Tablet**: 768px - 991px
- **Mobile**: 576px - 767px
- **Small Mobile**: < 576px

## 🚀 Performance Optimization

### Database Optimization
- Proper indexing on search columns
- Optimized queries with LIMIT
- Foreign key relationships
- Connection pooling

### Frontend Optimization
- Minified CSS/JS (in production)
- Image optimization
- Lazy loading for images
- AJAX for seamless UX

### Caching
- Browser caching headers
- Session-based caching
- Query result caching
- Static asset caching

## 📞 Support Information

### Demo Video
A comprehensive demo video is available showing:
- Complete system walkthrough
- All features demonstration
- Mobile responsiveness
- Security features
- Admin and student workflows

### Documentation
- Complete user manual
- API documentation
- Security best practices
- Deployment guide
- Troubleshooting guide

### Contact Information
For technical support or questions:
- Email: [your-email@domain.com]
- GitHub: [your-github-repo]
- Demo: [live-demo-url]

---

**Student Portal v1.0.0** - Setup complete! 🎉
