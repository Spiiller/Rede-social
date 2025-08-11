-- Add is_admin column to users table
ALTER TABLE users
ADD COLUMN is_admin BOOLEAN DEFAULT FALSE;

-- Create first admin user (password: admin123)
INSERT INTO users (name, email, password, is_admin) 
VALUES ('Admin', 'admin@plantgram.com', SHA2('admin123', 256), TRUE);

