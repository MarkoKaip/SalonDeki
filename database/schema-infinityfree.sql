CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS schedule_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    week_type ENUM('week_current', 'week_next') NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL COMMENT '1=Mon, 7=Sun',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    slot_interval INT UNSIGNED NOT NULL DEFAULT 30
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id INT UNSIGNED NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    client_name VARCHAR(150) NOT NULL,
    client_phone VARCHAR(30) NOT NULL,
    note TEXT NULL,
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_slot (booking_date, booking_time),
    CONSTRAINT fk_bookings_service FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL
) ENGINE=InnoDB;

INSERT INTO services (name, duration_minutes, is_active, sort_order) VALUES
    ('Fade', 30, 1, 1),
    ('Kratka kosa', 30, 1, 2),
    ('Duga kosa', 30, 1, 3);

INSERT INTO schedule_rules (week_type, day_of_week, start_time, end_time, slot_interval) VALUES
    ('week_current', 2, '12:00:00', '19:30:00', 30),
    ('week_current', 3, '12:00:00', '19:30:00', 30),
    ('week_current', 4, '12:00:00', '19:30:00', 30),
    ('week_current', 5, '12:00:00', '19:30:00', 30),
    ('week_current', 6, '08:00:00', '16:00:00', 30),
    ('week_current', 7, '08:00:00', '16:00:00', 30),
    ('week_next', 2, '08:00:00', '16:00:00', 30),
    ('week_next', 3, '08:00:00', '16:00:00', 30),
    ('week_next', 4, '08:00:00', '16:00:00', 30),
    ('week_next', 5, '08:00:00', '16:00:00', 30),
    ('week_next', 6, '08:00:00', '16:00:00', 30),
    ('week_next', 7, '08:00:00', '16:00:00', 30);

INSERT INTO settings (setting_key, setting_value) VALUES
    ('salon_name', 'Salon Deki'),
    ('salon_phone', '+381 60 123 4567'),
    ('salon_address', 'Ulica Primer 12, Beograd'),
    ('salon_description', 'Premium frizerski salon sa pažnjom prema svakom detalju.'),
    ('admin_pin_hash', '$2y$10$5oABMm7T1duKEp9cZaqXV.DIdhlh9yXd8aNml8kSNeijJ6Gx2fMN.');
