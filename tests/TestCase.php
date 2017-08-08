<?php
namespace pavlm\yii\stats\tests;

class TestCase extends \PHPUnit_Framework_TestCase
{
    public $backupGlobals = false;
    
    public $backupGlobalsBlacklist = [
        'application',
    ];
    
    public static function setUpBeforeClass()
    {
    }
    
}