<?php

declare(strict_types=1);

namespace PHPAdmin\Tests\Integration;

use Illuminate\Database\Capsule\Manager as Capsule;
use PHPAdmin\Core\Exceptions\NotFoundAppException;
use PHPAdmin\Modules\Access\Services\UserService;
use PHPAdmin\Modules\Access\Contracts\IUserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    private IUserService $userService;

    public static function setUpBeforeClass(): void
    {
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Canonical schema — must match production exactly (varchar(36) UUID ids)
        Capsule::schema()->create('users', function ($table) {
            $table->string('id', 36)->primary();
            $table->string('code', 20)->unique();
            $table->string('name', 50);
            $table->string('phone', 15)->nullable();
            $table->string('email', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->string('password_otp', 255)->nullable();
            $table->bigInteger('password_otp_expires')->nullable();
            $table->string('status', 20)->default('Active');
            $table->string('picture', 255)->nullable();
            $table->boolean('blocked')->default(false);
            $table->string('blocked_reason', 255)->nullable();
            $table->string('timezone', 255)->default('UTC');
            $table->string('created_by', 36)->nullable();
            $table->string('updated_by', 36)->nullable();
            $table->timestamps();
        });

        Capsule::schema()->create('roles', function ($table) {
            $table->string('id', 36)->primary();
            $table->string('name', 255)->unique();
            $table->string('status', 20)->default('Active');
            $table->string('desc', 255)->nullable();
            $table->string('created_by', 36)->nullable();
            $table->string('updated_by', 36)->nullable();
            $table->timestamps();
        });

        Capsule::schema()->create('users_roles', function ($table) {
            $table->string('user_id', 36);
            $table->string('role_id', 36);
            $table->primary(['user_id', 'role_id']);
        });
    }

    protected function setUp(): void
    {
        Capsule::table('users_roles')->delete();
        Capsule::table('users')->delete();
        $this->userService = new UserService();
    }

    private function makeUser(string $suffix = '1'): array
    {
        return [
            'code'     => 'TST' . str_pad($suffix, 3, '0', STR_PAD_LEFT),
            'name'     => 'Test User ' . $suffix,
            'email'    => 'user' . $suffix . '@example.com',
            'password' => 'secret1234',
            'status'   => 'Active',
            'timezone' => 'UTC',
        ];
    }

    public function testCreateUserPasswordIsHashed(): void
    {
        $user = $this->userService->create($this->makeUser('1'));

        $this->assertNotSame('secret1234', $user->password);
        $this->assertTrue(password_verify('secret1234', $user->password));
        $this->assertSame('TST001', $user->code);
    }

    public function testCreateUserHasUuidId(): void
    {
        $user = $this->userService->create($this->makeUser('2'));

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $user->id
        );
    }

    public function testFindById(): void
    {
        $created = $this->userService->create($this->makeUser('3'));
        $found   = $this->userService->findById($created->id);

        $this->assertNotNull($found);
        $this->assertSame($created->id, $found->id);
        $this->assertSame('Test User 3', $found->name);
    }

    public function testFindByIdThrowsForMissing(): void
    {
        $this->expectException(NotFoundAppException::class);
        $this->userService->findById('00000000-0000-4000-8000-000000000000');
    }

    public function testUpdate(): void
    {
        $user    = $this->userService->create($this->makeUser('4'));
        $updated = $this->userService->update($user->id, ['name' => 'Updated Name']);

        $this->assertSame('Updated Name', $updated->name);
        $this->assertSame($user->email, $updated->email);
    }

    public function testDelete(): void
    {
        $user = $this->userService->create($this->makeUser('5'));
        $this->userService->delete($user->id);

        $this->expectException(NotFoundAppException::class);
        $this->userService->findById($user->id);
    }
}
