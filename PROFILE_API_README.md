# API Cập Nhật Thông Tin Cá Nhân

## Mô tả
API này cho phép người dùng cập nhật thông tin cá nhân của họ trong hệ thống.

## Endpoints

### 1. Cập nhật thông tin cá nhân
**PATCH** `/users/me`

#### Headers
```
Authorization: Bearer {user_token}
Content-Type: application/json
```

#### Body Parameters
```json
{
    "fullName": "string (optional)",
    "phone": "string (optional)",
    "age": "number (optional)",
    "address": "string (optional)",
    "gender": "string (optional)"
}
```

#### Validation Rules
- **fullName**: Không được để trống, tối đa 100 ký tự
- **phone**: Số điện thoại Việt Nam hợp lệ (10-11 chữ số, bắt đầu bằng 0), không được trùng với tài khoản khác
- **age**: Số nguyên từ 1-150
- **address**: Tối đa 255 ký tự
- **gender**: Một trong các giá trị: Nam, Nữ, Khác, male, female, other

#### Response Success (200)
```json
{
    "success": true,
    "data": {
        "id": 1,
        "fullName": "Nguyễn Văn A",
        "email": "user@example.com",
        "phone": "0123456789",
        "age": 30,
        "address": "123 ABC Street",
        "gender": "Nam",
        "role": "elderly",
        "active": true,
        "isPremium": false,
        "premiumEndDate": null,
        "premiumTrialUsed": false,
        "createdAt": "2024-01-01 00:00:00",
        "updatedAt": "2024-01-01 12:00:00"
    },
    "message": "Profile updated successfully"
}
```

#### Response Errors
- **400**: Dữ liệu không hợp lệ
- **401**: Token không hợp lệ hoặc thiếu
- **403**: Tài khoản bị khóa
- **404**: Người dùng không tồn tại
- **409**: Số điện thoại đã được sử dụng
- **500**: Lỗi server

### 2. Lấy thông tin cá nhân
**GET** `/users/me`

#### Headers
```
Authorization: Bearer {user_token}
```

#### Response Success (200)
```json
{
    "success": true,
    "data": {
        "id": 1,
        "fullName": "Nguyễn Văn A",
        "email": "user@example.com",
        "phone": "0123456789",
        "age": 30,
        "address": "123 ABC Street",
        "gender": "Nam",
        "role": "elderly",
        "active": true,
        "isPremium": false,
        "premiumEndDate": null,
        "premiumTrialUsed": false,
        "createdAt": "2024-01-01 00:00:00",
        "updatedAt": "2024-01-01 12:00:00"
    },
    "message": "Profile retrieved successfully"
}
```

## Authentication
API sử dụng Bearer token với định dạng: `{userId}_{randomToken}`

Token này được tạo khi đăng nhập và cần được gửi trong header Authorization.

## Testing

### Sử dụng curl
```bash
# Cập nhật thông tin
curl -X PATCH "http://localhost/backendphp/api.php/users/me" \
  -H "Authorization: Bearer 1_abc123..." \
  -H "Content-Type: application/json" \
  -d '{
    "fullName": "Nguyễn Văn B",
    "phone": "0987654321",
    "age": 25
  }'

# Lấy thông tin
curl -X GET "http://localhost/backendphp/api.php/users/me" \
  -H "Authorization: Bearer 1_abc123..."
```

## Frontend Integration

### React Native / TypeScript
```typescript
// Cập nhật thông tin
const updateProfile = async (userData: Partial<User>) => {
  const token = await AsyncStorage.getItem('access_token');
  const response = await fetch(`${API_URL}/users/me`, {
    method: 'PATCH',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(userData),
  });
  
  const result = await response.json();
  if (result.success) {
    return result.data;
  } else {
    throw new Error(result.error.message);
  }
};
```

## Security Notes
1. Token phải được validate trước khi xử lý request
2. Tất cả input đều được sanitize và validate
3. Không cho phép cập nhật email (cần endpoint riêng với xác thực)
4. Kiểm tra trùng lặp số điện thoại
5. Log tất cả lỗi để debug

## Database Schema
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fullName VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    age INT,
    address VARCHAR(255),
    gender VARCHAR(10),
    role ENUM('elderly', 'relative') NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    isPremium BOOLEAN DEFAULT FALSE,
    premiumEndDate DATETIME,
    premiumTrialUsed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```
