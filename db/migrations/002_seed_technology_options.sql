-- Seed preset technology options
-- Run with: psql -U arcd -d donayre_cv -f db/migrations/002_seed_technology_options.sql

BEGIN;

-- Clear existing preset options
DELETE FROM technology_options WHERE is_preset = TRUE;

-- Frontend technologies
INSERT INTO technology_options (category, name, display_order) VALUES
('Frontend', 'HTML', 1),
('Frontend', 'CSS', 2),
('Frontend', 'JavaScript', 3),
('Frontend', 'TypeScript', 4),
('Frontend', 'React', 5),
('Frontend', 'Vue.js', 6),
('Frontend', 'Angular', 7),
('Frontend', 'Svelte', 8),
('Frontend', 'Bootstrap', 9),
('Frontend', 'Tailwind CSS', 10),
('Frontend', 'jQuery', 11),
('Frontend', 'Next.js', 12),
('Frontend', 'Nuxt.js', 13),
('Frontend', 'Flutter Web', 14),
('Frontend', 'WebAssembly', 15),
('Frontend', 'Other', 999);

-- Backend technologies
INSERT INTO technology_options (category, name, display_order) VALUES
('Backend', 'PHP', 1),
('Backend', 'Laravel', 2),
('Backend', 'Symfony', 3),
('Backend', 'Node.js', 4),
('Backend', 'Express', 5),
('Backend', 'Python', 6),
('Backend', 'Django', 7),
('Backend', 'Flask', 8),
('Backend', 'Ruby', 9),
('Backend', 'Ruby on Rails', 10),
('Backend', 'Java', 11),
('Backend', 'Spring', 12),
('Backend', 'C#', 13),
('Backend', '.NET', 14),
('Backend', 'Go', 15),
('Backend', 'Rust', 16),
('Backend', 'Other', 999);

-- Databases
INSERT INTO technology_options (category, name, display_order) VALUES
('Databases', 'MySQL', 1),
('Databases', 'PostgreSQL', 2),
('Databases', 'MariaDB', 3),
('Databases', 'SQLite', 4),
('Databases', 'MongoDB', 5),
('Databases', 'Redis', 6),
('Databases', 'DynamoDB', 7),
('Databases', 'Firebase Realtime DB', 8),
('Databases', 'Firestore', 9),
('Databases', 'Other', 999);

-- DevOps / Tools
INSERT INTO technology_options (category, name, display_order) VALUES
('DevOps', 'Git', 1),
('DevOps', 'GitHub', 2),
('DevOps', 'GitLab', 3),
('DevOps', 'Bitbucket', 4),
('DevOps', 'Docker', 5),
('DevOps', 'Kubernetes', 6),
('DevOps', 'Jenkins', 7),
('DevOps', 'CircleCI', 8),
('DevOps', 'Travis CI', 9),
('DevOps', 'Ansible', 10),
('DevOps', 'Terraform', 11),
('DevOps', 'Vagrant', 12),
('DevOps', 'Other', 999);

-- Multimedia / Design
INSERT INTO technology_options (category, name, display_order) VALUES
('Multimedia', 'Adobe Photoshop', 1),
('Multimedia', 'Adobe Illustrator', 2),
('Multimedia', 'Figma', 3),
('Multimedia', 'Sketch', 4),
('Multimedia', 'Adobe XD', 5),
('Multimedia', 'Blender', 6),
('Multimedia', 'After Effects', 7),
('Multimedia', 'Premiere Pro', 8),
('Multimedia', 'Canva', 9),
('Multimedia', 'Other', 999);

-- Mobile
INSERT INTO technology_options (category, name, display_order) VALUES
('Mobile', 'Android (Java/Kotlin)', 1),
('Mobile', 'iOS (Swift/Obj-C)', 2),
('Mobile', 'React Native', 3),
('Mobile', 'Xamarin', 4),
('Mobile', 'Flutter', 5),
('Mobile', 'Ionic', 6),
('Mobile', 'Other', 999);

-- Testing / QA
INSERT INTO technology_options (category, name, display_order) VALUES
('Testing', 'PHPUnit', 1),
('Testing', 'Jest', 2),
('Testing', 'Mocha', 3),
('Testing', 'Cypress', 4),
('Testing', 'Selenium', 5),
('Testing', 'Playwright', 6),
('Testing', 'JUnit', 7),
('Testing', 'RSpec', 8),
('Testing', 'Other', 999);

COMMIT;

-- Verify seeded data
SELECT category, COUNT(*) as count
FROM technology_options
GROUP BY category
ORDER BY category;