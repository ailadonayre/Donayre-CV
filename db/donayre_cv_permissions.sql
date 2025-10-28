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

-- Change owner of all tables to arcd
ALTER TABLE users OWNER TO arcd;
ALTER TABLE social_links OWNER TO arcd;
ALTER TABLE education OWNER TO arcd;
ALTER TABLE experience OWNER TO arcd;
ALTER TABLE experience_traits OWNER TO arcd;
ALTER TABLE achievements OWNER TO arcd;
ALTER TABLE tech_categories OWNER TO arcd;
ALTER TABLE technologies OWNER TO arcd;

-- Change owner of all sequences
ALTER SEQUENCE users_id_seq OWNER TO arcd;
ALTER SEQUENCE social_links_id_seq OWNER TO arcd;
ALTER SEQUENCE education_id_seq OWNER TO arcd;
ALTER SEQUENCE experience_id_seq OWNER TO arcd;
ALTER SEQUENCE experience_traits_id_seq OWNER TO arcd;
ALTER SEQUENCE achievements_id_seq OWNER TO arcd;
ALTER SEQUENCE tech_categories_id_seq OWNER TO arcd;
ALTER SEQUENCE technologies_id_seq OWNER TO arcd;