# VieGrand API Testing với CURL - Windows

## 🔍 Test API đăng ký user bằng curl

### BƯỚC 1: Kiểm tra server đang chạy
```bash
# Test xem server có hoạt động không
curl -X GET http://localhost/backendphp/signup.php
# Hoặc nếu dùng XAMPP port khác
curl -X GET http://localhost:8080/backendphp/signup.php
```

### BƯỚC 2: Test API đăng ký với curl

#### ✅ Test đăng ký thành công:
```bash
curl -X POST http://localhost/backendphp/signup.php ^
  -H "Content-Type: application/json" ^
  -d "{\"fullName\":\"Test User\",\"email\":\"testuser@example.com\",\"password\":\"123456\",\"phone\":\"0123456789\"}"
```

#### ✅ Test với dữ liệu từ React Native (lỗi hiện tại):
```bash
curl -X POST http://localhost/backendphp/signup.php ^
  -H "Content-Type: application/json" ^
  -d "{\"fullName\":\"Vhhj\",\"email\":\"pmkkhoaminh@gmail.com\",\"password\":\"123123\",\"phone\":\"2580147369\"}"
```

#### ✅ Test các trường hợp lỗi:

**Email đã tồn tại:**
```bash
curl -X POST http://localhost/backendphp/signup.php ^
  -H "Content-Type: application/json" ^
  -d "{\"fullName\":\"Test User 2\",\"email\":\"testuser@example.com\",\"password\":\"123456\",\"phone\":\"0987654321\"}"
```

**Password quá ngắn:**
```bash
curl -X POST http://localhost/backendphp/signup.php ^
  -H "Content-Type: application/json" ^
  -d "{\"fullName\":\"Test User\",\"email\":\"test2@example.com\",\"password\":\"123\",\"phone\":\"0123456789\"}"
```

**Thiếu trường bắt buộc:**
```bash
curl -X POST http://localhost/backendphp/signup.php ^
  -H "Content-Type: application/json" ^
  -d "{\"fullName\":\"Test User\",\"email\":\"test3@example.com\"}"
```

### BƯỚC 3: Test API login

```bash
curl -X POST http://localhost/backendphp/login.php ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"testuser@example.com\",\"password\":\"123456\"}"
```

### BƯỚC 4: Test Premium APIs

#### Lấy danh sách gói premium:
```bash
curl -X GET http://localhost/backendphp/premium.php/plans
```

#### Lấy trạng thái premium:
```bash
curl -X GET http://localhost/backendphp/premium.php/my-status
```

#### Kích hoạt premium trial:
```bash
curl -X POST http://localhost/backendphp/premium.php/activate-trial ^
  -H "Content-Type: application/json" ^
  -d "{}"
```

#### Mua gói premium:
```bash
curl -X POST http://localhost/backendphp/premium.php/purchase ^
  -H "Content-Type: application/json" ^
  -d "{\"planId\":1,\"paymentMethod\":\"momo\"}"
```

## 🔧 Debug lỗi 400

### BƯỚC 1: Bật error reporting trong PHP
Thêm vào đầu file `signup.php`:
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// Rest of code...
```

### BƯỚC 2: Kiểm tra log lỗi
```bash
# Kiểm tra PHP error log
tail -f C:\xampp\apache\logs\error.log
# Hoặc
tail -f C:\wamp64\logs\php_error.log
```

### BƯỚC 3: Test từng phần

#### Test database connection:
```bash
curl -X POST http://localhost/backendphp/signup.php ^
  -H "Content-Type: application/json" ^
  -d "{\"test\":\"connection\"}" ^
  -v
```

#### Test validation:
```bash
curl -X POST http://localhost/backendphp/signup.php ^
  -H "Content-Type: application/json" ^
  -d "{\"fullName\":\"\",\"email\":\"invalid-email\",\"password\":\"123\",\"phone\":\"abc\"}" ^
  -v
```

## 🐛 Các lỗi thường gặp và cách fix:

### 1. **Database connection failed**
```bash
# Kiểm tra MySQL đang chạy
netstat -an | findstr 3306

# Test connection
mysql -u root -p -e "SHOW DATABASES;"
```

### 2. **Table 'users' doesn't exist**
```bash
# Kiểm tra bảng users
mysql -u root -p viegrand_app -e "DESCRIBE users;"

# Nếu chưa có, chạy lại database schema
mysql -u root -p viegrand_app < database_schema_fixed.sql
```

### 3. **CORS errors**
Thêm vào đầu các file PHP:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

### 4. **JSON parsing errors**
```bash
# Test với raw data
curl -X POST http://localhost/backendphp/signup.php ^
  -H "Content-Type: application/json" ^
  --data-raw "{\"fullName\":\"Test\",\"email\":\"test@test.com\",\"password\":\"123456\",\"phone\":\"0123456789\"}" ^
  -v
```

## 📱 Test từ React Native

### Cách test network từ React Native:
1. **Kiểm tra IP local:**
```bash
ipconfig
```

2. **Thay đổi base URL trong app:**
```javascript
// Thay vì localhost, dùng IP thực
const BASE_URL = 'http://192.168.1.100/backendphp';
```

3. **Test connectivity:**
```javascript
// Test ping trước
fetch('http://192.168.1.100/backendphp/signup.php')
  .then(response => console.log('Server reachable'))
  .catch(error => console.log('Server unreachable'));
```

## ⚡ Quick Debug Script

Tạo file `debug.php` để test nhanh:
```php
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $pdo = new PDO('mysql:host=localhost;dbname=viegrand_app', 'root', '');
    echo json_encode([
        'status' => 'success',
        'message' => 'Database connected',
        'tables' => $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}
?>
```

Test debug script:
```bash
curl -X GET http://localhost/backendphp/debug.php
```
