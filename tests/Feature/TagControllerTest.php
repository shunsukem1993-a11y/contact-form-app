<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 管理画面でタグ一覧が表示される(): void
    {
        // Arrange
        $user = User::factory()->create();

        $tags = Tag::factory()
            ->count(3)
            ->create();

        // Act
        $response = $this->actingAs($user)
            ->get(route('admin.index'));

        // Assert
        $response->assertStatus(200);

        foreach ($tags as $tag) {
            $response->assertSee($tag->name);
        }
    }

    /** @test */
    public function 認証済みユーザーはタグを追加できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $this->actingAs($user)
            ->post(route('tags.store'), [
                'name' => '新しいタグ',
            ]);

        // Assert
        $this->assertDatabaseHas('tags', [
            'name' => '新しいタグ',
        ]);
    }

    /** @test */
    public function タグ追加後に管理画面へリダイレクトされる(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->post(route('tags.store'), [
                'name' => '新しいタグ',

            ]);

        // Assert
        $response->assertRedirect(route('admin.index'));
    }

    /** @test */
    public function 未認証ユーザーはタグ追加できずログイン画面へリダイレクトされる(): void
    {
        // Act
        $response = $this->post(route('tags.store'), [
            'name' => '新しいタグ',
        ]);

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('tags', [
            'name' => '新しいタグ',
        ]);
    }

    /** @test */
    public function タグ名が未入力の場合バリデーションエラーになる(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->from(route('admin.index'))
            ->post(route('tags.store'), [
                'name' => '',
            ]);

        // Assert
        $response
            ->assertRedirect(route('admin.index'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('tags', 0);
    }

    /** @test */
    public function タグ名は50文字まで登録できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $tagName = str_repeat('a', 50);

        // Act
        $response = $this->actingAs($user)
            ->post(route('tags.store'), [
                'name' => $tagName,
            ]);

        // Assert
        $response->assertRedirect(route('admin.index'));

        $this->assertDatabaseHas('tags', [
            'name' => $tagName,
        ]);
    }

    /** @test */
    public function タグ名が51文字以上の場合バリデーションエラーになる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $tagName = str_repeat('a', 51);

        // Act
        $response = $this->actingAs($user)
            ->from(route('admin.index'))
            ->post(route('tags.store'), [
                'name' => $tagName,
            ]);

        // Assert
        $response
            ->assertRedirect(route('admin.index'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('tags', 0);
    }

    /** @test */
    public function 既に存在するタグ名では登録できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $tag = Tag::factory()->create([
            'name' => '質問',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->from(route('admin.index'))
            ->post(route('tags.store'), [
                'name' => $tag->name,
            ]);

        // Assert
        $response
            ->assertRedirect(route('admin.index'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('tags', 1);
    }

    /** @test */
    public function 認証済みユーザーはタグ編集画面を表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $tag = Tag::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->get(route('tags.edit', $tag));

        // Assert
        $response->assertStatus(200);

        $response->assertSee($tag->name);
    }

    /** @test */
    public function 未認証ユーザーはタグ編集画面へアクセスできずログイン画面へリダイレクトされる(): void
    {
        // Arrange
        $tag = Tag::factory()->create();

        // Act
        $response = $this->get(route('tags.edit', $tag));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 認証済みユーザーはタグ名を更新できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $tag = Tag::factory()->create([
            'name' => '変更前',
        ]);

        // Act
        $this->actingAs($user)
            ->put(route('tags.update', $tag), [
                'name' => '変更後',
            ]);

        // Assert
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '変更後',
        ]);
    }

    /** @test */
    public function タグ更新後に管理画面へリダイレクトされる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $tag = Tag::factory()->create([
            'name' => '変更前',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->put(route('tags.update', $tag), [
                'name' => '変更後',
            ]);

        // Assert
        $response->assertRedirect(route('admin.index'));
    }

    /** @test */
    public function 未認証ユーザーはタグ更新できずログイン画面へリダイレクトされる(): void
    {
        // Arrange
        $tag = Tag::factory()->create([
            'name' => '変更前',
        ]);

        // Act
        $response = $this->put(route('tags.update', $tag), [
            'name' => '変更後',
        ]);

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '変更前',
        ]);
    }

    /** @test */
    public function 自身のタグ名を維持したまま更新できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $tag = Tag::factory()->create([
            'name' => '重要',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->put(route('tags.update', $tag), [
                'name' => '重要',
            ]);

        // Assert
        $response->assertRedirect(route('admin.index'));

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '重要',
        ]);
    }

    /** @test */
    public function 他のタグで使用されている名前へ更新できない(): void
    {
        // Arrange
        $user = User::factory()->create();

        $tag1 = Tag::factory()->create([
            'name' => '質問',
        ]);

        $tag2 = Tag::factory()->create([
            'name' => '要望',
        ]);

        // Act
        $response = $this->actingAs($user)
            ->from(route('tags.edit', $tag2))
            ->put(route('tags.update', $tag2), [
                'name' => '質問',
            ]);

        // Assert
        $response
            ->assertRedirect(route('tags.edit', $tag2))
            ->assertSessionHasErrors('name');
    }

    /** @test */
    public function 認証済みユーザーはタグを削除できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $tag = Tag::factory()->create();

        // Act
        $this->actingAs($user)
            ->delete(route('tags.destroy', $tag));

        // Assert
        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    /** @test */
    public function タグ削除後に管理画面へリダイレクトされる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $tag = Tag::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->delete(route('tags.destroy', $tag));

        // Assert
        $response->assertRedirect(route('admin.index'));
    }

    /** @test */
    public function タグ削除時にcontact_tagテーブルのレコードも削除される(): void
    {
        // Arrange
        $user = User::factory()->create();

        $contact = Contact::factory()->create();

        $tag = Tag::factory()->create();

        $contact->tags()->attach($tag->id);

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);

        // Act
        $this->actingAs($user)
            ->delete(route('tags.destroy', $tag));

        // Assert
        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    /** @test */
    public function 未認証ユーザーはタグ削除できずログイン画面へリダイレクトされる(): void
    {
        // Arrange
        $tag = Tag::factory()->create();

        // Act
        $response = $this->delete(route('tags.destroy', $tag));

        // Assert
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
        ]);
    }
}
