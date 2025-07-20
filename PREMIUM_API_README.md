# VieGrand Premium API Documentation

## Tổng quan

API Premium cho phép quản lý các gói premium, subscription và payment cho ứng dụng VieGrand.

## Endpoints

### 1. Lấy danh sách gói Premium
```
GET /premium/plans
```

**Response:**
```json
{
  "success": true,
  "message": "Premium plans retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Gói Cơ Bản",
      "description": "Gói premium cơ bản với các tính năng cần thiết",
      "price": 99000,
      "duration": 30,
      "type": "monthly",
      "features": ["Truy cập không giới hạn", "Hỗ trợ 24/7", "Không quảng cáo"],
      "isActive": true,
      "isRecommended": false,
      "discountPercent": 0
    }
  ]
}
```

### 2. Lấy trạng thái Premium của User
```
GET /premium/my-status
```

**Response:**
```json
{
  "success": true,
  "message": "Premium status retrieved successfully",
  "data": {
    "userId": 1,
    "userName": "Nguyễn Văn A",
    "userEmail": "user1@example.com",
    "isPremium": true,
    "premiumStartDate": "2024-12-19 10:00:00",
    "premiumEndDate": "2025-01-19 10:00:00",
    "daysRemaining": 30,
    "isTrialActive": false,
    "trialUsed": false,
    "subscription": {
      "id": 1,
      "status": "active",
      "autoRenewal": true,
      "nextPaymentDate": "2025-01-19 10:00:00"
    },
    "plan": {
      "id": 2,
      "name": "Gói Nâng Cao",
      "price": 199000,
      "type": "monthly",
      "features": ["Tất cả tính năng cơ bản", "Tùy chỉnh giao diện"]
    }
  }
}
```

### 3. Lấy phương thức thanh toán
```
GET /premium/payment-methods
```

**Response:**
```json
{
  "success": true,
  "message": "Payment methods retrieved successfully",
  "data": [
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
  ]
}
```

### 4. Lấy lịch sử giao dịch
```
GET /premium/payment/my-transactions
```

**Response:**
```json
{
  "success": true,
  "message": "Transactions retrieved successfully",
  "data": [
    {
      "id": 1,
      "transactionCode": "TXN_1702876543_2_2",
      "amount": 199000,
      "currency": "VND",
      "status": "completed",
      "paymentMethod": "momo",
      "type": "subscription",
      "description": "Thanh toán Gói Nâng Cao - monthly",
      "paidAt": "2024-12-19 10:00:00",
      "planName": "Gói Nâng Cao",
      "subscriptionStatus": "active"
    }
  ]
}
```

### 5. Mua gói Premium
```
POST /premium/purchase
Content-Type: application/json

{
  "planId": 2,
  "paymentMethod": "momo"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Premium purchase completed successfully",
  "data": {
    "success": true,
    "transaction": {
      "id": 1,
      "transactionCode": "TXN_1702876543_1_2",
      "amount": 199000,
      "currency": "VND",
      "status": "completed",
      "paymentMethod": "momo",
      "type": "subscription",
      "description": "Thanh toán Gói Nâng Cao - monthly",
      "paidAt": "2024-12-19 10:00:00"
    },
    "subscription": {
      "id": 1,
      "userId": 1,
      "planId": 2,
      "status": "active",
      "startDate": "2024-12-19 10:00:00",
      "endDate": "2025-01-19 10:00:00",
      "autoRenewal": true,
      "paidAmount": 199000,
      "paymentMethod": "momo"
    },
    "plan": {
      "id": 2,
      "name": "Gói Nâng Cao",
      "price": 199000,
      "duration": 30,
      "type": "monthly"
    },
    "message": "Purchase completed successfully"
  }
}
```

### 6. Kích hoạt Premium Trial
```
POST /premium/activate-trial
```

**Response:**
```json
{
  "success": true,
  "message": "Premium trial activated successfully",
  "data": {
    "success": true,
    "isPremium": true,
    "premiumEndDate": "2024-12-26 10:00:00",
    "trialEndDate": "2024-12-26 10:00:00",
    "trialDays": 7,
    "message": "Premium trial activated for 7 days"
  }
}
```

### 7. Hủy Subscription
```
PUT /premium/subscription/cancel
Content-Type: application/json

{
  "cancelReason": "Không cần thiết nữa"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Subscription cancelled successfully",
  "data": {
    "id": 1,
    "status": "cancelled",
    "cancelReason": "Không cần thiết nữa",
    "cancelledAt": "2024-12-19 10:00:00"
  }
}
```

## Admin APIs

### 1. Kiểm tra subscription hết hạn
```
POST /premium/admin/check-expired
```

**Response:**
```json
{
  "success": true,
  "message": "Subscription status check completed",
  "data": {
    "success": true,
    "expiredCount": 5,
    "checkedAt": "2024-12-19 10:00:00",
    "message": "Checked and updated 5 expired subscriptions"
  }
}
```

### 2. Cập nhật trạng thái Premium user
```
POST /premium/admin/update-status
Content-Type: application/json

{
  "userId": 1
}
```

### 3. Thống kê Premium
```
GET /premium/admin/stats
```

**Response:**
```json
{
  "success": true,
  "message": "Premium statistics retrieved successfully",
  "data": {
    "totalPremiumUsers": 125,
    "activeSubscriptions": 98,
    "totalRevenue": 24750000,
    "expiringSoon": 12,
    "topPlans": [
      {
        "name": "Gói Nâng Cao",
        "price": 199000,
        "subscriptionCount": 45,
        "totalRevenue": 8955000
      }
    ]
  }
}
```

## Error Responses

Khi có lỗi, API sẽ trả về format:

```json
{
  "success": false,
  "error": "Error message",
  "details": "Detailed error information",
  "timestamp": "2024-12-19T10:00:00Z"
}
```

## Status Codes

- `200` - Thành công
- `400` - Lỗi request (thiếu tham số, dữ liệu không hợp lệ)
- `404` - Endpoint không tồn tại
- `405` - Method không được phép
- `500` - Lỗi server

## Authentication

Hiện tại API chưa có authentication hoàn chỉnh. Tạm thời sử dụng userId cố định = 1 để test.

Cần implement JWT token authentication trong tương lai.

## Database Schema

### Bảng Users (đã cập nhật)
- `isPremium`: BOOLEAN - Trạng thái premium
- `premiumStartDate`: TIMESTAMP - Ngày bắt đầu premium
- `premiumEndDate`: TIMESTAMP - Ngày hết hạn premium
- `premiumPlanId`: INT - ID của plan premium
- `premiumTrialUsed`: BOOLEAN - Đã dùng trial chưa
- `premiumTrialEndDate`: TIMESTAMP - Ngày hết hạn trial

### Stored Procedures
- `CreateSubscription(userId, planId, paymentMethod, paidAmount)`: Tạo subscription mới
- `UpdateUserPremiumStatus(userId)`: Cập nhật trạng thái premium user
- `CheckExpiredSubscriptions()`: Kiểm tra và cập nhật subscription hết hạn
- `ActivatePremiumTrial(userId, trialDays)`: Kích hoạt premium trial

## Setup và Deployment

1. Chạy `database_schema.sql` để tạo database structure
2. Chạy `sample_data_safe.sql` để thêm dữ liệu mẫu
3. Cấu hình database connection trong `config.php`
4. Deploy các file PHP lên server
5. Setup cron job chạy `POST /premium/admin/check-expired` hàng ngày để kiểm tra subscription hết hạn

## Testing

Sử dụng Postman hoặc curl để test các endpoint:

```bash
# Test lấy plans
curl -X GET http://localhost/premium/plans

# Test mua gói premium
curl -X POST http://localhost/premium/purchase \
  -H "Content-Type: application/json" \
  -d '{"planId": 2, "paymentMethod": "momo"}'

# Test kích hoạt trial
curl -X POST http://localhost/premium/activate-trial

# Test lấy trạng thái premium
curl -X GET http://localhost/premium/my-status
```
