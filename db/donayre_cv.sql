-- Grant all privileges on the database
GRANT ALL PRIVILEGES ON DATABASE donayre_cv TO arcd;

-- Grant usage on schema
GRANT USAGE ON SCHEMA public TO arcd;

-- Grant all privileges on all tables
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO arcd;

-- Grant all privileges on all sequences (for SERIAL columns)
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO arcd;

-- Set default privileges for future tables
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL PRIVILEGES ON TABLES TO arcd;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL PRIVILEGES ON SEQUENCES TO arcd;

-- Verify grants (optional - to check permissions)
SELECT grantee, privilege_type 
FROM information_schema.role_table_grants 
WHERE table_name='users' AND grantee='arcd';

-- Users table (main profile)
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    fullname VARCHAR(100),
    title VARCHAR(100),
    contact VARCHAR(50),
    address VARCHAR(255),
    age INTEGER,
    profile_summary TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP
);

-- Social Links
CREATE TABLE IF NOT EXISTS social_links (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    platform VARCHAR(50) NOT NULL,
    url VARCHAR(255) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Education
CREATE TABLE IF NOT EXISTS education (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    degree VARCHAR(200) NOT NULL,
    institution VARCHAR(200),
    start_date VARCHAR(50),
    end_date VARCHAR(50),
    description TEXT,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Experience
CREATE TABLE IF NOT EXISTS experience (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    job_title VARCHAR(200) NOT NULL,
    company VARCHAR(200),
    start_date VARCHAR(50),
    end_date VARCHAR(50),
    description TEXT,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- New table: keywords assigned to a particular experience
CREATE TABLE IF NOT EXISTS experience_keywords (
    id SERIAL PRIMARY KEY,
    experience_id INTEGER NOT NULL REFERENCES experience(id) ON DELETE CASCADE,
    keyword VARCHAR(150) NOT NULL,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- New table: card-level experience traits (per user)
CREATE TABLE IF NOT EXISTS experience_traits_global (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    trait_icon VARCHAR(100) NOT NULL,   -- e.g. 'fa-code' or 'fa-solid fa-code'
    trait_label VARCHAR(150) NOT NULL,  -- e.g. 'Problem Solving'
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for speed
CREATE INDEX IF NOT EXISTS idx_experience_keywords_exp_id ON experience_keywords(experience_id);
CREATE INDEX IF NOT EXISTS idx_experience_traits_global_user_id ON experience_traits_global(user_id);

-- Achievements
CREATE TABLE IF NOT EXISTS achievements (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(200) NOT NULL,
    achievement_date VARCHAR(50),
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fa-trophy',
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Technology Categories
CREATE TABLE IF NOT EXISTS tech_categories (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    category_name VARCHAR(100) NOT NULL,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Technologies (individual tech items in categories)
CREATE TABLE IF NOT EXISTS technologies (
    id SERIAL PRIMARY KEY,
    category_id INTEGER NOT NULL REFERENCES tech_categories(id) ON DELETE CASCADE,
    tech_name VARCHAR(100) NOT NULL,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_social_links_user_id ON social_links(user_id);
CREATE INDEX IF NOT EXISTS idx_education_user_id ON education(user_id);
CREATE INDEX IF NOT EXISTS idx_experience_user_id ON experience(user_id);
CREATE INDEX IF NOT EXISTS idx_experience_traits_exp_id ON experience_traits(experience_id);
CREATE INDEX IF NOT EXISTS idx_achievements_user_id ON achievements(user_id);
CREATE INDEX IF NOT EXISTS idx_tech_categories_user_id ON tech_categories(user_id);
CREATE INDEX IF NOT EXISTS idx_technologies_cat_id ON technologies(category_id);

-- Migration: Add public_slug and ensure username uniqueness
-- This migration creates a unique public URL per user based on username

-- Step 1: Ensure username is unique (it already has UNIQUE constraint, but let's verify)
-- If any duplicate usernames exist, this will fail - clean them up first
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint 
        WHERE conname = 'users_username_unique'
    ) THEN
        ALTER TABLE users ADD CONSTRAINT users_username_unique UNIQUE (username);
    END IF;
END $$;

-- Step 2: Add public_slug column if it doesn't exist
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'users' AND column_name = 'public_slug'
    ) THEN
        ALTER TABLE users ADD COLUMN public_slug VARCHAR(50);
    END IF;
END $$;

-- Step 3: Populate public_slug with username for existing users
UPDATE users 
SET public_slug = username 
WHERE public_slug IS NULL;

-- Step 4: Make public_slug NOT NULL and UNIQUE
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'users' 
        AND column_name = 'public_slug' 
        AND is_nullable = 'YES'
    ) THEN
        ALTER TABLE users ALTER COLUMN public_slug SET NOT NULL;
    END IF;
END $$;

-- Step 5: Create unique index on public_slug (case-insensitive)
CREATE UNIQUE INDEX users_public_slug_idx ON users(LOWER(public_slug));

-- Step 6: Verify the migration
SELECT 
    username, 
    public_slug,
    CASE 
        WHEN username = public_slug THEN 'OK'
        ELSE 'MISMATCH'
    END as status
FROM users
ORDER BY id;

-- Migration complete
-- Public URL format will be: /public/{public_slug}

-- GitHub -> brand
UPDATE social_links
SET icon = 'fa-brands fa-github'
WHERE LOWER(platform) = 'github';

-- LinkedIn -> brand
UPDATE social_links
SET icon = 'fa-brands fa-linkedin'
WHERE LOWER(platform) = 'linkedin';

-- Custom -> solid link
UPDATE social_links
SET icon = 'fa-solid fa-link'
WHERE LOWER(platform) IN ('custom', 'customlink', 'website', 'personal', 'portfolio');

SELECT * FROM users;