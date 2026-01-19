-- ============================================================================
-- Email notification tracking for IP approvals
-- ============================================================================

-- Add notified_at column to track when email was sent
ALTER TABLE pending_ip_approvals 
ADD COLUMN IF NOT EXISTS notified_at TIMESTAMPTZ DEFAULT NULL;

-- Index for finding un-notified pending requests
CREATE INDEX IF NOT EXISTS idx_pending_ips_unnotified 
ON pending_ip_approvals(forum_user_id) 
WHERE status = 'pending' AND notified_at IS NULL;
