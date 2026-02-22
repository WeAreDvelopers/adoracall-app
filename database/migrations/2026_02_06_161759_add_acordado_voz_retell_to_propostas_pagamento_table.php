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
        // Para SQLite, precisamos recriar a tabela ou usar CHECK constraint
        if (DB::getDriverName() === 'sqlite') {
            // Adiciona um CHECK constraint para permitir o novo valor
            DB::statement("PRAGMA foreign_keys=OFF");

            // Recriar tabela com a constraint atualizada
            DB::statement("
                CREATE TABLE propostas_pagamento_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    uuid VARCHAR(36) NOT NULL UNIQUE,
                    contato_id INTEGER NOT NULL,
                    script_id INTEGER,
                    mailing_id INTEGER,
                    telefone VARCHAR(20) NOT NULL,
                    nome_cliente VARCHAR(255),
                    valor_original DECIMAL(10, 2) NOT NULL,
                    valor_proposta DECIMAL(10, 2) NOT NULL,
                    desconto DECIMAL(5, 2) DEFAULT 0,
                    tipo_proposta VARCHAR(255) DEFAULT 'link_generico' CHECK(tipo_proposta IN ('pix', 'boleto', 'cartao', 'link_generico', 'acordado_voz_retell')),
                    link_pagamento VARCHAR(500) NOT NULL,
                    mensagem_sms TEXT,
                    status VARCHAR(255) DEFAULT 'gerada' CHECK(status IN ('gerada', 'sms_enviado', 'sms_erro', 'aguardando_pagamento', 'pago', 'expirado', 'cancelado')),
                    twilio_message_sid VARCHAR(255),
                    sms_status VARCHAR(255) CHECK(sms_status IN ('enviando', 'enviado', 'entregue', 'falhou', 'não_enviado')),
                    sms_erro_mensagem TEXT,
                    sms_enviado_em TIMESTAMP,
                    sms_entregue_em TIMESTAMP,
                    gateway_id VARCHAR(255),
                    transaction_id VARCHAR(255),
                    pago_em TIMESTAMP,
                    valor_pago DECIMAL(10, 2),
                    valido_ate TIMESTAMP,
                    expira_em TIMESTAMP,
                    metadata JSON,
                    criado_por VARCHAR(100),
                    atualizado_por VARCHAR(100),
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    deleted_at TIMESTAMP,
                    FOREIGN KEY(contato_id) REFERENCES contatos(id) ON DELETE CASCADE,
                    FOREIGN KEY(script_id) REFERENCES scripts(id) ON DELETE SET NULL,
                    FOREIGN KEY(mailing_id) REFERENCES mailings(id) ON DELETE SET NULL
                )
            ");

            DB::statement("INSERT INTO propostas_pagamento_new SELECT * FROM propostas_pagamento");
            DB::statement("DROP TABLE propostas_pagamento");
            DB::statement("ALTER TABLE propostas_pagamento_new RENAME TO propostas_pagamento");

            // Recriar índices
            DB::statement("CREATE INDEX propostas_pagamento_contato_id_index ON propostas_pagamento(contato_id)");
            DB::statement("CREATE INDEX propostas_pagamento_script_id_index ON propostas_pagamento(script_id)");
            DB::statement("CREATE INDEX propostas_pagamento_mailing_id_index ON propostas_pagamento(mailing_id)");
            DB::statement("CREATE INDEX propostas_pagamento_telefone_index ON propostas_pagamento(telefone)");
            DB::statement("CREATE INDEX propostas_pagamento_status_index ON propostas_pagamento(status)");
            DB::statement("CREATE INDEX propostas_pagamento_tipo_proposta_index ON propostas_pagamento(tipo_proposta)");
            DB::statement("CREATE INDEX propostas_pagamento_sms_status_index ON propostas_pagamento(sms_status)");
            DB::statement("CREATE INDEX propostas_pagamento_uuid_index ON propostas_pagamento(uuid)");
            DB::statement("CREATE INDEX propostas_pagamento_created_at_index ON propostas_pagamento(created_at)");
            DB::statement("CREATE INDEX propostas_pagamento_pago_em_index ON propostas_pagamento(pago_em)");
            DB::statement("CREATE INDEX propostas_pagamento_twilio_message_sid_index ON propostas_pagamento(twilio_message_sid)");

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
                CREATE TABLE propostas_pagamento_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    uuid VARCHAR(36) NOT NULL UNIQUE,
                    contato_id INTEGER NOT NULL,
                    script_id INTEGER,
                    mailing_id INTEGER,
                    telefone VARCHAR(20) NOT NULL,
                    nome_cliente VARCHAR(255),
                    valor_original DECIMAL(10, 2) NOT NULL,
                    valor_proposta DECIMAL(10, 2) NOT NULL,
                    desconto DECIMAL(5, 2) DEFAULT 0,
                    tipo_proposta VARCHAR(255) DEFAULT 'link_generico' CHECK(tipo_proposta IN ('pix', 'boleto', 'cartao', 'link_generico')),
                    link_pagamento VARCHAR(500) NOT NULL,
                    mensagem_sms TEXT,
                    status VARCHAR(255) DEFAULT 'gerada' CHECK(status IN ('gerada', 'sms_enviado', 'sms_erro', 'aguardando_pagamento', 'pago', 'expirado', 'cancelado')),
                    twilio_message_sid VARCHAR(255),
                    sms_status VARCHAR(255) CHECK(sms_status IN ('enviando', 'enviado', 'entregue', 'falhou', 'não_enviado')),
                    sms_erro_mensagem TEXT,
                    sms_enviado_em TIMESTAMP,
                    sms_entregue_em TIMESTAMP,
                    gateway_id VARCHAR(255),
                    transaction_id VARCHAR(255),
                    pago_em TIMESTAMP,
                    valor_pago DECIMAL(10, 2),
                    valido_ate TIMESTAMP,
                    expira_em TIMESTAMP,
                    metadata JSON,
                    criado_por VARCHAR(100),
                    atualizado_por VARCHAR(100),
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    deleted_at TIMESTAMP,
                    FOREIGN KEY(contato_id) REFERENCES contatos(id) ON DELETE CASCADE,
                    FOREIGN KEY(script_id) REFERENCES scripts(id) ON DELETE SET NULL,
                    FOREIGN KEY(mailing_id) REFERENCES mailings(id) ON DELETE SET NULL
                )
            ");

            DB::statement("INSERT INTO propostas_pagamento_new SELECT * FROM propostas_pagamento");
            DB::statement("DROP TABLE propostas_pagamento");
            DB::statement("ALTER TABLE propostas_pagamento_new RENAME TO propostas_pagamento");

            DB::statement("PRAGMA foreign_keys=ON");
        }
    }
};
