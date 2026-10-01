-- Preserve the campaign state so restoration does not accidentally republish a closed draft.
ALTER TABLE Campaign
  ADD COLUMN status_before_suspension ENUM('draft', 'pending_review', 'published', 'closed', 'archived') NULL;

UPDATE Campaign
SET status_before_suspension = 'published'
WHERE status = 'suspended' AND status_before_suspension IS NULL;