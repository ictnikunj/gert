import template from './index.html.twig';
import './index.scss';

const {Component} = Shopware;

Component.register('moorl-fb2-hint', {
    template,

    data() {
        return {
            open: false
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            if (!localStorage.getItem('fb2-hint-seen')) {
                this.open = true;
                localStorage.setItem('fb2-hint-seen', "true");
            }
        }
    }
});