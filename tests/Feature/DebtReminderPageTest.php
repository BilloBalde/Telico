<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DebtReminder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Relances de dettes et image par défaut des produits sans photo. */
class DebtReminderPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_client_archive_avec_un_rappel_ne_casse_pas_la_page(): void
    {
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $admin = User::create([
            'name' => 'Patron', 'email' => 'patron@example.com', 'username' => 'patron',
            'password' => Hash::make('password'), 'role_id' => User::ROLE_ADMIN,
            'phone' => '620', 'status' => 'Active', 'token' => '',
        ]);
        $actif = Customer::create(['customerName' => 'Client actif', 'mark' => 'A', 'tel' => '620000001', 'address' => 'Madina']);
        $archive = Customer::create(['customerName' => 'Client archivé', 'mark' => 'B', 'tel' => '620000002', 'address' => 'Madina']);
        DebtReminder::create(['customer_id' => $actif->id, 'amount' => 50000, 'status' => 'sent']);
        DebtReminder::create(['customer_id' => $archive->id, 'amount' => 80000, 'status' => 'sent']);
        $archive->delete(); // archivage (suppression douce)

        $this->actingAs($admin)->get(route('admin.debt-reminders.index'))->assertOk()
            ->assertSee('Client actif')->assertSee('Client archivé')->assertSee('archivé');

        // « Résolu » fonctionne aussi pour le client archivé.
        $this->actingAs($admin)->post(route('admin.debt-reminders.resolve', $archive->id))->assertRedirect();
        $this->assertSame('resolved', DebtReminder::where('customer_id', $archive->id)->value('status'));
    }

    public function test_image_par_defaut_pour_un_produit_sans_photo(): void
    {
        $sansPhoto = new Product(['image' => '']);
        $photoAbsente = new Product(['image' => 'inexistante-' . uniqid() . '.jpg']);

        $this->assertStringEndsWith(Product::IMAGE_PAR_DEFAUT, $sansPhoto->image_url);
        $this->assertStringEndsWith(Product::IMAGE_PAR_DEFAUT, $photoAbsente->image_url);
        $this->assertFileExists(public_path(Product::IMAGE_PAR_DEFAUT));
    }
}
