USE ict_school;

CREATE TABLE students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    class_name VARCHAR(2) NOT NULL,
    score TINYINT UNSIGNED NOT NULL,
    CONSTRAINT valid_score CHECK (score BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO students (name, class_name, score) VALUES
('陳大文', '4A', 78),
('李小美', '4A', 92),
('黃志明', '5B', 65),
('林嘉欣', '5B', 88),
('何俊傑', '6C', 54),
('張雅晴', '6C', 40);
