CREATE TABLE IF NOT EXISTS users (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  first_name VARCHAR(64) NOT NULL,
  last_name VARCHAR(64) NOT NULL,
  company VARCHAR(128),
  university VARCHAR(128),
  degree_field VARCHAR(128),
  degree_file_path VARCHAR(255),
  cnic VARCHAR(32) UNIQUE,
  cnic_issue_date DATE,
  passport_photo_path VARCHAR(255),
  profile_picture_path VARCHAR(255),
  address_line VARCHAR(255),
  country VARCHAR(64),
  city VARCHAR(64),
  zipcode VARCHAR(16),
  sex VARCHAR(16),
  gender VARCHAR(32),
  nationality VARCHAR(64),
  bio_short TEXT,
  phone VARCHAR(32),
  github_url VARCHAR(255),
  linkedin_url VARCHAR(255),
  website_url VARCHAR(255),
  reference_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS work_experience (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL,
  title VARCHAR(128) NOT NULL,
  company VARCHAR(128),
  start_date DATE,
  end_date DATE,
  description TEXT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS interests (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL,
  label VARCHAR(128) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS profiles (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL UNIQUE,
  summary TEXT,
  performance_report TEXT,
  certificate_path VARCHAR(255),
  last_summary_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS mcqs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(64) NOT NULL,
  difficulty ENUM('easy','medium','hard') DEFAULT 'medium',
  question TEXT NOT NULL,
  option_a VARCHAR(255) NOT NULL,
  option_b VARCHAR(255) NOT NULL,
  option_c VARCHAR(255) NOT NULL,
  option_d VARCHAR(255) NOT NULL,
  correct CHAR(1) NOT NULL
);

CREATE TABLE IF NOT EXISTS attempts (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL,
  started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ended_at TIMESTAMP NULL,
  status ENUM('active','submitted','terminated','blocked') DEFAULT 'active',
  attempt_number INT NOT NULL,
  added_mcqs INT NOT NULL DEFAULT 0,
  termination_at TIMESTAMP NULL,
  unblock_after TIMESTAMP NULL,
  grade VARCHAR(16),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS attempt_answers (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  attempt_id BIGINT NOT NULL,
  mcq_id BIGINT NOT NULL,
  selected CHAR(1) NULL,
  locked TINYINT(1) DEFAULT 0,
  FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (mcq_id) REFERENCES mcqs(id) ON DELETE CASCADE
);

INSERT INTO mcqs (category, difficulty, question, option_a, option_b, option_c, option_d, correct) VALUES
('iq','medium','Which number completes the series: 2, 4, 8, 16, ?', '24','32','20','18','B'),
('eq','easy','A colleague is upset. What should you do first?', 'Ignore it','Offer support','Tell them to work','Report to HR','B'),
('personality','easy','You prefer to work...', 'Alone','In teams','With supervision','Under pressure','B'),
('general','easy','Capital of France?', 'Berlin','Madrid','Paris','Rome','C');

CREATE INDEX IF NOT EXISTS idx_attempts_user ON attempts(user_id);
CREATE INDEX IF NOT EXISTS idx_attempt_answers_attempt ON attempt_answers(attempt_id);
CREATE INDEX IF NOT EXISTS idx_interests_user ON interests(user_id);
CREATE INDEX IF NOT EXISTS idx_work_user ON work_experience(user_id);
CREATE INDEX IF NOT EXISTS idx_mcqs_cat_diff ON mcqs(category, difficulty);
