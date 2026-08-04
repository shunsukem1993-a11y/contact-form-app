<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 認証済みユーザーは管理画面を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('admin.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.index');
    }

    /** @test */
    public function 未認証ユーザーは管理画面へアクセスできずログインへリダイレクトされる(): void
    {
        $response = $this->get(route('admin.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 管理画面にお問い合わせ一覧が表示される(): void
    {
        $user = User::factory()->create();

        Contact::factory()->count(3)->create();

        $response = $this->actingAs($user)
            ->get(route('admin.index'));

        $response->assertStatus(200);

        foreach (Contact::all() as $contact) {
            $response->assertSee($contact->email);
        }
    }

    /** @test */
    public function 管理画面にカテゴリー情報が表示される(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create();

        Contact::factory()
            ->create([
                'category_id' => $category->id,
            ]);

        $response = $this->actingAs($user)
            ->get(route('admin.index'));

        $response->assertSee($category->content);
    }

    /** @test */
    public function 管理画面にタグ情報が表示される(): void
    {
        $user = User::factory()->create();

        $tag = Tag::factory()->create();

        $contact = Contact::factory()->create();

        $contact->tags()->attach($tag);

        $response = $this->actingAs($user)
            ->get(route('admin.index'));

        $response->assertSee($tag->name);
    }

    /** @test */
    public function お問い合わせ一覧が作成日の降順で表示される(): void
    {
        $user = User::factory()->create();

        $oldContact = Contact::factory()
            ->createdAt('2026-01-01 10:00:00')
            ->create();

        $newContact = Contact::factory()
            ->createdAt('2026-02-01 10:00:00')
            ->create();

        $response = $this->actingAs($user)
            ->get(route('admin.index'));

        $response->assertSeeInOrder([
            $newContact->email,
            $oldContact->email,
        ]);
    }

    /** @test */
    public function キーワード検索で名前の部分一致検索ができる(): void
    {
        $user = User::factory()->create();

        Contact::factory()->create([
            'first_name' => '太郎',
        ]);

        Contact::factory()->create([
            'first_name' => '次郎',
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.index', ['keyword' => '太郎']));

        $response->assertSee('太郎');
        $response->assertDontSee('次郎');
    }

    /** @test */
    public function キーワード検索でメール検索ができる(): void
    {
        $user = User::factory()->create();

        Contact::factory()->create([
            'email' => 'test@example.com',
        ]);

        Contact::factory()->create([
            'email' => 'other@example.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.index', ['keyword' => 'test']));

        $response->assertSee('test@example.com');
        $response->assertDontSee('other@example.com');
    }

    /** @test */
    public function 性別検索で指定したお問い合わせのみ表示される(): void
    {
        $user = User::factory()->create();

        Contact::factory()->create([
            'gender' => 1,
            'email' => 'male@example.com',
        ]);

        Contact::factory()->create([
            'gender' => 2,
            'email' => 'female@example.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.index', ['gender' => 1]));

        $response->assertSee('male@example.com');
        $response->assertDontSee('female@example.com');
    }

    /** @test */
    public function カテゴリー検索で指定したお問い合わせのみ表示される(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create();

        Contact::factory()->create([
            'category_id' => $category->id,
            'email' => 'target@example.com',
        ]);

        Contact::factory()->create([
            'email' => 'other@example.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.index', ['category_id' => $category->id]));

        $response->assertSee('target@example.com');
        $response->assertDontSee('other@example.com');
    }

    /** @test */
    public function 日付検索で指定日のお問い合わせのみ表示される(): void
    {
        $user = User::factory()->create();

        Contact::factory()
            ->createdAt('2026-08-01 10:00:00')
            ->create([
                'email' => 'target@example.com',
            ]);

        Contact::factory()
            ->createdAt('2026-08-02 10:00:00')
            ->create([
                'email' => 'other@example.com',
            ]);

        $response = $this->actingAs($user)
            ->get(route('admin.index', ['date' => '2026-08-01']));

        $response->assertSee('target@example.com');
        $response->assertDontSee('other@example.com');
    }

    /** @test */
    public function 複数条件で検索できる(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create();

        Contact::factory()->create([
            'first_name' => '太郎',
            'gender' => 1,
            'category_id' => $category->id,
            'email' => 'target@example.com',
        ]);

        Contact::factory()->create([
            'first_name' => '太郎',
            'gender' => 2,
            'email' => 'other@example.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.index', [
                'keyword' => '太郎',
                'gender' => 1,
                'category_id' => $category->id,
            ]));

        $response->assertSee('target@example.com');
        $response->assertDontSee('other@example.com');
    }

    /** @test */
    public function 不正な性別値の場合バリデーションエラーになる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('admin.index', ['gender' => 5]));

        $response->assertSessionHasErrors('gender');
    }

    /** @test */
    public function お問い合わせ一覧は7件ごとにページネーションされる(): void
    {
        $user = User::factory()->create();

        Contact::factory()->count(8)->create();

        $response = $this->actingAs($user)
            ->get(route('admin.index'));

        $response->assertViewHas('contacts', function ($contacts) {
            return $contacts->perPage() === 7;
        });
    }

    /** @test */
    public function 検索条件を保持してページネーションできる(): void
    {
        $user = User::factory()->create();

        Contact::factory()
            ->count(8)
            ->create([
                'first_name' => '太郎',
            ]);

        $response = $this->actingAs($user)
            ->get(route('admin.index', ['keyword' => '太郎']));

        $response->assertStatus(200);

        $response->assertViewHas('contacts');
    }

    /** @test */
    public function リセットで検索条件を解除できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('admin.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function お問い合わせ詳細ページを表示できる(): void
    {
        $user = User::factory()->create();

        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('admin.show', $contact));

        $response->assertStatus(200);
        $response->assertViewIs('admin.show');
    }

    /** @test */
    public function 詳細ページにカテゴリー情報が表示される(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create();

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.show', $contact));

        $response->assertSee($category->content);
    }

    /** @test */
    public function 詳細ページにタグ情報が表示される(): void
    {
        $user = User::factory()->create();

        $tag = Tag::factory()->create();

        $contact = Contact::factory()->create();

        $contact->tags()->attach($tag);

        $response = $this->actingAs($user)
            ->get(route('admin.show', $contact));

        $response->assertSee($tag->name);
    }

    /** @test */
    public function 未認証ユーザーは詳細ページへアクセスできない(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->get(route('admin.show', $contact));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function 存在しないお問い合わせIDの場合404になる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('admin.show', 9999));

        $response->assertStatus(404);
    }

    /** @test */
    public function お問い合わせを削除できる(): void
    {
        $user = User::factory()->create();

        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)
            ->delete(route('admin.destroy', $contact));

        $response->assertRedirect(route('admin.index'));

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    /** @test */
    public function 削除後お問い合わせ一覧へリダイレクトされる(): void
    {
        $user = User::factory()->create();

        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)
            ->delete(route('admin.destroy', $contact));

        $response->assertRedirect(route('admin.index'));
    }

    /** @test */
    public function 削除後お問い合わせデータが_d_bから削除される(): void
    {
        $user = User::factory()->create();

        $contact = Contact::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.destroy', $contact));

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    /** @test */
    public function 未認証ユーザーはお問い合わせ削除ができない(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->delete(route('admin.destroy', $contact));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
        ]);
    }
}
