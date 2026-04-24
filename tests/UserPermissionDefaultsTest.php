<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/UserPermissionModel.php';

class UserPermissionDefaultsTest extends TestCase {

    private const EXPECTED_PERMISSIONS = [
        'crear_tickets',
        'ver_tickets_departamento',
        'comentar_tickets',
        'ver_todos_tickets',
        'cambiar_estado_ajenos',
        'reasignar_tickets',
        'cerrar_tickets_ajenos',
        'crear_en_nombre_de',
        'acceso_kanban',
        'acceso_configuracion',
    ];

    private function getDefaults(): array {
        $ref = new ReflectionClass(UserPermissionModel::class);
        $prop = $ref->getProperty('defaults');
        $prop->setAccessible(true);
        return $prop->getValue();
    }

    public function testAllRolesAreDefined(): void {
        $defaults = $this->getDefaults();
        $this->assertArrayHasKey('admin',   $defaults);
        $this->assertArrayHasKey('user',    $defaults);
        $this->assertArrayHasKey('cliente', $defaults);
    }

    public function testAllPermissionsDefinedForEveryRole(): void {
        $defaults = $this->getDefaults();
        foreach (['admin', 'user', 'cliente'] as $role) {
            foreach (self::EXPECTED_PERMISSIONS as $perm) {
                $this->assertArrayHasKey(
                    $perm,
                    $defaults[$role],
                    "Permiso '{$perm}' no definido para rol '{$role}'"
                );
            }
        }
    }

    public function testAdminHasAllPermissionsGranted(): void {
        $defaults = $this->getDefaults();
        foreach (self::EXPECTED_PERMISSIONS as $perm) {
            $this->assertTrue(
                $defaults['admin'][$perm],
                "Admin debe tener '{$perm}' concedido por defecto"
            );
        }
    }

    public function testClienteHasNoPermissionsGranted(): void {
        $defaults = $this->getDefaults();
        foreach (self::EXPECTED_PERMISSIONS as $perm) {
            $this->assertFalse(
                $defaults['cliente'][$perm],
                "Cliente no debe tener '{$perm}' concedido"
            );
        }
    }

    public function testAgentHasExpectedDefaultPermissions(): void {
        $defaults = $this->getDefaults();
        $user = $defaults['user'];

        $this->assertTrue($user['crear_tickets'],    'Agente debe poder crear tickets');
        $this->assertTrue($user['comentar_tickets'], 'Agente debe poder comentar');
        $this->assertTrue($user['acceso_kanban'],    'Agente debe tener acceso al kanban');

        $this->assertFalse($user['ver_todos_tickets'],     'Agente no ve todos los tickets por defecto');
        $this->assertFalse($user['acceso_configuracion'],  'Agente no tiene acceso a configuración');
        $this->assertFalse($user['reasignar_tickets'],     'Agente no puede reasignar por defecto');
        $this->assertFalse($user['cerrar_tickets_ajenos'], 'Agente no puede cerrar tickets ajenos por defecto');
    }

    public function testPermissionsAreAllBooleans(): void {
        $defaults = $this->getDefaults();
        foreach ($defaults as $role => $perms) {
            foreach ($perms as $perm => $value) {
                $this->assertIsBool(
                    $value,
                    "El permiso '{$perm}' del rol '{$role}' debe ser boolean"
                );
            }
        }
    }

    public function testExactlyTenPermissionsPerRole(): void {
        $defaults = $this->getDefaults();
        foreach (['admin', 'user', 'cliente'] as $role) {
            $this->assertCount(
                10,
                $defaults[$role],
                "El rol '{$role}' debe tener exactamente 10 permisos"
            );
        }
    }
}
