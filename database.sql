
CREATE TABLE clubs (
    club_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    club_name VARCHAR(255) NOT NULL,
    acronym VARCHAR(11) NOT NULL,
    club_type VARCHAR(255) NOT NULL,
    moderator VARCHAR(255) NOT NULL
);

/* */

CREATE TABLE users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    contact VARCHAR(55) NOT NULL
);


CREATE TABLE club_memberships (
    membership_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    club_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    designation VARCHAR(255) NOT NULL,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
);


CREATE TABLE activity_proposals (
    proposal_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    club_name VARCHAR(255) DEFAULT NULL,
    acronym VARCHAR(50) DEFAULT NULL,
    club_type VARCHAR(50) DEFAULT NULL,
    designation VARCHAR(255) DEFAULT NULL,
    activity_title VARCHAR(255) DEFAULT NULL,
    activity_type VARCHAR(55) DEFAULT NULL,
    objectives TEXT DEFAULT NULL,
    program_category VARCHAR(255) DEFAULT NULL,
    venue VARCHAR(255) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    activity_date DATE DEFAULT NULL,
    start_time TIME DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    target_participants VARCHAR(255) DEFAULT NULL,
    expected_participants INT(11) DEFAULT NULL,
    applicant_name VARCHAR(255) DEFAULT NULL,
    applicant_signature BLOB DEFAULT NULL,
    applicant_designation VARCHAR(255) DEFAULT NULL,
    applicant_date_filed DATE DEFAULT NULL,
    applicant_contact VARCHAR(50) DEFAULT NULL,
    moderator_name VARCHAR(255) DEFAULT NULL,
    moderator_signature BLOB DEFAULT NULL,
    moderator_date_signed DATE DEFAULT NULL,
    moderator_contact VARCHAR(50) DEFAULT NULL,
    faculty_signature VARCHAR(255) DEFAULT NULL,
    faculty_contact VARCHAR(50) DEFAULT NULL,
    dean_name VARCHAR(255) DEFAULT NULL,
    dean_signature BLOB DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'Received',
    rejection_reason TEXT DEFAULT NULL,
    submitted_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
);


CREATE TABLE notifications (
    notification_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT(11),
    user_id INT(11),
    message TEXT,
    status ENUM('unread', 'read') DEFAULT 'unread',
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proposal_id) REFERENCES activity_proposals(proposal_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
);


CREATE TABLE events (
    event_id INT(11) NOT NULL AUTO_INCREMENT,
    event_date DATE NOT NULL,
    event_title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    event_description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    event_start_date DATE DEFAULT NULL,
    event_end_date DATE DEFAULT NULL,
    PRIMARY KEY (event_id)
);

CREATE TABLE facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    code VARCHAR(50) UNIQUE,
    description TEXT
);

CREATE TABLE facility_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facility_id INT,
    date DATE,
    status ENUM('blocked', 'unavailable') NOT NULL,
    FOREIGN KEY (facility_id) REFERENCES facilities(id)
);


INSERT INTO facilities (name, code, description) VALUES
('Ladouix Hall', 'ladouix', 'A versatile hall suitable for events and gatherings.'),
('Boulay Bldg.', 'boulay', 'A modern building for conferences and meetings.'),
('Gymnasium', 'gym', 'A large facility for sports and community events.'),
('Misereor Bldg.', 'misereor', 'A multipurpose building for educational events.'),
('Polycarp Bldg.', 'polycarp', 'A space for seminars and workshops.'),
('Coinindre Bldg.', 'coinindre', 'A well-equipped building for professional events.'),
('Piazza', 'piazza', 'An open area for outdoor activities and events.'),
('Xavier Hall', 'xavier', 'A hall designed for formal gatherings and ceremonies.'),
('Open Court w/ Lights', 'openCourt', 'An outdoor court for sports and recreational activities.'),
('IVET', 'ivet', 'A building dedicated to vocational education and training.'),
('Nursing Room/Hall', 'nursing', 'A hall tailored for nursing-related events and training.'),
('Coindre Bldg. (CR)', 'coindre', 'A dedicated facility for organizational meetings.'),
('Power Campus', 'powerCampus', 'A campus facility for large-scale events.'),
('Camp Raymond Bldg.', 'campRaymond', 'A serene building for retreats and workshops.'),
('Norbert Bldg.', 'norbert', 'A facility ideal for small group activities.'),
('H.E Hall', 'hehall', 'A hall specifically for home economics activities.'),
('Atrium', 'atrium', 'A grand indoor space for exhibitions and gatherings.');


INSERT INTO facility_availability (facility_id, date, status) VALUES
-- Ladouix Hall
(1, '2025-01-05', 'blocked'), (1, '2025-01-12', 'unavailable'), (1, '2025-01-19', 'blocked'),
-- Boulay Bldg.
(2, '2025-02-03', 'blocked'), (2, '2025-02-10', 'unavailable'), (2, '2025-02-17', 'blocked'),
-- Gymnasium
(3, '2025-03-07', 'blocked'), (3, '2025-03-14', 'unavailable'), (3, '2025-03-21', 'blocked'),
-- Misereor Bldg.
(4, '2025-04-02', 'blocked'), (4, '2025-04-09', 'unavailable'), (4, '2025-04-16', 'blocked'),
-- Polycarp Bldg.
(5, '2025-05-04', 'blocked'), (5, '2025-05-11', 'unavailable'), (5, '2025-05-18', 'blocked'),
-- Coinindre Bldg.
(6, '2025-06-06', 'blocked'), (6, '2025-06-13', 'unavailable'), (6, '2025-06-20', 'blocked'),
-- Piazza
(7, '2025-07-08', 'blocked'), (7, '2025-07-15', 'unavailable'), (7, '2025-07-22', 'blocked'),
-- Xavier Hall
(8, '2025-08-01', 'blocked'), (8, '2025-08-08', 'unavailable'), (8, '2025-08-15', 'blocked'),
-- Open Court w/ Lights
(9, '2025-09-03', 'blocked'), (9, '2025-09-10', 'unavailable'), (9, '2025-09-17', 'blocked'),
-- IVET
(10, '2025-10-05', 'blocked'), (10, '2025-10-12', 'unavailable'), (10, '2025-10-19', 'blocked'),
-- Nursing Room/Hall
(11, '2025-11-07', 'blocked'), (11, '2025-11-14', 'unavailable'), (11, '2025-11-21', 'blocked'),
-- Coindre Bldg. (CR)
(12, '2025-12-02', 'blocked'), (12, '2025-12-09', 'unavailable'), (12, '2025-12-16', 'blocked'),
-- Power Campus
(13, '2025-01-03', 'blocked'), (13, '2025-01-10', 'unavailable'), (13, '2025-01-17', 'blocked'),
-- Camp Raymond Bldg.
(14, '2025-02-05', 'blocked'), (14, '2025-02-12', 'unavailable'), (14, '2025-02-19', 'blocked'),
-- Norbert Bldg.
(15, '2025-03-03', 'blocked'), (15, '2025-03-10', 'unavailable'), (15, '2025-03-17', 'blocked'),
-- H.E Hall
(16, '2025-04-04', 'blocked'), (16, '2025-04-11', 'unavailable'), (16, '2025-04-18', 'blocked'),
-- Atrium
(17, '2025-05-05', 'blocked'), (17, '2025-05-12', 'unavailable'), (17, '2025-05-19', 'blocked');
