-- =====================================================================
-- Migration v12 — Email Verification & Password Reset (6-digit codes)
--   - users.email_verified_at: NULL until the owner confirms their email
--     with the 6-digit code sent at registration. Platform-level login
--     (the public /login page) is blocked until this is set; per-tenant
--     staff login (added by the owner from inside the dashboard) is not
--     affected by this column at all.
--   - users.verification_code / verification_code_expires_at: the
--     currently pending signup verification code, and when it expires
--     (15 minutes after it's issued).
--   - users.reset_code / reset_code_expires_at: the currently pending
--     "forgot password" code, and when it expires (15 minutes).
-- Safe to run on an existing database.
-- =====================================================================
SET NAMES utf8mb4;

ALTER TABLE users
    ADD COLUMN email_verified_at DATETIME NULL AFTER is_active,
    ADD COLUMN verification_code VARCHAR(6) NULL AFTER email_verified_at,
    ADD COLUMN verification_code_expires_at DATETIME NULL AFTER verification_code,
    ADD COLUMN reset_code VARCHAR(6) NULL AFTER verification_code_expires_at,
    ADD COLUMN reset_code_expires_at DATETIME NULL AFTER reset_code;

-- Existing accounts (created before this migration) shouldn't be locked
-- out by the new verification requirement — treat them as already verified.
UPDATE users SET email_verified_at = created_at WHERE email_verified_at IS NULL;
