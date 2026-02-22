<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddSuperAdminRoleToUsers extends Migration
{
    public function up()
    {
        // Adicionar super_admin ao ENUM de roles
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('super_admin', 'admin', 'supervisor', 'operador') NOT NULL DEFAULT 'operador'");
    }

    public function down()
    {
        // Reverter: remover super_admin (mover super_admins para admin antes)
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin', 'supervisor', 'operador') NOT NULL DEFAULT 'operador'");
    }
}
