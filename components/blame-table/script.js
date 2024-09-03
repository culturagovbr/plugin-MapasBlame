app.component('blame-table', {
    template: $TEMPLATES['blame-table'],

    setup(props, { slots }) {
        const hasSlot = name => !!slots[name];
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('blame-table');
        return { text, hasSlot }
    },

    props: {
        userId: {
            type: Number,
            default: null,
        },
    },
    
    watch: {
        date: {
            handler(date){
                if (!date) {
                    this.date = [new Date(), new Date()];
                    delete this.query['requestTimestamp'];
                    return;
                }

                const d0 = new McDate(new Date(date[0]));
                const d1 = new McDate(new Date(date[1]));
                this.query['requestTimestamp'] = `BET(${d0.date('sql')}, ${d1.date('sql')})`
            },
            deep: true,
        },

        sessionId: {
            handler(id) {
                if (!id) {
                    delete this.query['sessionId'];
                    return;
                }

                this.query['sessionId'] = `LIKE(*${id}*)`;
            }
        },

        IPAddress: {
            handler(ip) {
                if (!ip) {
                    delete this.query['ip'];
                    return;
                }

                this.query['ip'] = `LIKE(*${ip}*)`
            }
        },
    },

    data() {
        let query = {
            '@select': 'id,sessionId,requestId,requestTimestamp,action,logTimestamp,userBrowserName,userBrowserVersion,ip,userId,userOS,userDevice',
        };

        if (this.userId) {
            query['userId'] = `EQ(${this.userId})`;
        }

        return {
            query,
            date: [new Date(), new Date()],
            locale: $MAPAS.config.locale,
            sessionId: '',
            IPAddress: '',
            action: '',
            selectedActions: [],

            actionOptions: {
                'GET': __('REQUISIÇÕES - GET (acessos)', 'blame-table'),
                'POST': __('REQUISIÇÕES - POST (Criações e outras ações)', 'blame-table'),
                'PATCH': __('REQUISIÇÕES - PATCH (Atualizações parciais)', 'blame-table'),
                'PUT': __('REQUISIÇÕES - PUT (Atualizações completas)', 'blame-table'),
                'DELETE': __('REQUISIÇÕES - DELETE (Exclusões)', 'blame-table'),

                'POST%registration.sendEvaluation%': __('Envio de Avaliações'),

                // INSCRIÇÕES
                'POST%(registration.index)': __('INSCRIÇÕES - criação de inscrição'),
                'PATCH%(registration.single)': __('INSCRIÇÕES - modificação de inscrição'),
                'POST%(registration.send)': __('INSCRIÇÕES - envio de inscrição'),
                'POST%(registration.validateEntity)': __('INSCRIÇÕES - validação de inscrição'),
                'DELETE%(registration.single)': __('INSCRIÇÕES - exclusão de inscrição'),

                // AGENTES
                'GET%(agent.single)': __('AGENTES - acesso à página'),
                'POST%(agent.index)': __('AGENTES - criação de agente'),
                'PATCH%(agent.single)': __('AGENTES - modificação de agente'),
                'DELETE%(agent.single)': __('AGENTES - exclusão de agente'),

                // ESPAÇOS
                'GET%(space.single)': __('ESPAÇOS - acesso à página'),
                'POST%(space.index)': __('ESPAÇOS - criação de espaço'),
                'PATCH%(space.single)': __('ESPAÇOS - modificação de espaço'),
                'DELETE%(space.single)': __('ESPAÇOS - exclusão de espaço'),

                // PROJETOS
                'GET%(project.single)': __('PROJETOS - acesso à página'),
                'POST%(project.index)': __('PROJETOS - criação de projeto'),
                'PATCH%(project.single)': __('PROJETOS - modificação de projeto'),
                'DELETE%(project.single)': __('PROJETOS - exclusão de projeto'),
                
                // EVENTOS
                'GET%(event.single)': __('EVENTOS - acesso à página'),
                'POST%(event.index)': __('EVENTOS - criação de evento'),
                'PATCH%(event.single)': __('EVENTOS - modificação de evento'),
                'DELETE%(event.single)': __('EVENTOS - exclusão de evento'),
                'POST%(eventoccurrence.index)': __('EVENTOS - criação de ocorrência'),
                'DELETE%(eventoccurrence.single)': __('EVENTOS - exclusão de ocorrência'),

                // OPORTUNIDADES
                'GET%(opportunity.single)': __('OPORTUNIDADES - acesso à página'),
                'GET%(opportunity.edit)': __('OPORTUNIDADES - acesso à página de gestão'),
                'POST%(opportunity.index)': __('OPORTUNIDADES - criação de oportunidade'),
                'PATCH%(opportunity.single)': __('OPORTUNIDADES - modificação de oportunidade'),
                'DELETE%(opportunity.single)': __('OPORTUNIDADES - exclusão de oportunidade'),

                // OPORTUNIDADES - fases
                'POST%(evaluationmethodconfiguration.index)': __('OPORTUNIDADES - fase de avaliação - criação'),
                'POST%(evaluationmethodconfiguration.index)': __('OPORTUNIDADES - fase de avaliação - exclusão'),

                // OPORTUNIDADES - campos
                'POST%(registrationfieldconfiguration.index)': __('OPORTUNIDADES - configuração do formulário - criação de campo'),
                'POST%(registrationfieldconfiguration.single)': __('OPORTUNIDADES - configuração do formulário - modificação de campo'),
                'GET%(registrationfieldconfiguration.delete)': __('OPORTUNIDADES - configuração do formulário - exclusão de campo'),
                'POST%(registrationfileconfiguration.index)': __('OPORTUNIDADES - configuração do formulário - criação de anexo'),
                'POST%(registrationfileconfiguration.single)': __('OPORTUNIDADES - configuração do formulário - modificação de anexo'),
                'POST%(registrationfileconfiguration.delete)': __('OPORTUNIDADES - configuração do formulário - exclusão de anexo'),
                
            },
        }
    },

    computed: {
        headers() {
            let itens = [
                { text: __('ID do log', 'blame-table'), value: "id"},
                { text: __('ID da seção', 'blame-table'), value: "sessionId"},
                { text: __('ID do usuário', 'blame-table'), value: "userId"},
                { text: __('Ação', 'blame-table'), value: "action"},
                { text: __('Data do log', 'blame-table'), value: "logTimestamp"},
                { text: __('Data da requisição', 'blame-table'), value: "requestTimestamp"},
                { text: __('Navegador', 'blame-table'), value: "userBrowserName"},
                { text: __('Versão', 'blame-table'), value: "userBrowserVersion"},
                { text: __('IP', 'blame-table'), value: "ip"},
                { text: __('Sistema Operacional', 'blame-table'), value: "userOS"},
                { text: __('Dispositivo', 'blame-table'), value: "userDevice"},
            ];

            return itens;
        },

        visible() {
            return ['id', 'action', 'userId', 'ip']
        }
    },
    
    methods: {
        rawProcessor(data) {
            data.logTimestamp = new McDate(data.logTimestamp.date);
            data.requestTimestamp = new McDate(data.requestTimestamp.date);
            return data;
        },
        filterActions() {
            let search = [];

            for (const action of this.selectedActions) {
                let clausure = `LIKE(${action}*)`;
                search.push(clausure);
            }

            if (search.length > 0) {
                this.query['action'] = `OR(${search.join()})`;
            } else {
                delete this.query['action'];
            }
        }
    },
});
