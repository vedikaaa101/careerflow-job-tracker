CREATE TABLE job_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL,
    job_title VARCHAR(100) NOT NULL,
    location VARCHAR(100) DEFAULT NULL,
    application_date DATE NOT NULL,
    status ENUM('Applied', 'Interviewing', 'Offer', 'Rejected') NOT NULL DEFAULT 'Applied',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO job_applications
    (company_name, job_title, location, application_date, status, notes)
VALUES
    ('Example Technologies', 'Software Engineering Intern', 'Remote', '2026-09-01', 'Applied', 'Portfolio submitted.'),
    ('Northstar Labs', 'Database Intern', 'Mumbai', '2026-09-03', 'Interviewing', 'Technical round scheduled.');
