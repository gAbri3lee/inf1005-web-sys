CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    category VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    room_id INT,
    check_in DATE,
    check_out DATE,
    total_price DECIMAL(10, 2),
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);

CREATE TABLE IF NOT EXISTS amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50)
);

-- Insert some sample data
INSERT INTO rooms (name, description, price, image_url, category) VALUES
('Ocean View Suite', 'Luxurious suite with a breathtaking view of the Indian Ocean.', 450.00, 'ocean_view.jpg', 'Suite'),
('Garden Villa', 'Private villa surrounded by lush tropical gardens.', 350.00, 'garden_villa.jpg', 'Villa'),
('Presidential Suite', 'The ultimate luxury experience with private pool and butler service.', 1200.00, 'presidential.jpg', 'Suite');

INSERT INTO amenities (name, description, icon) VALUES
('Infinity Pool', 'A stunning pool that blends into the horizon.', 'pool'),
('AWAY Spa', 'Rejuvenate your senses with our signature treatments.', 'spa'),
('FIT Gym', 'State-of-the-art fitness center open 24/7.', 'fitness_center');
