-- A schema name is unique within an entity
ALTER TABLE llx_schemas ADD UNIQUE INDEX uk_schemas_name (name, entity);
-- Schemas are referenced by uuid (catalogue download, composed_of)
ALTER TABLE llx_schemas ADD INDEX idx_schemas_uuid (uuid);
