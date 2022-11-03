const {Component, Mixin} = Shopware;
import template from './she-api-test-button.html.twig';

Component.register('she-api-test-button', {
  template,

  props: {
    btnLabel: {
      type: String,
      required: true,
    },
  },
  inject: ['sheApiTest'],

  mixins: [
    Mixin.getByName('notification'),
  ],

  data() {
    return {
      isLoading: false,
      isSaveSuccessful: false,
    };
  },

  computed: {
    pluginConfig() {
      const config = this.$parent.$parent.$parent.actualConfigData;
      if (config) {
        return config.null;
      }

      // in SW6.3.4 it's one step above
      return this.$parent.$parent.$parent.$parent.actualConfigData.null;
    },
  },

  methods: {
    saveFinish() {
      this.isSaveSuccessful = false;
    },

    check() {
      this.isLoading = true;
      this.sheApiTest.check(this.pluginConfig).then((res) => {
        if (res.success) {
          this.isSaveSuccessful = true;
          this.createNotificationSuccess({
            title: this.$tc('she-api-test-button.title'),
            message: this.$tc('she-api-test-button.success'),
          });
        } else {
          this.createNotificationError({
            title: this.$tc('she-api-test-button.title'),
            message: this.$tc('she-api-test-button.error'),
          });
        }

        this.isLoading = false;
      });
    },
  },
});
