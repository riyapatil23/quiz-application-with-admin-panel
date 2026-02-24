-- SmartQuiz Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS smartquiz;
USE smartquiz;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admin table
CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Quiz categories table
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Quizzes table
CREATE TABLE quizzes (
    quiz_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    total_questions INT DEFAULT 0,
    time_limit INT DEFAULT 30, -- in minutes
    difficulty_level ENUM('Easy', 'Medium', 'Hard') DEFAULT 'Medium',
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES admin(admin_id) ON DELETE SET NULL
);

-- Questions table
CREATE TABLE questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_option ENUM('A', 'B', 'C', 'D') NOT NULL,
    explanation TEXT,
    question_order INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE
);

-- Results table
CREATE TABLE results (
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score INT NOT NULL,
    total_questions INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    time_taken INT, -- in seconds
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE
);

-- User attempts table (stores individual answers)
CREATE TABLE attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    result_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option ENUM('A', 'B', 'C', 'D'),
    is_correct BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (result_id) REFERENCES results(result_id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO admin (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@smartquiz.com');

-- Insert default categories
INSERT INTO categories (category_name, description) VALUES 
('General Knowledge', 'Questions covering various topics of general interest'),
('Science', 'Questions related to physics, chemistry, biology, and other sciences'),
('Technology', 'Questions about computers, programming, and modern technology'),
('History', 'Questions about historical events and figures'),
('Sports', 'Questions about various sports and athletes'),
('Entertainment', 'Questions about movies, music, and pop culture');

-- Insert sample quiz
INSERT INTO quizzes (title, description, category_id, total_questions, time_limit, difficulty_level, created_by) VALUES 
('Basic General Knowledge', 'Test your general knowledge with these basic questions', 1, 5, 10, 'Easy', 1);

-- Insert sample questions
INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, question_order) VALUES 
(1, 'What is the capital of France?', 'London', 'Berlin', 'Paris', 'Madrid', 'C', 'Paris is the capital and most populous city of France.', 1),
(1, 'Which planet is known as the Red Planet?', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'B', 'Mars is called the Red Planet due to its reddish appearance.', 2),
(1, 'What is 2 + 2?', '3', '4', '5', '6', 'B', 'Basic arithmetic: 2 + 2 = 4', 3),
(1, 'Who wrote "Romeo and Juliet"?', 'Charles Dickens', 'William Shakespeare', 'Jane Austen', 'Mark Twain', 'B', 'Romeo and Juliet is a famous play by William Shakespeare.', 4),
(1, 'What is the largest ocean on Earth?', 'Atlantic Ocean', 'Indian Ocean', 'Arctic Ocean', 'Pacific Ocean', 'D', 'The Pacific Ocean is the largest ocean covering about 46% of the water surface.', 5);

-- Update quiz total_questions
UPDATE quizzes SET total_questions = 5 WHERE quiz_id = 1;
