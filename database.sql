
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
