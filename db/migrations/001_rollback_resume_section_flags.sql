-- Rollback migration
-- Run with: psql -U arcd -d donayre_cv -f db/migrations/001_rollback_resume_section_flags.sql

BEGIN;

-- Drop new tables
DROP TABLE IF EXISTS user_technologies;
DROP TABLE IF EXISTS technology_options;

-- Remove columns from users table
ALTER TABLE users 
DROP COLUMN IF EXISTS has_education,
DROP COLUMN IF EXISTS has_experience,
DROP COLUMN IF EXISTS has_achievements;

COMMIT;

-- Verify rollback
SELECT column_name
FROM information_schema.columns
WHERE table_name = 'users';