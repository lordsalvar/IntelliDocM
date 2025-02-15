-- Drop existing table if it exists
DROP TABLE IF EXISTS notifications;

-- Create notifications table with correct column names
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    proposal_id INT,  -- Changed from activity_id to proposal_id
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (proposal_id) REFERENCES activity_proposals(proposal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
