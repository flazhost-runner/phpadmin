<?php

declare(strict_types=1);

namespace PHPAdmin\Tests\Unit;

use PHPAdmin\Core\RouteRegistry;
use PHPUnit\Framework\TestCase;

class RouteRegistryTest extends TestCase
{
    private RouteRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new RouteRegistry();
    }

    public function testRegisterAndUrlWithoutParams(): void
    {
        $this->registry->get('/admin/dashboard', ['DashboardController', 'index'], 'admin.v1.dashboard.index');
        $this->assertSame('/admin/dashboard', $this->registry->url('admin.v1.dashboard.index'));
    }

    public function testRegisterAndUrlWithParamSubstitution(): void
    {
        $this->registry->get('/admin/users/{id}/edit', ['UserController', 'edit'], 'admin.v1.access.user.edit');
        $url = $this->registry->url('admin.v1.access.user.edit', ['id' => 42]);
        $this->assertSame('/admin/users/42/edit', $url);
    }

    public function testUrlWithMultipleParams(): void
    {
        $this->registry->get('/admin/{section}/{id}', ['Controller', 'show'], 'admin.v1.section.show');
        $url = $this->registry->url('admin.v1.section.show', ['section' => 'roles', 'id' => 7]);
        $this->assertSame('/admin/roles/7', $url);
    }

    public function testGetNameByPathAndMethod(): void
    {
        $this->registry->post('/admin/users', ['UserController', 'store'], 'admin.v1.access.user.store');
        $name = $this->registry->getNameByPathAndMethod('/admin/users', 'POST');
        $this->assertSame('admin.v1.access.user.store', $name);
    }

    public function testGetNameByPathAndMethodReturnsNullForMismatch(): void
    {
        $this->registry->get('/admin/users', ['UserController', 'index'], 'admin.v1.access.user.index');
        $name = $this->registry->getNameByPathAndMethod('/admin/users', 'POST');
        $this->assertNull($name);
    }

    public function testHasRoute(): void
    {
        $this->registry->get('/admin/roles', ['RoleController', 'index'], 'admin.v1.access.role.index');
        $this->assertTrue($this->registry->hasRoute('admin.v1.access.role.index'));
        $this->assertFalse($this->registry->hasRoute('admin.v1.access.role.nonexistent'));
    }

    public function testRegisterMultipleMethods(): void
    {
        $this->registry->get('/admin/permissions', ['PermController', 'index'], 'admin.v1.access.permission.index');
        $this->registry->post('/admin/permissions', ['PermController', 'store'], 'admin.v1.access.permission.store');

        $this->assertTrue($this->registry->hasRoute('admin.v1.access.permission.index'));
        $this->assertTrue($this->registry->hasRoute('admin.v1.access.permission.store'));
    }
}
