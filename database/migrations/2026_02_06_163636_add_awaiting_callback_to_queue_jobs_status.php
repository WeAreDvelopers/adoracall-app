<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("PRAGMA foreign_keys=OFF");

            DB::statement("
                CREATE TABLE queue_jobs_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    uuid VARCHAR(36) NOT NULL UNIQUE,
                    mailing_id INTEGER NOT NULL,
                    contato_id INTEGER NOT NULL,
                    status VARCHAR(255) DEFAULT 'pending' CHECK(status IN ('pending', 'processing', 'completed', 'failed', 'cancelled', 'awaiting_callback')),
                    tentativas INTEGER DEFAULT 0,
                    proxima_tentativa TIMESTAMP,
                    ultima_tentativa TIMESTAMP,
                    resultado JSON,
                    tempo_processamento INTEGER,
                    erro_mensagem TEXT,
                    worker_id VARCHAR(100),
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    completed_at TIMESTAMP,
                    FOREIGN KEY(mailing_id) REFERENCES mailings(id) ON DELETE CASCADE,
                    FOREIGN KEY(contato_id) REFERENCES contatos(id) ON DELETE CASCADE
                )
            ");

            DB::statement("INSERT INTO queue_jobs_new SELECT * FROM queue_jobs");
            DB::statement("DROP TABLE queue_jobs");
            DB::statement("ALTER TABLE queue_jobs_new RENAME TO queue_jobs");

            DB::statement("CREATE INDEX queue_jobs_status_index ON queue_jobs(status)");
            DB::statement("CREATE INDEX queue_jobs_mailing_id_index ON queue_jobs(mailing_id)");
            DB::statement("CREATE INDEX queue_jobs_contato_id_index ON queue_jobs(contato_id)");
            DB::statement("CREATE INDEX queue_jobs_proxima_tentativa_index ON queue_jobs(proxima_tentativa)");
            DB::statement("CREATE INDEX queue_jobs_created_at_status_index ON queue_jobs(created_at, status)");

            DB::statement("PRAGMA foreign_keys=ON");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("PRAGMA foreign_keys=OFF");

            DB::statement("
                CREATE TABLE queue_jobs_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    uuid VARCHAR(36) NOT NULL UNIQUE,
                    mailing_id INTEGER NOT NULL,
                    contato_id INTEGER NOT NULL,
                    status VARCHAR(255) DEFAULT 'pending' CHECK(status IN ('pending', 'processing', 'completed', 'failed', 'cancelled')),
                    tentativas INTEGER DEFAULT 0,
                    proxima_tentativa TIMESTAMP,
                    ultima_tentativa TIMESTAMP,
                    resultado JSON,
                    tempo_processamento INTEGER,
                    erro_mensagem TEXT,
                    worker_id VARCHAR(100),
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    completed_at TIMESTAMP,
                    FOREIGN KEY(mailing_id) REFERENCES mailings(id) ON DELETE CASCADE,
                    FOREIGN KEY(contato_id) REFERENCES contatos(id) ON DELETE CASCADE
                )
            ");

            DB::statement("INSERT INTO queue_jobs_new SELECT * FROM queue_jobs");
            DB::statement("DROP TABLE queue_jobs");
            DB::statement("ALTER TABLE queue_jobs_new RENAME TO queue_jobs");

            DB::statement("PRAGMA foreign_keys=ON");
        }
    }
};
