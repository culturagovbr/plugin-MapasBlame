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

        action: {
            handler(action) {
                if (!action) {
                    delete this.query['action'];
                    return;
                }

                this.query['action'] = `LIKE(*${action}*)`
            }
        }
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
            action: '',
            actionOptions: [
                { value: 'GET', label: __('Acessos', 'entity-table') },
                { value: 'PUT',  label: __('Atualizações', 'entity-table') },
                { value: 'PATCH',  label: __('Atualizações parciais', 'entity-table') },
                { value: 'POST',  label: __('Criações', 'entity-table') },
                { value: '/inscricoes/sendEvaluation/', label: __('Envio de avaliações', 'entity-table') },
                { value: '/inscricoes/send/', label: __('Envio de inscrições', 'entity-table') },
                { value: 'DELETE',  label: __('Exclusões', 'entity-table') },
            ]
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
    },
});
