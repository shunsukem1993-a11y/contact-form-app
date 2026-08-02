<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactRelationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function カテゴリーは複数のお問い合わせを取得できる(): void
    {
        // Arrange
        $category = Category::factory()->create();

        Contact::factory()
            ->count(3)
            ->create([
                'category_id' => $category->id,
            ]);

        // Act
        $categoryWithContacts = Category::with('contacts')
            ->find($category->id);

        $contacts = $categoryWithContacts->contacts;

        // Assert
        $this->assertCount(3, $contacts);

        $this->assertTrue(
            $contacts->every(
                fn ($contact) => $contact->category_id === $category->id
            )
        );
    }

    /** @test */
    public function お問い合わせは所属するカテゴリーを取得できる(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        // Act
        $contactWithCategory = Contact::with('category')
            ->find($contact->id);

        $contactCategory = $contactWithCategory->category;

        // Assert
        $this->assertInstanceOf(
            Category::class,
            $contactCategory
        );

        $this->assertEquals(
            $category->id,
            $contactCategory->id
        );
    }

    /** @test */
    public function お問い合わせは複数のタグを取得できる(): void
    {
        // Arrange
        $contact = Contact::factory()->create();

        $tags = Tag::factory()
            ->count(3)
            ->create();

        $contact->tags()->attach(
            $tags->pluck('id')
        );

        // Act
        $contactWithTags = Contact::with('tags')
            ->find($contact->id);

        $contactTags = $contactWithTags->tags;

        // Assert
        $this->assertCount(3, $contactTags);

        foreach ($tags as $tag) {
            $this->assertTrue(
                $contactTags->contains($tag)
            );
        }
    }

    /** @test */
    public function タグは紐づく複数のお問い合わせを取得できる(): void
    {
        // Arrange
        $tag = Tag::factory()->create();

        $contacts = Contact::factory()
            ->count(3)
            ->create();

        foreach ($contacts as $contact) {
            $contact->tags()->attach($tag->id);
        }

        // Act
        $tagWithContacts = Tag::with('contacts')
            ->find($tag->id);

        $tagContacts = $tagWithContacts->contacts;

        // Assert
        $this->assertCount(3, $tagContacts);

        foreach ($contacts as $contact) {
            $this->assertTrue(
                $tagContacts->contains($contact)
            );
        }
    }

    /** @test */
    public function お問い合わせにタグを追加するとcontact_tagテーブルに保存される(): void
    {
        // Arrange
        $contact = Contact::factory()->create();

        $tag = Tag::factory()->create();

        // Act
        $contact->tags()->attach($tag->id);

        // Assert
        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    /** @test */
    public function お問い合わせのタグをsyncすると関連するタグ情報が更新される(): void
    {
        // Arrange
        $contact = Contact::factory()->create();

        $tags = Tag::factory()
            ->count(3)
            ->create();

        // Act
        $contact->tags()->sync([
            $tags[0]->id,
            $tags[1]->id,
        ]);

        $contactWithTags = Contact::with('tags')
            ->find($contact->id);

        // Assert
        $this->assertCount(
            2,
            $contactWithTags->tags
        );

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tags[0]->id,
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tags[1]->id,
        ]);

        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tags[2]->id,
        ]);
    }

    /** @test */
    public function お問い合わせからカテゴリー情報を取得できる(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $contact = Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        // Act
        $contactWithCategory = Contact::with('category')
            ->find($contact->id);

        $contactCategory = $contactWithCategory->category;

        // Assert
        $this->assertNotNull(
            $contactCategory
        );

        $this->assertEquals(
            $category->content,
            $contactCategory->content
        );
    }
}
