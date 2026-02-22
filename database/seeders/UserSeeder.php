<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $empresa = DB::table('empresas')->where('slug', 'empresa-padrao')->first();

        if (!$empresa) {
            $this->command->warn('Empresa padrão não encontrada. Pulando UserSeeder.');
            return;
        }

        // Admin
        $admin = User::create([
            'name' => 'Admin Dvelopers',
            'email' => 'admin@dvelopers.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'active' => true,
            'empresa_id' => $empresa->id,
        ]);
        $admin->generateApiToken();

        // Supervisor
        $supervisor = User::create([
            'name' => 'Supervisor Teste',
            'email' => 'supervisor@dvelopers.com',
            'password' => Hash::make('supervisor123'),
            'role' => 'supervisor',
            'active' => true,
            'empresa_id' => $empresa->id,
        ]);
        $supervisor->generateApiToken();

        // Operador
        $operador = User::create([
            'name' => 'Operador Teste',
            'email' => 'operador@dvelopers.com',
            'password' => Hash::make('operador123'),
            'role' => 'operador',
            'active' => true,
            'empresa_id' => $empresa->id,
        ]);
        $operador->generateApiToken();

        $this->command->info('✅ Usuários criados:');
        $this->command->info('   Admin: admin@dvelopers.com / admin123');
        $this->command->info('   Supervisor: supervisor@dvelopers.com / supervisor123');
        $this->command->info('   Operador: operador@dvelopers.com / operador123');
    }
}
