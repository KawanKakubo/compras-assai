<?php

namespace Tests\Feature;

use App\Models\Secretaria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretariaManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test an admin can create, edit and delete secretariats.
     */
    public function test_admin_can_manage_secretariats(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // 1. List secretariats
        $response = $this->actingAs($admin)->get(route('admin.secretarias.index'));
        $response->assertStatus(200);

        // 2. Create secretariat
        $response = $this->actingAs($admin)->post(route('admin.secretarias.store'), [
            'name' => 'Secretaria de Cultura e Esportes',
            'acronym' => 'cul', // test case conversion to uppercase
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('secretarias', [
            'name' => 'Secretaria de Cultura e Esportes',
            'acronym' => 'CUL',
        ]);

        // 3. Edit view
        $secretaria = Secretaria::where('acronym', 'CUL')->firstOrFail();
        $response = $this->actingAs($admin)->get(route('admin.secretarias.edit', $secretaria));
        $response->assertStatus(200);
        $response->assertSee('Secretaria de Cultura e Esportes');

        // 4. Update secretariat
        $response = $this->actingAs($admin)->put(route('admin.secretarias.update', $secretaria), [
            'name' => 'Secretaria de Cultura, Turismo e Esportes',
            'acronym' => 'CTE',
        ]);

        $response->assertRedirect(route('admin.secretarias.index'));
        $this->assertDatabaseHas('secretarias', [
            'name' => 'Secretaria de Cultura, Turismo e Esportes',
            'acronym' => 'CTE',
        ]);

        // 5. Delete secretariat
        $response = $this->actingAs($admin)->delete(route('admin.secretarias.destroy', $secretaria));
        $response->assertRedirect();
        $this->assertDatabaseMissing('secretarias', [
            'id' => $secretaria->id,
        ]);
    }

    /**
     * Test single secretary per secretariat rule is enforced.
     */
    public function test_one_secretary_per_secretariat_is_enforced(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $secretaria = Secretaria::create([
            'name' => 'Secretaria de Saúde',
            'acronym' => 'SAU',
        ]);

        // Create first secretary
        $user1 = User::create([
            'name' => 'Secretário Um',
            'email' => 'sec1@assai.pr.gov.br',
            'password' => bcrypt('password123'),
            'role' => 'secretario',
            'secretaria_id' => $secretaria->id,
        ]);

        // Attempt to create second secretary for the same secretariat
        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Secretário Dois',
            'email' => 'sec2@assai.pr.gov.br',
            'password' => 'password123',
            'role' => 'secretario',
            'secretaria_id' => $secretaria->id,
        ]);

        $response->assertSessionHasErrors(['secretaria_id']);
        $this->assertDatabaseMissing('users', [
            'email' => 'sec2@assai.pr.gov.br',
        ]);
    }

    /**
     * Test backward-compatible attributes on User.
     */
    public function test_user_backward_compatibility_attributes(): void
    {
        $secretaria = Secretaria::create([
            'name' => 'Secretaria de Educação',
            'acronym' => 'EDU',
        ]);

        // User with relation
        $user = User::factory()->create([
            'role' => 'elaborador',
            'secretaria_id' => $secretaria->id,
            'secretaria_name' => 'Old Name', // DB values
            'secretaria_acronym' => 'OLD',
        ]);

        // Accessors should resolve from relationship if present
        $this->assertEquals('Secretaria de Educação', $user->secretaria_name);
        $this->assertEquals('EDU', $user->secretaria_acronym);

        // User without relation (fallback to attributes)
        $legacyUser = User::factory()->create([
            'role' => 'elaborador',
            'secretaria_id' => null,
            'secretaria_name' => 'Legacy Name',
            'secretaria_acronym' => 'LEG',
        ]);

        $this->assertEquals('Legacy Name', $legacyUser->secretaria_name);
        $this->assertEquals('LEG', $legacyUser->secretaria_acronym);
    }

    /**
     * Test secretary details are prefilled on the wizard creation form.
     */
    public function test_secretary_details_are_prefilled_in_the_wizard(): void
    {
        $secretaria = Secretaria::create([
            'name' => 'Secretaria de Assistência Social',
            'acronym' => 'SAS',
        ]);

        $secretary = User::create([
            'name' => 'Dr. Carlos Alberto',
            'email' => 'carlos@assai.pr.gov.br',
            'password' => bcrypt('password123'),
            'role' => 'secretario',
            'secretaria_id' => $secretaria->id,
            'cpf' => '123.456.789-00',
        ]);

        $elaborador = User::create([
            'name' => 'Paula Silva',
            'email' => 'paula@assai.pr.gov.br',
            'password' => bcrypt('password123'),
            'role' => 'elaborador',
            'secretaria_id' => $secretaria->id,
        ]);

        $response = $this->actingAs($elaborador)->get(route('planning.module-one.create'));

        $response->assertStatus(200);
        $response->assertSee('Dr. Carlos Alberto');
        $response->assertSee('Secretário Municipal');
        $response->assertSee('123.456.789-00');
    }
}
