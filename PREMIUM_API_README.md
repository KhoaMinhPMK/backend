# Premium API Documentation

## Tổng quan
Hệ thống Premium API cung cấp các endpoint để quản lý gói premium, thanh toán và trạng thái subscription của người dùng.

## Các file API

### 1. `premium_api.php` - API chính cho Premium
**Base URL:** `/premium_api.php`

#### Endpoints:

##### GET `/plans`
Lấy danh sách các gói premium có sẵn
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Gói Cơ Bản",
      "description": "Gói dành cho người mới bắt đầu",
      "price": 99000,
      "duration": 30,
      "type": "monthly",
      "features": ["Thông báo cơ bản", "Hỗ trợ 24/7"],
      "isActive": true,
      "sortOrder": 1,
      "isRecommended": false
    }
  ],
  "message": "Thành công"
}
```

##### GET `/my-status`
Lấy trạng thái premium của user hiện tại
```json
{
  "success": true,
  "data": {
    "isPremium": true,
    "subscription": {
      "id": 1,
      "status": "active",
      "startDate": "2024-01-01 00:00:00",
      "endDate": "2024-02-01 00:00:00",
      "autoRenewal": true,
      "paidAmount": 199000,
      "paymentMethod": "momo"
    },
    "plan": {
      "id": 2,
      "name": "Gói Premium",
      "description": "Gói cao cấp với nhiều tính năng",
      "price": 199000,
      "type": "monthly",
      "features": ["Thông báo nâng cao", "Hỗ trợ 24/7", "Báo cáo chi tiết"]
    },
    "daysRemaining": 15
  },
  "message": "Thành công"
}
```

##### POST `/purchase`
Mua gói premium
```json
// Request
{
  "planId": 2,
  "paymentMethod": "momo"
}

// Response
{
  "success": true,
  "data": {
    "success": true,
    "subscription": {
      "id": 1,
      "userId": 1,
      "planId": 2,
      "status": "active",
      "startDate": "2024-01-01 00:00:00",
      "endDate": "2024-02-01 00:00:00",
      "autoRenewal": true,
      "paidAmount": 199000,
      "paymentMethod": "momo"
    },
    "transaction": {
      "id": 1,
      "transactionCode": "TXN_1704067200_1234",
      "amount": 199000,
      "status": "completed",
      "paymentMethod": "momo",
      "type": "subscription"
    },
    "message": "Thanh toán thành công"
  },
  "message": "Thành công"
}
```

##### GET `/transactions`
Lấy lịch sử giao dịch của user
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "transactionCode": "TXN_1704067200_1234",
      "amount": 199000,
      "status": "completed",
      "paymentMethod": "momo",
      "type": "subscription",
      "description": "Thanh toán gói Premium",
      "createdAt": "2024-01-01 00:00:00",
      "planName": "Gói Premium",
      "planType": "monthly"
    }
  ],
  "message": "Thành công"
}
```

##### GET `/payment-methods`
Lấy danh sách phương thức thanh toán
```json
{
  "success": true,
  "data": [
    {
      "id": "credit_card",
      "type": "credit_card",
      "name": "Thẻ Tín dụng / Ghi nợ",
      "description": "Visa, Mastercard, JCB",
      "icon": "💳",
      "enabled": true,
      "isAvailable": true,
      "processingFee": 0
    },
    {
      "id": "momo",
      "type": "e_wallet",
      "name": "Ví MoMo",
      "description": "Thanh toán qua MoMo",
      "icon": "🐷",
      "enabled": true,
      "isAvailable": true,
      "processingFee": 0
    }
  ],
  "message": "Thành công"
}
```

##### POST `/cancel-subscription`
Hủy gói premium
```json
// Request
{
  "reason": "Không cần sử dụng nữa"
}

// Response
{
  "success": true,
  "data": {
    "message": "Đã hủy gói premium thành công"
  },
  "message": "Thành công"
}
```

##### POST `/retry-payment`
Thử lại thanh toán
```json
// Request
{
  "transactionId": 1
}

// Response
{
  "success": true,
  "data": {
    "success": true,
    "paymentUrl": "https://payment-gateway.example.com/retry/1",
    "message": "Đã tạo link thanh toán mới"
  },
  "message": "Thành công"
}
```

### 2. `update_user_premium.php` - Cập nhật premium cho user
**Base URL:** `/update_user_premium.php`

#### POST `/`
Cập nhật thông tin premium cho user cụ thể
```json
// Request
{
  "userId": 1,
  "planId": 2,
  "paymentMethod": "momo",
  "autoRenewal": true
}

// Response
{
  "success": true,
  "data": {
    "subscription": {
      "id": 1,
      "userId": 1,
      "planId": 2,
      "status": "active",
      "startDate": "2024-01-01 00:00:00",
      "endDate": "2024-02-01 00:00:00",
      "planName": "Gói Premium",
      "planDescription": "Gói cao cấp với nhiều tính năng",
      "planType": "monthly"
    },
    "transaction": {
      "id": 1,
      "transactionCode": "TXN_1704067200_1234",
      "amount": 199000,
      "status": "completed"
    },
    "message": "Premium subscription created successfully"
  },
  "message": "Premium subscription updated successfully"
}
```

### 3. `check_premium_status.php` - Kiểm tra trạng thái premium
**Base URL:** `/check_premium_status.php`

#### GET `/`
Kiểm tra và cập nhật trạng thái premium cho tất cả users
```json
{
  "success": true,
  "data": {
    "expiredCount": 5,
    "stats": {
      "totalUsers": 100,
      "activePremiumUsers": 45,
      "expiredUsers": 30,
      "cancelledUsers": 5
    },
    "users": [
      {
        "id": 1,
        "fullName": "Nguyễn Văn A",
        "email": "user@example.com",
        "role": "elderly",
        "subscriptionId": 1,
        "subscriptionStatus": "active",
        "startDate": "2024-01-01 00:00:00",
        "endDate": "2024-02-01 00:00:00",
        "planName": "Gói Premium",
        "planType": "monthly",
        "daysRemaining": 15,
        "isPremium": true
      }
    ],
    "message": "Premium status checked and updated successfully"
  },
  "message": "Premium status checked successfully"
}
```

### 4. `setup_premium_data.php` - Thiết lập dữ liệu premium
**Base URL:** `/setup_premium_data.php`

#### POST `/`
Thiết lập premium cho user hoặc tất cả users
```json
// Request - Cho user cụ thể
{
  "userId": 1,
  "planId": 2,
  "paymentMethod": "momo"
}

// Request - Cho tất cả users
{
  "planId": 2,
  "paymentMethod": "momo"
}

// Response
{
  "success": true,
  "data": {
    "totalUsers": 10,
    "planName": "Gói Premium",
    "users": [
      {
        "userId": 1,
        "userName": "Nguyễn Văn A",
        "subscriptionId": 1,
        "planName": "Gói Premium",
        "endDate": "2024-02-01 00:00:00"
      }
    ],
    "message": "Premium setup completed for 10 users"
  },
  "message": "Premium data setup completed successfully"
}
```

## Authentication
Tất cả các API đều yêu cầu authentication thông qua JWT token trong header:
```
Authorization: Bearer <token>
```

## Error Handling
Tất cả API đều trả về response theo format:
```json
{
  "success": false,
  "error": {
    "message": "Error message",
    "code": 400
  }
}
```

## Database Schema
Hệ thống sử dụng các bảng chính:
- `users` - Thông tin người dùng
- `premium_plans` - Gói premium
- `user_subscriptions` - Đăng ký premium
- `payment_transactions` - Giao dịch thanh toán

## Cách sử dụng

### 1. Thiết lập ban đầu
```bash
# Chạy database schema
mysql -u username -p database_name < database_schema.sql

# Thiết lập premium cho tất cả users
curl -X POST http://localhost/premium/setup_premium_data.php \
  -H "Content-Type: application/json" \
  -d '{"planId": 2, "paymentMethod": "momo"}'
```

### 2. Kiểm tra trạng thái
```bash
# Kiểm tra trạng thái premium
curl -X GET http://localhost/premium/check_premium_status.php
```

### 3. Sử dụng trong app
```javascript
// Lấy trạng thái premium
const status = await premiumAPI.getMyPremiumStatus();

// Mua gói premium
const result = await premiumAPI.purchase(2, 'momo');

// Lấy lịch sử giao dịch
const transactions = await premiumAPI.getMyTransactions();
```

## Lưu ý
- Tất cả API đều có CORS headers để hỗ trợ cross-origin requests
- Database sử dụng UTF-8 để hỗ trợ tiếng Việt
- Các giao dịch thanh toán được lưu trữ đầy đủ để audit
- Hệ thống tự động cập nhật trạng thái subscription hết hạn 