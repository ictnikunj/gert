import template from './bst-cron-manager-detail.html.twig';
import moment from 'moment';

const { Criteria } = Shopware.Data;
const { Component, Mixin, Filter } = Shopware;
const { mapPropertyErrors } = Shopware.Component.getComponentHelper();

Component.register('bst-cron-manager-detail', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('placeholder'),
        Mixin.getByName('notification'),
        Mixin.getByName('discard-detail-page-changes')('cronjob')
    ],

    shortcuts: {
        'SYSTEMKEY+S': 'onSave',
        ESCAPE: 'onCancel'
    },

    props: {
        cronjobId: {
            type: String,
            required: false,
            default: null
        }
    },

    data() {
        return {
            cronjob: null,
            isLoading: false,
            isSaveSuccessful: false,
            lastExecutionTime: 'fail'
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.identifier)
        };
    },

    computed: {
        identifier() {
            return this.placeholder(this.cronjob, 'name');
        },

        cronjobIsLoading() {
            return this.isLoading || this.cronjob == null;
        },

        cronjobRepository() {
            return this.repositoryFactory.create('scheduled_task');
        },

        tooltipSave() {
            const systemKey = this.$device.getSystemKey();

            return {
                message: `${systemKey} + S`,
                appearance: 'light'
            };
        },

        tooltipCancel() {
            return {
                message: 'ESC',
                appearance: 'light'
            };
        },

        dateFilter() {
            return Filter.getByName('date');
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

        ...mapPropertyErrors('cronjob', ['name', 'scheduledTaskClass', 'runInterval', 'lastExecutionTime', 'nextExecutionTime', 'status']),
    },

    watch: {
        cronjobId() {
            this.createdComponent();
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            if (this.cronjobId) {
                this.loadEntityData();
                return;
            }

            this.cronjob = this.cronjobRepository.create(Shopware.Context.api);
            this.cronjob.active = false;
            this.lastExecutionTime = this.formatDate(this.cronjob.lastExecutionTime);
        },

        formatDate(dateTime) {
            return this.dateFilter(dateTime, {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        },

        loadEntityData() {
            this.isLoading = true;

            this.cronjobRepository.get(this.cronjobId, Shopware.Context.api).then((cronjob) => {
                this.isLoading = false;
                this.cronjob = cronjob;
                this.lastExecutionTime = this.formatDate(this.cronjob.lastExecutionTime);
            });
        },

        onSave() {
            this.isLoading = true;

            this.cronjobRepository.save(this.cronjob, Shopware.Context.api).then(() => {
                this.isLoading = false;
                this.isSaveSuccessful = true;
                if (this.cronjobId === null) {
                    this.$router.push({ name: 'bst.cron.manager.detail', params: { id: this.cronjob.id } });
                    return;
                }

                this.loadEntityData();
            }).catch(() => {
                this.isLoading = false;
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: this.$tc(
                        'global.notification.notificationSaveErrorMessage', 0, { entityName: this.cronjob.id }
                    )
                });
            });
        },

        onCancel() {
            this.$router.push({ name: 'bst.cron.manager.index' });
        },

        changeNextExecutionTime(){
            const utcStr = new Date().toUTCString();
            const oneMinAdd = new Date(utcStr).setUTCMinutes(new Date(utcStr).getUTCMinutes()+1);
            this.cronjob.nextExecutionTime = new Date(oneMinAdd).toISOString();
            this.onSave();
        }
    }
});
