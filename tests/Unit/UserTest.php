<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_many_projects()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->projects);
    }

    #[Test]
    public function it_has_many_tasks()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->tasks);
    }

    #[Test]
    public function it_has_many_tags()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->tags);
    }

    #[Test]
    public function it_hashes_password()
    {
        $user = User::factory()->create([
            'password' => 'plaintext123'
        ]);

        $this->assertNotEquals('plaintext123', $user->password);
        $this->assertTrue(\Hash::check('plaintext123', $user->password));
    }

    #[Test]
    public function it_has_fillable_attributes()
    {
        $fillable = ['name', 'email', 'password'];
        $user = new User();

        $this->assertEquals($fillable, $user->getFillable());
    }

    #[Test]
    public function it_hides_password_in_array()
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    #[Test]
    public function it_can_create_api_tokens()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token');

        $this->assertNotNull($token->plainTextToken);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token'
        ]);
    }
}
