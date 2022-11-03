import formState from './state';

const { Component } = Shopware;

if (Shopware.Feature.isActive('FEATURE_SWAGCMSEXTENSIONS_63')) {
    Component.override('sw-cms-detail', {
        inject: [
            'SwagCmsExtensionsFormValidationService',
        ],

        beforeCreate() {
            if (Shopware.State.list().indexOf('swCmsDetailCurrentCustomForm') !== -1) {
                Shopware.State.unregisterModule('swCmsDetailCurrentCustomForm');
            }
            Shopware.State.registerModule('swCmsDetailCurrentCustomForm', formState);
            Shopware.State.commit('swCmsDetailCurrentCustomForm/resetState');
        },

        methods: {
            onSave() {
                const forms = [];

                this.page.sections.forEach((section) => {
                    section.blocks.forEach((block) => {
                        block.slots.forEach((slot) => {
                            if (!Shopware.Utils.object.hasOwnProperty(slot, 'extensions') ||
                                !Shopware.Utils.object.hasOwnProperty(slot.extensions, 'swagCmsExtensionsForm')
                            ) {
                                return;
                            }

                            forms.push(slot.extensions.swagCmsExtensionsForm);
                        });
                    });
                });

                return this.SwagCmsExtensionsFormValidationService.validateAllForms(forms).catch(() => {
                    this.createNotificationError({
                        message: this.$tc('swag-cms-extensions.sw-cms.detail.errors.customFormConfigurationInvalid'),
                    });

                    return Promise.reject();
                }).then(() => {
                    return this.$super('onSave');
                });
            },
        },
    });
}
