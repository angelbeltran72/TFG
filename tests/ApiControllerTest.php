<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../api/controllers/ApiController.php';

// Subclase pública para acceder a métodos protegidos de ApiController.
class TestableApiController extends ApiController {
    public function __construct() {
        // No llamamos al parent para evitar emitir cabeceras HTTP en tests
    }

    public function exposedGetBearerToken(): ?string {
        return $this->getBearerToken();
    }

    public function exposedGetAuthorizationHeader(): string {
        return $this->getAuthorizationHeader();
    }
}

class ApiControllerTest extends TestCase {

    private TestableApiController $ctrl;

    protected function setUp(): void {
        $this->ctrl = new TestableApiController();
        // Limpiar cualquier cabecera residual
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }

    public function testGetBearerTokenReturnNullWhenNoHeader(): void {
        $token = $this->ctrl->exposedGetBearerToken();
        $this->assertNull($token);
    }

    public function testGetBearerTokenExtractsTokenCorrectly(): void {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer abc123token';
        $token = $this->ctrl->exposedGetBearerToken();
        $this->assertSame('abc123token', $token);
    }

    public function testGetBearerTokenIsCaseInsensitive(): void {
        $_SERVER['HTTP_AUTHORIZATION'] = 'bearer mitoken456';
        $token = $this->ctrl->exposedGetBearerToken();
        $this->assertSame('mitoken456', $token);
    }

    public function testGetBearerTokenReturnNullForNonBearerScheme(): void {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic dXNlcjpwYXNz';
        $token = $this->ctrl->exposedGetBearerToken();
        $this->assertNull($token);
    }

    public function testGetAuthorizationHeaderReturnsEmptyWhenMissing(): void {
        $header = $this->ctrl->exposedGetAuthorizationHeader();
        $this->assertSame('', $header);
    }

    public function testGetAuthorizationHeaderReturnsValueFromServer(): void {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer testtoken';
        $header = $this->ctrl->exposedGetAuthorizationHeader();
        $this->assertSame('Bearer testtoken', $header);
    }

    public function testGetBearerTokenTrimsWhitespace(): void {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer   tokenconespacios   ';
        $token = $this->ctrl->exposedGetBearerToken();
        $this->assertSame('tokenconespacios', $token);
    }

    protected function tearDown(): void {
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }
}
