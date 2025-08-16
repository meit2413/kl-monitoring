-- This script updates the database schema to version 2.
-- It adds the ssl_verify column to the servers table.

ALTER TABLE `servers`
ADD COLUMN `ssl_verify` TINYINT(1) NOT NULL DEFAULT 1 AFTER `api_token`;

-- You can run this script from the command line:
-- mysql -u your_user -p your_database < update_schema_1.sql
