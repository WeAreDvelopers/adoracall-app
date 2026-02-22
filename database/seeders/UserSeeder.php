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

        $users = [
            ['name' => 'Admin Dvelopers',   'email' => 'admin@dvelopers.com',      'password' => 'admin123',      'role' => 'admin'],
            ['name' => 'Supervisor Teste',   'email' => 'supervisor@dvelopers.com', 'password' => 'supervisor123', 'role' => 'supervisor'],
            ['name' => 'Operador Teste',     'email' => 'operador@dvelopers.com',   'password' => 'operador123',   'role' => 'operador'],
        ];

        foreach ($users as $userData) {
            if (User::withoutGlobalScopes()->where('email', $userData['email'])->exists()) {
                continue;
            }

            $user = User::create([
                'name'       => $userData['name'],
                'email'      => $userData['email'],
                'password'   => Hash::make($userData['password']),
                'role'       => $userData['role'],
                'active'     => true,
                'empresa_id' => $empresa->id,
            ]);
            $user->generateApiToken();
        }

        $this->command->info('✅ Usuários criados:');
        $this->command->info('   Admin: admin@dvelopers.com / admin123');
        $this->command->info('   Supervisor: supervisor@dvelopers.com / supervisor123');
        $this->command->info('   Operador: operador@dvelopers.com / operador123');
    }
}
