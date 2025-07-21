# API Cập Nhật Thông Tin Người Dùng - Hướng Dẫn Setup

## 📋 Tổng Quan
API này cho phép người dùng cập nhật thông tin cá nhân trong ứng dụng VieGrand, bao gồm:
- Thông tin cá nhân: Họ tên, số điện thoại, tuổi, địa chỉ, giới tính
- Thông tin y tế (tương lai): Nhóm máu, dị ứng, bệnh mãn tính

## 🗂️ Cấu Trúc Files

```
backendphp/
├── config.php                     # Cấu hình database và helpers
├── get_profile.php                # API lấy thông tin profile
├── update_profile.php             # API cập nhật profile
├── login.php                      # API đăng nhập (đã có sẵn)
├── add_health_info_columns.sql    # Script thêm cột y tế (tùy chọn)
├── simple_profile_test.php        # Tool test API
├── PROFILE_UPDATE_API_TEST.md     # Hướng dẫn test bằng CURL
└── PROFILE_API_README.md          # File này
```

## 🚀 Cách Bắt Đầu

### Bước 1: Chuẩn Bị Environment
1. **Khởi động XAMPP/WAMP/MAMP:**
   - Apache Server
   - MySQL Database

2. **Tạo Database:**
   ```sql
   CREATE DATABASE IF NOT EXISTS viegrand_app;
   ```

3. **Kiểm tra bảng users tồn tại với các cột:**
   - id, fullName, email, password, phone, age, address, gender, role, active
   - isPremium, premiumStartDate, premiumEndDate, premiumPlanId, premiumTrialUsed
   - created_at, updated_at

### Bước 2: Cấu Hình Database
Kiểm tra file `config.php` có đúng thông tin:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'viegrand_app');
define('DB_USER', 'root');        // Thay đổi nếu cần
define('DB_PASS', '');            // Thay đổi nếu cần
```

### Bước 3: Test API
1. **Mở trình duyệt và truy cập:**
   ```
   http://localhost/viegrandApp/backendphp/simple_profile_test.php
   ```

2. **Kiểm tra kết quả:**
   - ✅ Database connection thành công
   - ✅ Test user được tạo
   - ✅ Các file API tồn tại

## 🔧 Cách Sử Dụng API

### 1. Lấy Thông Tin Profile

**Endpoint:** `GET /get_profile.php`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "1",
    "fullName": "Nguyễn Văn A",
    "email": "user@example.com",
    "phone": "0123456789",
    "age": 25,
    "address": "123 ABC Street",
    "gender": "Nam",
    "role": "elderly",
    "premium": { ... }
  },
  "message": "User profile retrieved successfully"
}
```

### 2. Cập Nhật Thông Tin Profile

**Endpoint:** `PUT /update_profile.php`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN
```

**Body:**
```json
{
  "fullName": "Nguyễn Văn A",
  "phone": "0123456789", 
  "age": 30,
  "address": "456 XYZ Street",
  "gender": "Nam"
}
```

**Response:**
```json
{
  "success": true,
  "data": { /* updated user object */ },
  "message": "Thông tin hồ sơ đã được cập nhật thành công"
}
```

## 🔐 Authentication

### Token Format
- Token được lấy từ login API
- Format: `userId_randomHash`
- Gửi trong header: `Authorization: Bearer TOKEN`

### Cách Lấy Token
1. Gọi API login để lấy token
2. Lưu token vào AsyncStorage (React Native)
3. Gửi token trong mọi request cần authentication

## ✅ Validation Rules

| Field | Required | Type | Validation |
|-------|----------|------|------------|
| fullName | ✓ | string | Không được rỗng |
| phone | ✗ | string | Định dạng số điện thoại |
| age | ✗ | number | 1-120 |
| address | ✗ | string | - |
| gender | ✗ | string | "Nam" hoặc "Nữ" |
| bloodType | ✗ | string | A+, A-, B+, B-, AB+, AB-, O+, O- |
| allergies | ✗ | text | - |
| chronicDiseases | ✗ | text | - |

## 🧪 Testing

### 1. Test với CURL
```bash
# Test GET profile
curl -X GET "http://localhost/viegrandApp/backendphp/get_profile.php" \
  -H "Authorization: Bearer 1_test_token_123456"

# Test UPDATE profile  
curl -X PUT "http://localhost/viegrandApp/backendphp/update_profile.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer 1_test_token_123456" \
  -d '{"fullName":"Test Updated","age":25}'
```

### 2. Test với Postman
1. Import collection từ `PROFILE_UPDATE_API_TEST.md`
2. Set environment variables
3. Run tests

### 3. Test trong React Native
```typescript
import { usersAPI } from '../services/api';

// Lấy thông tin profile
const profile = await usersAPI.getProfile();

// Cập nhật profile
const updatedProfile = await usersAPI.updateMyProfile({
  fullName: 'Tên mới',
  age: 30
});
```

## 🚨 Troubleshooting

### Lỗi Database Connection
- Kiểm tra XAMPP/WAMP đang chạy
- Kiểm tra thông tin database trong `config.php`
- Kiểm tra database `viegrand_app` đã được tạo

### Lỗi Authentication
- Kiểm tra token có đúng format không
- Kiểm tra user có tồn tại và active không
- Kiểm tra header Authorization có đúng không

### Lỗi Validation
- Kiểm tra data gửi lên có đúng format không
- Kiểm tra required fields
- Kiểm tra giá trị có nằm trong range cho phép không

## 🔄 React Native Integration

File `EditProfileScreen.tsx` đã được cấu hình để sử dụng API này:

1. **Import API:**
   ```typescript
   import { usersAPI } from '../../services/api';
   ```

2. **Gọi API update:**
   ```typescript
   const updatedUser = await usersAPI.updateMyProfile(userData);
   ```

3. **Update context:**
   ```typescript
   updateCurrentUser(updatedUser);
   ```

## 🔮 Tính Năng Tương Lai

### Thông Tin Y Tế
Để thêm tính năng lưu trữ thông tin y tế:

1. **Chạy SQL script:**
   ```bash
   mysql -u root -p viegrand_app < add_health_info_columns.sql
   ```

2. **Uncomment code trong update_profile.php:**
   - Tìm các dòng có comment về bloodType, allergies, chronicDiseases
   - Uncomment để kích hoạt

3. **Update React Native:**
   - Thêm fields vào EditProfileScreen
   - Update API calls

## 📚 API Documentation

Chi tiết đầy đủ về API có thể tìm thấy trong:
- `PROFILE_UPDATE_API_TEST.md` - Hướng dẫn test
- Source code trong `get_profile.php` và `update_profile.php`

## 🤝 Support

Nếu gặp vấn đề:
1. Kiểm tra `simple_profile_test.php` để debug
2. Xem log trong browser console
3. Kiểm tra PHP error logs
4. Test với CURL trước khi test trong app
