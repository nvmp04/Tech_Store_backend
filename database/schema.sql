CREATE DATABASE IF NOT EXISTS tech_store
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE tech_store;

CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_verification_token (verification_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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