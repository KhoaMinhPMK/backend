# API Testing for Profile Update

## 1. Get User Profile
```bash
curl -X GET "http://localhost/viegrandApp/backendphp/get_profile.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## 2. Update User Profile
```bash
curl -X PUT "http://localhost/viegrandApp/backendphp/update_profile.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "fullName": "Nguyễn Văn A",
    "phone": "0123456789",
    "age": 25,
    "address": "123 Đường ABC, Quận 1, TP.HCM",
    "gender": "Nam"
  }'
```

## 3. Update with Health Information (when database columns are added)
```bash
curl -X PUT "http://localhost/viegrandApp/backendphp/update_profile.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "fullName": "Nguyễn Văn A",
    "phone": "0123456789",
    "age": 25,
    "address": "123 Đường ABC, Quận 1, TP.HCM",
    "gender": "Nam",
    "bloodType": "O+",
    "allergies": "Không có dị ứng đặc biệt",
    "chronicDiseases": "Không có bệnh mãn tính"
  }'
```

## 4. Partial Update (only update specific fields)
```bash
curl -X PUT "http://localhost/viegrandApp/backendphp/update_profile.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "phone": "0987654321",
    "address": "456 Đường XYZ, Quận 2, TP.HCM"
  }'
```

## Response Format

### Success Response:
```json
{
  "success": true,
  "data": {
    "id": "1",
    "fullName": "Nguyễn Văn A",
    "email": "user@example.com",
    "phone": "0123456789",
    "age": 25,
    "address": "123 Đường ABC, Quận 1, TP.HCM",
    "gender": "Nam",
    "role": "elderly",
    "active": "1",
    "premium": {
      "isPremium": false,
      "premiumStartDate": null,
      "premiumEndDate": null,
      "trialUsed": false,
      "daysRemaining": 0,
      "plan": null
    },
    "createdAt": "2024-01-01 10:00:00",
    "updatedAt": "2024-01-01 15:30:00"
  },
  "message": "Thông tin hồ sơ đã được cập nhật thành công"
}
```

### Error Response:
```json
{
  "success": false,
  "error": {
    "statusCode": 400,
    "message": "Họ và tên không được để trống",
    "error": "Bad request",
    "timestamp": "2024-01-01 15:30:00",
    "path": "/viegrandApp/backendphp/update_profile.php",
    "method": "PUT"
  }
}
```

## Authentication Token
- Lấy token từ response của login API
- Format: `userId_tokenHash` (ví dụ: `1_abc123def456...`)
- Gửi trong header: `Authorization: Bearer YOUR_TOKEN`

## Validation Rules
- **fullName**: Bắt buộc, không được rỗng
- **phone**: Tùy chọn, phải là số điện thoại hợp lệ
- **age**: Tùy chọn, từ 1 đến 120
- **address**: Tùy chọn
- **gender**: Tùy chọn, chỉ chấp nhận "Nam" hoặc "Nữ"
- **bloodType**: Tùy chọn, chỉ chấp nhận A+, A-, B+, B-, AB+, AB-, O+, O-
- **allergies**: Tùy chọn, text
- **chronicDiseases**: Tùy chọn, text
