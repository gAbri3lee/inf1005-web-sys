CREATE DATABASE IF NOT EXISTS azure_horizon
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE azure_horizon;

CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rooms (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    price_per_night DECIMAL(10, 2) NOT NULL,
    occupancy INT NOT NULL,
    view_type VARCHAR(50) DEFAULT NULL,
    is_accessible TINYINT(1) DEFAULT 0,
    size_label VARCHAR(100) DEFAULT NULL,
    bed_summary VARCHAR(100) DEFAULT NULL,
    sort_order INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_images (
    id INT NOT NULL AUTO_INCREMENT,
    room_id INT DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 1,
    PRIMARY KEY (id),
    KEY room_id (room_id),
    CONSTRAINT room_images_ibfk_1
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_features (
    id INT NOT NULL AUTO_INCREMENT,
    room_id INT DEFAULT NULL,
    feature_text VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 1,
    PRIMARY KEY (id),
    KEY room_id (room_id),
    CONSTRAINT room_features_ibfk_1
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_benefits (
    id INT NOT NULL AUTO_INCREMENT,
    room_id INT DEFAULT NULL,
    benefit_text VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 1,
    PRIMARY KEY (id),
    KEY room_id (room_id),
    CONSTRAINT room_benefits_ibfk_1
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_bathroom_features (
    id INT NOT NULL AUTO_INCREMENT,
    room_id INT DEFAULT NULL,
    feature_text VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 1,
    PRIMARY KEY (id),
    KEY room_id (room_id),
    CONSTRAINT room_bathroom_features_ibfk_1
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_furnishings (
    id INT NOT NULL AUTO_INCREMENT,
    room_id INT DEFAULT NULL,
    item_text VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 1,
    PRIMARY KEY (id),
    KEY room_id (room_id),
    CONSTRAINT room_furnishings_ibfk_1
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    room_id INT DEFAULT NULL,
    check_in DATE DEFAULT NULL,
    check_out DATE DEFAULT NULL,
    total_price DECIMAL(10, 2) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Confirmed',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY room_id (room_id),
    CONSTRAINT bookings_ibfk_1
        FOREIGN KEY (room_id) REFERENCES rooms(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS amenities (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    user_name VARCHAR(150) NOT NULL,
    rating INT NOT NULL,
    title VARCHAR(150) DEFAULT NULL,
    body TEXT,
    image_path VARCHAR(255) DEFAULT NULL,
    is_published TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS review_categories (
    id INT NOT NULL AUTO_INCREMENT,
    review_id INT DEFAULT NULL,
    category_code VARCHAR(50) DEFAULT NULL,
    sort_order INT DEFAULT 1,
    PRIMARY KEY (id),
    KEY review_id (review_id),
    CONSTRAINT review_categories_ibfk_1
        FOREIGN KEY (review_id) REFERENCES reviews(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO reviews (user_name, rating, title, body, image_path, is_published, created_at)
SELECT 'Alicia T.', 5, 'Perfect weekend getaway', 'Smooth check-in, spotless room, and the view at sunrise was unreal. We loved how calm the lobby felt even when it was busy.', 'assets/images/HotelHomePage.webp', 1, '2026-03-25 08:00:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM reviews
    WHERE user_name = 'Alicia T.'
      AND title = 'Perfect weekend getaway'
);

INSERT INTO reviews (user_name, rating, title, body, image_path, is_published, created_at)
SELECT 'Marcus L.', 4, 'Great amenities, minor wait', 'Facilities were excellent and the spa was a highlight. Only downside was a short wait during peak dinner time, but staff handled it well.', 'assets/images/SpaRoom.webp', 1, '2026-03-24 19:30:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM reviews
    WHERE user_name = 'Marcus L.'
      AND title = 'Great amenities, minor wait'
);

INSERT INTO reviews (user_name, rating, title, body, image_path, is_published, created_at)
SELECT 'Nur S.', 5, 'Best views from the lounge', 'The skyline view from the lounge is stunning. Clean, quiet, and very comfortable. Will definitely come back for another staycation.', 'assets/images/AboutUs.webp', 1, '2026-03-23 14:15:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM reviews
    WHERE user_name = 'Nur S.'
      AND title = 'Best views from the lounge'
);

INSERT INTO reviews (user_name, rating, title, body, image_path, is_published, created_at)
SELECT 'Daniel R.', 4, 'Clean rooms and good breakfast', 'Breakfast had a solid range and everything tasted fresh. The room was well-kept and the bed was super comfortable.', 'assets/images/dining/Cafe.webp', 1, '2026-03-22 09:10:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM reviews
    WHERE user_name = 'Daniel R.'
      AND title = 'Clean rooms and good breakfast'
);

INSERT INTO reviews (user_name, rating, title, body, image_path, is_published, created_at)
SELECT 'Priya M.', 5, 'Spa day was amazing', 'Booked a spa session and it was honestly the best part of the trip. Quiet, relaxing, and the facilities were immaculate.', 'assets/images/SpaRoom.webp', 1, '2026-03-21 16:45:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM reviews
    WHERE user_name = 'Priya M.'
      AND title = 'Spa day was amazing'
);

INSERT INTO reviews (user_name, rating, title, body, image_path, is_published, created_at)
SELECT 'Olivia B.', 5, 'Loved the amenities', 'Gym was well-equipped and not crowded. Everything felt polished and thoughtfully designed. Would recommend for business trips too.', 'assets/images/Suite1.webp', 1, '2026-03-20 11:20:00'
WHERE NOT EXISTS (
    SELECT 1
    FROM reviews
    WHERE user_name = 'Olivia B.'
      AND title = 'Loved the amenities'
);

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'room', 1
FROM reviews r
WHERE r.user_name = 'Alicia T.'
  AND r.title = 'Perfect weekend getaway'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'room'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'views', 2
FROM reviews r
WHERE r.user_name = 'Alicia T.'
  AND r.title = 'Perfect weekend getaway'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'views'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'cleanliness', 3
FROM reviews r
WHERE r.user_name = 'Alicia T.'
  AND r.title = 'Perfect weekend getaway'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'cleanliness'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'service', 4
FROM reviews r
WHERE r.user_name = 'Alicia T.'
  AND r.title = 'Perfect weekend getaway'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'service'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'amenities', 1
FROM reviews r
WHERE r.user_name = 'Marcus L.'
  AND r.title = 'Great amenities, minor wait'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'amenities'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'service', 2
FROM reviews r
WHERE r.user_name = 'Marcus L.'
  AND r.title = 'Great amenities, minor wait'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'service'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'food', 3
FROM reviews r
WHERE r.user_name = 'Marcus L.'
  AND r.title = 'Great amenities, minor wait'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'food'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'views', 1
FROM reviews r
WHERE r.user_name = 'Nur S.'
  AND r.title = 'Best views from the lounge'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'views'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'room', 2
FROM reviews r
WHERE r.user_name = 'Nur S.'
  AND r.title = 'Best views from the lounge'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'room'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'cleanliness', 3
FROM reviews r
WHERE r.user_name = 'Nur S.'
  AND r.title = 'Best views from the lounge'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'cleanliness'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'food', 1
FROM reviews r
WHERE r.user_name = 'Daniel R.'
  AND r.title = 'Clean rooms and good breakfast'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'food'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'room', 2
FROM reviews r
WHERE r.user_name = 'Daniel R.'
  AND r.title = 'Clean rooms and good breakfast'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'room'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'cleanliness', 3
FROM reviews r
WHERE r.user_name = 'Daniel R.'
  AND r.title = 'Clean rooms and good breakfast'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'cleanliness'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'amenities', 1
FROM reviews r
WHERE r.user_name = 'Priya M.'
  AND r.title = 'Spa day was amazing'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'amenities'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'cleanliness', 2
FROM reviews r
WHERE r.user_name = 'Priya M.'
  AND r.title = 'Spa day was amazing'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'cleanliness'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'service', 3
FROM reviews r
WHERE r.user_name = 'Priya M.'
  AND r.title = 'Spa day was amazing'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'service'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'amenities', 1
FROM reviews r
WHERE r.user_name = 'Olivia B.'
  AND r.title = 'Loved the amenities'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'amenities'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'service', 2
FROM reviews r
WHERE r.user_name = 'Olivia B.'
  AND r.title = 'Loved the amenities'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'service'
  );

INSERT INTO review_categories (review_id, category_code, sort_order)
SELECT r.id, 'cleanliness', 3
FROM reviews r
WHERE r.user_name = 'Olivia B.'
  AND r.title = 'Loved the amenities'
  AND NOT EXISTS (
      SELECT 1
      FROM review_categories rc
      WHERE rc.review_id = r.id
        AND rc.category_code = 'cleanliness'
  );
