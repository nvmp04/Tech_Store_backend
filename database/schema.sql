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

-- ==========================================
-- PRODUCT REVIEWS & COMMENTS
-- ==========================================

CREATE TABLE IF NOT EXISTS product_reviews (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    product_id INT UNSIGNED NOT NULL,
    user_id CHAR(36) NOT NULL,
    order_id CHAR(36) DEFAULT NULL,
    
    -- Content
    rating TINYINT UNSIGNED DEFAULT NULL CHECK (rating BETWEEN 1 AND 5),
    content TEXT NOT NULL,
    
    -- Classification
    verified TINYINT(1) DEFAULT 0 COMMENT '1: review sau mua, 0: comment thường',
    status ENUM('approved', 'hidden', 'spam') DEFAULT 'approved',
    
    -- Admin response
    admin_response TEXT DEFAULT NULL,
    admin_response_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    
    -- Generated column để giả lập partial UNIQUE (1 review/user/product)
    verified_key TINYINT 
        AS (CASE 
                WHEN verified = 1 AND status = 'approved' THEN 1 
                ELSE NULL 
            END) STORED,
    UNIQUE KEY unique_verified_review (user_id, product_id, verified_key),
    
    INDEX idx_product_id (product_id),
    INDEX idx_user_id (user_id),
    INDEX idx_verified (verified),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- POST CATEGORIES
-- ==========================================

CREATE TABLE IF NOT EXISTS post_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT DEFAULT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT INTO post_categories (name, slug, description, display_order) VALUES
('Tin công nghệ', 'tech-news', 'Tin tức công nghệ mới nhất', 1),
('Review sản phẩm', 'product-reviews', 'Đánh giá chi tiết sản phẩm', 2),
('Hướng dẫn', 'guides', 'Hướng dẫn sử dụng và mẹo hay', 3),
('Khuyến mãi', 'promotions', 'Chương trình khuyến mãi hot', 4)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- ==========================================
-- POSTS (Bài viết)
-- ==========================================

CREATE TABLE IF NOT EXISTS posts (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    
    -- Content
    content LONGTEXT NOT NULL,
    excerpt TEXT DEFAULT NULL,
    thumbnail VARCHAR(500) DEFAULT NULL,
    
    -- Author & Category
    author_id CHAR(36) NOT NULL,
    category_id INT UNSIGNED DEFAULT NULL,
    
    -- Status
    status ENUM('draft', 'published') DEFAULT 'draft',
    is_featured TINYINT(1) DEFAULT 0,
    
    -- Metrics
    view_count INT UNSIGNED DEFAULT 0,
    
    -- Timestamps
    published_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES post_categories(id) ON DELETE SET NULL,
    
    INDEX idx_slug (slug),
    INDEX idx_author_id (author_id),
    INDEX idx_category_id (category_id),
    INDEX idx_status (status),
    INDEX idx_is_featured (is_featured),
    INDEX idx_published_at (published_at),
    INDEX idx_deleted_at (deleted_at),
    INDEX idx_view_count (view_count),
    
    FULLTEXT INDEX ft_search (title, excerpt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- POST COMMENTS (Nested threading)
-- ==========================================

CREATE TABLE IF NOT EXISTS post_comments (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    post_id CHAR(36) NOT NULL,
    parent_id CHAR(36) DEFAULT NULL COMMENT 'NULL: root comment, có giá trị: reply',
    
    -- Author info
    user_id CHAR(36) DEFAULT NULL COMMENT 'NULL nếu guest comment',
    author_name VARCHAR(100) NOT NULL,
    author_email VARCHAR(255) DEFAULT NULL,
    
    -- Content
    content TEXT NOT NULL,
    
    -- Status
    status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES post_comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_post_id (post_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;