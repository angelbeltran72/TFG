<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../libs/Csrf.php';

class CsrfTest extends TestCase {
    
    protected function setUp(): void {
        // Inicializar o reiniciar la sesión para las pruebas
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    public function testGenerateCreatesToken() {
        $token = Csrf::generate();
        $this->assertNotEmpty($token, 'El token generado no debería estar vacío');
        $this->assertEquals(64, strlen($token), 'El token generado debería tener 64 caracteres (hex de 32 bytes)');
        $this->assertEquals($_SESSION['csrf_token'], $token, 'El token debe guardarse en la sesión');
    }

    public function testGenerateReturnsExistingToken() {
        $token1 = Csrf::generate();
        $token2 = Csrf::generate();
        $this->assertEquals($token1, $token2, 'generate() debe devolver el mismo token si ya existe en la sesión');
    }

    public function testValidateReturnsTrueForCorrectToken() {
        $token = Csrf::generate();
        $this->assertTrue(Csrf::validate($token), 'validate() debe devolver true para un token correcto');
    }

    public function testValidateReturnsFalseForIncorrectToken() {
        Csrf::generate();
        $this->assertFalse(Csrf::validate('token_falso'), 'validate() debe devolver false para un token incorrecto');
    }

    public function testValidateReturnsFalseIfSessionEmpty() {
        // No llamamos a generate() para que la sesión esté vacía
        $this->assertFalse(Csrf::validate('algun_token'), 'validate() debe devolver false si no hay token en la sesión');
    }

    public function testFieldReturnsCorrectHtml() {
        $token = Csrf::generate();
        $field = Csrf::field();
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
        $this->assertStringContainsString('value="' . $token . '"', $field);
    }

    public function testRegenerateCreatesNewToken() {
        $token1 = Csrf::generate();
        Csrf::regenerate();
        $token2 = $_SESSION['csrf_token'];
        
        $this->assertNotEquals($token1, $token2, 'regenerate() debe crear un token nuevo y distinto');
        $this->assertEquals(64, strlen($token2), 'El nuevo token debe tener la longitud correcta');
    }
}
