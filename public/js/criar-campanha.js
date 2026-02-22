/**
 * ============================================
 * CRIAR CAMPANHA - Form Handler
 * ============================================
 * Utiliza ValidationManager para validação
 * e FeedbackManager para notificações
 */

document.addEventListener('DOMContentLoaded', function() {
    const notificacao = new NotificacaoManager();

    // Configurar ValidationManager com as regras de validação
    const validator = new ValidationManager('criarCampanhaForm', {
        rules: {
            nome: {
                required: true,
                minLength: 3,
                maxLength: 255,
            },
            descricao: {
                maxLength: 1000,
            },
            prioridade: {
                required: true,
            },
            max_tentativas: {
                required: true,
                min: 1,
                max: 10,
            },
            velocidade_contatos_hora: {
                required: true,
                min: 5,
                max: 500,
            },
            intervalo_retry: {
                required: true,
                min: 5,
                max: 1440,
            },
            data_fim_agendado: {
                custom: (value, formData) => {
                    if (value && formData.data_inicio_agendado) {
                        const dataInicio = new Date(formData.data_inicio_agendado);
                        const dataFim = new Date(value);
                        if (dataFim <= dataInicio) {
                            return 'Data de término deve ser após data de início';
                        }
                    }
                    return true;
                }
            }
        },
        messages: {
            nome: {
                required: 'Nome da campanha é obrigatório',
                minLength: 'Nome deve ter pelo menos 3 caracteres',
                maxLength: 'Nome não pode exceder 255 caracteres',
            },
            prioridade: {
                required: 'Prioridade é obrigatória',
            },
            max_tentativas: {
                required: 'Máximo de tentativas é obrigatório',
                min: 'Mínimo 1 tentativa',
                max: 'Máximo 10 tentativas',
            },
            velocidade_contatos_hora: {
                required: 'Velocidade de contatos é obrigatória',
                min: 'Mínimo 5 contatos por hora',
                max: 'Máximo 500 contatos por hora',
            },
            intervalo_retry: {
                required: 'Intervalo de retry é obrigatório',
                min: 'Mínimo 5 minutos',
                max: 'Máximo 1440 minutos (24 horas)',
            },
        },
        onSuccess: async (data) => {
            await submeterCampanha(data);
        },
        onError: (errors) => {
            feedback.error('Por favor, corrija os erros no formulário');
            console.error('Erros de validação:', errors);
        }
    });

    /**
     * Submete a campanha para o servidor
     *
     * @param {Object} data - Dados do formulário validados
     */
    async function submeterCampanha(data) {
        const btnSubmit = document.getElementById('btnSubmit');
        btnSubmit.disabled = true;
        const btnText = btnSubmit.textContent;
        btnSubmit.textContent = 'Criando campanha...';

        try {
            console.log('🔄 Iniciando criação de campanha...');

            const dados = {
                nome: data.nome,
                descricao: data.descricao || null,
                prioridade: data.prioridade,
                max_tentativas: parseInt(data.max_tentativas),
                velocidade_contatos_hora: parseInt(data.velocidade_contatos_hora),
                intervalo_retry: parseInt(data.intervalo_retry),
                data_inicio_agendado: data.data_inicio_agendado || null,
                data_fim_agendado: data.data_fim_agendado || null,
                status: 'pronto'
            };

            // Remover campos vazios
            Object.keys(dados).forEach(key => {
                if (dados[key] === '' || dados[key] === null) {
                    delete dados[key];
                }
            });

            console.log('📋 Dados do formulário:', dados);
            console.log('📤 Enviando para:', `${API_BASE_URL}/filas_campanha`);

            // Enviar para o backend com autenticação
            const response = await fetchWithAuth(`${API_BASE_URL}/filas_campanha`, {
                method: 'POST',
                body: JSON.stringify(dados),
            });

            console.log('📊 Status da resposta:', response.status, response.statusText);

            const resultado = await response.json();

            console.log('📨 Resposta da API:', resultado);

            if (!response.ok) {
                throw new Error(resultado.message || `Erro ao criar campanha (status: ${response.status})`);
            }

            // Sucesso
            console.log('✅ Campanha criada com sucesso!');
            feedback.success('Campanha criada com sucesso! Redirecionando...');
            notificacao.sucesso('Campanha criada com sucesso!');

            // Salvar a campanha criada no localStorage para a página de importação
            localStorage.setItem('campanhaAcabadoCriar', resultado.mailing.id.toString());
            console.log('💾 ID salvo no localStorage:', resultado.mailing.id);

            // Redirecionar para a página de importação após 2 segundos
            setTimeout(() => {
                console.log('🔀 Redirecionando para importacao');
                window.location.href = '/campanha/importacao';
            }, 2000);

        } catch (erro) {
            console.error('🚨 Erro completo:', erro);
            console.error('Stack:', erro.stack);
            feedback.error(erro.message || 'Erro ao criar campanha');

            // Tentar notificar mesmo se houver erro
            if (typeof notificacao !== 'undefined') {
                notificacao.erro(erro.message || 'Erro ao criar campanha');
            }
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.textContent = btnText;
        }
    }

    // Requestar permissão para notificações
    notificacao.solicitarPermissao();
});
