CREATE DATABASE IF NOT EXISTS tech_store
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE tech_store;

CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('guest', 'user', 'admin') DEFAULT 'user',
    -- email_verified TINYINT(1) DEFAULT 0,
    -- verification_token VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_verification_token (verification_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id, email, password_hash, full_name, role) 
VALUES (
    UUID(),
    'admin@techstore.com',
    '$2y$12$LQv3c1yycEn7sZVxfQDkjO8JhCkYiEZq.Uw8pQC5fN5o3W5X5m5Jm', -- Admin@123
    'System Admin',
    'admin'
)
ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    role = 'admin';

CREATE TABLE IF NOT EXISTS products (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(255)        NOT NULL,
  price       BIGINT UNSIGNED     NOT NULL,
  old_price   BIGINT UNSIGNED     DEFAULT 0,
  badge       VARCHAR(32)         DEFAULT NULL,     
  rating      DECIMAL(2,1)        DEFAULT 0.0,     
  reviews     INT UNSIGNED        DEFAULT 0,
  in_stock    TINYINT(1)          NOT NULL DEFAULT 1,
  images      JSON                DEFAULT NULL,     
  
  cpu         VARCHAR(128)        DEFAULT NULL,
  ram         VARCHAR(64)         DEFAULT NULL,
  storage     VARCHAR(128)        DEFAULT NULL,
  display     VARCHAR(128)        DEFAULT NULL,
  gpu         VARCHAR(128)        DEFAULT NULL,
  os          VARCHAR(64)         DEFAULT NULL,
  description TEXT                DEFAULT NULL,
  created_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_badge (badge),
  INDEX idx_rating (rating),
  INDEX idx_price (price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- giỏ hàng
CREATE TABLE IF NOT EXISTS carts (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id CHAR(36) NOT NULL,
    status ENUM('active', 'checked_out', 'abandoned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- chi tiết giỏ hàng
CREATE TABLE IF NOT EXISTS cart_items (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    cart_id CHAR(36) NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    price BIGINT UNSIGNED NOT NULL,
    is_selected TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_product (cart_id, product_id),
    INDEX idx_cart_id (cart_id),
    INDEX idx_product_id (product_id),
    INDEX idx_is_selected (is_selected)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- đơn hàng
CREATE TABLE IF NOT EXISTS orders (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id CHAR(36) NOT NULL,
    
    -- Thông tin người nhận
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    province VARCHAR(100) NOT NULL,
    district VARCHAR(100) NOT NULL,
    ward VARCHAR(100) NOT NULL,
    address_detail TEXT NOT NULL,
    
    -- Thông tin đơn hàng
    total_amount BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'confirmed', 'shipping', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
    
    -- Ghi chú
    note TEXT DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- chi tiết đơn hàng
CREATE TABLE IF NOT EXISTS order_items (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    order_id CHAR(36) NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    price BIGINT UNSIGNED NOT NULL,
    subtotal BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;