<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../libs/Config.php';

class ConfigTest extends TestCase {
    
    protected function setUp(): void {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'test_db';
        $_ENV['DB_USER'] = 'test_user';
        $_ENV['DB_PASS'] = 'test_pass';
        $_ENV['DB_CHARSET'] = 'utf8';
    }

    public function testDbReturnsCorrectArray() {
        $dbConfig = Config::db();
        
        $this->assertIsArray($dbConfig);
        $this->assertArrayHasKey('host', $dbConfig);
        $this->assertEquals('localhost', $dbConfig['host']);
        $this->assertEquals('test_db', $dbConfig['dbname']);
        $this->assertEquals('test_user', $dbConfig['user']);
        $this->assertEquals('test_pass', $dbConfig['pass']);
        $this->assertEquals('utf8', $dbConfig['charset']);
    }
}
