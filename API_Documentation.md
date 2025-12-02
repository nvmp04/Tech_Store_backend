# PHP JWT Authentication API

## Authentication
Tất cả các endpoint có đánh dấu **[Protected]** yêu cầu gửi JWT token trong header:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

---

## API Endpoints

### 1. Register – Đăng ký tài khoản

**Endpoint:**
```
POST /api/auth/register.php
```

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "Test123456",
  "full_name": "John Doe"
}
```

**Validation Rules:**
- `email`: Bắt buộc, hợp lệ định dạng email  
- `password`: Bắt buộc, tối thiểu 8 ký tự, chứa chữ hoa, chữ thường và số  
- `full_name`: Tùy chọn  

**Response – 201 Created**
```json
{
  "success": true,
  "message": "Đăng ký thành công. Vui lòng kiểm tra email để xác thực tài khoản",
  "user_id": 1,
  "verification_url": "http://localhost/TechStore/api/verify-email.php?token=abc123..."
}
```

**Error – 400 Bad Request**
```json
{
  "success": false,
  "message": "Email đã được sử dụng"
}
```

---

### 2. Verify Email – Xác thực email

**Endpoint:**
```
GET /api/auth/verify-email.php?token={verification_token}
```

**Parameters:**
- `token`: Token được gửi qua email hoặc trả về sau đăng ký  

**Response – 200 OK**
```json
{
  "success": true,
  "message": "Email đã được xác thực thành công"
}
```

**Error – 400 Bad Request**
```json
{
  "success": false,
  "message": "Token không hợp lệ hoặc đã được sử dụng"
}
```

---

### 3. Login – Đăng nhập

**Endpoint:**
```
POST /api/auth/login.php
```

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "Test123456"
}
```

**Response – 200 OK**
```json
{
  "success": true,
  "message": "Đăng nhập thành công",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "full_name": "John Doe",
    "email_verified": 1
  }
}
```

**Token Details:**
- Loại: JWT  
- Expiration: 3600s (1 giờ)  
- Algorithm: HS256  

**Error – 401 Unauthorized**
```json
{
  "success": false,
  "message": "Email hoặc mật khẩu không đúng"
}
```

**Error – 400 Bad Request**
```json
{
  "success": false,
  "message": "Vui lòng xác thực email trước khi đăng nhập"
}
```

---

### 4. Get Current User – Lấy thông tin user hiện tại [Protected]

**Endpoint:**
```
GET /api/auth/me.php
```

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
```

**Response – 200 OK**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "email": "user@example.com",
    "full_name": "John Doe",
    "email_verified": 1,
    "created_at": "2025-10-30 10:30:00",
    "updated_at": "2025-10-30 15:45:00"
  }
}
```

**Error – 401 Unauthorized**
```json
{
  "success": false,
  "message": "Token không hợp lệ hoặc đã hết hạn"
}
```

---

### 5. Change Password – Đổi mật khẩu [Protected]

**Endpoint:**
```
POST /api/auth/change-password.php
```

**Headers:**
```
Content-Type: application/json
Authorization: Bearer YOUR_JWT_TOKEN
```

**Request Body:**
```json
{
  "old_password": "Test123456",
  "new_password": "NewTest123456"
}
```

**Response – 200 OK**
```json
{
  "success": true,
  "message": "Đổi mật khẩu thành công"
}
```

**Error – 400 Bad Request**
```json
{
  "success": false,
  "message": "Mật khẩu cũ không đúng"
}
```



### 6. Lấy profile
```http
GET /user/profile.php
Authorization: Bearer <token>
```

### 7. Cập nhật profile
```http
PUT /user/profile.php
Authorization: Bearer <token>
Content-Type: application/json

{
  "full_name": "Nguyen Van B"
}
```


## Admin Endpoints (Requires: admin)
**Thông tin:**
- Email: `admin@techstore.com`
- Password: `Admin@123`
- Role: `admin`
### User Management

#### 1. Lấy danh sách users
```http
GET /admin/users.php?limit=20&offset=0
Authorization: Bearer <admin-token>
```

#### 2. Lọc users theo role
```http
GET /admin/users.php?role=user&limit=20&offset=0
Authorization: Bearer <admin-token>
```

#### 3. Chi tiết user
```http
GET /admin/users.php?id=user-uuid
Authorization: Bearer <admin-token>
```

#### 4. Cập nhật role user
```http
PUT /admin/users.php
Authorization: Bearer <admin-token>
Content-Type: application/json

{
  "user_id": "uuid",
  "role": "admin"
}
```

**Allowed roles:** `guest`, `user`, `admin`

#### 5. Xóa user
```http
DELETE /admin/users.php?id=user-uuid
Authorization: Bearer <admin-token>
```

### Statistics

#### 6. Thống kê users
```http
GET /admin/statistics.php
Authorization: Bearer <admin-token>
```

**Response:**
```json
{
  "success": true,
  "statistics": {
    "total_users": 150,
    "total_admins": 3,
    "total_regular_users": 145,
    "total_guests": 2
  }
}
```

## Response Format

### Success Response
```json
{
  "success": true,
  "data": [...],
  "message": "Optional message"
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message"
}
```

### Pagination Format
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "total": 150,
    "limit": 20,
    "offset": 0,
    "pages": 8
  }
}
```

---

## Admin
**Thông tin:**
- Email: `admin@techstore.com`
- Password: `Admin@123`
- Role: `admin`

# API Documentation - Giỏ hàng & Đơn hàng 

## CART APIs

### 1. Lấy giỏ hàng
```http
GET /api/cart/index.php
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "cart": {
    "id": "cart-uuid",
    "status": "active",
    "items": [
      {
        "id": "item-uuid",
        "product_id": 1,
        "name": "MacBook Pro M3",
        "quantity": 2,
        "price": 45000000,
        "is_selected": 1,
        "subtotal": 90000000,
        "images": ["..."],
        "in_stock": 1
      }
    ],
    "total": 90000000,
    "selected_total": 90000000,
    "item_count": 1,
    "selected_count": 1
  }
}
```

**Cart Status:**
- `active` - Giỏ hàng đang hoạt động
- `checked_out` - Đã thanh toán
- `abandoned` - Đã bỏ quên

### 2. Thêm sản phẩm vào giỏ
```http
POST /api/cart/index.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "product_id": 1,
  "quantity": 2
}
```

### 3. Cập nhật số lượng
```http
PUT /api/cart/index.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "item_id": "item-uuid",
  "quantity": 3
}
```

**Note:** Nếu quantity = 0, item sẽ bị xóa khỏi giỏ

### 4. Đánh dấu chọn/bỏ chọn sản phẩm

#### 4.1. Toggle single item
```http
PATCH /api/cart/index.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "action": "toggle_selection",
  "item_id": "item-uuid",
  "is_selected": true
}
```

#### 4.2. Chọn tất cả
```http
PATCH /api/cart/index.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "action": "select_all"
}
```

#### 4.3. Bỏ chọn tất cả
```http
PATCH /api/carts/index.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "action": "unselect_all"
}
```

#### 4.4. Cập nhật trạng thái giỏ hàng
```http
PATCH /api/cart/index.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "action": "update_status",
  "status": "abandoned"
}
```

### 5. Xóa sản phẩm
```http
DELETE /api/cart/index.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "item_id": "item-uuid"
}
```

### 6. Xóa toàn bộ giỏ hàng
```http
POST /api/cart/clear.php
Authorization: Bearer {token}
```

---

## ORDER APIs

### 1. MUA NGAY (Buy Now)
Đặt hàng trực tiếp 1 sản phẩm không cần giỏ hàng

```http
POST /api/orders/buy-now.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "product_id": 1,
  "quantity": 1,
  "full_name": "Nguyễn Văn A",
  "email": "user@example.com",
  "phone": "0901234567",
  "province": "Hồ Chí Minh",
  "district": "Quận 1",
  "ward": "Phường Bến Nghé",
  "address_detail": "123 Đường Lê Lợi"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Đặt hàng thành công",
  "order_id": "order-uuid"
}
```

### 2. CHECKOUT Selected Items
Đặt hàng những sản phẩm đã chọn trong giỏ

**Cách 1: Checkout items đã đánh dấu `is_selected = 1`**
```http
POST /api/orders/checkout.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "full_name": "Nguyễn Văn A",
  "email": "user@example.com",
  "phone": "0901234567",
  "province": "Hồ Chí Minh",
  "district": "Quận 1",
  "ward": "Phường Bến Nghé",
  "address_detail": "123 Đường Lê Lợi"
}
```

**Cách 2: Checkout theo IDs cụ thể**
```http
POST /api/orders/checkout.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "cart_item_ids": ["item-uuid-1", "item-uuid-2"],
  "full_name": "Nguyễn Văn A",
  "email": "user@example.com",
  "phone": "0901234567",
  "province": "Hồ Chí Minh",
  "district": "Quận 1",
  "ward": "Phường Bến Nghé",
  "address_detail": "123 Đường Lê Lợi"
}
```

**Note:** 
- Nếu không truyền `cart_item_ids`, hệ thống checkout items có `is_selected = 1`
- Sau khi checkout thành công, items sẽ bị xóa khỏi giỏ
- Nếu giỏ trống sau checkout → cart status = `checked_out`

### 3. Lấy danh sách đơn hàng (có pagination)
```http
GET /api/orders/index.php?page=1&limit=10&status=pending
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (optional): Trang hiện tại, default = 1
- `limit` (optional): Số items/trang, default = 10, max = 100
- `status` (optional): Filter theo trạng thái (pending, confirmed, shipping, delivered, cancelled)

**Response:**
```json
{
  "success": true,
  "orders": [
    {
      "id": "order-uuid",
      "full_name": "Nguyễn Văn A",
      "email": "user@example.com",
      "phone": "0901234567",
      "province": "Hồ Chí Minh",
      "district": "Quận 1",
      "ward": "Phường Bến Nghé",
      "address_detail": "123 Đường Lê Lợi",
      "total_amount": 90000000,
      "rate": false,
      "status": "pending",
      "payment_status": "unpaid",
      "total_items": 2,
      "created_at": "2025-01-15 10:30:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 47,
    "items_per_page": 10,
    "has_next": true,
    "has_prev": false
  }
}
```

### 4. Lấy chi tiết đơn hàng theo ID
```http
GET /api/orders/detail.php?id=order-uuid
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "order": {
    "id": "order-uuid",
    "full_name": "Nguyễn Văn A",
    "province": "Hồ Chí Minh",
    "district": "Quận 1",
    "ward": "Phường Bến Nghé",
    "address_detail": "123 Đường Lê Lợi",
    "total_amount": 90000000,
    "rate": false,
    "status": "confirmed",
    "payment_status": "unpaid",
    "items": [
      {
        "id": "item-uuid",
        "product_id": 1,
        "product_name": "MacBook Pro M3",
        "quantity": 2,
        "price": 45000000,
        "subtotal": 90000000,
        "images": ["..."]
      }
    ]
  }
}
```

### 5. Thống kê đơn hàng theo trạng thái
```http
GET /api/orders/statistics.php
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "statistics": {
    "total_orders": 15,
    "total_amount": 500000000,
    "by_status": {
      "pending": {
        "count": 3,
        "total_amount": 100000000
      },
      "confirmed": {
        "count": 2,
        "total_amount": 80000000
      },
      "shipping": {
        "count": 5,
        "total_amount": 200000000
      },
      "delivered": {
        "count": 4,
        "total_amount": 100000000
      },
      "cancelled": {
        "count": 1,
        "total_amount": 20000000
      }
    }
  }
}
```

### 6. Hủy đơn hàng
```http
POST /api/orders/cancel.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "order_id": "order-uuid"
}
```

**Note:** Chỉ hủy được đơn hàng có status = `pending` hoặc `confirmed`

---

## COMMENTS APIs

### 1. Lấy comments theo product
```http
GET /api/comments.php?product_id={product_id}
```

**Response:**
```json
{
  "success": true,
  "comments": [
    {
      "id": "comment-uuid",
      "parent_id": null,
      "userName": "Nguyễn Văn A",
      "date": "15/10/2024",
      "verified": false,
      "comment": "Sản phẩm dùng rất tốt!",
      "rating": 0.0
    }
  ]
}
```

### 2. Thêm comment (POST)
```http
POST /api/comments.php
Content-Type: application/json

{
  "user_id": "user-uuid",
  "product_id": 123,
  "content": "Sản phẩm dùng rất tốt!",
  "rating": 4.0, # optional, if not provided default 0
  "parent_id": null,
  "verified": false
}

### 2. Delete comment
```http
DELETE /api/comments.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "id": "comment-uuid"
}

Example (PowerShell):
```powershell
curl -X DELETE 'http://localhost/BE_Tech_Store/api/comments.php' -H 'Content-Type: application/json' -H 'Authorization: Bearer YOUR_TOKEN' -d '{"id":"comment-uuid"}'
```
```

**Behavior:**
- Soft deletes the specified comment by setting `status` to `deleted`.
- Recursively soft-deletes child comments where `parent_id` matches a deleted comment.
- Only the comment owner or admin can delete a comment.

**Response – 200 OK**
```json
{
  "success": true,
  "deleted_ids": ["comment-uuid", "child-comment-uuid-1", "child-comment-uuid-2"],
  "message": "Comment deleted"
}
```
```

### 3. Thêm rating/đánh giá (POST)
```http
POST /api/rating.php
Content-Type: application/json

{
  "user_id": "user-uuid",
  "order_id": "order-uuid",
  "product_id": 123, # optional; if provided, only update this product; otherwise all products in order
  "rating": 4.0,
  "content": "Sản phẩm tốt, giao nhanh!",
  "verified": true,
  "parent_id": null
}
```

**Behavior:**
- Uses `order_id` to find product(s) purchased in that order.
- If `content` provided, a `comment` entry is inserted for each product.
- Sets `orders.rate` = true for the specified `order_id`.
- For each product, increments `reviews` and recalculates `rating` as a new average (rounded to 1 decimal place in display).

**Response – 200 OK**
```json
{
  "success": true,
  "message": "Rating saved and product(s) updated",
  "products": [
    {
      "product_id": 1,
      "rating": 4.3,
      "reviews": 12
    }
  ]
}
```


**Response – 201 Created**
```json
{
  "success": true,
  "comment": {
    "id": "comment-uuid",
    "product_id": 123,
    "user_id": "user-uuid",
    "content": "Sản phẩm dùng rất tốt!",
    "parent_id": null,
    "verified": false,
    "created_at": "2025-11-12 10:30:00"
  },
  "message": "Comment created"
}
```


## ADMIN APIs

### 1. Lấy tất cả đơn hàng (Admin)
```http
GET /api/admin/orders.php?page=1&limit=20&status=pending
Authorization: Bearer {admin_token}
```

### 2. Lấy chi tiết đơn hàng (Admin)
```http
GET /api/admin/orders.php?id=order-uuid
Authorization: Bearer {admin_token}
```

### 3. Cập nhật trạng thái đơn hàng (Admin)
```http
PUT /api/admin/orders.php
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "order_id": "order-uuid",
  "status": "shipping",
  "payment_status": "paid"
}
```

**Valid Statuses:**
- Order Status: `pending`, `confirmed`, `shipping`, `delivered`, `cancelled`
- Payment Status: `unpaid`, `paid`, `refunded`

---

## Workflow Thực tế

### Flow 1: Mua qua giỏ hàng (với selection)
```
1. Thêm sản phẩm vào giỏ: POST /api/cart/index.php
2. Xem giỏ hàng: GET /api/cart/index.php
3. Đánh dấu chọn items muốn mua: PATCH /api/cart/index.php
   Body: { action: "toggle_selection", item_id: "...", is_selected: true }
4. Checkout selected items: POST /api/orders/checkout.php
   Body: { full_name, email, phone, province, district, ward, address_detail }
```

### Flow 2: Mua ngay (Buy Now)
```
1. Mua trực tiếp: POST /api/orders/buy-now.php
   Body: { product_id, quantity, full_name, email, phone, province, district, ward, address_detail }
```

### Flow 3: Quản lý giỏ hàng
```
1. Chọn tất cả: PATCH /api/cart/index.php { action: "select_all" }
2. Bỏ chọn tất cả: PATCH /api/cart/index.php { action: "unselect_all" }
3. Cập nhật trạng thái giỏ: PATCH /api/cart/index.php { action: "update_status", status: "abandoned" }
```

---

## Key Features

### Cart Selection System
- Mỗi item có flag `is_selected` (0 hoặc 1)
- User có thể chọn/bỏ chọn từng item hoặc tất cả
- Checkout chỉ các items được chọn
- Tính tổng riêng cho items được chọn

### Cart Status Management
- `active` - Giỏ hàng đang sử dụng
- `checked_out` - Đã thanh toán (tự động khi giỏ trống sau checkout)
- `abandoned` - Có thể đánh dấu thủ công

### Address Fields (Required)
- `province` - Tỉnh/Thành phố
- `district` - Quận/Huyện
- `ward` - Phường/Xã ✨ **NEW**
- `address_detail` - Số nhà, tên đường

### Payment Method
 `payment_method`, mặc định COD

---

Common HTTP Status Codes:
- `400` - Bad Request (thiếu thông tin, dữ liệu không hợp lệ)
- `401` - Unauthorized (chưa đăng nhập)
- `403` - Forbidden (không có quyền)
- `404` - Not Found (không tìm thấy)
- `405` - Method Not Allowed
- `500` - Internal Server Error