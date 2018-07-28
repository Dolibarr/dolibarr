<?php

if (!interface_exists('JsonSerializable')) {
    interface JsonSerializable {
        public function jsonSerialize();
    }
}