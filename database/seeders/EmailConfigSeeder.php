<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailConfig;

class EmailConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $emails = [
            [
                'email' => 'cleiversoares2@gmail.com',
                'nome' => 'Cleiver Soares',
                'ativo' => true,
                'tipo_alerta' => 'prioritario'
            ],
            [
                'email' => 'danilofariaspereira90@gmail.com',
                'nome' => 'Danilo Farias',
                'ativo' => true,
                'tipo_alerta' => 'prioritario'
            ],
            [
                'email' => 'leandro_ferraz@outlook.com',
                'nome' => 'Leandro Ferraz',
                'ativo' => true,
                'tipo_alerta' => 'prioritario'
            ]
        ];

        foreach ($emails as $emailData) {
            EmailConfig::create($emailData);
        }

        $this->command->info('✅ Emails de configuração criados com sucesso!');
    }
}