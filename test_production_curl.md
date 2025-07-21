# Test VieGrand Production API với CURL

## Bước 1: Test Login
```bash
curl -X POST "https://viegrand.site/backend/login.php" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "pmkkhoaminh@gmail.com",
    "password": "123123"
  }'
```

## Bước 2: Lấy token từ response login và test Update Profile
**Thay YOUR_TOKEN_HERE bằng token thật từ bước 1**

```bash
curl -X PUT "https://viegrand.site/backend/update_profile.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "fullName": "Test Update Name",
    "phone": "1234567890",
    "age": 30,
    "address": "Test Address",
    "gender": "Nam"
  }'
```

## Bước 3: Test Get Profile
```bash
curl -X GET "https://viegrand.site/backend/get_profile.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Kiểm tra files có tồn tại không
```bash
curl -I "https://viegrand.site/backend/update_profile.php"
curl -I "https://viegrand.site/backend/get_profile.php"
```

## Debug: Kiểm tra endpoints có response không
```bash
curl -X OPTIONS "https://viegrand.site/backend/update_profile.php" \
  -H "Content-Type: application/json"
```

## Nếu gặp lỗi 404, có thể files chưa được upload
## Nếu gặp lỗi 500, có thể có lỗi PHP trên server
## Nếu gặp lỗi 400, có thể validation fail hoặc authentication fail
