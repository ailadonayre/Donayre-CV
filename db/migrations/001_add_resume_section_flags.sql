-- Migration: Add has_* flags and technology structure
-- Run this with: psql -U arcd -d donayre_cv -f db/migrations/001_add_resume_section_flags.sql

BEGIN;

-- Add section flags to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS has_education BOOLEAN NOT NULL DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS has_experience BOOLEAN NOT NULL DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS has_achievements BOOLEAN NOT NULL DEFAULT FALSE;

-- Set existing users' flags based on current data
UPDATE users u
SET has_education = EXISTS (SELECT 1 FROM education WHERE user_id = u.id);

UPDATE users u
SET has_experience = EXISTS (SELECT 1 FROM experience WHERE user_id = u.id);

UPDATE users u
SET has_achievements = EXISTS (SELECT 1 FROM achievements WHERE user_id = u.id);

-- Create technology_options table (preset options per category)
CREATE TABLE IF NOT EXISTS technology_options (
    id SERIAL PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_preset BOOLEAN DEFAULT TRUE,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(category, name)
);

-- Create user_technologies table (user's selected technologies)
CREATE TABLE IF NOT EXISTS user_technologies (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    category VARCHAR(100) NOT NULL,
    technology_name VARCHAR(100) NOT NULL,
    is_custom BOOLEAN DEFAULT FALSE,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_user_technologies_user_id ON user_technologies(user_id);
CREATE INDEX IF NOT EXISTS idx_user_technologies_category ON user_technologies(user_id, category);

COMMIT;

-- Verify migration
SELECT 
    column_name, 
    data_type, 
    is_nullable, 
    column_default
FROM information_schema.columns
WHERE table_name = 'users' 
AND column_name IN ('has_education', 'has_experience', 'has_achievements');