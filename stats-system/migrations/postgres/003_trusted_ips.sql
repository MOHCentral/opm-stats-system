-- ============================================================================
-- Trusted IPs for Login Security
-- Allows users to approve trusted IPs that can auto-login without token
-- ============================================================================

-- Trusted IPs for a forum user
-- Once a token is successfully used from an IP, that IP becomes trusted
CREATE TABLE trusted_ips (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- Forum user this IP is trusted for
    forum_user_id INTEGER NOT NULL,
    
    -- The trusted IP address
    ip_address INET NOT NULL,
    
    -- How it was added
    source VARCHAR(32) NOT NULL DEFAULT 'token_login',  -- 'token_login', 'manual_approval', 'admin'
    
    -- Optional label (e.g., "Home", "Work", "VPN")
    label VARCHAR(64),
    
    -- The player GUID that was used when this IP was trusted
    player_guid VARCHAR(64),
    
    -- When this IP was first trusted
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    -- Last successful login from this IP
    last_used_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    -- If the user wants to revoke this IP
    is_active BOOLEAN NOT NULL DEFAULT true,
    revoked_at TIMESTAMPTZ,
    
    -- Prevent duplicate IP entries for same user
    CONSTRAINT unique_user_ip UNIQUE (forum_user_id, ip_address)
);

CREATE INDEX idx_trusted_ips_user ON trusted_ips(forum_user_id) WHERE is_active = true;
CREATE INDEX idx_trusted_ips_lookup ON trusted_ips(forum_user_id, ip_address) WHERE is_active = true;

-- Pending IP approval requests
-- Created when someone tries to login from an untrusted IP
CREATE TABLE pending_ip_approvals (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    
    -- Forum user this request is for
    forum_user_id INTEGER NOT NULL,
    
    -- The IP requesting access
    ip_address INET NOT NULL,
    
    -- Info about the request
    player_guid VARCHAR(64),
    player_name VARCHAR(64),
    server_name VARCHAR(128),
    server_address VARCHAR(255),
    
    -- Request lifecycle
    requested_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    expires_at TIMESTAMPTZ NOT NULL DEFAULT NOW() + INTERVAL '24 hours',
    
    -- What happened
    status VARCHAR(16) NOT NULL DEFAULT 'pending',  -- 'pending', 'approved', 'denied', 'expired'
    resolved_at TIMESTAMPTZ,
    
    -- Prevent spam - only one pending request per IP per user
    CONSTRAINT unique_pending_user_ip UNIQUE (forum_user_id, ip_address)
);

CREATE INDEX idx_pending_ips_user ON pending_ip_approvals(forum_user_id) WHERE status = 'pending';
CREATE INDEX idx_pending_ips_expires ON pending_ip_approvals(expires_at) WHERE status = 'pending';

-- ============================================================================
-- Clean up expired pending requests (run periodically)
-- ============================================================================

CREATE OR REPLACE FUNCTION cleanup_expired_ip_approvals()
RETURNS INTEGER AS $$
DECLARE
    deleted_count INTEGER;
BEGIN
    UPDATE pending_ip_approvals
    SET status = 'expired', resolved_at = NOW()
    WHERE status = 'pending' AND expires_at < NOW();
    
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    RETURN deleted_count;
END;
$$ LANGUAGE plpgsql;
