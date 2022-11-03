import template from './bst-cron-manager-list.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('bst-cron-manager-list', {
    template,

    inject: [
        'repositoryFactory',
        'stateStyleDataProviderService'
    ],

    mixins: [
        Mixin.getByName('listing')
    ],

    data() {
        return {
            cronjobs: null,
            isLoading: true
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        cronRepository() {
            return this.repositoryFactory.create('scheduled_task');
        },

        cronColumns() {
            return [{
                property: 'name',
                dataIndex: 'name',
                allowResize: true,
                routerLink: 'bst.cron.manager.detail',
                label: 'bst-cron-manager.list.columnName',
                primary: true
            }, {
                property: 'scheduledTaskClass',
                dataIndex: 'scheduledTaskClass',
                allowResize: true,
                label: 'bst-cron-manager.list.columnClass'
            }, {
                property: 'runInterval',
                dataIndex: 'runInterval',
                allowResize: true,
                label: 'bst-cron-manager.list.columnRunInterval'
            }, {
                property: 'lastExecutionTime',
                dataIndex: 'lastExecutionTime',
                allowResize: true,
                label: 'bst-cron-manager.list.columnLastExecutionTime'
            }, {
                property: 'nextExecutionTime',
                dataIndex: 'nextExecutionTime',
                allowResize: true,
                label: 'bst-cron-manager.list.columnNextExecutionTime'
            }, {
                property: 'status',
                dataIndex: 'status',
                allowResize: true,
                label: 'bst-cron-manager.list.columnStatus'
            }];
        },

        intervals() {
            return [
                {
                    label: this.$tc('bst-cron-manager.detail.interval.none'),
                    value: 0
                },
                {
                    label: this.$tc('bst-cron-manager.detail.interval.2minutes'),
                    value: 120
                },
                {
                    label: this.$tc('bst-cron-manager.detail.interval.5minutes'),
                    value: 300
                },
                {
                    label: this.$tc('bst-cron-manager.detail.interval.10minutes'),
                    value: 600
                },
                {
                    label: this.$tc('bst-cron-manager.detail.interval.15minutes'),
                    value: 900
                },
                {
                    label: this.$tc('bst-cron-manager.detail.interval.30minutes'),
                    value: 1800
                },
                {
                    label: this.$tc('bst-cron-manager.detail.interval.1hour'),
                    value: 3600
                },
                {
                    label: this.$tc('bst-cron-manager.detail.interval.2hours'),
                    value: 7200
                },
                {
                    label: this.$tc('bst-cron-manager.detail.interval.4hours'),
                    value: 14400
                },
                {
                    label: this.$tc('bst-cron-manager.detail.interval.8hours'),
                    value: 28800
                },
                {
                    label: this.$tc('bst-cron-manager.detail.interval.12hours'),
                    value: 43200
                },
                {
                    label: this.$tc('bst-cron-manager.detail.interval.1day'),
                    value: 86400
                },
                {
                    label: this.$tc('bst-cron-manager.detail.interval.2days'),
                    value: 172800
                },
                {
                    label: this.$tc('bst-cron-manager.detail.interval.1week'),
                    value: 604800
                }
            ];
        },

        cronCriteria() {
            const criteria = new Criteria();
            let params;

            if ( typeof this.getListingParams !== 'undefined' ) {
                params = this.getListingParams(); // Used for shopware < 6.4
            } else {
                try {
                    params = this.getMainListingParams(); // Used for shopware >= 6.4
                } catch(e) {
                    console.log(e);
                    params = null;
                }
            }

            if ( params != null ) {
                // Default sorting
                params.sortBy = params.sortBy || 'name';
                params.sortDirection = params.sortDirection || 'ASC';

                criteria.setTerm(this.term);
                criteria.addSorting(Criteria.sort(params.sortBy, params.sortDirection));
            }

            return criteria;
        }
    },

    methods: {
        onChangeLanguage(languageId) {
            this.getList(languageId);
        },

        getList() {
            this.isLoading = true;

            return this.cronRepository.search(this.cronCriteria, Shopware.Context.api)
                .then((searchResult) => {
                    this.cronjobs = searchResult;
                    this.total = searchResult.total;
                    this.isLoading = false;
                });
        },

        getInterval(time) {
            let cleanInterval = time + this.$tc('bst-cron-manager.detail.interval.seconds');

            Object.keys(this.intervals).forEach((interval) => {
                if ( this.intervals[interval].value == time ) {
                    cleanInterval = this.intervals[interval].label;
                }
            });

            return cleanInterval;
        },

        getStyleForStatus(status) {
            let variant = '';

            if ( status == 'scheduled' ) {
                variant = 'success';
            } else if ( status == 'queued' ) {
                variant = 'info';
            } else if ( status == 'running' ) {
                variant = 'warning';
            } else if ( status == 'failed' ) {
                variant = 'danger';
            } else if ( status == 'inactive' ) {
                variant = 'done';
            }

            return variant;
        },

        updateTotal({ total }) {
            this.total = total;
        }
    }
});
