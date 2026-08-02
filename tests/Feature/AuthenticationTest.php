<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ログイン画面を表示できる(): void
    {
        // Act
        $response = $this->get(route('login'));

        // Assert
        $response->assertStatus(200);
    }

    /** @test */
    public function 登録画面を表示できる(): void
    {
        // Act
        $response = $this->get(route('register'));

        // Assert
        $response->assertStatus(200);
    }

    /** @test */
    public function ユーザー登録が成功するとユーザーが作成される(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function ユーザー登録成功後に認証状態になる(): void
    {
        // Act
        $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $this->assertAuthenticated();
    }

    /** @test */
    public function 名前が未入力の場合バリデーションエラーになる(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function メールアドレスが未入力の場合バリデーションエラーになる(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function メールアドレス形式が不正の場合バリデーションエラーになる(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function 登録済みメールアドレスではユーザー登録できない(): void
    {
        // Arrange
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function パスワードが未入力の場合バリデーションエラーになる(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        // Assert
        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function パスワード確認が一致しない場合バリデーションエラーになる(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        // Assert
        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function 正しい認証情報でログインできる(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        // Act
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Assert
        $response->assertRedirect(route('admin.index'));

        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function 存在しないメールアドレスではログインできない(): void
    {
        // Act
        $response = $this->post(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /** @test */
    public function パスワードが間違っている場合ログインできない(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        // Act
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /** @test */
    public function ログアウトすると認証状態が解除される(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('logout'));

        // Assert
        $this->assertGuest();

        $response->assertRedirect();
    }

    /** @test */
    public function ログアウト後に問い合わせ画面へリダイレクトされる(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('logout'));

        // Assert
        $response->assertRedirect('/');
    }

    /** @test */
    public function ログアウト後は管理画面にアクセスできない(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $this->actingAs($user)
            ->post(route('logout'));

        $response = $this->get(route('admin.index'));

        // Assert
        $response->assertRedirect('/login');
    }

    /** @test */
    public function 認証済みユーザーはログインページにアクセスするとリダイレクトされる(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('login'));

        // Assert
        $response->assertRedirect(route('admin.index'));
    }
}
