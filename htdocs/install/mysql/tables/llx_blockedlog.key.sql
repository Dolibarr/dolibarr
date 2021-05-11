ALTER TABLE llx_blockedlog ADD INDEX signature (signature);
ALTER TABLE llx_blockedlog ADD INDEX fk_object_element (fk_object,element);
ALTER TABLE llx_blockedlog ADD INDEX entity (entity);
ALTER TABLE llx_blockedlog ADD INDEX fk_user (fk_user); 
ALTER TABLE llx_blockedlog ADD INDEX entity_action (entity,action);
ALTER TABLE llx_blockedlog ADD INDEX entity_action_certified (entity,action,certified);
