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
PATCH /api/cart/index.php
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
  "address_detail": "123 Đường Lê Lợi",
  "note": "Giao giờ hành chính"
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
  "address_detail": "123 Đường Lê Lợi",
  "note": "Gọi trước khi giao"
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
  "address_detail": "123 Đường Lê Lợi",
  "note": "Gọi trước khi giao"
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
# 📚 API DOCUMENTATION - REVIEWS & POSTS SYSTEM

## 🎯 TỔNG QUAN

Hệ thống bao gồm:
- **Product Reviews/Comments**: Đánh giá & bình luận sản phẩm
- **Posts (Blog/News)**: Quản lý bài viết/tin tức
- **Post Comments**: Bình luận bài viết (nested threading)
- **Post Categories**: Danh mục bài viết

---

## 🔐 AUTHENTICATION

Tất cả API cần auth sử dụng JWT token trong header:
```
Authorization: Bearer {token}
```

**User roles:**
- `guest`: Khách (hạn chế)
- `user`: User thông thường
- `admin`: Quản trị viên

---

# 📦 PRODUCT REVIEWS & COMMENTS

## 1. Lấy Reviews/Comments của Sản Phẩm

```http
GET /api/products/reviews.php?product_id={id}
```

**Query Params:**
- `product_id` (required): ID sản phẩm
- `verified` (optional): `1` = chỉ reviews, `0` = chỉ comments
- `page` (default: 1)
- `limit` (default: 20, max: 50)

**Response:**
```json
{
  "success": true,
  "reviews": [
    {
      "id": "uuid",
      "product_id": 123,
      "user_id": "uuid",
      "full_name": "Nguyễn Văn A",
      "rating": 5,
      "content": "Sản phẩm rất tốt",
      "verified": 1,
      "admin_response": "Cảm ơn bạn đã tin dùng",
      "admin_response_at": "2024-01-15 10:30:00",
      "created_at": "2024-01-15 09:00:00"
    }
  ],
  "stats": {
    "average_rating": 4.5,
    "review_count": 120,
    "total_comments": 45,
    "distribution": {
      "5": {"count": 80, "percentage": 66.7},
      "4": {"count": 25, "percentage": 20.8},
      "3": {"count": 10, "percentage": 8.3},
      "2": {"count": 3, "percentage": 2.5},
      "1": {"count": 2, "percentage": 1.7}
    }
  },
  "pagination": {...}
}
```

---

## 2. Tạo Review/Comment

```http
POST /api/products/reviews.php
Authorization: Bearer {token}
```

**Body:**
```json
{
  "product_id": 123,
  "content": "Sản phẩm tốt",
  "rating": 5  // Optional, bắt buộc nếu đã mua
}
```

**Logic:**
- Nếu user **đã mua** sản phẩm → tạo **review** (verified=1), rating bắt buộc
- Nếu user **chưa mua** → tạo **comment** (verified=0), rating optional

**Response:**
```json
{
  "success": true,
  "message": "Đánh giá của bạn đã được gửi",
  "review_id": "uuid",
  "verified": true
}
```

---

## 3. Admin: Quản Lý Reviews

```http
GET /api/admin/product-reviews.php
Authorization: Bearer {admin_token}
```

**Query Params:**
- `product_id` (optional): Filter theo sản phẩm
- `verified` (optional): `1` = reviews, `0` = comments
- `status` (optional): `approved`, `hidden`, `spam`
- `page`, `limit`

---

## 4. User: Sửa Review (trong 30 ngày)

```http
PUT /api/products/reviews.php?id={review_id}
Authorization: Bearer {token}
```

**Body:**
```json
{
  "content": "Nội dung đã sửa sau 1 tháng dùng...",
  "rating": 4
}
```

**Response:**
```json
{
  "success": true,
  "message": "Đã cập nhật đánh giá của bạn"
}
```

**Rules:**
- Chỉ edit được review của mình
- Chỉ edit được trong vòng 30 ngày
- Sau 30 ngày → Error 403

---

## 5. User: Xóa Review (trong 7 ngày)

```http
DELETE /api/products/reviews.php?id={review_id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Đã xóa đánh giá của bạn"
}
```

**Rules:**
- Chỉ xóa được review của mình
- Chỉ xóa được trong vòng 7 ngày
- Sau 7 ngày → Error 403

---

## 6. Admin: Cập Nhật Review

```http
PUT /api/admin/product-reviews.php?id={review_id}
Authorization: Bearer {admin_token}
```

**Body (chọn 1 hoặc nhiều):**
```json
{
  "status": "hidden",  // approved, hidden, spam
  "admin_response": "Cảm ơn bạn đã đánh giá"
}
```

---

## 7. Admin: Xóa Review

```http
DELETE /api/admin/product-reviews.php?id={review_id}
Authorization: Bearer {admin_token}
```

---

# 📰 POSTS (BÀI VIẾT)

## 1. Lấy Danh Sách Bài Viết (Public)

```http
GET /api/posts.php
```

**Query Params:**
- `category` (optional): Slug category (vd: `tech-news`)
- `search` (optional): Tìm kiếm trong title & excerpt
- `featured` (optional): `1` = chỉ bài nổi bật
- `page` (default: 1)
- `limit` (default: 10, max: 50)

**Response:**
```json
{
  "success": true,
  "posts": [
    {
      "id": "uuid",
      "title": "Laptop Gaming 2024",
      "slug": "laptop-gaming-2024",
      "excerpt": "Top 5 laptop...",
      "thumbnail": "https://...",
      "author_name": "Admin",
      "category_name": "Review sản phẩm",
      "category_slug": "product-reviews",
      "view_count": 1250,
      "published_at": "2024-01-15 10:00:00"
    }
  ],
  "pagination": {...},
  "filters": {...}
}
```

---

## 2. Lấy Chi Tiết Bài Viết

```http
GET /api/posts.php?slug={slug}
```

**Response:**
```json
{
  "success": true,
  "post": {
    "id": "uuid",
    "title": "...",
    "slug": "...",
    "content": "<p>Nội dung HTML...</p>",
    "excerpt": "...",
    "thumbnail": "...",
    "author_name": "Admin",
    "category_name": "Tin công nghệ",
    "view_count": 1251,
    "published_at": "..."
  },
  "comments": [...],  // Nested tree structure
  "comment_count": 15,
  "related_posts": [...]
}
```

---

## 3. Admin: Tạo Bài Viết

```http
POST /api/admin/posts.php
Authorization: Bearer {admin_token}
```

**Body:**
```json
{
  "title": "Tiêu đề bài viết",
  "content": "<p>Nội dung HTML</p>",
  "excerpt": "Mô tả ngắn",
  "category_id": 1,
  "thumbnail": "https://...",
  "status": "draft",  // draft hoặc published
  "is_featured": 0
}
```

**Response:**
```json
{
  "success": true,
  "message": "Đã tạo bài viết",
  "post": {...}
}
```

---

## 4. Admin: Cập Nhật Bài Viết

```http
PUT /api/admin/posts.php?id={post_id}
Authorization: Bearer {admin_token}
```

**Body (các field đều optional):**
```json
{
  "title": "Tiêu đề mới",
  "content": "...",
  "excerpt": "...",
  "category_id": 2,
  "thumbnail": "...",
  "status": "published",
  "is_featured": 1
}
```

---

## 5. Admin: Xóa Bài Viết (Soft Delete)

```http
DELETE /api/admin/posts.php?id={post_id}
Authorization: Bearer {admin_token}
```

**Khôi phục:**
```http
PUT /api/admin/posts.php?id={post_id}
Body: { "action": "restore" }
```

---

## 6. Admin: Lấy Tất Cả Bài Viết

```http
GET /api/admin/posts.php
Authorization: Bearer {admin_token}
```

**Query Params:**
- `status`: `draft`, `published`
- `category_id`: Filter theo category
- `search`: Tìm kiếm
- `include_deleted`: `1` = bao gồm bài đã xóa
- `page`, `limit`

---

# 💬 POST COMMENTS

## 1. Lấy Comments của Bài Viết

```http
GET /api/post-comments.php?post_id={id}
```

**Response:** Nested tree structure
```json
{
  "success": true,
  "comments": [
    {
      "id": "uuid",
      "post_id": "uuid",
      "parent_id": null,
      "author_name": "Nguyễn Văn A",
      "author_email": "user@example.com",
      "content": "Bài viết hay quá",
      "created_at": "...",
      "replies": [
        {
          "id": "uuid",
          "parent_id": "uuid_parent",
          "author_name": "Admin",
          "content": "Cảm ơn bạn!",
          "replies": []
        }
      ]
    }
  ],
  "total_count": 25
}
```

---

## 2. Tạo Comment (User hoặc Guest)

```http
POST /api/post-comments.php
Authorization: Bearer {token}  // Optional (guest không cần)
```

**Body (User đã login):**
```json
{
  "post_id": "uuid",
  "content": "Bình luận của tôi",
  "parent_id": "uuid"  // Optional, để reply
}
```

**Body (Guest):**
```json
{
  "post_id": "uuid",
  "content": "Bình luận của tôi",
  "author_name": "Khách",
  "author_email": "guest@example.com",
  "parent_id": null
}
```

**Response:**
```json
{
  "success": true,
  "message": "Bình luận của bạn đang chờ duyệt",  // Guest
  "comment_id": "uuid",
  "status": "pending"  // User: approved, Guest: pending
}
```

---

## 3. User: Sửa Comment (trong 24h)

```http
PUT /api/post-comments.php?id={comment_id}
Authorization: Bearer {token}
```

**Body:**
```json
{
  "content": "Nội dung đã sửa"
}
```

---

## 4. User: Xóa Comment (trong 24h)

```http
DELETE /api/post-comments.php?id={comment_id}
Authorization: Bearer {token}
```

---

## 5. Admin: Quản Lý Comments

```http
GET /api/admin/post-comments.php
Authorization: Bearer {admin_token}
```

**Query Params:**
- `status`: `pending`, `approved`, `spam`
- `post_id`: Filter theo bài viết
- `search`: Tìm kiếm
- `page`, `limit`

**Response bao gồm:**
```json
{
  "comments": [...],
  "pending_count": 5,  // Số comment chờ duyệt
  ...
}
```

---

## 6. Admin: Duyệt/Spam Comment

```http
PUT /api/admin/post-comments.php?id={comment_id}
Authorization: Bearer {admin_token}
```

**Body:**
```json
{
  "action": "approve"  // approve, spam, pending
}
```

Hoặc:
```json
{
  "status": "approved"  // approved, spam, pending
}
```

---

## 7. Admin: Xóa Comment

```http
DELETE /api/admin/post-comments.php?id={comment_id}
Authorization: Bearer {admin_token}
```

---

# 📂 POST CATEGORIES

## 1. Lấy Danh Sách Categories (Public)

```http
GET /api/post-categories.php
```

**Response:**
```json
{
  "success": true,
  "categories": [
    {
      "id": 1,
      "name": "Tin công nghệ",
      "slug": "tech-news",
      "description": "...",
      "post_count": 45,
      "display_order": 1
    }
  ]
}
```

---

## 2. Admin: Tạo Category

```http
POST /api/admin/post-categories.php
Authorization: Bearer {admin_token}
```

**Body:**
```json
{
  "name": "Khuyến mãi",
  "description": "Chương trình khuyến mãi",
  "display_order": 5
}
```

---

## 3. Admin: Cập Nhật Category

```http
PUT /api/admin/post-categories.php?id={category_id}
Authorization: Bearer {admin_token}
```

**Body:**
```json
{
  "name": "Tên mới",
  "description": "...",
  "display_order": 3
}
```

---

## 4. Admin: Xóa Category

```http
DELETE /api/admin/post-categories.php?id={category_id}
Authorization: Bearer {admin_token}
```

**Lưu ý:** Không thể xóa nếu category có bài viết

---

# 📤 UPLOAD ẢNH

```http
POST /api/admin/upload.php
Authorization: Bearer {admin_token}
Content-Type: multipart/form-data
```

**Form Data:**
- `file`: Image file (JPG, PNG, GIF, WEBP, max 5MB)

**Response:**
```json
{
  "success": true,
  "message": "Upload thành công",
  "url": "https://domain.com/uploads/posts/post_abc123.jpg",
  "filename": "post_abc123.jpg"
}
```

---

# 🎯 WORKFLOW EXAMPLES

## Workflow 1: User Đánh Giá Sản Phẩm

1. User mua sản phẩm → Order status: `delivered`
2. User gọi `POST /api/products/reviews.php` với `rating` + `content`
3. Hệ thống check `hasUserPurchased()` → verified=1
4. Review hiển thị ngay (status: approved)
5. Admin có thể reply qua admin_response

## Workflow 2: User Chưa Mua - Comment Sản Phẩm

1. User chưa mua, muốn hỏi
2. User gọi `POST /api/products/reviews.php` với `content` (không có rating)
3. Hệ thống tạo comment (verified=0)
4. Comment hiển thị ngay

## Workflow 3: Guest Comment Bài Viết

1. Guest gọi `POST /api/post-comments.php` với name + email
2. Comment status: `pending`
3. Admin vào `/api/admin/post-comments.php` duyệt
4. Admin gọi `PUT ...?id=xxx` với `action: approve`
5. Comment hiển thị public

## Workflow 4: User Comment Bài Viết

1. User đã login gọi `POST /api/post-comments.php`
2. Comment status: `approved` (hiện ngay)
3. User có thể reply lồng nhau (nested)
4. User có 24h để edit/xóa comment

---
Common HTTP Status Codes:
- `400` - Bad Request (thiếu thông tin, dữ liệu không hợp lệ)
- `401` - Unauthorized (chưa đăng nhập)
- `403` - Forbidden (không có quyền)
- `404` - Not Found (không tìm thấy)
- `405` - Method Not Allowed
- `500` - Internal Server Error