<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/TicketModel.php';

class TicketModelTest extends TestCase {

  public function testBuildVisibilityWhereForAdmin(): void {
    [$where, $params] = TicketModel::buildVisibilityWhere('t', 9, true, []);

    $this->assertSame('t.deleted_at IS NULL', $where);
    $this->assertSame([], $params);
  }

  public function testBuildVisibilityWhereForAgentWithoutDepartments(): void {
    [$where, $params] = TicketModel::buildVisibilityWhere('t', 15, false, []);

    $this->assertStringContainsString('t.deleted_at IS NULL', $where);
    $this->assertStringContainsString('(t.creado_por = ? OR t.asignado_a = ?)', $where);
    $this->assertSame([15, 15], $params);
  }

  public function testBuildVisibilityWhereForAgentWithDepartments(): void {
    [$where, $params] = TicketModel::buildVisibilityWhere('t', 4, false, [3, 2, 2]);

    $this->assertStringContainsString('t.deleted_at IS NULL', $where);
    $this->assertStringContainsString('(t.creado_por = ? OR t.asignado_a = ? OR t.departamento_id IN (?,?))', $where);
    $this->assertSame([4, 4, 2, 3], $params);
  }
}
