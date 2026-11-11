-- A field name is unique within a schema (also serves as index on fk_schema)
ALTER TABLE llx_extrafields_schemas ADD UNIQUE INDEX uk_extrafields_schemas_name (fk_schema, name, entity);
ALTER TABLE llx_extrafields_schemas ADD CONSTRAINT fk_extrafields_schemas_fk_schema FOREIGN KEY (fk_schema) REFERENCES llx_schemas (rowid);
