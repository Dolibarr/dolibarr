<?php

interface LogHandlerInterface
{
	public function getName();
	public function getVersion();
	public function getInfo();
	public function configure();
	public function checkConfiguration();
	public function isActive();
	public function export($content);
}