<?php
/**
 * Logger - نظام السجلات
 * 
 * يسجل جميع الأنشطة والأخطاء
 */

namespace SCCIT\ERP\Saas\Core;

class Logger
{
    protected static $logDir = '';
    protected static $levels = array(
        'debug' => 0,
        'info' => 1,
        'notice' => 2,
        'warning' => 3,
        'error' => 4,
        'critical' => 5
    );
    protected static $currentLevel = 1; // info
    
    /**
     * تعيين دليل السجلات
     * 
     * @param string $dir
     */
    public static function setLogDir($dir)
    {
        self::$logDir = $dir;
        
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
    
    /**
     * تعيين مستوى السجل
     * 
     * @param string $level
     */
    public static function setLevel($level)
    {
        if (isset(self::$levels[$level])) {
            self::$currentLevel = self::$levels[$level];
        }
    }
    
    /**
     * تسجيل رسالة
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     */
    protected static function log($level, $message, $context = array())
    {
        if (self::$levels[$level] < self::$currentLevel) {
            return;
        }
        
        if (empty(self::$logDir)) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logFile = self::$logDir . '/' . $level . '.log';
        
        $logMessage = sprintf(
            "[%s] %s: %s",
            $timestamp,
            strtoupper($level),
            $message
        );
        
        if (!empty($context)) {
            $logMessage .= ' ' . json_encode($context);
        }
        
        $logMessage .= "\n";
        
        @file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * تسجيل رسالة تصحيح
     * 
     * @param string $message
     * @param array $context
     */
    public static function debug($message, $context = array())
    {
        self::log('debug', $message, $context);
    }
    
    /**
     * تسجيل رسالة معلومات
     * 
     * @param string $message
     * @param array $context
     */
    public static function info($message, $context = array())
    {
        self::log('info', $message, $context);
    }
    
    /**
     * تسجيل تنبيه
     * 
     * @param string $message
     * @param array $context
     */
    public static function warning($message, $context = array())
    {
        self::log('warning', $message, $context);
    }
    
    /**
     * تسجيل خطأ
     * 
     * @param string $message
     * @param array $context
     */
    public static function error($message, $context = array())
    {
        self::log('error', $message, $context);
    }
    
    /**
     * تسجيل خطأ حرج
     * 
     * @param string $message
     * @param array $context
     */
    public static function critical($message, $context = array())
    {
        self::log('critical', $message, $context);
    }
}
?>
