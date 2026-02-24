-- Enhanced SmartQuiz Database Schema with Comprehensive Categories
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
    avatar VARCHAR(255) DEFAULT NULL,
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

-- Quiz categories table with icons and colors
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fas fa-question-circle',
    color VARCHAR(20) DEFAULT '#007bff',
    is_featured BOOLEAN DEFAULT FALSE,
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
    is_featured BOOLEAN DEFAULT FALSE,
    thumbnail VARCHAR(255) DEFAULT NULL,
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

-- Insert comprehensive categories with icons and colors
INSERT INTO categories (category_name, description, icon, color, is_featured) VALUES 
('General Knowledge', 'Test your knowledge across various topics and current affairs', 'fas fa-globe', '#28a745', TRUE),
('Science & Technology', 'Explore physics, chemistry, biology, and modern technology', 'fas fa-flask', '#17a2b8', TRUE),
('Mathematics', 'Challenge yourself with numbers, algebra, geometry, and calculus', 'fas fa-calculator', '#6f42c1', TRUE),
('History & Geography', 'Journey through time and explore world geography', 'fas fa-map', '#fd7e14', TRUE),
('Literature & Language', 'Dive into books, poetry, grammar, and linguistics', 'fas fa-book', '#e83e8c', FALSE),
('Sports & Games', 'Test your knowledge of sports, athletes, and gaming', 'fas fa-trophy', '#ffc107', FALSE),
('Entertainment & Movies', 'Hollywood, Bollywood, music, and pop culture', 'fas fa-film', '#dc3545', TRUE),
('Business & Economics', 'Finance, marketing, entrepreneurship, and economics', 'fas fa-chart-line', '#20c997', FALSE),
('Computer Science', 'Programming, algorithms, data structures, and IT', 'fas fa-code', '#6610f2', TRUE),
('Medicine & Health', 'Anatomy, diseases, treatments, and health awareness', 'fas fa-heartbeat', '#e74c3c', FALSE),
('Art & Culture', 'Paintings, sculptures, music, and cultural heritage', 'fas fa-palette', '#f39c12', FALSE),
('Current Affairs', 'Latest news, politics, and world events', 'fas fa-newspaper', '#34495e', TRUE),
('Food & Cooking', 'Cuisine, recipes, nutrition, and culinary arts', 'fas fa-utensils', '#e67e22', FALSE),
('Travel & Tourism', 'Countries, landmarks, cultures, and travel facts', 'fas fa-plane', '#3498db', FALSE),
('Automotive', 'Cars, motorcycles, racing, and automobile technology', 'fas fa-car', '#95a5a6', FALSE),
('Fashion & Lifestyle', 'Trends, designers, beauty, and lifestyle choices', 'fas fa-tshirt', '#e91e63', FALSE),
('Environment & Nature', 'Ecology, wildlife, climate change, and conservation', 'fas fa-leaf', '#4caf50', FALSE),
('Space & Astronomy', 'Planets, stars, galaxies, and space exploration', 'fas fa-rocket', '#9c27b0', FALSE),
('Philosophy & Religion', 'Philosophical thoughts, world religions, and ethics', 'fas fa-yin-yang', '#795548', FALSE),
('Music & Dance', 'Musical instruments, composers, dance forms, and genres', 'fas fa-music', '#ff5722', FALSE);

-- Insert sample quizzes for different categories
INSERT INTO quizzes (title, description, category_id, total_questions, time_limit, difficulty_level, is_featured, created_by) VALUES 
-- General Knowledge
('World General Knowledge', 'Test your knowledge about world facts, capitals, and current events', 1, 10, 15, 'Medium', TRUE, 1),
('Indian General Knowledge', 'Questions about Indian history, culture, and current affairs', 1, 12, 18, 'Medium', FALSE, 1),

-- Science & Technology
('Basic Physics Quiz', 'Fundamental concepts of physics including mechanics and thermodynamics', 2, 15, 20, 'Medium', TRUE, 1),
('Computer Technology', 'Latest trends in technology, AI, and computer science', 2, 10, 12, 'Easy', FALSE, 1),

-- Mathematics
('Algebra Fundamentals', 'Basic algebraic equations and problem solving', 3, 12, 25, 'Medium', FALSE, 1),
('Geometry Basics', 'Shapes, angles, and geometric calculations', 3, 10, 20, 'Easy', FALSE, 1),

-- History & Geography
('World History', 'Major historical events and civilizations', 4, 15, 25, 'Hard', TRUE, 1),
('World Geography', 'Countries, capitals, rivers, and geographical features', 4, 20, 30, 'Medium', FALSE, 1),

-- Entertainment & Movies
('Bollywood Quiz', 'Test your knowledge about Hindi cinema and actors', 7, 15, 20, 'Easy', TRUE, 1),
('Hollywood Classics', 'Classic movies, directors, and legendary actors', 7, 12, 18, 'Medium', FALSE, 1),

-- Computer Science
('Programming Basics', 'Fundamental programming concepts and languages', 9, 15, 30, 'Hard', TRUE, 1),
('Web Development', 'HTML, CSS, JavaScript, and web technologies', 9, 12, 25, 'Medium', FALSE, 1),

-- Current Affairs
('2024 Current Affairs', 'Latest news and events from around the world', 12, 20, 25, 'Medium', TRUE, 1),

-- Sports
('Cricket World Cup', 'Test your cricket knowledge and statistics', 6, 15, 20, 'Easy', FALSE, 1),
('Olympic Games', 'Olympic history, records, and sports trivia', 6, 12, 18, 'Medium', FALSE, 1);

-- Sample questions for World General Knowledge Quiz
INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, question_order) VALUES 
(1, 'What is the capital of Australia?', 'Sydney', 'Melbourne', 'Canberra', 'Brisbane', 'C', 'Canberra is the capital city of Australia, not Sydney which is the largest city.', 1),
(1, 'Which planet is known as the Red Planet?', 'Venus', 'Mars', 'Jupiter', 'Saturn', 'B', 'Mars is called the Red Planet due to its reddish appearance caused by iron oxide.', 2),
(1, 'Who painted the Mona Lisa?', 'Vincent van Gogh', 'Pablo Picasso', 'Leonardo da Vinci', 'Michelangelo', 'C', 'The Mona Lisa was painted by Leonardo da Vinci between 1503-1519.', 3),
(1, 'What is the largest ocean on Earth?', 'Atlantic Ocean', 'Indian Ocean', 'Arctic Ocean', 'Pacific Ocean', 'D', 'The Pacific Ocean covers about 46% of the water surface and 32% of the total surface area.', 4),
(1, 'Which country has the most time zones?', 'Russia', 'United States', 'China', 'Canada', 'A', 'Russia spans 11 time zones, making it the country with the most time zones.', 5),
(1, 'What is the smallest country in the world?', 'Monaco', 'Vatican City', 'San Marino', 'Liechtenstein', 'B', 'Vatican City is the smallest country with an area of just 0.17 square miles.', 6),
(1, 'Who wrote "Romeo and Juliet"?', 'Charles Dickens', 'William Shakespeare', 'Jane Austen', 'Mark Twain', 'B', 'Romeo and Juliet is one of William Shakespeare\'s most famous tragedies.', 7),
(1, 'What is the longest river in the world?', 'Amazon River', 'Nile River', 'Yangtze River', 'Mississippi River', 'B', 'The Nile River in Africa is approximately 6,650 kilometers long.', 8),
(1, 'Which element has the chemical symbol "Au"?', 'Silver', 'Gold', 'Aluminum', 'Argon', 'B', 'Au comes from the Latin word "aurum" meaning gold.', 9),
(1, 'In which year did World War II end?', '1944', '1945', '1946', '1947', 'B', 'World War II ended in 1945 with the surrender of Japan in September.', 10);

-- Sample questions for Basic Physics Quiz
INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, question_order) VALUES 
(3, 'What is the speed of light in vacuum?', '299,792,458 m/s', '300,000,000 m/s', '299,000,000 m/s', '298,000,000 m/s', 'A', 'The speed of light in vacuum is exactly 299,792,458 meters per second.', 1),
(3, 'What is Newton\'s first law of motion?', 'F = ma', 'Every action has an equal and opposite reaction', 'An object at rest stays at rest unless acted upon by a force', 'Energy cannot be created or destroyed', 'C', 'Newton\'s first law states that an object will remain at rest or in uniform motion unless acted upon by an external force.', 2),
(3, 'What is the unit of electric current?', 'Volt', 'Ampere', 'Ohm', 'Watt', 'B', 'The ampere (A) is the base unit of electric current in the International System of Units.', 3),
(3, 'Which law states that energy cannot be created or destroyed?', 'Newton\'s Law', 'Ohm\'s Law', 'Conservation of Energy', 'Boyle\'s Law', 'C', 'The Law of Conservation of Energy states that energy can only be transformed from one form to another.', 4),
(3, 'What is the acceleration due to gravity on Earth?', '9.8 m/s²', '10 m/s²', '9.6 m/s²', '8.9 m/s²', 'A', 'The standard acceleration due to gravity on Earth is approximately 9.8 m/s².', 5);

-- Sample questions for Bollywood Quiz
INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, question_order) VALUES 
(9, 'Who is known as the "King of Bollywood"?', 'Amitabh Bachchan', 'Shah Rukh Khan', 'Salman Khan', 'Aamir Khan', 'B', 'Shah Rukh Khan is widely known as the "King of Bollywood" or "King Khan".', 1),
(9, 'Which movie won the first National Film Award for Best Feature Film?', 'Shyamchi Aai', 'Do Bigha Zamin', 'Awaara', 'Pyaasa', 'A', 'Shyamchi Aai (1953) won the first National Film Award for Best Feature Film.', 2),
(9, 'Who directed the movie "Sholay"?', 'Yash Chopra', 'Ramesh Sippy', 'Raj Kapoor', 'Guru Dutt', 'B', 'Sholay (1975) was directed by Ramesh Sippy and is considered one of the greatest Indian films.', 3);

-- Sample questions for Programming Basics
INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, question_order) VALUES 
(11, 'What does HTML stand for?', 'Hyper Text Markup Language', 'High Tech Modern Language', 'Home Tool Markup Language', 'Hyperlink and Text Markup Language', 'A', 'HTML stands for Hyper Text Markup Language, used for creating web pages.', 1),
(11, 'Which programming language is known as the "mother of all languages"?', 'Java', 'C', 'Python', 'COBOL', 'B', 'C is often called the mother of all programming languages as many languages are derived from it.', 2),
(11, 'What is the time complexity of binary search?', 'O(n)', 'O(log n)', 'O(n²)', 'O(1)', 'B', 'Binary search has a time complexity of O(log n) as it divides the search space in half each time.', 3);

-- Update quiz total_questions count
UPDATE quizzes SET total_questions = (SELECT COUNT(*) FROM questions WHERE questions.quiz_id = quizzes.quiz_id);

-- Create indexes for better performance
CREATE INDEX idx_results_user_id ON results(user_id);
CREATE INDEX idx_results_quiz_id ON results(quiz_id);
CREATE INDEX idx_questions_quiz_id ON questions(quiz_id);
CREATE INDEX idx_attempts_result_id ON attempts(result_id);
