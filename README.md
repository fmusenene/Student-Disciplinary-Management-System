CREATE DATABASE disciplinary_system;
exit; 

ALTER TABLE users ADD COLUMN phone VARCHAR(20) UNIQUE AFTER email; 