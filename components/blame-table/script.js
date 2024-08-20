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
                'GET': __('Acessos', 'blame-table'),
                'PATCH': __('Atualizações parciais', 'blame-table'),

                '%POST%registration.sendEvaluation%': __('Envio de Avaliações'),
                '%POST%registration.send%': __('Envio de Inscrições'),

                'POST': __('Criações', 'blame-table'),
                '%POST%agent.index%': __('Criação de Agentes', 'blame-table'),
                '%POST%opportunity.index%': __('Criação de Oportunidades', 'blame-table'),
                '%POST%event.index%': __('Criação de Eventos', 'blame-table'),
                '%POST%space.index%': __('Criação de Espaços', 'blame-table'),
                '%POST%project.index%': __('Criação de Projetos', 'blame-table'),
                
                'PATCH': __('Atualizações', 'blame-table'),
                '%PATCH%agent.edit%': __('Edição de Agentes', 'blame-table'),
                '%PATCH%opportunity.edit%': __('Edição de Oportunidades', 'blame-table'),
                '%PATCH%event.edit%': __('Edição de Eventos', 'blame-table'),
                '%PATCH%space.edit%': __('Edição de Espaços', 'blame-table'),
                '%PATCH%project.edit%': __('Edição de Projetos', 'blame-table'),
                
                'DELETE': __('Exclusões', 'blame-table'), 
                '%DELETE%agent.single%': __('Exclusão de Agentes', 'blame-table'),
                '%DELETE%opportunity.single%': __('Exclusão de Oportunidades', 'blame-table'),
                '%DELETE%event.single%': __('Exclusão de Eventos', 'blame-table'),
                '%DELETE%space.single%': __('Exclusão de Espaços', 'blame-table'),
                '%DELETE%project.single%': __('Exclusão de Projetos', 'blame-table'),
                
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
                let clausure = `LIKE(*${action}*)`;
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
