<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests para las reglas de validación de contraseña del sistema.
 * Replica la lógica de AuthController::passwordStrong() de forma aislada.
 */
class PasswordValidationTest extends TestCase {

    private function passwordStrong(string $pass): bool {
        return strlen($pass) >= 8
            && strlen($pass) <= 72
            && preg_match('/[A-Z]/', $pass)
            && preg_match('/[a-z]/', $pass)
            && preg_match('/[0-9]/', $pass);
    }

    public function testValidPasswordPasses(): void {
        $this->assertTrue($this->passwordStrong('Segura123'));
        $this->assertTrue($this->passwordStrong('MiClave99!'));
        $this->assertTrue($this->passwordStrong('A1bcdefgh'));
    }

    public function testTooShortPasswordFails(): void {
        $this->assertFalse($this->passwordStrong('Ab1'));
        $this->assertFalse($this->passwordStrong('Ab1defg')); // 7 chars
    }

    public function testNoUppercaseFails(): void {
        $this->assertFalse($this->passwordStrong('sinmayus1'));
    }

    public function testNoLowercaseFails(): void {
        $this->assertFalse($this->passwordStrong('SINMINUS1'));
    }

    public function testNoDigitFails(): void {
        $this->assertFalse($this->passwordStrong('SinNumero'));
    }

    public function testEmptyPasswordFails(): void {
        $this->assertFalse($this->passwordStrong(''));
    }

    public function testExactlyEightCharsWithAllRulesPasses(): void {
        $this->assertTrue($this->passwordStrong('Abcde1fg'));
    }

    public function testPasswordExceeding72CharsFails(): void {
        $long = str_repeat('aA1', 25); // 75 chars
        $this->assertFalse($this->passwordStrong($long));
    }
}
