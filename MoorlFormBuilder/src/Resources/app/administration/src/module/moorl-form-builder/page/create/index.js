const { Component } = Shopware;

Component.extend('moorl-form-builder-create', 'moorl-form-builder-detail', {
    methods: {
        getForm() {
            let entity = this.formRepository.create(Shopware.Context.api);

            if (this.$route.params) {
                Object.assign(entity, this.$route.params);
            }

            this.form = this.sanitizeForm(entity);
            this.form.bootstrapGrid = true;

            this.loadCustomFieldSets();
        },
        onClickSave() {
            this.prepareSave();
            this.isLoading = true;
            this.formRepository
                .save(this.form, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.$router.push({name: 'moorl.form.builder.detail', params: {id: this.form.id}});
                }).catch((exception) => {
                this.isLoading = false;
                this.createNotificationError({
                    title: this.$t('moorl-form-builder.detail.errorTitle'),
                    message: exception
                });
            });
        }
    }
});
