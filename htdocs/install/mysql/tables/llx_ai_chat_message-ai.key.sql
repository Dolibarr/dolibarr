ALTER TABLE llx_ai_chat_conversation ADD INDEX idx_ai_chat_conversation_user (fk_user, tms);
ALTER TABLE llx_ai_chat_message ADD INDEX idx_ai_chat_message_conv (fk_conversation, position);
ALTER TABLE llx_ai_chat_message ADD CONSTRAINT fk_ai_chat_message_conv FOREIGN KEY (fk_conversation) REFERENCES llx_ai_chat_conversation (rowid);
