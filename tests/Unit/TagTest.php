<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TagTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $tag->user);
        $this->assertEquals($user->id, $tag->user->id);
    }

    #[Test]
    public function it_has_many_tasks()
    {
        $tag = Tag::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $tag->tasks);
    }

    #[Test]
    public function it_has_fillable_attributes()
    {
        $fillable = ['user_id', 'name'];
        $tag = new Tag();

        $this->assertEquals($fillable, $tag->getFillable());
    }

    #[Test]
    public function it_can_create_tag_with_required_fields()
    {
        $user = User::factory()->create();
        $tag = Tag::create([
            'user_id' => $user->id,
            'name' => 'urgent'
        ]);

        $this->assertDatabaseHas('tags', [
            'name' => 'urgent',
            'user_id' => $user->id
        ]);
    }

    #[Test]
    public function tag_name_is_unique_per_user()
    {
        $user = User::factory()->create();

        Tag::create([
            'user_id' => $user->id,
            'name' => 'urgent'
        ]);

        // Same tag name for same user should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        Tag::create([
            'user_id' => $user->id,
            'name' => 'urgent'
        ]);
    }

    #[Test]
    public function different_users_can_have_same_tag_name()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $tag1 = Tag::create([
            'user_id' => $user1->id,
            'name' => 'urgent'
        ]);

        $tag2 = Tag::create([
            'user_id' => $user2->id,
            'name' => 'urgent'
        ]);

        $this->assertNotEquals($tag1->id, $tag2->id);
        $this->assertEquals('urgent', $tag1->name);
        $this->assertEquals('urgent', $tag2->name);
    }
}
